#!/usr/bin/env python3
"""Exercise the staff workflow against a dedicated, empty local test database.

Set PHP_BIN and DB_HOST, DB_PORT, DB_USER, DB_PASSWORD, DB_NAME explicitly.
DB_HOST must be loopback and DB_NAME must end in _test. The schema is imported
from database/schema.sql only when the database has no tables. Existing tables
must contain no records because the application's archive operation is global.
Only this run's synthetic fixture IDs are deleted; no tables are dropped.
"""

import html
import http.cookiejar
import json
import os
from pathlib import Path
import re
import socket
import subprocess
import sys
import tempfile
import time
import urllib.error
import urllib.parse
import urllib.request
import uuid
from decimal import Decimal


ROOT = Path(__file__).resolve().parents[1]
ERRORS = re.compile(r"(?:PHP\s+)?(?:Fatal error|Warning|Notice|Parse error|Deprecated):", re.I)
PHP_DATABASE = r"""
require $argv[1];
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $request = json_decode(stream_get_contents(STDIN), true, 512, JSON_THROW_ON_ERROR);
    if ($request['mode'] === 'hash') {
        echo json_encode(password_hash($request['password'], PASSWORD_DEFAULT));
        exit;
    }
    $db = db_connect();
    if ($request['mode'] === 'schema') {
        $db->multi_query($request['sql']);
        do {
            if ($result = $db->store_result()) { $result->free(); }
        } while ($db->more_results() && $db->next_result());
        echo '{}';
    } else {
        $statement = $db->prepare($request['sql']);
        $parameters = $request['params'];
        if ($parameters) {
            $types = '';
            foreach ($parameters as $parameter) {
                $types .= is_int($parameter) ? 'i' : (is_float($parameter) ? 'd' : 's');
            }
            $statement->bind_param($types, ...$parameters);
        }
        $statement->execute();
        $result = $statement->get_result();
        echo json_encode([
            'rows' => $result ? $result->fetch_all(MYSQLI_ASSOC) : [],
            'insert_id' => $db->insert_id,
            'affected' => $statement->affected_rows,
        ], JSON_THROW_ON_ERROR);
    }
} catch (Throwable $error) {
    // Keep connection details and fixture contents out of captured test output.
    fwrite(STDERR, 'Database helper failed (' . get_class($error) . ').');
    exit(1);
}
"""


def check(condition, description):
    if not condition:
        raise AssertionError(description)


def text_content(body):
    return html.unescape(re.sub(r"<[^>]+>", " ", body))


class Database:
    def __init__(self, php, env):
        self.php, self.env = php, env

    def call(self, mode="query", **payload):
        process = subprocess.run(
            [self.php, "-d", "display_errors=0", "-r", PHP_DATABASE,
             str(ROOT / "htdocs/config.php")],
            input=json.dumps(dict(mode=mode, **payload)), capture_output=True,
            text=True, env=self.env, timeout=20,
        )
        check(process.returncode == 0, "PHP database helper failed; verify local DB configuration")
        check(not ERRORS.search(process.stderr), "PHP database helper emitted a warning")
        try:
            return json.loads(process.stdout)
        except ValueError as error:
            raise AssertionError("PHP database helper did not return valid JSON") from error

    def query(self, sql, *params):
        return self.call(sql=sql, params=list(params))

    def scalar(self, sql, *params):
        return next(iter(self.query(sql, *params)["rows"][0].values()))


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, request, fp, code, message, headers, new_url):
        return None


class Browser:
    def __init__(self, base):
        self.base = base
        self.opener = urllib.request.build_opener(
            urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()), NoRedirect()
        )

    def request(self, path, data=None, status=200):
        url = urllib.parse.urljoin(self.base, path)
        request = urllib.request.Request(
            url, data=urllib.parse.urlencode(data).encode() if data is not None else None
        )
        try:
            response = self.opener.open(request, timeout=10)
        except urllib.error.HTTPError as error:
            response = error
        with response:
            body = response.read().decode("utf-8")
            check(response.status == status, f"{path.split('?')[0]} expected HTTP {status}, got {response.status}")
            check(not ERRORS.search(text_content(body)), f"PHP warning/error in {path.split('?')[0]}")
            return body, response.headers

    def redirect(self, path, data, target):
        _, headers = self.request(path, data, status=302)
        location = headers.get("Location", "")
        check(location == target, f"Unexpected redirect from {path.split('?')[0]}")
        return location

    def csrf(self, path="index.php"):
        body, _ = self.request(path)
        token = re.search(r'name="csrf_token"\s+value="([^"]+)"', body)
        check(token is not None, f"Missing CSRF field in {path}")
        return html.unescape(token.group(1))


def run():
    required = ["PHP_BIN", "DB_HOST", "DB_PORT", "DB_USER", "DB_PASSWORD", "DB_NAME"]
    missing = [name for name in required if name not in os.environ]
    check(not missing, "Set explicit environment variables: " + ", ".join(missing))
    env = os.environ.copy()
    check(env["DB_HOST"] in {"127.0.0.1", "localhost", "::1"}, "Tests require a loopback database host")
    check(re.fullmatch(r"[A-Za-z0-9_]+_test", env["DB_NAME"]) is not None,
          "Test database name must end in _test")
    check(env["DB_PORT"].isdigit() and 1 <= int(env["DB_PORT"]) <= 65535, "Invalid local DB port")
    env.pop("PHP_CLI_SERVER_WORKERS", None)
    db = Database(env["PHP_BIN"], env)
    tables = {next(iter(row.values())) for row in db.query("SHOW TABLES")["rows"]}
    if not tables:
        db.call(mode="schema", sql=(ROOT / "database/schema.sql").read_text())
        tables = {"users", "rooms", "reservations", "payment_attempts"}
    if tables == {"users", "rooms", "reservations"}:
        db.call(mode="schema", sql=(ROOT / "database/migrations/20261009_gcash_payments.sql").read_text())
        tables.add("payment_attempts")
    check(tables == {"users", "rooms", "reservations", "payment_attempts"}, "Test database has unexpected or incomplete tables")
    for table in sorted(tables):
        check(int(db.scalar(f"SELECT COUNT(*) FROM `{table}`")) == 0,
              "Use an empty test database; existing records must remain untouched")

    tag = "smoke_" + uuid.uuid4().hex[:12]
    password = "Synthetic-test-password-42!"
    users, rooms = [], []
    server = None
    completed = 0

    def passed(description):
        nonlocal completed
        completed += 1
        print("PASS " + description, flush=True)

    def fixture_reservations():
        return int(db.scalar("SELECT COUNT(*) FROM reservations WHERE room_id = ?", rooms[0]))

    with tempfile.TemporaryDirectory(prefix="hoteru-smoke-") as temporary:
        sessions = Path(temporary) / "sessions"
        sessions.mkdir()
        log_path = Path(temporary) / "php.log"
        try:
            hashed = db.call(mode="hash", password=password)
            emails = {}
            for role in ["admin", "staff", "customer"]:
                emails[role] = f"{tag}_{role}@example.invalid"
                users.append(int(db.query(
                    "INSERT INTO users (name,email,password,role) VALUES (?,?,?,?)",
                    f"Synthetic {role}", emails[role], password if role == "staff" else hashed, role,
                )["insert_id"]))
            room_name = tag + " room"
            room_type = tag + " Suite"
            rooms.append(int(db.query(
                "INSERT INTO rooms (room_name,room_type,room_rate,image,is_active) VALUES (?,?,?,'puno.png',1)",
                room_name, room_type, "1234.50",
            )["insert_id"]))
            with socket.socket() as sock:
                sock.bind(("127.0.0.1", 0))
                port = sock.getsockname()[1]
            base = f"http://127.0.0.1:{port}/"
            with log_path.open("w") as log:
                server = subprocess.Popen(
                    [env["PHP_BIN"], "-d", f"session.save_path={sessions}",
                     "-d", "display_errors=1", "-d", "log_errors=1", "-d", "error_reporting=-1",
                     "-S", f"127.0.0.1:{port}", "-t", str(ROOT / "htdocs")],
                    stdout=log, stderr=subprocess.STDOUT, env=env,
                )
            anonymous = Browser(base)
            for attempt in range(60):
                check(server.poll() is None, "Local PHP server exited during startup")
                try:
                    anonymous.request("login.php")
                    break
                except urllib.error.URLError:
                    time.sleep(0.1)
            else:
                raise AssertionError("Local PHP server did not start")

            protected = ["index.php", "rooms.php", "transactions.php", "reports.php", "receipt.php?id=1",
                         "save_reservation.php", "save_room.php", "checkout_guest.php", "clear_transactions.php"]
            for path in protected + ["save_guest_booking.php"]:
                anonymous.redirect(path, None, "login.php")
            passed("unauthenticated management pages redirect to login")

            browsers = {}
            for role, target in [("admin", "admin_dashboard.php"), ("staff", "index.php"),
                                 ("customer", "customer_home.php")]:
                browser = Browser(base)
                browser.redirect("login.php", {"email": emails[role], "password": password}, target)
                if role == "admin":
                    browser.redirect(target, None, "admin/dashboard.php")
                    browser.request("admin/dashboard.php")
                else:
                    browser.request(target)
                browsers[role] = browser
            for role, path, target in [("staff", "staff_login.php", "index.php"),
                                       ("customer", "customer_login.php", "customer_home.php")]:
                Browser(base).redirect(path, {"email": emails[role], "password": password}, target)
            passed("admin, staff and customer logins accept existing plaintext and hashed accounts")
            staff = browsers["staff"]
            customer = browsers["customer"]
            for path in protected:
                customer.request(path, status=403)
            customer.request("save_reservation.php", {"room_id": rooms[0]}, status=403)
            staff.request("save_guest_booking.php", {"room_id": rooms[0]}, status=403)
            staff.request("admin_dashboard.php", status=403)
            passed("customer cannot use staff endpoints and staff cannot use admin dashboard")

            token = staff.csrf(f"index.php?room_id={rooms[0]}")
            booking = {"room_id": rooms[0], "full_name": tag + " Guest", "contact_number": "09000000000",
                       "address": "Synthetic test address", "hours": "2", "payment_method": "Cash",
                       "csrf_token": token}
            for invalid in [dict(booking, hours=value) for value in ["0", "-1", "1.5", "366", ""]] + [
                dict(booking, **{field: ""}) for field in ["full_name", "contact_number", "address"]
            ]:
                target = staff.redirect("save_reservation.php", invalid, f"index.php?room_id={rooms[0]}#booking")
                body, _ = staff.request(target)
                check("whole nights" in body, "Invalid booking must display validation feedback")
                check(fixture_reservations() == 0, "Invalid booking created a reservation")
            staff.request("save_reservation.php", dict(booking, csrf_token="invalid"), status=403)
            staff.request("save_room.php", {"action": "disable", "room_id": rooms[0]}, status=403)
            staff.request("checkout_guest.php", {"reservation_id": 1}, status=403)
            staff.request("clear_transactions.php", {}, status=403)
            check(fixture_reservations() == 0, "CSRF rejection changed reservations")
            passed("invalid nights, missing guest details and missing/invalid CSRF tokens are rejected")

            _, headers = staff.request("save_reservation.php", booking, status=302)
            match = re.fullmatch(r"receipt\.php\?id=(\d+)", headers.get("Location", ""))
            check(match is not None, "Booking did not redirect to a receipt")
            reservation = int(match.group(1))
            row = db.query("SELECT room_id,hours,total_amount,TIMESTAMPDIFF(HOUR,check_in,check_out) AS duration "
                           "FROM reservations WHERE id = ?", reservation)["rows"][0]
            check(int(row["room_id"]) == rooms[0] and int(row["hours"]) == 2,
                  "Booked room or nights were not persisted correctly")
            check(Decimal(row["total_amount"]) == Decimal("2469.00") and int(row["duration"]) == 48,
                  "Booking must charge two nights and last 48 hours")
            receipt, _ = staff.request(headers["Location"])
            receipt_text = " ".join(text_content(receipt).split())
            check("Number of Nights: 2" in receipt_text and "Total Amount: ₱2,469.00" in receipt_text,
                  "Receipt does not show correct nights and total")
            dashboard, _ = staff.request(f"index.php?room_id={rooms[0]}")
            check("Check Out Guest" in dashboard and html.escape(booking["full_name"]) in dashboard,
                  "Booked room is not shown as occupied with checkout available")
            transactions, _ = staff.request("transactions.php?search=" + urllib.parse.quote(tag))
            check(html.escape(booking["full_name"]) in transactions and "₱2,469.00" in transactions,
                  "Booking is missing from transaction history")
            reports, _ = staff.request("reports.php")
            check(room_type in reports and "₱2,469.00" in reports, "Booking missing from sales reports")
            passed("staff booking persists correct room, 48-hour stay, receipt, occupancy, transactions and sales")

            target = staff.redirect("save_reservation.php", booking, f"index.php?room_id={rooms[0]}#booking")
            body, _ = staff.request(target)
            check("already has a booking" in body and fixture_reservations() == 1,
                  "Overlapping booking must be rejected without inserting another reservation")
            staff.redirect("save_room.php", {"csrf_token": token, "room_id": rooms[0], "action": "disable"},
                           "rooms.php?msg=occupied")
            check(int(db.scalar("SELECT is_active FROM rooms WHERE id = ?", rooms[0])) == 1,
                  "Occupied room was disabled")
            passed("overlapping booking and disabling an occupied room are blocked")

            added_name = tag + " added"
            room_form = {"csrf_token": token, "action": "add", "room_name": added_name,
                         "room_type": "Family Room", "room_rate": "500.25"}
            staff.redirect("save_room.php", room_form, "rooms.php?msg=added")
            added = int(db.scalar("SELECT id FROM rooms WHERE room_name = ?", added_name))
            rooms.append(added)
            room_form.update(action="update", room_id=added, room_rate="600.75")
            staff.redirect("save_room.php", room_form, "rooms.php?msg=updated")
            check(Decimal(db.scalar("SELECT room_rate FROM rooms WHERE id = ?", added)) == Decimal("600.75"),
                  "Room edit did not persist rate")
            for action, active in [("disable", 0), ("enable", 1)]:
                staff.redirect("save_room.php", {"csrf_token": token, "room_id": added, "action": action},
                               f"rooms.php?msg={action}d")
                check(int(db.scalar("SELECT is_active FROM rooms WHERE id = ?", added)) == active,
                      "Room enable/disable did not persist")
            passed("room creation, editing, disabling and enabling work")

            staff.redirect("checkout_guest.php", {"csrf_token": token, "reservation_id": reservation},
                           "index.php?checkout=success")
            dashboard, _ = staff.request(f"index.php?room_id={rooms[0]}")
            check('action="save_reservation.php"' in dashboard, "Checkout did not reopen room for booking")
            check(int(db.scalar("SELECT is_archived FROM reservations WHERE id = ?", reservation)) == 1,
                  "Checkout did not mark the reservation archived")
            _, headers = staff.request("save_reservation.php", dict(booking, hours="1", payment_method="GCash"), status=302)
            match = re.fullmatch(r"receipt\.php\?id=(\d+)", headers.get("Location", ""))
            check(match is not None and fixture_reservations() == 2, "Room cannot be rebooked after checkout")
            active_id = int(match.group(1))
            passed("checkout makes the room available immediately and allows another booking")

            completed_id = int(db.query(
                "INSERT INTO reservations (room_id,full_name,hours,payment_method,total_amount,check_in,check_out,is_archived) "
                "VALUES (?, ?, 1, 'Cash', 600.75, NOW() - INTERVAL 2 DAY, NOW() - INTERVAL 1 DAY, 0)",
                added, tag + " Completed Guest",
            )["insert_id"])
            staff.redirect("clear_transactions.php", {"csrf_token": token, "redirect": "transactions.php?clear=success"},
                           "transactions.php?clear=success")
            check(int(db.scalar("SELECT is_archived FROM reservations WHERE id = ?", completed_id)) == 1,
                  "Completed reservation was not archived")
            check(int(db.scalar("SELECT is_archived FROM reservations WHERE id = ?", active_id)) == 0,
                  "Archiving completed transactions affected an occupied room")
            archived, _ = staff.request("transactions.php?archive_filter=archived&search=" + urllib.parse.quote(tag))
            check(tag + " Completed Guest" in archived, "Archived transaction is missing from archived history")
            passed("archive includes completed stays and preserves active bookings")

            staff.redirect("checkout_guest.php", {"csrf_token": token, "reservation_id": active_id},
                           "index.php?checkout=success")
            customer_token = customer.csrf("customer_home.php")
            guest_booking = {"room_id": rooms[0], "full_name": tag + " Guest Portal", "contact_number": "09111111111",
                             "address": "Guest portal test address", "hours": "3", "payment_method": "Cash",
                             "csrf_token": customer_token}
            gcash_target = customer.redirect("save_guest_booking.php", dict(guest_booking, payment_method="GCash"),
                                             f"customer_home.php?room_id={rooms[0]}#book-room-{rooms[0]}")
            gcash_page, _ = customer.request(gcash_target)
            check("Online GCash payments are not configured" in gcash_page and fixture_reservations() == 2,
                  "Unconfigured GCash must not create a booking")
            customer.redirect("save_guest_booking.php", guest_booking, "customer_home.php#our-rooms")
            row = db.query("SELECT hours,total_amount,is_archived FROM reservations WHERE room_id = ? ORDER BY id DESC LIMIT 1", rooms[0])["rows"][0]
            check(int(row["hours"]) == 3 and Decimal(row["total_amount"]) == Decimal("3703.50") and int(row["is_archived"]) == 0,
                  "Guest portal booking was not persisted correctly")
            guest_page, _ = customer.request("customer_home.php")
            check("Occupied" in guest_page and f'id="book-room-{rooms[0]}"' not in guest_page,
                  "Guest portal did not show the booked room as occupied")
            dashboard, _ = staff.request(f"index.php?room_id={rooms[0]}")
            check("Check Out Guest" in dashboard and html.escape(guest_booking["full_name"]) in dashboard,
                  "Guest portal booking is not occupied in the staff portal")
            duplicate_target = customer.redirect("save_guest_booking.php", guest_booking, f"customer_home.php?room_id={rooms[0]}#book-room-{rooms[0]}")
            duplicate, _ = customer.request(duplicate_target)
            check("Another guest has just booked" in duplicate and fixture_reservations() == 3,
                  "Guest portal permitted an overlapping booking")
            passed("guest booking persists, marks rooms occupied in both portals and rejects overlaps")
        finally:
            if server is not None:
                server.terminate()
                try:
                    server.wait(timeout=5)
                except subprocess.TimeoutExpired:
                    server.kill()
                    server.wait(timeout=5)
            # Discover a just-added fixture if a failing HTTP assertion interrupted ID capture.
            for row in db.query("SELECT id FROM rooms WHERE room_name IN (?, ?)",
                                tag + " room", tag + " added")["rows"]:
                if int(row["id"]) not in rooms:
                    rooms.append(int(row["id"]))
            for room in rooms:
                db.query("DELETE FROM payment_attempts WHERE room_id = ?", room)
                db.query("DELETE FROM reservations WHERE room_id = ?", room)
                db.query("DELETE FROM rooms WHERE id = ?", room)
            for user in users:
                db.query("DELETE FROM users WHERE id = ?", user)
        check(not ERRORS.search(log_path.read_text()), "PHP server emitted warnings/errors; inspect application logs locally")
    print(f"PASS {completed} workflow groups; synthetic fixtures removed; no PHP warnings or errors")


if __name__ == "__main__":
    try:
        run()
    except (AssertionError, OSError, subprocess.SubprocessError) as error:
        print("FAIL " + str(error), file=sys.stderr)
        sys.exit(1)
