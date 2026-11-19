-- ============================================================================
-- FRFAS — FPDA Requisition (TA & Quotation) and Financial Approval System
-- FULL DATABASE SCRIPT — creates the database, every table, and seed data
-- in one run. Paste this whole file into phpMyAdmin's "SQL" tab (or run via
-- `mysql -u root -p < frfas_full_schema.sql`) and you're done — no need to
-- separately visit install.php afterwards (though it's still safe to, any
-- time you want to re-check/repair the install).
-- ============================================================================

CREATE DATABASE IF NOT EXISTS frfas_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE frfas_db;

-- ----------------------------------------------------------------------------
-- Table: users
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    username VARCHAR(60) NOT NULL UNIQUE,
    email VARCHAR(120) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('Staff','Supervisor','Finance','CEO','Admin') NOT NULL DEFAULT 'Staff',
    department VARCHAR(120) NOT NULL,
    position_title VARCHAR(120) DEFAULT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    theme VARCHAR(10) NOT NULL DEFAULT 'light',
    font_pref VARCHAR(20) NOT NULL DEFAULT 'default',
    mfa_enabled TINYINT(1) NOT NULL DEFAULT 1,
    failed_attempts INT NOT NULL DEFAULT 0,
    locked_until DATETIME DEFAULT NULL,
    last_login_at DATETIME DEFAULT NULL,
    must_change_password TINYINT(1) NOT NULL DEFAULT 0,
    last_seen DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- Table: programs  (FPDA's real key programs)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS programs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(30) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    allocated DECIMAL(12,2) NOT NULL DEFAULT 0,
    spent DECIMAL(12,2) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- Table: requisitions
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS requisitions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ref_no VARCHAR(30) NOT NULL UNIQUE,
    requester_id INT NOT NULL,
    category VARCHAR(60) NOT NULL,           -- Request type: Travel Advance / Procurement / Quotation
    department VARCHAR(120) NOT NULL,
    division VARCHAR(120) DEFAULT NULL,      -- FPDA division raising the request (per paper form)
    program_id INT NOT NULL,
    location VARCHAR(150) DEFAULT NULL,
    output VARCHAR(150) DEFAULT NULL,
    activity VARCHAR(150) DEFAULT NULL,
    description TEXT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    estimated_cost DECIMAL(12,2) NOT NULL,   -- auto-computed: SUM of requisition_items.value
    supplier VARCHAR(150) DEFAULT NULL,
    justification TEXT,                      -- doubles as the paper form's "Remarks" field
    attachment VARCHAR(255) DEFAULT NULL,
    flow VARCHAR(60) NOT NULL,               -- e.g. 'Supervisor,Finance,CEO'
    current_level VARCHAR(30) DEFAULT NULL,  -- NULL once finalised (Approved/Rejected)
    status ENUM('Pending','Approved','Rejected','Returned') NOT NULL DEFAULT 'Pending',
    approved_amount DECIMAL(12,2) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (requester_id) REFERENCES users(id),
    FOREIGN KEY (program_id) REFERENCES programs(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- Table: requisition_items  (the Goods & Services line-item table)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS requisition_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    requisition_id INT NOT NULL,
    line_no INT NOT NULL DEFAULT 1,
    item_description VARCHAR(255) NOT NULL,
    quantity DECIMAL(10,2) NOT NULL DEFAULT 1,
    unit_price DECIMAL(12,2) NOT NULL DEFAULT 0,
    value DECIMAL(12,2) NOT NULL DEFAULT 0,  -- quantity * unit_price, computed on save
    preferred_supplier VARCHAR(150) DEFAULT NULL,
    FOREIGN KEY (requisition_id) REFERENCES requisitions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- Table: requisition_history  (audit trail per requisition)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS requisition_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    requisition_id INT NOT NULL,
    actor_name VARCHAR(120) NOT NULL,
    action VARCHAR(60) NOT NULL,
    level VARCHAR(30) DEFAULT NULL,
    comment TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (requisition_id) REFERENCES requisitions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- Table: notifications
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message VARCHAR(255) NOT NULL,
    requisition_id INT DEFAULT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- Table: activity_log  (system-wide login/action log, for Admin > System Logs)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    actor_name VARCHAR(120) DEFAULT NULL,
    action VARCHAR(60) NOT NULL,        -- 'login','login_failed','submit','approve','reject','return','admin', etc.
    description VARCHAR(255) NOT NULL,
    ip_address VARCHAR(64) DEFAULT NULL,
    user_agent VARCHAR(255) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- Table: backups  (Admin > Backups history log)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS backups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    size_bytes BIGINT NOT NULL DEFAULT 0,
    checksum VARCHAR(64) DEFAULT NULL,
    encrypted TINYINT(1) NOT NULL DEFAULT 1,
    created_by VARCHAR(120) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ============================================================================
-- SEED DATA
-- ============================================================================

-- Default Admin account — log in with username "admin" or "admin@fpda.com.pg",
-- password "admin123". The password below is a real bcrypt hash of "admin123"
-- (compatible with PHP's password_verify()) — change it from My Profile after
-- your first login.
INSERT IGNORE INTO users (name, username, email, password, role, department, position_title, is_active)
VALUES (
    'ICT Admin', 'admin', 'admin@fpda.com.pg',
    '$2b$10$zLgr8mUJ2Qav.nWAg/QIU.RWhcEmH.qVq4hRP/3UNZIn3kGqk0ou.',
    'Admin', 'ICT', 'ICT Systems Administrator', 1
);

-- FPDA's real key programs
INSERT IGNORE INTO programs (code, name, allocated, spent) VALUES
    ('VEW-01',  'Village Extension Worker Program', 220000, 0),
    ('NBO-02',  'National Bulb Onion Program', 180000, 0),
    ('VCI-03',  'Value Chain Innovation Program', 150000, 0),
    ('SPM-04',  'Seed & Planting Materials Program', 160000, 0),
    ('ESP-05',  'Elite Seed Potato Program', 140000, 0),
    ('SPIC-06', 'Seed Potato Inspection & Certification Program', 90000, 0),
    ('RTD-07',  'Research & Technology Development Program', 200000, 0),
    ('PPME-08', 'Policy, Planning, Monitoring & Evaluation Program', 100000, 0),
    ('IMC-09',  'Information Management & Communication Program', 85000, 0),
    ('CFU-10',  'Corporate Functional Units & Programs', 130000, 0);

-- ============================================================================
-- Done. Visit http://localhost/frfas/ and log in as:
--   Username: admin   |   Password: admin123
-- Then go to Admin > User Management to add your Supervisor, Finance, CEO,
-- and Staff accounts.
-- ============================================================================
