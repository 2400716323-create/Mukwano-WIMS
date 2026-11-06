# Mukwano WIMS

Mukwano WIMS is a Laravel warehouse and inventory application for product and
supplier records, purchase orders, goods receiving, bin inventory, FEFO
dispatch, cycle counts, audit history, alerts, reports, and ERP CSV exchange.
The application targets Laravel 12 and PHP 8.3 or newer.

## Requirements

- PHP 8.3+ with the extensions required by Laravel and Composer (including
  `fileinfo`, `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `xml`, `ctype`,
  and `curl`).
- Composer 2.
- MySQL 8+ or a compatible MariaDB server for a normal installation.
- Node.js and npm to build the Vite frontend.
- Apache with URL rewriting enabled, or another web server configured to use
  the application's `public/` directory as its document root.

## Local setup on Windows with XAMPP

1. Install XAMPP with a PHP version supported by this application. In
   `C:\xampp\php\php.ini`, enable the required PHP extensions (at minimum
   `extension=pdo_mysql`, `extension=fileinfo`, `extension=mbstring`,
   `extension=openssl`, and `extension=curl`), then restart Apache. Confirm
   that the command-line `php` on `PATH` is the same compatible PHP version:

   ```powershell
   php -v
   composer -V
   node -v
   npm -v
   ```

2. Create a MySQL database and a dedicated local database user in
   phpMyAdmin, or use the local XAMPP MySQL account. From PowerShell:

   ```powershell
   Set-Location C:\xampp\htdocs\mukwano-wims
   Copy-Item .env.example .env
   composer install
   ```

3. Edit `.env` for the local database (`DB_CONNECTION=mysql`, host, port,
   database, username, and password). Keep local development settings such
   as `APP_ENV=local` and `APP_DEBUG=true` local-only. Generate the encryption
   key, migrate, and optionally seed locations and a development administrator:

   ```powershell
   php artisan key:generate
   php artisan migrate
   ```

   To create a local demo administrator, set `DEMO_USER_PASSWORD` to a unique
   password of at least 12 characters in your untracked `.env`, then run:

   ```powershell
   php artisan db:seed
   ```

   The local-only demo account uses `DEMO_USER_EMAIL` (default
   `demo-admin@example.test`) and the password you set. Do not copy demo
   credentials to production.

4. Create the public storage link and build frontend assets:

   ```powershell
   php artisan storage:link
   npm install
   npm run build
   ```

5. For a quick local server, run:

   ```powershell
   php artisan serve
   ```

   Open `http://127.0.0.1:8000`. For XAMPP Apache, configure a virtual host
   whose document root is `C:/xampp/htdocs/mukwano-wims/public` (not the
   repository root), allow `.htaccess` overrides, and enable `mod_rewrite`.
   Set `APP_URL` to the local virtual-host URL.

To run tests:

```powershell
php artisan test
```

See the [user manual](docs/USER-MANUAL.md) for application workflows and the
[future Flutter API contract](docs/FLUTTER-API.md) for the explicitly
not-yet-implemented mobile API design.

## Production installation

1. Provision HTTPS, a supported PHP runtime, a dedicated MySQL/MariaDB
   database and restricted database user, and a web server whose document
   root points only to the release's `public/` directory. Do not expose `.env`,
   `storage/`, `vendor/`, or the repository root over HTTP.
2. Install the release and dependencies:

   ```powershell
   Copy-Item .env.example .env
   composer install --no-dev --prefer-dist --optimize-autoloader
   ```

   On Linux production hosts, use the equivalent shell commands.
3. Set production secrets and service settings in `.env`, including a unique
   `APP_KEY`, `APP_ENV=production`, `APP_DEBUG=false`, the HTTPS `APP_URL`,
   database credentials, and production mail/cache/session settings. Never
   commit or publish `.env`. Serve the application only over HTTPS. The
   session cookie defaults to `Secure` when `APP_ENV=production`; explicitly
   keep `SESSION_HTTP_ONLY=true` and `SESSION_SAME_SITE=lax`. Install and
   build frontend assets after configuring the environment:

   ```powershell
   npm install
   npm run build
   ```
4. Run deployment commands:

   ```powershell
   php artisan key:generate
   php artisan migrate --force
   php artisan storage:link
   ```

   Generate `APP_KEY` only once for a new installation. Do not replace it on
   an existing deployment because encrypted data and sessions depend on it.
5. On the first production database only, provision the initial administrator
   through the guarded seeder. Temporarily set `INITIAL_ADMIN_EMAIL` and a
   unique `INITIAL_ADMIN_PASSWORD` of at least 12 characters in the protected
   environment, then run:

   ```powershell
   php artisan db:seed --class=InitialAdministratorSeeder --force
   ```

   The seeder refuses to create another initial admin if any user already
   exists. Remove the bootstrap credentials from the environment immediately
   afterward. Create all further accounts from **Administration**.
6. Cache production configuration and routes after environment and deployment
   configuration are final:

   ```powershell
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

   Use your host's process manager to run any required queue workers. Deploy
   assets and application code atomically, and restrict write access to
   `storage/` and `bootstrap/cache/` to the application account.

The production error page must not expose stack traces: `APP_DEBUG` must remain
`false`. Send logs to a protected log store, review them, and avoid logging
passwords, tokens, or uploaded file contents.

## Database backups and restore

`php artisan backup:run` supports SQLite deployments only. It writes the
database snapshot and files from the configured public filesystem disk below
`storage/app/private/backups/<timestamp>/`. Protect this directory and copy
backups to secure off-site storage.

For production MySQL/MariaDB, use the database provider's managed backups or
`mysqldump`; the application command does not back up these databases. Example
PowerShell backup (the password is prompted, not placed on the command line):

```powershell
mysqldump --single-transaction --routines --triggers -u wims_backup -p `
  --result-file="D:\secure-backups\mukwano-wims.sql" mukwano_wims
```

To restore, place the application in maintenance mode, stop queue workers,
preserve the current database and uploads, restore the dump to the intended
database, restore the matching uploads from the protected storage backup,
then verify the application before leaving maintenance mode. A MySQL restore
can be run from PowerShell with:

```powershell
cmd.exe /c 'mysql -u wims_admin -p mukwano_wims < D:\secure-backups\mukwano-wims.sql'
```

Test backup restoration regularly in a non-production environment. Database
and upload backups must represent the same recovery point.

## Offline scope

The installable PWA caches only its offline notice, manifest, and icons. Only
already-open goods-receipt review, dispatch-pick, and count-submission forms
can be queued in browser IndexedDB. Final receipt confirmation, count
approval, transfers, inventory changes, reference-data loading, reporting,
imports/exports, and all other operations require connectivity. The queue
revalidates on the server when connected and does not silently overwrite
balances. It does not sync after the page is closed, and local queue data is
not encrypted by the app. Do not use it on a shared or untrusted device.

The current offline sync routes use a browser session and CSRF protection.
There is no versioned bearer-token API for a Flutter client; the API document
is a future design only.

## Final hardening review

- Web mutations use Laravel's CSRF middleware and Blade's escaped output by
  default. The QR label's raw SVG is generated by the application. Product
  images are validated by decoded image MIME and served through the
  application image route with content-type protections. Database filters use
  validated values and parameterized query-builder/Eloquent bindings.
- Sign-in is rate-limited; inactive users cannot authenticate. Session IDs
  regenerate on sign-in, and session cookies default to `Secure` in
  production, `HttpOnly`, and `SameSite=Lax`.
- Removed per-line product/location queries from receipt review and
  per-count accuracy/settings lookups from the count list. Those screens now
  batch-load their related records and shared settings.
- The schema has explicit indexes/unique constraints for common lookups such
  as product SKU/status, batch/product and expiry, bin balances, movement
  references, count status/lines, and alert state. This code review did not
  have production-sized data or query plans; assess the audit date/event
  filters and time-ordered stock-movement queries with `EXPLAIN` before adding
  workload-specific indexes.
- Phone, tablet, and desktop viewport checks were performed on the dashboard
  and sign-in page; no horizontal overflow was observed at 390, 768, and
  1440 CSS pixels. Basic accessibility checks covered page headings, visible
  form labels, named buttons, and the dashboard's live network status. This
  was not a full WCAG audit or a test of every page or assistive technology.

## Delivery status

### Implemented

- Product, category, supplier, warehouse-location, and QR label/scan workflows.
- Purchase orders, goods receipts, stock balances and movement ledger, bin
  transfers and put-away, FEFO dispatch, and reviewed cycle counts.
- Inventory reports, PDF/CSV exports, ERP product CSV staging/validation,
  stock reconciliation exports, and inventory alerts.
- User roles, activation, profile/password management, sign-in/sign-out,
  administrator settings, and a read-only audit log.
- SQLite backup command, installable PWA shell, and idempotent offline queue
  limited to three already-loaded forms.

### Partial

- Offline behavior is limited to the PWA forms described above; there is no
  offline browsing, closed-app/background sync, or encrypted local queue.
- The in-app backup command supports SQLite only. Production MySQL/MariaDB
  backups must be handled by the provider or database tooling.
- Roles and their permissions are configured in code; the administration
  screen describes permissions but does not let administrators define
  arbitrary permission sets.
- Responsive and accessibility checks are spot checks, not a complete
  WCAG/accessibility certification across every screen and assistive
  technology.
- Query/index review was code-based and focused on identified list/detail
  paths; it is not a production-scale load test or query-plan assessment.

### Not implemented

- A versioned JSON API, token authentication, or a Flutter client. See
  [docs/FLUTTER-API.md](docs/FLUTTER-API.md); its endpoint list is proposed.
- Self-service password recovery or multi-factor authentication.
- Automated MySQL/MariaDB backup and restore through `backup:run`.

This status covers the scoped administration, PWA/offline, and final-hardening
requirements available in this repository. Earlier phase-specific acceptance
criteria were not included with this checkout and should be compared before
claiming a complete phase-by-phase certification.
