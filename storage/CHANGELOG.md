# CHANGELOG

## Session 2 — Feature completion + audit/notifications/scanner-mode

### Added
- `UserController` + `users/index.php` view — admin-only user management.
  Self-role/status change is blocked at the controller level (an admin
  can't demote or deactivate themselves; a different admin must do it).
- `ReportController` + `reports/index.php` view — daily sales, top
  products, low-stock list, date-range filter, CSV export.
- `StockAdjustmentController` + `StockAdjustment` model +
  `stock_adjustments/index.php` view — transactional add/remove stock
  with a required reason, guarded against taking stock negative.
- `App\Audit\AuditLogger` — append-only, hash-chained audit log.
  `AuditLogger::verifyChain()` detects direct-database tampering.
  Wired into: login success/failed/lockout, logout, product
  create/update/delete, stock adjustments, user creation, role changes,
  password changes, sale creation.
- `AuditController` + `audit/index.php` — admin-only, read-only audit
  viewer with filtering, pagination, and a chain-integrity indicator.
  No edit/delete route exists for this table anywhere in the app.
- `App\Notifications\Mailer` — dependency-free SMTP client (STARTTLS/
  SSL, AUTH LOGIN). Written from scratch instead of pulling in
  PHPMailer because this environment couldn't reach packagist.org to
  run `composer require`; functionally equivalent for this app's needs.
- `App\Notifications\AdminNotifier` — sends "new admin created," "new
  user created," "role changed," and "suspicious login" emails to
  `MAIL_ADMIN_ADDRESS`. All notification sends are fire-and-forget:
  failures are logged to `notification_log` and never roll back the
  business operation that triggered them.
- Camera / Hardware / Manual scanner mode switcher on the POS screen.
  Hardware and Manual modes share the existing Enter-terminated input
  (a USB/Bluetooth scanner is a keyboard from the browser's
  perspective — nothing scanner-specific to add beyond that). Camera
  now stops (releases the device) whenever a non-camera mode is
  selected, and also stops on tab visibility change.
- `public/setup-admin.php` — token-protected, single-use, first-admin
  creation for production. Writes `storage/setup-complete.lock` after
  use and refuses to run twice.
- `database/deploy/infinityfree.sql` — consolidated, idempotent
  (`CREATE TABLE IF NOT EXISTS` throughout, no `DROP TABLE`, no
  `CREATE DATABASE`) production schema safe for InfinityFree's
  managed-database-name hosting model.
- `database/migrations/002_login_throttle.sql`,
  `003_audit_and_notifications.sql` — additive, non-destructive.
- `DEPLOYMENT_INFINITYFREE.md` — step-by-step deployment + local
  smoke-test checklist.
- `.env.example` — added `MAIL_*`, `MAIL_ADMIN_ADDRESS`,
  `LOGIN_MAX_ATTEMPTS`, `LOGIN_LOCKOUT_WINDOW_SECONDS`, `SETUP_TOKEN`.

### Fixed (carried over from Session 1 security pass)
- Demo credentials removed from the production login page (gated
  behind `APP_DEBUG=true`).
- Login throttling added (5 failed attempts / 15 min lockout,
  keyed on email+IP).
- Two DB-sourced `<option value>` IDs cast to `(int)` for
  defense-in-depth (not exploitable as found — values weren't
  user input — but inconsistent with the rest of the codebase's style).

### Explicitly out of scope this session (not done, not claimed done)
- Localization (`resources/lang/en|gu|hi`) — not started.
- JavaScript modularization into `public/assets/js/` — POS scripts
  remain inline in the view; the mode-switcher code was added inline
  to match the existing pattern rather than partially modularizing
  just one feature.
- `TEST_REPORT.md` — not produced; no live PHP/MySQL server was
  available in this environment to execute end-to-end tests against.
  Every file was syntax-checked with `php -l` and cross-referenced
  method-by-method against its callers, but that is static verification,
  not a test run. Treat the local smoke-test checklist in
  `DEPLOYMENT_INFINITYFREE.md` as the real test report — run it before
  deploying.
- Hardware-scanner-specific input heuristics (distinguishing fast
  scanner input from human typing) — not implemented. Both rely on
  Enter-to-submit, which is how real USB/Bluetooth scanners behave, so
  this is not a functional gap, just something more sophisticated than
  what's here would be possible.
- `composer.json` unchanged — no new Composer dependency was added
  (Mailer is dependency-free by necessity, not by choice — this
  sandbox has no network access to packagist.org).

## Session 1 — Security pass on existing code

See `SECURITY_AUDIT.md` for full detail. Summary: audited every
controller, model, view, and config file; confirmed CSRF, session
hardening, security headers, directory `.htaccess` protection, and
mass-assignment whitelisting were already correctly implemented;
removed the plaintext demo-credential hint from the login page.
