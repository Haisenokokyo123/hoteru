# Hoteru

PHP hotel front desk application. Staff sign in at `login.php` or `staff_login.php` and use `index.php` to select rooms, record bookings, print receipts, and check out guests. Admin accounts reach the admin dashboard and can open Manage Hotel. Customer accounts reach room browsing and front desk contact information.

## Hosting this fix

Back up the current website files, then upload the updated contents of `htdocs/` to the hosting account's `htdocs/` directory, keeping the existing room images. Upload the PHP files together because the management forms and handlers share session and CSRF helpers. Sign out and back in, or reload open forms after uploading.

The redesigned interface also needs `style.css`, `auth.css`, `portals.css`, `ui.js`, `auth.js`, `staff.css`, `staff.js`, `staff-scroll.css`, and `staff-scroll.js`. Deploy these assets with the PHP pages so the mobile menu, password controls, and responsive layouts work. The guest page has a full-width photo hero and contact section, larger text, and scroll and hover motion that respects reduced-motion preferences.

The staff dashboard uses pronounced, reversible scroll animation throughout the page. Its photo header zooms and fades between photographs, a large decorative text ribbon travels horizontally, and a header progress line shows how far through the page you have scrolled. Statistics rise into view in sequence; section headings, room cards, the room picker, transaction panels and rows, booking steps, and the price summary each respond as they enter the screen. Scrolling back reverses the effects. On larger screens with enough vertical space, the photo header briefly stays in view while its sequence plays. Phones and shorter screens use an unpinned treatment. Scrolling stays native, booking links skip directly to the working sections, and focused controls remain visible. The Motion button remembers whether effects are enabled, and system reduced-motion settings take priority. The page remains usable without JavaScript. Dedicated staff assets use file modification times in their URLs so browsers load changes after deployment.

The existing database schema and account records remain compatible. **Do not import `database/schema.sql` over your live database.** It is a schema-only copy for a new, empty development database; it contains no guest records or accounts.

## QRPh online checkout

Guest bookings use PayMongo's hosted QRPh checkout. The guest is redirected to PayMongo, which displays its official QRPh code; compatible wallets, including GCash, can scan it. The hotel creates the reservation only after PayMongo sends a signed `checkout_session.payment.paid` webhook. Returning from the wallet alone never confirms a room.

Before enabling it in production:

1. Run [20261009_gcash_payments.sql](database/migrations/20261009_gcash_payments.sql) once in phpMyAdmin. This adds the `payment_attempts` table; it does not alter existing bookings.
2. Copy `htdocs/payment-config.php.example` to `htdocs/payment-config.php` on the server and set the PayMongo live secret key, webhook secret, and public HTTPS base URL. Keep this private file out of Git.
3. In PayMongo, create a webhook pointing to `https://your-domain/paymongo_webhook.php` and subscribe it to `checkout_session.payment.paid`, plus the failed/expired checkout events if available.

Online payment is deliberately unavailable until all three are complete. Pending checkouts hold a room for 15 minutes to prevent double booking; failed, cancelled, and expired attempts never create a reservation. The guest portal now shows Online payment (QRPh) as its single payment option.

## Hotel and room photographs

The supplied `pic1` through `pic6` photographs are now in `htdocs/images/hotel/`. The guest page uses `pic2` for its hero and displays all available photographs in a gallery. The staff header fades through the available photographs as you scroll, starting with `pic2` and then `pic4`, `pic5`, `pic6`, `pic1`, and `pic3`. Missing files are skipped automatically. Until these files are supplied, the headers use the existing `hoteru.png` and the gallery is omitted. No database changes are needed to add photographs.

Place room photographs in `htdocs/images/rooms/`, using these filenames (before the extension):

| Room                | Filename                                    |
| ------------------- | ------------------------------------------- |
| 2 Single Bed Room 1 | `2singlebed1`                               |
| 2 Single Bed Room 2 | `2singledbed2` (also accepts `2singlebed2`) |
| Family Room 1       | `1family1`                                  |
| Family Room 2       | `2family2`                                  |
| Fan Room 1          | `1fan1`                                     |
| Fan Room 2          | `2fan2`                                     |
| King Room 1         | `kingroom1`                                 |
| King Room 2         | `kingroom2`                                 |

Supported extensions are `.webp`, `.jpg`, `.jpeg`, `.png`, and `.avif`, including uppercase extensions. The app also checks `htdocs/images/` and `htdocs/` for these named files. Photos uploaded through Room Management take priority; otherwise the named photo overrides the existing generic image. Without a matching file, the existing image or illustrated placeholder remains. This mapping works across guest browsing and staff room management without changing database records. The new original photographs are not included in this repository yet.

## Local development

Requires PHP with `mysqli`/mysqlnd and a MySQL-compatible database. Tested with PHP 8.4 and MariaDB 11.8.

`htdocs/config.php` accepts `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`, and optional `DB_SOCKET`. If unset, the original hosting connection settings remain in use. Set local overrides before starting the app; use a local database for development.

1. Create a local database and import `database/schema.sql`, or import your database backup locally.
2. Supply the database connection variables through your shell or environment settings.
3. Run `php -S 127.0.0.1:8080 -t htdocs` from the repository root.

In the prepared cloud environment, PHP is `/workspace/.hoteru-runtime/bin/php`. Start MariaDB with `/workspace/.hoteru-db/start.sh`; use `DB_HOST=localhost`, `DB_USER=agent`, an empty `DB_PASSWORD`, `DB_NAME=hotel_test`, and `DB_SOCKET=/workspace/.hoteru-db/run/mariadb.sock` for local socket authentication. This local database contains the supplied backup.

Bookings use whole nights; the existing database calls that column `hours`. PHP and the database session use Philippine time. Online QRPh bookings are created only after the payment webhook is confirmed. Room availability considers a stay's checkout instant to be available. Receipts use the saved charge even if the room price changes later.

## Regression tests

`tests/staff_workflow.py` exercises HTTP login, booking, receipts, room management, checkout, archives, validation, and role restrictions. It requires a separate, empty local database whose name ends in `_test`; never point it at your working database. Supply the required `DB_*` variables and `PHP_BIN` before running:

```sh
PHP_BIN=/workspace/.hoteru-runtime/bin/php python3 tests/staff_workflow.py
```

The script starts a temporary PHP server, creates synthetic fixtures, checks responses and database state, and removes its own fixtures afterward.
