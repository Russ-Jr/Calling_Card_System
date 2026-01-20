<?php
/**
 * Database Configuration
 * Calling Card System
 */

// Database credentials
define('DB_HOST', 'localhost');

define('DB_PORT', 3306);

// Site configuration
define('SITE_URL', 'https://tito.ndasphilsinc.com/callingcard/');
define('BASE_PATH', dirname(__DIR__));

// Upload paths
define('UPLOAD_PATH', BASE_PATH . '/uploads/');
define('UPLOAD_URL', SITE_URL . 'uploads/');

// Create upload directories if they don't exist
$upload_dirs = [
    UPLOAD_PATH . 'profiles/',
    UPLOAD_PATH . 'logos/',
    UPLOAD_PATH . 'products/'
];

foreach ($upload_dirs as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Database connection using mysqli
$servername = DB_HOST;
$username = DB_USER;
$password = DB_PASS;
$database = DB_NAME;

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4
$conn->set_charset("utf8mb4");

// Get database connection
function getDB() {
    global $conn;
    return $conn;
}

