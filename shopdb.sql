-- ============================================================
--  shop_db.sql — Toàn bộ schema database cho shop PHP
--  Chạy trong phpMyAdmin hoặc MySQL CLI
-- ============================================================

CREATE DATABASE IF NOT EXISTS shop_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE shop_db;

-- ── Bảng roles ──
CREATE TABLE roles (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(50) NOT NULL UNIQUE  -- ADMIN, CLIENT, SELLER
);
INSERT INTO roles (name) VALUES ('ADMIN'), ('CLIENT'), ('SELLER');

-- ── Bảng users ──
CREATE TABLE users (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(100) NOT NULL,
    email        VARCHAR(150) NOT NULL UNIQUE,
    password     VARCHAR(255) NOT NULL,       -- bcrypt hash
    phoneNumber  VARCHAR(20),
    avatar       VARCHAR(500),
    role_id      INT NOT NULL DEFAULT 2,      -- 2 = CLIENT
    status       ENUM('ACTIVE','INACTIVE') DEFAULT 'ACTIVE',
    createdAt    DATETIME DEFAULT CURRENT_TIMESTAMP,
    updatedAt    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id)
);

-- Tài khoản admin mặc định (password: admin123)
INSERT INTO users (name, email, password, role_id) VALUES
('Admin', 'admin@pixcam.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1);

-- ── Bảng OTP ──
CREATE TABLE otps (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    email      VARCHAR(150) NOT NULL,
    code       VARCHAR(10)  NOT NULL,
    type       VARCHAR(50)  NOT NULL,   -- REGISTER, FORGOT_PASSWORD
    expiresAt  DATETIME     NOT NULL,
    createdAt  DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ── Bảng categories ──
CREATE TABLE categories (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    name             VARCHAR(100) NOT NULL,
    logo             VARCHAR(500),
    parentCategoryId INT,
    createdAt        DATETIME DEFAULT CURRENT_TIMESTAMP,
    updatedAt        DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parentCategoryId) REFERENCES categories(id) ON DELETE SET NULL
);

-- Danh mục mặc định
INSERT INTO categories (id, name, logo, parentCategoryId) VALUES
(1,  'Thời trang nam',  '👔', NULL),
(2,  'Thời trang nữ',  '👗', NULL),
(3,  'Phụ kiện',       '👜', NULL),
(4,  'Áo nam',          '👕', 1),
(5,  'Quần nam',        '👖', 1),
(6,  'Giày nam',        '👟', 1),
(7,  'Áo nữ',           '👚', 2),
(8,  'Váy nữ',          '👗', 2),
(9,  'Giày nữ',         '👠', 2),
(10, 'Túi xách',        '👜', 3),
(11, 'Mũ nón',          '🧢', 3),
(12, 'Trang sức',       '💍', 3),
(13, 'Sale',           '🏷️', NULL);

-- ── Bảng category_translations ──
CREATE TABLE category_translations (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    languageId  VARCHAR(10) NOT NULL,  -- vi, en
    name        VARCHAR(100) NOT NULL,
    createdAt   DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    UNIQUE KEY (category_id, languageId)
);

-- ── Bảng products ──
CREATE TABLE products (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(200) NOT NULL,
    basePrice    DECIMAL(12,2) NOT NULL DEFAULT 0,
    virtualPrice DECIMAL(12,2) NOT NULL DEFAULT 0,
    images       JSON,                  -- mảng URL ảnh
    publishedAt  DATETIME,
    createdAt    DATETIME DEFAULT CURRENT_TIMESTAMP,
    updatedAt    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deletedAt    DATETIME
);

-- ── Bảng product_categories (quan hệ nhiều-nhiều) ──
CREATE TABLE product_categories (
    product_id  INT NOT NULL,
    category_id INT NOT NULL,
    PRIMARY KEY (product_id, category_id),
    FOREIGN KEY (product_id)  REFERENCES products(id)   ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- ── Bảng product_translations ──
CREATE TABLE product_translations (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    product_id  INT NOT NULL,
    languageId  VARCHAR(10) NOT NULL,
    name        VARCHAR(200) NOT NULL,
    description TEXT,
    createdAt   DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY (product_id, languageId)
);

-- ── Bảng product_variants (màu sắc, kích thước...) ──
CREATE TABLE product_variants (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    name       VARCHAR(50) NOT NULL,   -- Màu sắc, Kích thước
    options    JSON NOT NULL,          -- ["Đỏ","Xanh","Đen"]
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- ── Bảng product_skus (biến thể cụ thể) ──
CREATE TABLE product_skus (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    value      VARCHAR(100) NOT NULL,  -- "Đỏ-M", "Xanh-L"
    price      DECIMAL(12,2) NOT NULL,
    stock      INT NOT NULL DEFAULT 0,
    image      VARCHAR(500),
    createdAt  DATETIME DEFAULT CURRENT_TIMESTAMP,
    updatedAt  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- ── Bảng cart ──
CREATE TABLE cart (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    user_id   INT NOT NULL,
    sku_id    INT NOT NULL,
    quantity  INT NOT NULL DEFAULT 1,
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY (user_id, sku_id),
    FOREIGN KEY (user_id) REFERENCES users(id)       ON DELETE CASCADE,
    FOREIGN KEY (sku_id)  REFERENCES product_skus(id) ON DELETE CASCADE
);

-- ── Bảng payments ──
CREATE TABLE payments (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    method    ENUM('COD','BANK') NOT NULL DEFAULT 'COD',
    status    ENUM('PENDING','PAID','FAILED') DEFAULT 'PENDING',
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ── Bảng orders ──
CREATE TABLE orders (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT NOT NULL,
    payment_id     INT NOT NULL,
    status         ENUM('PENDING_PAYMENT','PENDING_PICKUP','PENDING_DELIVERY','DELIVERED','RETURNED','CANCELLED')
                   DEFAULT 'PENDING_PAYMENT',
    receiver_name    VARCHAR(100),
    receiver_phone   VARCHAR(20),
    receiver_email   VARCHAR(150),
    receiver_address VARCHAR(300),
    receiver_note    TEXT,
    isCOD          TINYINT(1) DEFAULT 0,
    createdAt      DATETIME DEFAULT CURRENT_TIMESTAMP,
    updatedAt      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (payment_id) REFERENCES payments(id)
);

-- ── Bảng order_items ──
CREATE TABLE order_items (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    order_id     INT NOT NULL,
    product_id   INT NOT NULL,
    sku_id       INT NOT NULL,
    productName  VARCHAR(200) NOT NULL,  -- snapshot tên lúc đặt
    skuValue     VARCHAR(100) NOT NULL,  -- snapshot SKU
    skuPrice     DECIMAL(12,2) NOT NULL, -- snapshot giá
    image        VARCHAR(500),           -- snapshot ảnh
    quantity     INT NOT NULL DEFAULT 1,
    createdAt    DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id)   REFERENCES orders(id)      ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (sku_id)     REFERENCES product_skus(id)
);

-- ── Bảng reviews ──
CREATE TABLE reviews (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    product_id  INT NOT NULL,
    order_id    INT NOT NULL,
    rating      TINYINT NOT NULL DEFAULT 5,   -- 1-5 sao
    content     TEXT,
    updateCount INT DEFAULT 0,
    createdAt   DATETIME DEFAULT CURRENT_TIMESTAMP,
    updatedAt   DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY (user_id, product_id, order_id),
    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE CASCADE
);

-- ── Bảng review_medias ──
CREATE TABLE review_medias (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    review_id INT NOT NULL,
    url       VARCHAR(500) NOT NULL,
    type      ENUM('IMAGE','VIDEO') DEFAULT 'IMAGE',
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE
);

-- ── Bảng notifications ──
CREATE TABLE notifications (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    user_id   INT NOT NULL,
    title     VARCHAR(200) NOT NULL,
    content   TEXT,
    isRead    TINYINT(1) DEFAULT 0,
    type      VARCHAR(50),
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ── Bảng refresh_tokens ──
CREATE TABLE refresh_tokens (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    user_id   INT NOT NULL,
    token     VARCHAR(500) NOT NULL UNIQUE,
    expiresAt DATETIME NOT NULL,
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
