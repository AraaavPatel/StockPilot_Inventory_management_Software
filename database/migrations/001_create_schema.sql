-- =====================================================================
-- StockPilot Database Schema
-- QR / Barcode Based Inventory & Billing Platform for Kirana Stores
-- Engine: InnoDB | Charset: utf8mb4 | Normalized to 3NF
-- =====================================================================

CREATE DATABASE IF NOT EXISTS stockpilot CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE stockpilot;

SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- USERS  (Admin / Manager / Cashier)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS users;
CREATE TABLE users (
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
DROP TABLE IF EXISTS categories;
CREATE TABLE categories (
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
DROP TABLE IF EXISTS suppliers;
CREATE TABLE suppliers (
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
DROP TABLE IF EXISTS customers;
CREATE TABLE customers (
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
DROP TABLE IF EXISTS products;
CREATE TABLE products (
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
DROP TABLE IF EXISTS purchases;
CREATE TABLE purchases (
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
DROP TABLE IF EXISTS purchase_items;
CREATE TABLE purchase_items (
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
DROP TABLE IF EXISTS sales;
CREATE TABLE sales (
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
DROP TABLE IF EXISTS sale_items;
CREATE TABLE sale_items (
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
DROP TABLE IF EXISTS stock_adjustments;
CREATE TABLE stock_adjustments (
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
