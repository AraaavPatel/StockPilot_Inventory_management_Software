# StockPilot — InfinityFree Deployment Guide

Read this fully before you start. Two things will save you the most
pain: **test locally first** (checklist at the bottom), and
**InfinityFree cannot send outbound SMTP to arbitrary hosts on the free
tier** — you need an external SMTP relay for email to work at all.

## 1. Create the InfinityFree account & database

1. Sign up at infinityfree.net, create a website (subdomain or your own domain).
2. In the control panel, go to **MySQL Databases** and create one. InfinityFree
   assigns the database name and username for you — write down all four values:
   - Database host (often something like `sqlXXX.infinityfree.com`, NOT `localhost`)
   - Database name (e.g. `if0_12345678_stockpilot`)
   - Database username
   - Database password
3. Open **phpMyAdmin** from the control panel (it connects to that database automatically).

## 2. Import the schema

1. In phpMyAdmin, go to the **Import** tab.
2. Choose `database/deploy/infinityfree.sql` from this project.
3. Run the import. This file only contains `CREATE TABLE IF NOT EXISTS` —
   no `DROP TABLE`, no `CREATE DATABASE` — so it's safe even if you re-run it.
4. **Do NOT import `database/seeds/002_seed_data.sql` into production** —
   it creates demo accounts with the password `password123`. Use the
   product/category rows from it as inspiration if you want sample
   data, but type your own real admin credentials via Step 5 below.

## 3. Upload the application files

InfinityFree does not let you set a custom document root — whatever
you put in `htdocs/` is what the web server serves directly. Because of
that, upload the **entire project** (not just `public/`) into `htdocs/`,
keeping `public/` as a subfolder alongside `app/`, `config/`, etc.

This works because `app/`, `config/`, `database/`, and `storage/` each
already contain a `.htaccess` with `Require all denied` (and a legacy
`Deny from all` fallback) — Apache will refuse any direct request into
those folders even though they're physically inside `htdocs/`. Verify
this after upload (Step 8) — don't just trust it blindly.

If your InfinityFree plan happens to let you point the vHost root at
`htdocs/public` instead, that's strictly better (defense in depth) —
use it if available, but the `.htaccess` protection means it isn't
required for security.

Composer's `vendor/` folder: this sandbox couldn't reach packagist.org
to run `composer install`, so **you need to run `composer install`
yourself** (locally, with internet access) before uploading, then
upload the resulting `vendor/` folder along with everything else.
InfinityFree does not give you shell/SSH access to run Composer there.

## 4. Configure `.env`

Copy `.env.example` to `.env` and fill in real values:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.infinityfreeapp.com
APP_TIMEZONE=Asia/Kolkata

DB_HOST=<the sqlXXX.infinityfree.com host from Step 1>
DB_NAME=<the if0_... database name from Step 1>
DB_USER=<the database username from Step 1>
DB_PASS=<the database password from Step 1>

MAIL_HOST=<your SMTP relay, e.g. smtp-relay.brevo.com — see note below>
MAIL_PORT=587
MAIL_USERNAME=<relay username>
MAIL_PASSWORD=<relay password>
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@your-domain.example
MAIL_FROM_NAME=StockPilot
MAIL_ADMIN_ADDRESS=<your real email — gets security alerts>

SETUP_TOKEN=<a long random string, just for Step 5 — anything works>
```

**About email:** InfinityFree's free tier blocks outbound connections
to arbitrary SMTP ports for spam-prevention reasons. Leaving `MAIL_HOST`
blank disables email entirely and the app keeps working normally
(notification failures never block a sale, a checkout, or a user
creation — see `SECURITY_AUDIT.md`). If you want real email, use an
external relay's SMTP credentials (Brevo, SendGrid, Mailtrap, or a
Gmail App Password) — those work over standard ports InfinityFree
doesn't block.

**Never commit this `.env` file or upload it anywhere public.** It's
already covered by the `.env` deny rule in `public/.htaccess`, but
that's a second layer of defense, not a reason to be careless with it.

## 5. Create your first real admin account

1. Visit `https://your-domain/setup-admin.php?token=<your SETUP_TOKEN>`
2. Fill in your real name, email, and a strong password (12+ characters).
3. **Immediately delete `public/setup-admin.php` from your hosting file
   manager after this succeeds.** The script also writes
   `storage/setup-complete.lock` and refuses to run a second time even
   if you forget — but delete the file anyway; don't rely on the lock alone.

## 6. Test the deployment

Go through the checklist in the next section, but on the live URL this time.

## 7. Final hardening checklist before calling it done

- [ ] `.env` has `APP_DEBUG=false` — a debug page left on in production leaks
      stack traces, file paths, and database errors to any visitor.
- [ ] Visit `https://your-domain/.env` directly — it must return a 403 or 404,
      never the file contents. Same for `https://your-domain/composer.json`.
- [ ] Visit `https://your-domain/app/Core/Database.php` — must be blocked.
- [ ] `public/setup-admin.php` has been deleted from the server.
- [ ] The dev seed accounts (`admin@stockpilot.test` / `password123`) were
      never imported into this database.
- [ ] HTTPS is enabled in the InfinityFree control panel (free SSL is
      available) — the camera scanner requires a secure context to work
      in most browsers, and won't function over plain HTTP at all.
- [ ] Log in as your real admin, go to **Security & Audit Logs**, and confirm
      "Chain integrity: Verified" is shown.

---

## Local smoke test (do this BEFORE touching InfinityFree)

Run this against Laragon/XAMPP first. If any step fails locally, it
will fail identically on InfinityFree — find it here, where you can
actually see the PHP error log.

1. Import `database/migrations/001_create_schema.sql`, then
   `002_login_throttle.sql`, then `003_audit_and_notifications.sql`
   (in that order) into your local `stockpilot` database.
2. Import `database/seeds/002_seed_data.sql` for local test data (fine
   to use `password123` locally — never in production).
3. `composer install`.
4. Copy `.env.example` to `.env`, set local DB credentials, leave
   `MAIL_HOST` blank (email will just no-op locally, which is fine).
5. Log in as `admin@stockpilot.test`.
6. **POS**: switch between Camera / Hardware / Manual scanner modes —
   confirm the camera view disappears and the camera indicator light
   turns off when you leave Camera mode. Scan or type a known barcode
   (e.g. `8901030702057`), confirm it adds to cart, complete a checkout.
7. **Users**: create a new manager account. Confirm you cannot edit
   your own role/status (the form should be replaced with a note).
8. **Stock Adjustments**: record an "add" and a "remove" adjustment;
   confirm the product's stock quantity actually changes on the
   Products page.
9. **Reports**: confirm the daily sales table shows the sale from step 6,
   and Export CSV downloads a file.
10. **Security & Audit Logs**: confirm you see entries for your login,
    the user you created, and the stock adjustment — each with a
    timestamp, your name, and an IP address. Confirm "Chain integrity:
    Verified" is shown.
11. Log out, then deliberately fail a login 6 times in a row with the
    wrong password for the same account — the 6th attempt should show
    "Too many failed attempts" even with the *correct* password.
