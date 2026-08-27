USE stockpilot;

-- Default users (password for all: "password123")
-- Hash generated with PHP password_hash('password123', PASSWORD_BCRYPT)
INSERT INTO users (name, email, phone, password_hash, role, status) VALUES
('Sarthak Pandya', 'admin@stockpilot.test', '9900000001', '$2y$10$wduinXhYvEPABVI9PDNAcuBprByuAdCxvP9lp8jiyGnsqESBhS0iW', 'admin', 'active'),
('Arav Bhambhroliya', 'manager@stockpilot.test', '9900000002', '$2y$10$wduinXhYvEPABVI9PDNAcuBprByuAdCxvP9lp8jiyGnsqESBhS0iW', 'manager', 'active'),
('Cashier One', 'cashier@stockpilot.test', '9900000003', '$2y$10$wduinXhYvEPABVI9PDNAcuBprByuAdCxvP9lp8jiyGnsqESBhS0iW', 'cashier', 'active');

INSERT INTO categories (name, description) VALUES
('Groceries', 'Staple food items'),
('Beverages', 'Soft drinks, juices, water'),
('Snacks', 'Packaged snacks and namkeen'),
('Personal Care', 'Soap, shampoo, toiletries'),
('Household', 'Cleaning and household supplies');

INSERT INTO suppliers (name, contact_person, phone, email, address, gstin) VALUES
('Patel Wholesale Distributors', 'Ramesh Patel', '9812345670', 'ramesh@patelwholesale.in', 'APMC Market, Ahmedabad', '24AAAAA0000A1Z5'),
('Gujarat FMCG Traders', 'Kiran Shah', '9812345671', 'kiran@gujaratfmcg.in', 'Naroda Industrial Estate, Ahmedabad', '24BBBBB1111B1Z6');

INSERT INTO customers (name, phone, email) VALUES
('Walk-in Customer', NULL, NULL),
('Ramila Ben', '9898000001', NULL),
('Suresh Kumar', '9898000002', 'suresh@example.com');

INSERT INTO products (category_id, name, sku, barcode, unit, cost_price, selling_price, gst_percent, stock_qty, low_stock_threshold) VALUES
(1, 'Tata Salt 1kg', 'GRO-SALT-1KG', '8901030702057', 'pcs', 20.00, 25.00, 5.00, 100, 10),
(1, 'Aashirvaad Atta 5kg', 'GRO-ATTA-5KG', '8901030864496', 'pcs', 210.00, 245.00, 5.00, 40, 5),
(2, 'Coca-Cola 750ml', 'BEV-COKE-750', '8901030001234', 'pcs', 30.00, 40.00, 12.00, 60, 12),
(2, 'Bisleri Water 1L', 'BEV-WATER-1L', '8901030005678', 'pcs', 12.00, 20.00, 12.00, 150, 20),
(3, 'Lays Classic 52g', 'SNK-LAYS-52G', '8901030009999', 'pcs', 15.00, 20.00, 12.00, 80, 10),
(4, 'Lifebuoy Soap 100g', 'PC-LIFEBUOY-100', '8901030011111', 'pcs', 22.00, 30.00, 18.00, 90, 10),
(5, 'Vim Dishwash Bar', 'HH-VIM-BAR', '8901030022222', 'pcs', 8.00, 12.00, 18.00, 120, 15);
