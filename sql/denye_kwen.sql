-- ============================================================
-- DENYE KWEN - Sistèm Jesyon Biznis v2.0
-- Baz Done MySQL - Schema + Done Demo
-- Charset: utf8mb4_unicode_ci
-- ============================================================

CREATE DATABASE IF NOT EXISTS denye_kwen
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE denye_kwen;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- 1. TAB: users (itilizatè)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS users;
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  non_konple VARCHAR(120) NOT NULL,
  username VARCHAR(60) NOT NULL UNIQUE,
  email VARCHAR(120) DEFAULT NULL,
  telefon VARCHAR(30) DEFAULT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('Admin','Kesye','Odite') NOT NULL DEFAULT 'Kesye',
  aktif TINYINT(1) NOT NULL DEFAULT 1,
  dernye_koneksyon DATETIME DEFAULT NULL,
  dat_kreye DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 2. TAB: kategori
-- ------------------------------------------------------------
DROP TABLE IF EXISTS kategori;
CREATE TABLE kategori (
  id INT AUTO_INCREMENT PRIMARY KEY,
  non VARCHAR(80) NOT NULL,
  icon VARCHAR(10) DEFAULT '📦',
  aktif TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 3. TAB: products (pwodwi)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS products;
CREATE TABLE products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kod VARCHAR(30) NOT NULL UNIQUE,
  barcode VARCHAR(60) DEFAULT NULL UNIQUE,
  non VARCHAR(150) NOT NULL,
  deskripsyon TEXT DEFAULT NULL,
  kategori_id INT DEFAULT NULL,
  icon VARCHAR(10) DEFAULT '📦',
  pri_dacha DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  pri DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  stok INT NOT NULL DEFAULT 0,
  min_stok INT NOT NULL DEFAULT 5,
  aktif TINYINT(1) NOT NULL DEFAULT 1,
  dat_kreye DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  dat_modifye DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_prod_kat FOREIGN KEY (kategori_id) REFERENCES kategori(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 4. TAB: clients (kliyan)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS clients;
CREATE TABLE clients (
  id INT AUTO_INCREMENT PRIMARY KEY,
  non VARCHAR(120) NOT NULL,
  telefon VARCHAR(30) DEFAULT NULL,
  email VARCHAR(120) DEFAULT NULL,
  adres VARCHAR(200) DEFAULT NULL,
  vil VARCHAR(80) DEFAULT NULL,
  tip ENUM('regilye','vip','gwo') NOT NULL DEFAULT 'regilye',
  solde_det DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  pwen_fidelite INT NOT NULL DEFAULT 0,
  aktif TINYINT(1) NOT NULL DEFAULT 1,
  dat_kreye DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 5. TAB: suppliers (founisè)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS suppliers;
CREATE TABLE suppliers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  non VARCHAR(120) NOT NULL,
  konpayi VARCHAR(150) DEFAULT NULL,
  telefon VARCHAR(30) DEFAULT NULL,
  email VARCHAR(120) DEFAULT NULL,
  adres VARCHAR(200) DEFAULT NULL,
  nif VARCHAR(40) DEFAULT NULL,
  solde DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  aktif TINYINT(1) NOT NULL DEFAULT 1,
  dat_kreye DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 6. TAB: expenses (depans)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS expenses;
CREATE TABLE expenses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kategori VARCHAR(60) NOT NULL,
  deskripsyon VARCHAR(255) DEFAULT NULL,
  montan DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  mwayen_peman VARCHAR(30) NOT NULL DEFAULT 'kach',
  dat DATE NOT NULL,
  user_id INT DEFAULT NULL,
  dat_kreye DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_exp_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 7. TAB: sales (vant)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS sales;
CREATE TABLE sales (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nimewo_fakti VARCHAR(30) NOT NULL UNIQUE,
  client_id INT DEFAULT NULL,
  client_non VARCHAR(120) DEFAULT 'Kliyan Pasaj',
  user_id INT DEFAULT NULL,
  user_non VARCHAR(120) DEFAULT NULL,
  sou_total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  rabe DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  taks DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  kob_resevwa DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  monen_rann DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  mwayen_peman ENUM('kach','moncash','natcash','kat','kredi') NOT NULL DEFAULT 'kach',
  estati ENUM('konplete','anile','retounen') NOT NULL DEFAULT 'konplete',
  dat DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_sale_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL,
  CONSTRAINT fk_sale_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 8. TAB: sale_items (detay vant)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS sale_items;
CREATE TABLE sale_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sale_id INT NOT NULL,
  product_id INT DEFAULT NULL,
  product_non VARCHAR(150) NOT NULL,
  kantite INT NOT NULL DEFAULT 1,
  pri_inite DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  CONSTRAINT fk_item_sale FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
  CONSTRAINT fk_item_prod FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 9. TAB: activity_logs (jounal aktivite)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS activity_logs;
CREATE TABLE activity_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT DEFAULT NULL,
  user_non VARCHAR(120) DEFAULT NULL,
  aksyon VARCHAR(60) NOT NULL,
  modil VARCHAR(60) NOT NULL,
  deskripsyon VARCHAR(255) DEFAULT NULL,
  ip_address VARCHAR(50) DEFAULT NULL,
  dat DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_log_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 10b. TAB: services (sèvis)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS services;
CREATE TABLE services (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nimewo_resi VARCHAR(30) NOT NULL UNIQUE,
  deskripsyon VARCHAR(255) NOT NULL,
  pri DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  rabe DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  moun_bay_sevis VARCHAR(120) NOT NULL,
  jou VARCHAR(20) DEFAULT NULL,
  dat DATE NOT NULL,
  mwayen_peman ENUM('kach','moncash','natcash','kat') NOT NULL DEFAULT 'kach',
  user_id INT DEFAULT NULL,
  user_non VARCHAR(120) DEFAULT NULL,
  dat_kreye DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_svc_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 10. TAB: settings (paramèt)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS settings;
CREATE TABLE settings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  business_name VARCHAR(150) NOT NULL DEFAULT 'DENYE KWEN',
  phone VARCHAR(30) DEFAULT NULL,
  email VARCHAR(120) DEFAULT NULL,
  address VARCHAR(200) DEFAULT NULL,
  nif VARCHAR(40) DEFAULT NULL,
  currency VARCHAR(10) NOT NULL DEFAULT 'HTG',
  tax_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  receipt_footer VARCHAR(200) DEFAULT 'Mèsi pou acha ou! Tounen ankò!',
  dat_modifye DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- DONE DEMO
-- ============================================================

-- Itilizatè (modpas: admin123 / kesye123 / odite123)
INSERT INTO users (non_konple, username, email, telefon, password, role) VALUES
('Jean-Robert Baptiste', 'admin',  'admin@denyekwen.ht',  '+509 3711-0001', '$2y$10$YM4ru824n5fxFP9On7kijObf87XSSHlqoRFFlXi4rf5jMAv7.AIYa', 'Admin'),
('Marie-Lourdes Pierre', 'kesye',  'kesye@denyekwen.ht',  '+509 3711-0002', '$2y$10$ox3bxxtVkTJol/D8fM8FQegA/.j2A1f1yG.ESNDaBDlm7apYI4Pk.', 'Kesye'),
('Stanley Joseph',       'kesye2', 'kesye2@denyekwen.ht', '+509 3711-0003', '$2y$10$ox3bxxtVkTJol/D8fM8FQegA/.j2A1f1yG.ESNDaBDlm7apYI4Pk.', 'Kesye'),
('Fabiola Desir',        'odite',  'odite@denyekwen.ht',  '+509 3711-0004', '$2y$10$YhDQIhUpey1GMtCLekXv9.uvm2ERnyUwuR4i6foI8Enmq5MddKdCu', 'Odite');

-- Kategori
INSERT INTO kategori (non, icon) VALUES
('Manje', '🍽️'),
('Bwason', '🥤'),
('Menaj', '🧼'),
('Enèji', '⚡'),
('Lòt', '📦');

-- Pwodwi demo
INSERT INTO products (kod, barcode, non, deskripsyon, kategori_id, icon, pri_dacha, pri, stok, min_stok) VALUES
('PRD-001', '7401001000011', 'Diri Tchako 5lb',        'Sak diri 5 liv',              1, '🍚', 350.00, 500.00, 45, 10),
('PRD-002', '7401001000028', 'Lwil Mazola 1L',         'Boutey lwil kwit manje 1 lit', 1, '🛢️', 400.00, 550.00, 30, 8),
('PRD-003', '7401001000035', 'Sik Blan 1lb',           'Sik rafine 1 liv',            1, '🍬', 60.00, 100.00, 80, 15),
('PRD-004', '7401001000042', 'Espageti La Famosa',     'Pake espageti 400g',          1, '🍝', 55.00, 90.00, 60, 12),
('PRD-005', '7401001000059', 'Sadin Brunswick',        'Bwat sadin nan lwil',         1, '🐟', 90.00, 150.00, 40, 10),
('PRD-006', '7401002000010', 'Kola Couronne 355ml',    'Bwason gazez sitwon',         2, '🥤', 35.00, 60.00, 120, 24),
('PRD-007', '7401002000027', 'Ji Tampico 1L',          'Ji sitris 1 lit',             2, '🧃', 80.00, 125.00, 50, 12),
('PRD-008', '7401002000034', 'Dlo Culligan 5gal',      'Bokit dlo trete 5 galon',     2, '💧', 100.00, 175.00, 25, 6),
('PRD-009', '7401003000019', 'Savon Lave Fab',         'Savon poud 500g',             3, '🧼', 75.00, 125.00, 35, 10),
('PRD-010', '7401003000026', 'Klowòks 1L',             'Dezenfektan 1 lit',           3, '🧴', 60.00, 100.00, 3, 8),
('PRD-011', '7401004000018', 'Bouji Pak 6',            'Pak 6 bouji blan',            4, '🕯️', 50.00, 85.00, 0, 10),
('PRD-012', '7401004000025', 'Pil Duracell AA',        'Pak 2 pil AA',                4, '🔋', 120.00, 200.00, 18, 6);

-- Kliyan demo
INSERT INTO clients (non, telefon, email, adres, vil, tip, pwen_fidelite) VALUES
('Roseline Augustin', '+509 3845-1122', 'roseline.a@gmail.com', '12, Rue Capois',    'Pòtoprens', 'vip',     150),
('Emmanuel Charles',  '+509 4290-3344', 'emcharles@yahoo.com',  '45, Delmas 33',     'Delmas',    'regilye', 40),
('Nadège François',   '+509 3156-5566', NULL,                   '8, Route de Frères','Petyonvil', 'gwo',     320);

-- Founisè demo
INSERT INTO suppliers (non, konpayi, telefon, email, adres, nif) VALUES
('Patrick Moïse',   'Distribisyon Karayib S.A.', '+509 2812-7788', 'ventes@distkaraib.ht', 'Route Nationale #1, Pòtoprens', '003-456-789-1'),
('Guerline Estimé', 'Pwovizyon Plus',            '+509 3467-8899', 'info@pwovizyonplus.ht', 'Delmas 75, Pòtoprens',          '004-567-890-2');

-- Paramèt default
INSERT INTO settings (business_name, phone, email, address, nif, currency, tax_rate, receipt_footer) VALUES
('DENYE KWEN', '+509 3711-0000', 'kontak@denyekwen.ht', 'Anba Lavil, Pòtoprens, Ayiti', '001-234-567-8', 'HTG', 0.00, 'Mèsi pou acha ou! Tounen ankò!');

-- Kèk depans demo
INSERT INTO expenses (kategori, deskripsyon, montan, mwayen_peman, dat, user_id) VALUES
('Lwaye',      'Lwaye mwa a',              15000.00, 'kach',    CURDATE(), 1),
('Elektrisite','Faktè EDH',                 3500.00, 'moncash', CURDATE(), 1),
('Transpò',    'Livrezon machandiz',        1200.00, 'kach',    DATE_SUB(CURDATE(), INTERVAL 2 DAY), 1);

-- Kèk vant demo
INSERT INTO sales (nimewo_fakti, client_id, client_non, user_id, user_non, sou_total, rabe, taks, total, kob_resevwa, monen_rann, mwayen_peman, estati, dat) VALUES
('FKT-20260701-0001', 1, 'Roseline Augustin', 2, 'Marie-Lourdes Pierre', 1150.00, 50.00, 0.00, 1100.00, 1500.00, 400.00, 'kach',    'konplete', DATE_SUB(NOW(), INTERVAL 1 DAY)),
('FKT-20260701-0002', NULL, 'Kliyan Pasaj',   2, 'Marie-Lourdes Pierre',  360.00,  0.00, 0.00,  360.00,  360.00,   0.00, 'moncash', 'konplete', DATE_SUB(NOW(), INTERVAL 1 DAY)),
('FKT-20260702-0001', 2, 'Emmanuel Charles',  3, 'Stanley Joseph',        750.00,  0.00, 0.00,  750.00, 1000.00, 250.00, 'kach',    'konplete', NOW());

INSERT INTO sale_items (sale_id, product_id, product_non, kantite, pri_inite, total) VALUES
(1, 1, 'Diri Tchako 5lb', 2, 500.00, 1000.00),
(1, 5, 'Sadin Brunswick', 1, 150.00, 150.00),
(2, 6, 'Kola Couronne 355ml', 6, 60.00, 360.00),
(3, 2, 'Lwil Mazola 1L', 1, 550.00, 550.00),
(3, 7, 'Ji Tampico 1L', 1, 125.00, 125.00),
(3, 9, 'Savon Lave Fab', 1, 125.00, 125.00);

-- Sèvis demo
INSERT INTO services (nimewo_resi, deskripsyon, pri, rabe, total, moun_bay_sevis, jou, dat, mwayen_peman, user_id, user_non) VALUES
('SVC-20260701-0001', 'Livrezon machandiz Petyonvil', 500.00, 50.00, 450.00, 'Stanley Joseph', 'Madi', DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'kach', 2, 'Marie-Lourdes Pierre'),
('SVC-20260702-0001', 'Rechaj MonCash pou kliyan', 100.00, 0.00, 100.00, 'Marie-Lourdes Pierre', 'Mèkredi', CURDATE(), 'moncash', 2, 'Marie-Lourdes Pierre');

-- Jounal demo
INSERT INTO activity_logs (user_id, user_non, aksyon, modil, deskripsyon, ip_address) VALUES
(1, 'Jean-Robert Baptiste', 'ENSTALASYON', 'Sistèm', 'Sistèm DENYE KWEN enstale ak done demo', '127.0.0.1');
