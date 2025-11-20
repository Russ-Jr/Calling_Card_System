-- =====================================================
-- 1. ADMINS TABLE (Created first, foreign keys added later)
-- =====================================================
-- Stores admin account information
CREATE TABLE IF NOT EXISTS admins (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL COMMENT 'Hashed password',
    role ENUM('super_admin', 'admin') DEFAULT 'admin' COMMENT 'super_admin can create admins and companies, admin manages users',
    first_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100) DEFAULT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    contact_number VARCHAR(20) DEFAULT NULL,
    profile_picture VARCHAR(255) DEFAULT NULL COMMENT 'Path to profile picture file',
    company_id INT DEFAULT NULL COMMENT 'Company associated with this admin (NULL for super_admin)',
    nfc_uid VARCHAR(50) DEFAULT NULL COMMENT 'NFC Card UID for admin card',
    ndef_url VARCHAR(500) DEFAULT NULL COMMENT 'NDEF URL stored on admin NFC card',
    is_active TINYINT(1) DEFAULT 1 COMMENT '1=Active, 0=Inactive',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL DEFAULT NULL,
    created_by INT DEFAULT NULL COMMENT 'Super admin who created this admin',
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_nfc_uid (nfc_uid),
    INDEX idx_role (role),
    INDEX idx_company_id (company_id),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 2. USERS TABLE (Card Holder Members/Employees)
-- =====================================================
-- Stores card holder member/employee information
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL COMMENT 'Company this user belongs to',
    username VARCHAR(100) NOT NULL UNIQUE COMMENT 'Format: firstname+lastname+year',
    password VARCHAR(255) NOT NULL COMMENT 'Hashed password, default: 123456',
    first_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100) DEFAULT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    contact_number VARCHAR(20) DEFAULT NULL,
    profile_picture VARCHAR(255) DEFAULT NULL COMMENT 'Path to profile picture file',
    nfc_uid VARCHAR(50) DEFAULT NULL COMMENT 'NFC Card UID',
    ndef_url VARCHAR(500) DEFAULT NULL COMMENT 'NDEF URL: user/dashboard.php?{firstname+lastname+user_id}',
    account_created_year YEAR DEFAULT NULL COMMENT 'Year when account was created',
    is_active TINYINT(1) DEFAULT 1 COMMENT '1=Active, 0=Inactive',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL DEFAULT NULL,
    created_by INT DEFAULT NULL COMMENT 'Admin ID who created this user',
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_nfc_uid (nfc_uid),
    INDEX idx_company_id (company_id),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 3. ADMIN_SOCIAL_MEDIA TABLE
-- =====================================================
-- Stores admin's social media links
CREATE TABLE IF NOT EXISTS admin_social_media (
    social_media_id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    platform_name VARCHAR(50) NOT NULL COMMENT 'e.g., Facebook, Gmail, LinkedIn, etc.',
    platform_icon VARCHAR(100) DEFAULT NULL COMMENT 'Icon class or image path',
    url VARCHAR(500) NOT NULL,
    display_order INT DEFAULT 0 COMMENT 'Order for display',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_admin_id (admin_id),
    FOREIGN KEY (admin_id) REFERENCES admins(admin_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 4. USER_SOCIAL_MEDIA TABLE
-- =====================================================
-- Stores user's social media links
CREATE TABLE IF NOT EXISTS user_social_media (
    social_media_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    platform_name VARCHAR(50) NOT NULL COMMENT 'e.g., Facebook, Gmail, LinkedIn, etc.',
    platform_icon VARCHAR(100) DEFAULT NULL COMMENT 'Icon class or image path',
    url VARCHAR(500) NOT NULL,
    display_order INT DEFAULT 0 COMMENT 'Order for display',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 5. COMPANY TABLE
-- =====================================================
-- Stores main company information
CREATE TABLE IF NOT EXISTS company (
    company_id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(255) NOT NULL,
    map_latitude DECIMAL(10, 8) DEFAULT NULL COMMENT 'Latitude for map location',
    map_longitude DECIMAL(11, 8) DEFAULT NULL COMMENT 'Longitude for map location',
    map_location_text VARCHAR(500) DEFAULT NULL COMMENT 'Text description of location',
    logo_path VARCHAR(255) DEFAULT NULL COMMENT 'Path to company logo file',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT DEFAULT NULL COMMENT 'Super admin who created this company',
    updated_by INT DEFAULT NULL COMMENT 'Admin ID who last updated',
    INDEX idx_created_by (created_by),
    INDEX idx_updated_by (updated_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 6. COMPANY_ADDRESSES TABLE
-- =====================================================
-- Stores multiple company addresses (branches)
CREATE TABLE IF NOT EXISTS company_addresses (
    address_id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    address_type VARCHAR(50) DEFAULT 'main' COMMENT 'main, branch, etc.',
    address_line1 VARCHAR(255) NOT NULL,
    address_line2 VARCHAR(255) DEFAULT NULL,
    city VARCHAR(100) DEFAULT NULL,
    state_province VARCHAR(100) DEFAULT NULL,
    postal_code VARCHAR(20) DEFAULT NULL,
    country VARCHAR(100) DEFAULT NULL,
    full_address TEXT DEFAULT NULL COMMENT 'Complete formatted address',
    display_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_company_id (company_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 7. COMPANY_CONTACTS TABLE
-- =====================================================
-- Stores multiple company contact numbers
CREATE TABLE IF NOT EXISTS company_contacts (
    contact_id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    contact_type VARCHAR(50) DEFAULT 'phone' COMMENT 'phone, mobile, fax, etc.',
    contact_number VARCHAR(20) NOT NULL,
    display_label VARCHAR(100) DEFAULT NULL COMMENT 'e.g., Main Office, Sales, etc.',
    display_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_company_id (company_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 8. COMPANY_EMAILS TABLE
-- =====================================================
-- Stores multiple company email addresses
CREATE TABLE IF NOT EXISTS company_emails (
    email_id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    email_address VARCHAR(255) NOT NULL,
    display_label VARCHAR(100) DEFAULT NULL COMMENT 'e.g., General, Sales, Support, etc.',
    display_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_company_id (company_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 9. COMPANY_SOCIAL_MEDIA TABLE
-- =====================================================
-- Stores company social media links
CREATE TABLE IF NOT EXISTS company_social_media (
    social_media_id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    platform_name VARCHAR(50) NOT NULL COMMENT 'e.g., Facebook, Twitter, Instagram, etc.',
    platform_icon VARCHAR(100) DEFAULT NULL COMMENT 'Icon class or image path',
    url VARCHAR(500) NOT NULL,
    display_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_company_id (company_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 10. PRODUCTS TABLE
-- =====================================================
-- Stores product images uploaded by admin
CREATE TABLE IF NOT EXISTS products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL COMMENT 'Company this product belongs to',
    product_name VARCHAR(255) DEFAULT NULL COMMENT 'Optional product name',
    image_path VARCHAR(255) NOT NULL COMMENT 'Path to product image file',
    image_alt_text VARCHAR(255) DEFAULT NULL COMMENT 'Alt text for image',
    display_order INT DEFAULT 0 COMMENT 'Order for display (arrangeable)',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT DEFAULT NULL COMMENT 'Admin ID who uploaded',
    INDEX idx_display_order (display_order),
    INDEX idx_company_id (company_id),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 11. SYSTEM_SETTINGS TABLE
-- =====================================================
-- Stores system-wide settings
CREATE TABLE IF NOT EXISTS system_settings (
    setting_id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT DEFAULT NULL,
    setting_description VARCHAR(500) DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT DEFAULT NULL COMMENT 'Admin ID who last updated',
    INDEX idx_setting_key (setting_key),
    INDEX idx_updated_by (updated_by),
    FOREIGN KEY (updated_by) REFERENCES admins(admin_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 12. USER_ADDITIONAL_DATA TABLE
-- =====================================================
-- Stores optional additional data for users (as mentioned in requirements)
CREATE TABLE IF NOT EXISTS user_additional_data (
    data_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    field_name VARCHAR(100) NOT NULL,
    field_value TEXT DEFAULT NULL,
    field_type VARCHAR(50) DEFAULT 'text' COMMENT 'text, number, date, etc.',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 13. ACTIVITY_LOGS TABLE (Optional but Recommended)
-- =====================================================
-- Stores system activity logs for auditing
CREATE TABLE IF NOT EXISTS activity_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_type ENUM('admin', 'user') NOT NULL COMMENT 'Type of user who performed action',
    user_id INT NOT NULL COMMENT 'Admin ID or User ID',
    action VARCHAR(100) NOT NULL COMMENT 'e.g., login, create_user, update_profile, etc.',
    description TEXT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_type_id (user_type, user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 14. EMAIL_LOGS TABLE (Optional but Recommended)
-- =====================================================
-- Stores email sending logs for credential emails
CREATE TABLE IF NOT EXISTS email_logs (
    email_log_id INT AUTO_INCREMENT PRIMARY KEY,
    recipient_email VARCHAR(255) NOT NULL,
    recipient_name VARCHAR(255) DEFAULT NULL,
    email_type VARCHAR(50) NOT NULL COMMENT 'credentials, password_reset, etc.',
    subject VARCHAR(255) NOT NULL,
    email_body TEXT DEFAULT NULL,
    status ENUM('sent', 'failed', 'pending') DEFAULT 'pending',
    error_message TEXT DEFAULT NULL,
    sent_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_recipient_email (recipient_email),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INSERT DEFAULT DATA
-- =====================================================

-- Insert default super admin account
-- Default password: superadmin123 (CHANGE THIS IMMEDIATELY AFTER FIRST LOGIN)
-- Password hash for 'superadmin123' (bcrypt)
INSERT INTO admins (username, password, role, first_name, last_name, email, is_active) 
VALUES ('superadmin', '$2y$10$6sgPtrClUJHqaHqgLrut9.AL0UaTkAUuQ/A0Z/bFbpTn9giAvAZYy', 'super_admin', 'Super', 'Admin', 'superadmin@example.com', 1);

-- Note: Companies will be created by super admin through the interface
-- No default company record needed

-- Insert default system settings
INSERT INTO system_settings (setting_key, setting_value, setting_description) VALUES
('site_url', 'https://tito.ndasphilsinc.com/callingcard/', 'Base URL of the website'),
('smtp_host', 'mail.ndasphilsinc.com', 'SMTP server host'),
('smtp_port', '587', 'SMTP server port'),
('smtp_username', 'russel@ndasphilsinc.com', 'SMTP username'),
('smtp_password', 'RusselNDAS2025', 'SMTP password'),
('smtp_from_email', 'russel@ndasphilsinc.com', 'Default from email address'),
('smtp_from_name', 'Calling Card System', 'Default from name'),
('default_user_password', '123456', 'Default password for new users'),
('encryption_key', '0123456789abcdef0123456789abcdef', 'AES encryption key (32 bytes)'),
('encryption_iv', 'abcdef9876543210', 'AES encryption IV (16 bytes)'),
('nfc_card_type', 'NTAG213', 'NFC card type being used');

-- =====================================================
-- VIEWS (Optional but Helpful)
-- =====================================================

-- View for user dashboard data
CREATE OR REPLACE VIEW v_user_dashboard AS
SELECT 
    u.user_id,
    u.username,
    u.first_name,
    u.middle_name,
    u.last_name,
    u.email,
    u.contact_number,
    u.profile_picture,
    u.nfc_uid,
    u.ndef_url,
    u.created_at,
    u.updated_at,
    CONCAT(u.first_name, u.last_name, YEAR(u.created_at)) AS display_name
FROM users u
WHERE u.is_active = 1;

-- View for admin dashboard summary
CREATE OR REPLACE VIEW v_admin_dashboard_summary AS
SELECT 
    (SELECT COUNT(*) FROM users WHERE is_active = 1) AS total_active_users,
    (SELECT COUNT(*) FROM users WHERE nfc_uid IS NOT NULL) AS total_registered_cards,
    (SELECT COUNT(*) FROM products WHERE is_active = 1) AS total_products,
    (SELECT COUNT(*) FROM company_addresses WHERE is_active = 1) AS total_company_addresses;

-- =====================================================
-- STORED PROCEDURES (Optional but Helpful)
-- =====================================================

-- Procedure to create a new user with default credentials
DELIMITER //
CREATE PROCEDURE sp_create_user_with_defaults(
    IN p_first_name VARCHAR(100),
    IN p_middle_name VARCHAR(100),
    IN p_last_name VARCHAR(100),
    IN p_email VARCHAR(255),
    IN p_contact_number VARCHAR(20),
    IN p_created_by INT,
    OUT p_user_id INT,
    OUT p_username VARCHAR(100),
    OUT p_password VARCHAR(255)
)
BEGIN
    DECLARE v_year YEAR;
    DECLARE v_username VARCHAR(100);
    DECLARE v_default_password VARCHAR(255);
    
    SET v_year = YEAR(NOW());
    SET v_username = CONCAT(LOWER(REPLACE(p_first_name, ' ', '')), LOWER(REPLACE(p_last_name, ' ', '')), v_year);
    SET v_default_password = '123456';
    
    -- Check if username already exists, append number if needed
    SET @counter = 1;
    WHILE EXISTS(SELECT 1 FROM users WHERE username = v_username) DO
        SET v_username = CONCAT(LOWER(REPLACE(p_first_name, ' ', '')), LOWER(REPLACE(p_last_name, ' ', '')), v_year, @counter);
        SET @counter = @counter + 1;
    END WHILE;
    
    INSERT INTO users (
        username, 
        password, 
        first_name, 
        middle_name, 
        last_name, 
        email, 
        contact_number,
        account_created_year,
        created_by
    ) VALUES (
        v_username,
        v_default_password, -- Should be hashed in actual implementation
        p_first_name,
        p_middle_name,
        p_last_name,
        p_email,
        p_contact_number,
        v_year,
        p_created_by
    );
    
    SET p_user_id = LAST_INSERT_ID();
    SET p_username = v_username;
    SET p_password = v_default_password;
END //
DELIMITER ;

-- =====================================================
-- ADD FOREIGN KEY CONSTRAINTS
-- =====================================================
-- Add foreign keys after all tables are created

-- Admins table foreign keys
ALTER TABLE admins
    ADD CONSTRAINT fk_admins_company FOREIGN KEY (company_id) REFERENCES company(company_id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_admins_created_by FOREIGN KEY (created_by) REFERENCES admins(admin_id) ON DELETE SET NULL;

-- Users table foreign keys
ALTER TABLE users
    ADD CONSTRAINT fk_users_company FOREIGN KEY (company_id) REFERENCES company(company_id) ON DELETE CASCADE,
    ADD CONSTRAINT fk_users_created_by FOREIGN KEY (created_by) REFERENCES admins(admin_id) ON DELETE SET NULL;

-- Company table foreign keys
ALTER TABLE company
    ADD CONSTRAINT fk_company_created_by FOREIGN KEY (created_by) REFERENCES admins(admin_id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_company_updated_by FOREIGN KEY (updated_by) REFERENCES admins(admin_id) ON DELETE SET NULL;

-- Company addresses foreign key
ALTER TABLE company_addresses
    ADD CONSTRAINT fk_company_addresses_company FOREIGN KEY (company_id) REFERENCES company(company_id) ON DELETE CASCADE;

-- Company contacts foreign key
ALTER TABLE company_contacts
    ADD CONSTRAINT fk_company_contacts_company FOREIGN KEY (company_id) REFERENCES company(company_id) ON DELETE CASCADE;

-- Company emails foreign key
ALTER TABLE company_emails
    ADD CONSTRAINT fk_company_emails_company FOREIGN KEY (company_id) REFERENCES company(company_id) ON DELETE CASCADE;

-- Company social media foreign key
ALTER TABLE company_social_media
    ADD CONSTRAINT fk_company_social_media_company FOREIGN KEY (company_id) REFERENCES company(company_id) ON DELETE CASCADE;

-- Products foreign keys
ALTER TABLE products
    ADD CONSTRAINT fk_products_company FOREIGN KEY (company_id) REFERENCES company(company_id) ON DELETE CASCADE,
    ADD CONSTRAINT fk_products_created_by FOREIGN KEY (created_by) REFERENCES admins(admin_id) ON DELETE SET NULL;

-- =====================================================
-- END OF SCHEMA
-- =====================================================

