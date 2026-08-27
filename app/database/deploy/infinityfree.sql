-- =====================================================================
-- StockPilot — Production database schema for InfinityFree (or any
-- shared MySQL/MariaDB host where you cannot run `CREATE DATABASE` or
-- choose your own DB name/user).
--
-- HOW TO USE:
--   1. In your InfinityFree control panel, create a MySQL database.
--      InfinityFree assigns the database name and username for you
--      (usually looking like if0_12345678_stockpilot) — you cannot
--      pick "stockpilot" yourself. That's fine: this file does NOT
--      contain CREATE DATABASE or USE statements, so it works inside
--      whichever database phpMyAdmin already has you connected to.
--   2. Open phpMyAdmin for that database (linked from the InfinityFree
--      control panel), go to the Import tab, choose this file, and
--      run it.
--   3. Every statement below is CREATE TABLE IF NOT EXISTS — safe to
--      re-run if the import is interrupted partway through, and safe
--      to run again later against a database that already has data
--      (it will NOT drop or touch existing tables/rows).
--
-- COMPATIBILITY NOTE: the CHECK constraints below (e.g. stock_qty >= 0)
-- require MySQL 8.0.16+ or MariaDB 10.2.1+ to actually be enforced.
-- InfinityFree's shared MySQL is MariaDB-based and recent enough as of
-- this writing, but if your import fails specifically on a CHECK (...)
-- line, that's why — remove just that clause and rely on the
-- application-level validation instead (already present in every
-- controller: see SECURITY_AUDIT.md). Everything else in this file is
-- plain ANSI-ish SQL with no version-specific syntax.
-- =====================================================================



SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- USERS  (Admin / Manager / Cashier)
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS users (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100)    NOT NULL,
    email           VARCHAR(150)    NOT NULL,
    phone           VARCHAR(20)     DEFAULT NULL,
    password_hash   VARCHAR(255)    NOT NULL,
    role            ENUM('admin','manager','cashier') NOT NULL DEFAULT 'cashier',
    status          ENUM('active','inactive') NOT NULL DEFAULT 'active',
    last_login_at   DATETIME        DEFAULT NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- CATEGORIES
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS categories (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100)    NOT NULL,
    description     VARCHAR(255)    DEFAULT NULL,
    status          ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_categories_name (name)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- SUPPLIERS
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS suppliers (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(150)    NOT NULL,
    contact_person  VARCHAR(100)    DEFAULT NULL,
    phone           VARCHAR(20)     DEFAULT NULL,
    email           VARCHAR(150)    DEFAULT NULL,
    address         VARCHAR(255)    DEFAULT NULL,
    gstin           VARCHAR(20)     DEFAULT NULL,
    status          ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- CUSTOMERS
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS customers (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(150)    NOT NULL DEFAULT 'Walk-in Customer',
    phone           VARCHAR(20)     DEFAULT NULL,
    email           VARCHAR(150)    DEFAULT NULL,
    address         VARCHAR(255)    DEFAULT NULL,
    loyalty_points  INT UNSIGNED    NOT NULL DEFAULT 0,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_customers_phone (phone)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- PRODUCTS
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS products (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id     INT UNSIGNED    NOT NULL,
    name            VARCHAR(150)    NOT NULL,
    sku             VARCHAR(60)     NOT NULL COMMENT 'Internal stock keeping unit',
    barcode         VARCHAR(60)     NOT NULL COMMENT 'QR / Barcode value scanned at POS',
    unit            VARCHAR(20)     NOT NULL DEFAULT 'pcs' COMMENT 'pcs, kg, ltr, box...',
    cost_price      DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
    selling_price   DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
    gst_percent     DECIMAL(5,2)    NOT NULL DEFAULT 0.00,
    stock_qty       INT             NOT NULL DEFAULT 0,
    low_stock_threshold INT         NOT NULL DEFAULT 5,
    image_path      VARCHAR(255)    DEFAULT NULL,
    status          ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_products_sku (sku),
    UNIQUE KEY uq_products_barcode (barcode),
    KEY idx_products_category (category_id),
    CONSTRAINT chk_products_prices CHECK (cost_price >= 0 AND selling_price >= 0),
    CONSTRAINT chk_products_stock CHECK (stock_qty >= 0),
    CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- PURCHASES  (stock-in header, linked to supplier)
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS purchases (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    supplier_id     INT UNSIGNED    NOT NULL,
    user_id         INT UNSIGNED    NOT NULL COMMENT 'who recorded the purchase',
    invoice_no      VARCHAR(60)     DEFAULT NULL COMMENT 'supplier invoice reference',
    total_amount    DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    purchase_date   DATE            NOT NULL,
    notes           VARCHAR(255)    DEFAULT NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_purchases_supplier (supplier_id),
    KEY idx_purchases_user (user_id),
    CONSTRAINT chk_purchases_total CHECK (total_amount >= 0),
    CONSTRAINT fk_purchases_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_purchases_user FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- PURCHASE_ITEMS (line items -> products; feeds stock-in)
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS purchase_items (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_id     INT UNSIGNED    NOT NULL,
    product_id      INT UNSIGNED    NOT NULL,
    quantity        INT             NOT NULL,
    unit_cost       DECIMAL(10,2)   NOT NULL,
    line_total      DECIMAL(12,2)   NOT NULL,
    KEY idx_purchase_items_purchase (purchase_id),
    KEY idx_purchase_items_product (product_id),
    CONSTRAINT chk_purchase_items_qty CHECK (quantity > 0),
    CONSTRAINT fk_purchase_items_purchase FOREIGN KEY (purchase_id) REFERENCES purchases(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_purchase_items_product FOREIGN KEY (product_id) REFERENCES products(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- SALES  (POS billing header)
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS sales (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_no      VARCHAR(30)     NOT NULL COMMENT 'e.g. SP-2026-000001',
    customer_id     INT UNSIGNED    DEFAULT NULL,
    user_id         INT UNSIGNED    NOT NULL COMMENT 'cashier who billed',
    subtotal        DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    gst_amount      DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    discount_amount DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    total_amount    DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    payment_method  ENUM('cash','card','upi','other') NOT NULL DEFAULT 'cash',
    payment_status  ENUM('paid','due') NOT NULL DEFAULT 'paid',
    sale_date       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_sales_customer (customer_id),
    KEY idx_sales_user (user_id),
    KEY idx_sales_date (sale_date),
    UNIQUE KEY uq_sales_invoice (invoice_no),
    CONSTRAINT chk_sales_total CHECK (total_amount >= 0),
    CONSTRAINT fk_sales_customer FOREIGN KEY (customer_id) REFERENCES customers(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_sales_user FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- SALE_ITEMS (line items -> products; feeds stock-out)
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS sale_items (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sale_id         INT UNSIGNED    NOT NULL,
    product_id      INT UNSIGNED    NOT NULL,
    quantity        INT             NOT NULL,
    unit_price      DECIMAL(10,2)   NOT NULL,
    gst_percent     DECIMAL(5,2)    NOT NULL DEFAULT 0.00,
    line_total      DECIMAL(12,2)   NOT NULL,
    KEY idx_sale_items_sale (sale_id),
    KEY idx_sale_items_product (product_id),
    CONSTRAINT chk_sale_items_qty CHECK (quantity > 0),
    CONSTRAINT fk_sale_items_sale FOREIGN KEY (sale_id) REFERENCES sales(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_sale_items_product FOREIGN KEY (product_id) REFERENCES products(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- STOCK_ADJUSTMENTS (manual correction log — damage, theft, recount)
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS stock_adjustments (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id      INT UNSIGNED    NOT NULL,
    user_id         INT UNSIGNED    NOT NULL COMMENT 'who made the adjustment',
    adjustment_type ENUM('add','remove') NOT NULL,
    quantity        INT             NOT NULL,
    reason          VARCHAR(255)    NOT NULL COMMENT 'damage, theft, recount, expiry...',
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_stock_adj_product (product_id),
    KEY idx_stock_adj_user (user_id),
    CONSTRAINT chk_stock_adj_qty CHECK (quantity > 0),
    CONSTRAINT fk_stock_adj_product FOREIGN KEY (product_id) REFERENCES products(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_stock_adj_user FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- Login throttling, audit logging, and notification tables
-- ---------------------------------------------------------------------
-- Login throttling
-- Non-destructive: safe to run against an existing production database.
-- Tracks failed login attempts so AuthController can lock out an
-- email+IP combination after repeated failures (see App\Core\LoginThrottle).
-- =====================================================================

CREATE TABLE IF NOT EXISTS login_attempts (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email           VARCHAR(150)    NOT NULL,
    ip_address      VARCHAR(45)     NOT NULL,
    succeeded       TINYINT(1)      NOT NULL DEFAULT 0,
    attempted_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_login_attempts_lookup (email, ip_address, attempted_at)
) ENGINE=InnoDB;

-- Audit logging + notification settings
-- Non-destructive: CREATE TABLE IF NOT EXISTS only, safe to run against
-- an existing production database.
-- =====================================================================

CREATE TABLE IF NOT EXISTS audit_logs (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED    DEFAULT NULL COMMENT 'NULL for pre-login events e.g. failed login',
    actor_name      VARCHAR(150)    DEFAULT NULL COMMENT 'snapshot of the user name at the time, survives user deletion',
    action          VARCHAR(60)     NOT NULL COMMENT 'e.g. LOGIN_SUCCESS, PRODUCT_UPDATED, STOCK_ADJUSTED',
    module          VARCHAR(60)     NOT NULL,
    entity_type     VARCHAR(60)     DEFAULT NULL,
    entity_id       INT UNSIGNED    DEFAULT NULL,
    old_values      TEXT            DEFAULT NULL COMMENT 'JSON snapshot before the change',
    new_values      TEXT            DEFAULT NULL COMMENT 'JSON snapshot after the change',
    ip_address      VARCHAR(45)     DEFAULT NULL,
    user_agent      VARCHAR(255)    DEFAULT NULL,
    request_id      VARCHAR(40)     DEFAULT NULL,
    prev_hash       CHAR(64)        DEFAULT NULL COMMENT 'record_hash of the previous row, forms a hash chain',
    record_hash     CHAR(64)        NOT NULL COMMENT 'sha256 of this row + prev_hash — detects direct DB tampering',
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_audit_user (user_id),
    KEY idx_audit_created (created_at),
    KEY idx_audit_action (action)
) ENGINE=InnoDB;

-- No UPDATE/DELETE routes exist anywhere in the app for this table
-- (see App\Audit\AuditLogger — it only ever INSERTs) and the account
-- the application connects as should itself not have DROP/ALTER rights
-- on this table in a hardened production setup (documented in
-- DEPLOYMENT_INFINITYFREE.md; InfinityFree's shared-DB-user model can't
-- fully enforce this at the grant level, which is why the hash chain
-- exists as a second layer of tamper *detection* even though it can't
-- prevent a determined DB-level attacker).

CREATE TABLE IF NOT EXISTS notification_log (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event           VARCHAR(60)     NOT NULL,
    recipient       VARCHAR(150)    NOT NULL,
    success         TINYINT(1)      NOT NULL DEFAULT 0,
    error_message   VARCHAR(255)    DEFAULT NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_notif_created (created_at)
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;
