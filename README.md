# Hoteru

PHP hotel front desk application. Staff sign in at `login.php` or `staff_login.php` and use `index.php` to select rooms, record bookings, print receipts, and check out guests. Admin accounts reach the admin dashboard and can open Manage Hotel. Customer accounts reach room browsing and front desk contact information.

## Hosting this fix

Back up the current website files, then upload the updated contents of `htdocs/` to the hosting account's `htdocs/` directory, keeping the existing room images. Upload the PHP files together because the management forms and handlers share session and CSRF helpers. Sign out and back in, or reload open forms after uploading.

The existing database schema and account records remain compatible. **Do not import `database/schema.sql` over your live database.** It is a schema-only copy for a new, empty development database; it contains no guest records or accounts. No database migration is required for these fixes.

## Local development

Requires PHP with `mysqli`/mysqlnd and a MySQL-compatible database. Tested with PHP 8.4 and MariaDB 11.8.

`htdocs/config.php` accepts `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`, and optional `DB_SOCKET`. If unset, the original hosting connection settings remain in use. Set local overrides before starting the app; use a local database for development.

1. Create a local database and import `database/schema.sql`, or import your database backup locally.
2. Supply the database connection variables through your shell or environment settings.
3. Run `php -S 127.0.0.1:8080 -t htdocs` from the repository root.

In the prepared cloud environment, PHP is `/workspace/.hoteru-runtime/bin/php`. Start MariaDB with `/workspace/.hoteru-db/start.sh`; use `DB_HOST=localhost`, `DB_USER=agent`, an empty `DB_PASSWORD`, `DB_NAME=hotel_test`, and `DB_SOCKET=/workspace/.hoteru-db/run/mariadb.sock` for local socket authentication. This local database contains the supplied backup.

Bookings use whole nights; the existing database calls that column `hours`. PHP and the database session use Philippine time. Room availability considers a stay's checkout instant to be available, and booking requests lock the room while checking overlapping stays. Receipts use the saved charge even if the room price changes later.

## Regression tests

`tests/staff_workflow.py` exercises HTTP login, booking, receipts, room management, checkout, archives, validation, and role restrictions. It requires a separate, empty local database whose name ends in `_test`; never point it at your working database. Supply the required `DB_*` variables and `PHP_BIN` before running:

```sh
PHP_BIN=/workspace/.hoteru-runtime/bin/php python3 tests/staff_workflow.py
```

The script starts a temporary PHP server, creates synthetic fixtures, checks responses and database state, and removes its own fixtures afterward.
