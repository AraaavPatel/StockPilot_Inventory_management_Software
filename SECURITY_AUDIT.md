# StockPilot — Security Audit (Pass 1: existing-code hardening)

Scope of this pass: audit and harden code that already exists in the
uploaded SP3 project. This does **not** cover features that don't exist
yet (audit logging, email notifications, hardware scanner mode,
localization, User/Report/StockAdjustment controllers) — those are
separate phases, not security issues in what's here today.

Every finding below was confirmed by direct file inspection, not assumed
from the request brief.

## Already correctly implemented (verified, no change needed)

| Area | Finding |
|---|---|
| SQL injection | Every query goes through `App\Core\Model` or hand-written `PDO::prepare()` calls with bound parameters. No string-concatenated SQL found anywhere in `app/`. |
| XSS | Every dynamic value in every view is passed through `htmlspecialchars()`, `(int)` cast, or `number_format()`. No raw `<?= $var ?>` of untrusted string data found. |
| CSRF | Single implementation in `Controller::csrfToken()` / `verifyCsrf()`, using `hash_equals()`, applied to every state-changing POST route. |
| Session security | `config/config.php` already sets `HttpOnly`, `Secure`-when-HTTPS, `SameSite=Lax` cookie flags; rotates the session ID every 15 minutes; enforces a 30-minute idle timeout; fully wipes `$_SESSION` on logout. |
| Security headers | `config/config.php` already sends CSP, `X-Content-Type-Options`, `X-Frame-Options: DENY`, `Referrer-Policy`, `Permissions-Policy`, and HSTS-when-HTTPS on every response. |
| Directory protection | `app/`, `config/`, `database/`, `storage/` each already ship a `.htaccess` with both Apache 2.4 (`Require all denied`) and 2.2 (`Deny from all`) syntax — this is exactly the InfinityFree-safe pattern needed since that host won't let you move the document root. `public/.htaccess` denies dotfiles and `.sql/.log/.bak/.env`. |
| Mass assignment | `ProductController::collectInput()` (and equivalents) whitelist fields explicitly; nothing passes raw `$_POST` into `Model::create()/update()`. |
| Price/stock trust | `PosController::checkout()` re-fetches price, GST, and stock from the DB for every cart line and ignores whatever the client sent. |
| Stock race conditions | `Product::decrementStock()` uses `UPDATE ... WHERE stock_qty >= :qty` with a rowcount check inside `Sale::checkout()`'s transaction — a second concurrent sale cannot oversell the same unit. |
| File upload | No upload feature exists yet, but `App\Core\UploadValidator` is already built and ready (random filenames, real `finfo` MIME sniffing, `getimagesize` verification, size cap) for whenever product images are added. |
| Password storage | `password_hash()`/`password_verify()`, generic "Invalid email or password" message (no user-enumeration leak). |

## Findings fixed in this pass

| Severity | Finding | Fix |
|---|---|---|
| **High** | Login page displayed the seeded demo credentials (`admin@stockpilot.test` / `password123`) in plain text to every visitor, including in production. | Gated behind `APP_DEBUG=true` in `app/Views/auth/login.php`. Never shown when debug is off. |
| **Medium** | No login throttling — an attacker could brute-force any account's password with unlimited attempts. | Added `login_attempts` table (`database/migrations/002_login_throttle.sql`, non-destructive `CREATE TABLE IF NOT EXISTS`) and `App\Core\LoginThrottle`: locks an email+IP pair for 15 minutes after 5 failed attempts, wired into `AuthController::login()`. Lockout message is identical to the normal failure message, so it doesn't leak account existence. |
| **Low** | Two `<select>` option values (`category_id`, `supplier_id`) echoed DB-sourced integer IDs without an explicit cast. Not exploitable (values come from the DB, not user input) but inconsistent with the rest of the codebase's defense-in-depth style. | Cast to `(int)` in `products/form.php` and `purchases/form.php`. |

## Open findings — real, not yet addressed

| Severity | Finding | Why it's not fixed in this pass |
|---|---|---|
| Medium | `database/migrations/001_create_schema.sql` uses `DROP TABLE IF EXISTS` for every table. Fine for first install, destructive if re-run against a live database. | Needs a proper migrations directory split (`001_initial`, `002_...` additive-only) — bigger structural change, belongs in the deployment-prep phase. |
| Medium | No hardware/USB barcode scanner mode, and no manual-entry fallback — `pos/index.php` only has the camera scanner (`html5-qrcode`, loaded from CDN, inline `<script>`). | Out of scope for a "security pass"; this is the scanner-UX phase. |
| Low | POS/product JavaScript lives inline in view files rather than modular files under `public/assets/js/`. | Not a security issue, a maintainability one — Step 25 in your brief, separate phase. |
| Informational | `routes/web.php` still points at `UserController`, `ReportController`, `StockAdjustmentController`, none of which exist — hitting `/users`, `/reports`, `/stock-adjustments` is a fatal PHP error today, not a vulnerability, but worth fixing before deployment. | Feature-completion phase, not hardening. |
| Informational | Invoice numbers are generated from `COUNT(*) ... WHERE YEAR(sale_date) = :year` inside the transaction — under very high concurrency two simultaneous checkouts could theoretically compute the same sequence number before either commits. Low real-world risk for a single-till kirana store. | Would need a dedicated sequence table or `INSERT ... ON DUPLICATE KEY` counter to fully close; noted for awareness, not fixed here since it's not a security hole (no data leak/oversell), just a rare uniqueness edge case. |
| Informational | The local `.env` in this upload contains what looks like a real local MySQL password. It's already `.gitignore`d, but don't ship it in any handoff/zip — treat it as compromised and rotate it locally if this ever left your machine. | Not something I can "fix" — flagging so you rotate it if needed. |

## Verification performed

Static review only in this pass — grep/read every controller, model,
view, middleware, and config file for the patterns above; no live DB or
HTTP server was available in this environment to run the login-throttle
or checkout flows end-to-end. Recommend a manual pass of: 6 failed
logins in a row (should lock on the 6th), then confirm a *correct*
password still works after the 15-minute window.
