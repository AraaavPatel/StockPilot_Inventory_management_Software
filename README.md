# StockPilot

QR / Barcode Based Inventory & Billing Platform for Kirana Stores
PHP 8.3 · MySQL 8 · Custom MVC · Bootstrap-free Swiss-design UI

## What's built (Phase 1)

- Full DB schema (9 tables, 3NF, FK constraints, CHECK constraints) — `database/migrations/001_create_schema.sql`
- Seed data (users, categories, suppliers, customers, products) — `database/seeds/002_seed_data.sql`
- Core MVC framework: Router, PDO Database singleton, base Model/Controller, Auth, role middleware (Admin / Manager / Cashier)
- Login + session auth + CSRF protection
- Dashboard (today/month sales, low-stock alerts, 7-day trend chart, recent sales)
- **POS Billing screen (flagship module)**: camera QR/barcode scan (html5-qrcode) or USB scanner / manual entry, live cart, GST calculation, discount, checkout as one DB transaction (stock never oversold), GST invoice — on-screen + downloadable PDF (DomPDF)

## Not yet built (Phase 2 — say the word and I'll continue)

Products/Categories/Suppliers/Purchases/Customers CRUD screens, Sales history list, Stock Adjustments, Reports (Excel export via PhpSpreadsheet), User Management (admin).
The routes for all of these already exist in `routes/web.php` — they just need their Controllers/Models/Views.

## Setup on Laragon

1. **Place the project**: copy this whole folder to `C:\laragon\www\stockpilot`

2. **Install dependencies** (Laragon's terminal, or any terminal with PHP+Composer on PATH):
   ```
   cd C:\laragon\www\stockpilot
   composer install
   ```

3. **Create the database**: open phpMyAdmin (or HeidiSQL from Laragon) and run, in order:
   - `database/migrations/001_create_schema.sql`
   - `database/seeds/002_seed_data.sql`

4. **Configure environment**:
   ```
   copy .env.example .env
   ```
   Edit `.env` if your MySQL root password isn't blank, or if you'll access the app at a different path.

5. **Enable mod_rewrite** (Laragon's Apache has it on by default). If you get 404s on every route except `/`, check `public/.htaccess` is present and `AllowOverride All` is set for the Laragon www directory (it is, by default).

6. **Visit**: `http://localhost/stockpilot/public`

   Login with any seeded account — password is `password123` for all three:
   - `admin@stockpilot.test` (full access)
   - `manager@stockpilot.test` (no user management)
   - `cashier@stockpilot.test` (POS + view-only catalog)

## Notes on the seeded password hash

The bcrypt hash in the seed file was generated for `password123`. If login fails because your PHP's bcrypt cost/salt handling differs, just re-hash it yourself:
```php
<?php echo password_hash('password123', PASSWORD_BCRYPT);
```
and swap the `password_hash` values in `002_seed_data.sql` (or update via SQL) before re-running the seed.

## Architecture notes

- **No framework, but not "no Composer" either** — this project uses Composer purely for autoloading (PSR-4) + three libraries (dotenv, dompdf, phpspreadsheet), matching what your deck's tech-stack slide specifies. The MVC itself (Router, Model, Controller, Auth, Middleware) is hand-rolled, ~350 lines total, in `app/Core/`.
- **Every DB write goes through prepared statements** (PDO, `ATTR_EMULATE_PREPARES => false`) — no raw string interpolation into SQL anywhere.
- **Checkout is transactional**: `Sale::checkout()` wraps the sale header, line items, and stock decrement in one `beginTransaction()/commit()/rollBack()`, with a `WHERE stock_qty >= :qty` guard on the decrement so two cashiers can never oversell the same last unit.
- **Prices are never trusted from the client**: `PosController::checkout()` re-reads price/GST/stock from the DB for every cart line before charging.

## WhatsApp auto-send — one hard requirement

`WhatsAppService::sendInvoiceNotification()` sends the customer a link to `/pos/invoice/{id}/pdf` for them to open. **That URL only works if it's publicly reachable** — a WhatsApp user's phone can't open `localhost` or your home Wi-Fi IP. This means WhatsApp auto-send will only actually work once StockPilot is deployed to a real server with a domain + HTTPS (see the VPS deployment steps discussed earlier), not while running locally on Laragon. Locally, you'll see the attempt logged to `storage/logs/whatsapp.log` but the link in the message won't open for the customer.

Full setup checklist (Meta Business account, template approval, credentials) is documented at the top of `app/Services/WhatsAppService.php`.

## Mobile / tablet UI

Below 960px width, the sidebar collapses into a black top bar (with a ☰ menu for the full nav) plus a 5-item bottom tab bar — Home, Bill, Stock, Sales, Customers — matching the pattern cashiers already know from UPI apps. The POS screen drops to one column with the bill summary pinned near the bottom so total + checkout stay reachable one-handed. Test by resizing your browser or opening on an actual phone/tablet once deployed.
