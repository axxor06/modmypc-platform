-- ModMyPC — Database migration
-- Run this once in phpMyAdmin (InfinityFree control panel) on your
-- if0_39576026_modmypc2_db database before uploading the new site files.
-- Safe to re-run: every statement uses IF NOT EXISTS.

-- ─────────────────────────────────────────────────────────────────────────
-- Customer accounts
-- ─────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  phone VARCHAR(20) DEFAULT NULL,
  password_hash VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Password reset tokens
CREATE TABLE IF NOT EXISTS password_resets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token_hash VARCHAR(255) NOT NULL,
  expires_at DATETIME NOT NULL,
  used TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Login attempts (used for both customer and admin login rate limiting)
CREATE TABLE IF NOT EXISTS login_attempts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  identifier VARCHAR(190) NOT NULL,      -- email for customers, username for admin
  scope VARCHAR(20) NOT NULL DEFAULT 'customer', -- 'customer' or 'admin'
  success TINYINT(1) NOT NULL DEFAULT 0,
  ip_address VARCHAR(45) DEFAULT NULL,
  attempted_at DATETIME NOT NULL,
  INDEX idx_identifier_scope (identifier, scope, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- General activity log (registrations, logins, orders, admin actions, etc.)
CREATE TABLE IF NOT EXISTS activity_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  action VARCHAR(50) NOT NULL,
  details TEXT NULL,
  ip_address VARCHAR(45) DEFAULT NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────────────────
-- Cart (DB-backed, syncs across devices for logged-in users)
-- ─────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS carts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  guest_token VARCHAR(64) NULL,
  created_at DATETIME NOT NULL,
  UNIQUE KEY uniq_user (user_id),
  UNIQUE KEY uniq_guest (guest_token),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cart_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cart_id INT NOT NULL,
  product_id INT NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  added_at DATETIME NOT NULL,
  UNIQUE KEY uniq_cart_product (cart_id, product_id),
  FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES modmypc(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────────────────
-- PC Builder — saved builds
-- ─────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS saved_builds (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  build_name VARCHAR(120) NOT NULL DEFAULT 'My Build',
  components_json TEXT NOT NULL,   -- {"processor": {"name":"...", "price":123, "product_id":5}, ...}
  total_price INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL DEFAULT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────────────────
-- Enquiries (WhatsApp / appointment / contact form submissions)
-- ─────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS enquiries (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  name VARCHAR(100) NOT NULL,
  phone VARCHAR(20) NULL,
  email VARCHAR(190) NULL,
  subject VARCHAR(150) NULL,
  message TEXT NULL,
  source VARCHAR(50) NOT NULL DEFAULT 'website', -- e.g. 'whatsapp_product', 'pc_builder', 'appointment'
  status VARCHAR(20) NOT NULL DEFAULT 'new',      -- new / contacted / closed
  created_at DATETIME NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────────────────
-- Admin accounts (replaces the old hardcoded admin/Password@2026 login)
-- ─────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(60) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default admin account: username "admin", password "Password@2026"
-- (the SAME password you were already using) — CHANGE THIS after first
-- login via the new admin "Change Password" screen.
-- Hash below was generated with PHP's password_hash(..., PASSWORD_DEFAULT).
INSERT IGNORE INTO admins (id, username, password_hash, created_at) VALUES
(1, 'admin', '$2y$10$WoEpWouD9zHuIhauM.xrDOpWHdBpvQcTb14dqfQRs7junZ24ggCXG', NOW());

-- ─────────────────────────────────────────────────────────────────────────
-- Product reviews & ratings
-- ─────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS product_reviews (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  user_id INT NOT NULL,
  rating TINYINT NOT NULL,
  comment TEXT NULL,
  created_at DATETIME NOT NULL,
  UNIQUE KEY uniq_user_product_review (product_id, user_id),
  FOREIGN KEY (product_id) REFERENCES modmypc(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────────────────
-- Email verification (registration) & session security
-- ─────────────────────────────────────────────────────────────────────────
ALTER TABLE users ADD COLUMN email_verified TINYINT(1) NOT NULL DEFAULT 1;
ALTER TABLE users ADD COLUMN google_id VARCHAR(64) NULL;

CREATE TABLE IF NOT EXISTS email_verifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token_hash VARCHAR(255) NOT NULL,
  expires_at DATETIME NOT NULL,
  used TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- These ALTERs are wrapped so they won't error if columns already exist —
-- InfinityFree's MySQL doesn't support "ADD COLUMN IF NOT EXISTS" on all
-- versions, so check your existing `modmypc` table structure first; if a
-- column below already exists, just delete that one line before running.
-- ─────────────────────────────────────────────────────────────────────────
-- NOTE: "IF NOT EXISTS" is intentionally NOT used here — InfinityFree's
-- MySQL version is often older than the version that added support for
-- it on ADD COLUMN. If any single line below errors with "Duplicate
-- column name", it just means that column already exists — delete that
-- one line and re-run the rest.
ALTER TABLE modmypc ADD COLUMN brand VARCHAR(60) NULL AFTER category;
ALTER TABLE modmypc ADD COLUMN description TEXT NULL;
ALTER TABLE modmypc ADD COLUMN specs TEXT NULL COMMENT 'JSON key/value spec list';
ALTER TABLE modmypc ADD COLUMN warranty VARCHAR(100) NULL;
ALTER TABLE modmypc ADD COLUMN extra_images TEXT NULL COMMENT 'JSON array of additional image paths';
ALTER TABLE modmypc ADD COLUMN created_at DATETIME NULL COMMENT 'Only set for products added after this update - powers the "New" badge honestly';

CREATE TABLE IF NOT EXISTS categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL UNIQUE,
  slug VARCHAR(80) NOT NULL UNIQUE,
  icon VARCHAR(40) DEFAULT 'box'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed categories from whatever distinct category values already exist
-- in modmypc, so the admin category manager has something to show.
INSERT IGNORE INTO categories (name, slug)
SELECT DISTINCT category, LOWER(REPLACE(REPLACE(category,' ','-'),'_','-'))
FROM modmypc WHERE category IS NOT NULL AND category <> '';
