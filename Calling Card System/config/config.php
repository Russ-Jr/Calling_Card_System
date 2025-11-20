<?php
/**
 * System Configuration
 * Calling Card System
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Timezone
date_default_timezone_set('Asia/Manila');

// Error reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
require_once __DIR__ . '/database.php';

// Session timeout (30 minutes)
define('SESSION_TIMEOUT', 1800);

// Password requirements
define('MIN_PASSWORD_LENGTH', 6);

// File upload settings
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp']);

// NFC Settings
define('NFC_ENCRYPTION_KEY', '0123456789abcdef0123456789abcdef'); // 32 bytes
define('NFC_ENCRYPTION_IV', 'abcdef9876543210'); // 16 bytes
define('NFC_CARD_TYPE', 'NTAG213');

// Default user password
define('DEFAULT_USER_PASSWORD', '123456');

// User roles
define('ROLE_SUPER_ADMIN', 'super_admin');
define('ROLE_ADMIN', 'admin');
define('ROLE_USER', 'user');

// SMTP Configuration
define('MAIL_HOST', 'mail.ndasphilsinc.com');
define('MAIL_USER', 'russel@ndasphilsinc.com');
define('MAIL_PASS', 'RusselNDAS2025');
define('MAIL_PORT', 587);
define('MAIL_FROM_EMAIL', 'russel@ndasphilsinc.com');
define('MAIL_FROM_NAME', 'Calling Card System');

