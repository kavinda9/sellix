-- ============================================================
--  Marketplace Database
--  Compatible with: MySQL 5.7+ / MariaDB (XAMPP)
--  How to use:
--    1. Open phpMyAdmin (http://localhost/phpmyadmin)
--    2. Click "New" and create a database named: marketplace_db
--    3. Select that database, go to the "SQL" tab
--    4. Paste this entire file and click "Go"
-- ============================================================

CREATE DATABASE IF NOT EXISTS marketplace_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE marketplace_db;

-- ============================================================
-- 1. USERS  (customers + admins share this table)
-- ============================================================
CREATE TABLE users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100)        NOT NULL,
    email         VARCHAR(150)        NOT NULL UNIQUE,
    password      VARCHAR(255)        NOT NULL,          -- bcrypt hash
    role          ENUM('customer','admin') NOT NULL DEFAULT 'customer',
    phone         VARCHAR(20)         DEFAULT NULL,
    avatar        VARCHAR(255)        DEFAULT NULL,      -- filename in assets/images/
    is_active     TINYINT(1)          NOT NULL DEFAULT 1,
    created_at    TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 2. PASSWORD RESET TOKENS
-- ============================================================
CREATE TABLE password_resets (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email         VARCHAR(150)        NOT NULL,
    token         VARCHAR(64)         NOT NULL UNIQUE,   -- sha256 hex token
    expires_at    DATETIME            NOT NULL,          -- token valid for 1 hour
    used          TINYINT(1)          NOT NULL DEFAULT 0,
    created_at    TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 3. CATEGORIES
-- ============================================================
CREATE TABLE categories (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100)        NOT NULL,
    slug          VARCHAR(110)        NOT NULL UNIQUE,
    image         VARCHAR(255)        DEFAULT NULL,
    parent_id     INT UNSIGNED        DEFAULT NULL,      -- supports sub-categories
    sort_order    INT                 NOT NULL DEFAULT 0,
    created_at    TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 4. PRODUCTS
-- ============================================================
CREATE TABLE products (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id   INT UNSIGNED        NOT NULL,
    name          VARCHAR(255)        NOT NULL,
    slug          VARCHAR(270)        NOT NULL UNIQUE,
    description   TEXT                DEFAULT NULL,
    price         DECIMAL(10,2)       NOT NULL,
    sale_price    DECIMAL(10,2)       DEFAULT NULL,      -- NULL means no sale
    stock         INT UNSIGNED        NOT NULL DEFAULT 0,
    image         VARCHAR(255)        DEFAULT NULL,      -- main image filename
    is_active     TINYINT(1)          NOT NULL DEFAULT 1,
    is_featured   TINYINT(1)          NOT NULL DEFAULT 0,
    created_at    TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
    INDEX idx_category  (category_id),
    INDEX idx_active    (is_active),
    INDEX idx_featured  (is_featured)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 5. PRODUCT IMAGES  (multiple images per product)
-- ============================================================
CREATE TABLE product_images (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id    INT UNSIGNED        NOT NULL,
    filename      VARCHAR(255)        NOT NULL,
    sort_order    INT                 NOT NULL DEFAULT 0,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 6. ORDERS
-- ============================================================
CREATE TABLE orders (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id       INT UNSIGNED        NOT NULL,
    status        ENUM('pending','processing','shipped','delivered','cancelled')
                                      NOT NULL DEFAULT 'pending',
    total_amount  DECIMAL(10,2)       NOT NULL,
    -- Shipping snapshot (stored so address changes don't break history)
    ship_name     VARCHAR(100)        NOT NULL,
    ship_address  VARCHAR(255)        NOT NULL,
    ship_city     VARCHAR(100)        NOT NULL,
    ship_zip      VARCHAR(20)         NOT NULL,
    ship_phone    VARCHAR(20)         NOT NULL,
    notes         TEXT                DEFAULT NULL,
    created_at    TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_user   (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 7. ORDER ITEMS
-- ============================================================
CREATE TABLE order_items (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id      INT UNSIGNED        NOT NULL,
    product_id    INT UNSIGNED        NOT NULL,
    product_name  VARCHAR(255)        NOT NULL,   -- snapshot at time of order
    product_image VARCHAR(255)        DEFAULT NULL,
    quantity      INT UNSIGNED        NOT NULL DEFAULT 1,
    unit_price    DECIMAL(10,2)       NOT NULL,   -- price paid (after any sale)
    FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 8. CART  (persistent, per user — also used for guests via session)
-- ============================================================
CREATE TABLE cart (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id       INT UNSIGNED        NOT NULL,
    product_id    INT UNSIGNED        NOT NULL,
    quantity      INT UNSIGNED        NOT NULL DEFAULT 1,
    added_at      TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_product (user_id, product_id),
    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 9. WISHLIST
-- ============================================================
CREATE TABLE wishlist (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id       INT UNSIGNED        NOT NULL,
    product_id    INT UNSIGNED        NOT NULL,
    added_at      TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_wish (user_id, product_id),
    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 10. REVIEWS
-- ============================================================
CREATE TABLE reviews (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id    INT UNSIGNED        NOT NULL,
    user_id       INT UNSIGNED        NOT NULL,
    rating        TINYINT UNSIGNED    NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment       TEXT                DEFAULT NULL,
    is_approved   TINYINT(1)          NOT NULL DEFAULT 0,  -- admin approves first
    created_at    TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_review (product_id, user_id),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 11. SITE SETTINGS  (key-value store for admin config)
-- ============================================================
CREATE TABLE settings (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key   VARCHAR(100)        NOT NULL UNIQUE,
    setting_value TEXT                DEFAULT NULL,
    updated_at    TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 12. BANNERS  (homepage hero banners, managed by admin)
-- ============================================================
CREATE TABLE banners (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title         VARCHAR(200)        NOT NULL,
    subtitle      VARCHAR(255)        DEFAULT NULL,
    image         VARCHAR(255)        NOT NULL,
    link_url      VARCHAR(255)        DEFAULT NULL,
    is_active     TINYINT(1)          NOT NULL DEFAULT 1,
    sort_order    INT                 NOT NULL DEFAULT 0,
    created_at    TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SEED DATA
-- ============================================================

-- Default admin account
-- Password: Admin@1234  (bcrypt, change this after first login!)
INSERT INTO users (name, email, password, role) VALUES
('Admin', 'admin@marketplace.com',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Sample categories
INSERT INTO categories (name, slug, sort_order) VALUES
('Electronics',     'electronics',     1),
('Clothing',        'clothing',        2),
('Home & Garden',   'home-garden',     3),
('Sports',          'sports',          4),
('Books',           'books',           5),
('Beauty',          'beauty',          6),
('Toys',            'toys',            7),
('Automotive',      'automotive',      8);

-- Sub-category example
INSERT INTO categories (name, slug, parent_id, sort_order) VALUES
('Mobile Phones',   'mobile-phones',   1, 1),
('Laptops',         'laptops',         1, 2),
('Men Clothing',    'men-clothing',    2, 1),
('Women Clothing',  'women-clothing',  2, 2);

-- Sample products
INSERT INTO products (category_id, name, slug, description, price, sale_price, stock, is_featured) VALUES
(1, 'Wireless Bluetooth Headphones',
   'wireless-bluetooth-headphones',
   'Premium sound quality with noise cancellation and 30-hour battery life.',
   4999.00, 3499.00, 50, 1),

(1, 'Smart Watch Pro',
   'smart-watch-pro',
   'Fitness tracker with heart rate monitor, GPS, and 7-day battery.',
   8999.00, NULL, 30, 1),

(2, 'Classic Cotton T-Shirt',
   'classic-cotton-t-shirt',
   '100% organic cotton, available in 12 colours. Soft and breathable.',
   1299.00, 999.00, 200, 0),

(3, 'Ceramic Plant Pot Set',
   'ceramic-plant-pot-set',
   'Set of 3 handcrafted ceramic pots, perfect for indoor plants.',
   2499.00, NULL, 80, 1),

(4, 'Yoga Mat Premium',
   'yoga-mat-premium',
   'Non-slip, eco-friendly 6mm thick yoga mat with carry strap.',
   3200.00, 2800.00, 120, 0);

-- Default site settings
INSERT INTO settings (setting_key, setting_value) VALUES
('site_name',           'MyShop'),
('site_tagline',        'Find everything you love'),
('currency_symbol',     'Rs.'),
('currency_code',       'LKR'),
('contact_email',       'support@myshop.com'),
('contact_phone',       '+94 11 000 0000'),
('orders_per_page',     '20'),
('products_per_page',   '24'),
('low_stock_threshold', '5'),
('maintenance_mode',    '0');

-- Sample banner
INSERT INTO banners (title, subtitle, image, link_url, sort_order) VALUES
('Welcome to MyShop', 'Find the best deals every day', 'banner1.jpg', '/pages/shop.php', 1),
('New Arrivals',      'Fresh styles just landed',       'banner2.jpg', '/pages/shop.php?sort=new', 2);

-- ============================================================
--  Admin login → admin@marketplace.com / Admin@1234
-- ============================================================