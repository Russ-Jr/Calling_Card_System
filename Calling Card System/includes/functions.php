<?php
/**
 * Helper Functions
 * Calling Card System
 */

/**
 * Sanitize input data
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Generate random string
 */
function generateRandomString($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Hash password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    // FIX: Ensure $hash is a string. If it is null or not a string (e.g., from an empty DB result), 
    // treat it as an empty string to prevent the PHP Deprecated notice.
    if (!is_string($hash) || is_null($hash)) {
        $hash = '';
    }
    return password_verify($password, $hash);
}

/**
 * Execute prepared statement and fetch one row
 */
function dbFetchOne($stmt) {
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    if ($result) {
        $result->free(); // Explicitly free the result
    }
    return $data;
}

/**
 * Execute prepared statement and fetch all rows
 */
function dbFetchAll($stmt) {
    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    if ($result) {
        $result->free(); // Explicitly free the result
    }
    return $rows;
}

/**
 * Execute prepared statement and fetch column
 */
function dbFetchColumn($stmt) {
    $result = $stmt->get_result();
    $row = $result->fetch_array();
    $data = $row ? $row[0] : false;
    if ($result) {
        $result->free(); // Explicitly free the result
    }
    return $data;
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_type']);
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return isLoggedIn() && ($_SESSION['user_type'] === ROLE_ADMIN || $_SESSION['user_type'] === ROLE_SUPER_ADMIN);
}

/**
 * Check if user is super admin
 */
function isSuperAdmin() {
    return isLoggedIn() && $_SESSION['user_type'] === ROLE_SUPER_ADMIN;
}

/**
 * Require login
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . 'index.php');
        exit;
    }
}

/**
 * Require admin
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: ' . SITE_URL . 'index.php');
        exit;
    }
}

/**
 * Require super admin
 */
function requireSuperAdmin() {
    requireLogin();
    if (!isSuperAdmin()) {
        header('Location: ' . SITE_URL . 'index.php');
        exit;
    }
}

/**
 * Get current user ID
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user type
 */
function getCurrentUserType() {
    return $_SESSION['user_type'] ?? null;
}

/**
 * Get current company ID (for admin)
 */
function getCurrentCompanyId() {
    return $_SESSION['company_id'] ?? null;
}

/**
 * Encrypt data using AES
 */
function encryptData($data) {
    $key = NFC_ENCRYPTION_KEY;
    $iv = NFC_ENCRYPTION_IV;
    $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
    return base64_encode($encrypted);
}

/**
 * Decrypt data using AES
 */
function decryptData($encrypted) {
    $key = NFC_ENCRYPTION_KEY;
    $iv = NFC_ENCRYPTION_IV;
    $encrypted = base64_decode($encrypted);
    return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
}

/**
 * Generate NDEF URL for user
 */
function generateNDEFUrl($firstName, $lastName, $userId) {
    $data = strtolower(str_replace(' ', '', $firstName) . str_replace(' ', '', $lastName) . $userId);
    $encrypted = encryptData($data);
    return SITE_URL . 'user/dashboard.php?data=' . urlencode($encrypted);
}

/**
 * Parse NDEF URL data
 */
function parseNDEFData($encryptedData) {
    try {
        $decrypted = decryptData($encryptedData);
        return $decrypted;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Upload file
 */
function uploadFile($file, $directory, $allowedTypes = ALLOWED_IMAGE_TYPES) {
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['success' => false, 'message' => 'No file uploaded'];
    }
    
    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File size exceeds maximum allowed size'];
    }
    
    // Check file type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $filepath = $directory . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return [
            'success' => true,
            'filename' => $filename,
            'filepath' => $filepath,
            'url' => str_replace(BASE_PATH, SITE_URL, $filepath)
        ];
    }
    
    return ['success' => false, 'message' => 'Failed to upload file'];
}

/**
 * Delete file
 */
function deleteFile($filepath) {
    if (file_exists($filepath)) {
        return unlink($filepath);
    }
    return false;
}

/**
 * Format date
 */
function formatDate($date, $format = 'Y-m-d H:i:s') {
    if (empty($date)) return '';
    $dt = new DateTime($date);
    return $dt->format($format);
}

/**
 * Generate username from name and year
 */
function generateUsername($firstName, $lastName, $year = null) {
    if ($year === null) {
        $year = date('Y');
    }
    $username = strtolower(str_replace(' ', '', $firstName) . str_replace(' ', '', $lastName)) . $year;
    
    // Check if username exists and append number if needed
    $db = getDB();
    $counter = 1;
    $originalUsername = $username;
    
    while (true) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $count = dbFetchColumn($stmt);
        $stmt->close();
        
        if ($count == 0) {
            break;
        }
        $username = $originalUsername . $counter;
        $counter++;
    }
    
    return $username;
}

/**
 * Log activity
 */
function logActivity($userType, $userId, $action, $description = null) {
    $db = getDB();
    $stmt = $db->prepare("
        INSERT INTO activity_logs (user_type, user_id, action, description, ip_address, user_agent)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    $stmt->bind_param("sissss", $userType, $userId, $action, $description, $ip, $userAgent);
    $stmt->execute();
    $stmt->close();
}

/**
 * Send email using SMTP
 */
function sendEmail($to, $subject, $message, $fromEmail = null, $fromName = null) {
    // Get SMTP settings from database
    $db = getDB();
    $settings = [];
    $result = $db->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key LIKE 'smtp_%'");
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    if ($result) {
        $result->free(); // <--- THIS LINE IS CRITICAL
    }
    
    // Use PHPMailer or similar library
    // For now, using basic mail() function
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $fromEmail = $fromEmail ?? ($settings['smtp_from_email'] ?? 'noreply@example.com');
    $fromName = $fromName ?? ($settings['smtp_from_name'] ?? 'Calling Card System');
    $headers .= "From: $fromName <$fromEmail>\r\n";
    
    // Log email
    $stmt = $db->prepare("
        INSERT INTO email_logs (recipient_email, email_type, subject, email_body, status)
        VALUES (?, ?, ?, ?, 'pending')
    ");
    $emailType = 'general';
    $status = 'pending';
    $stmt->bind_param("ssss", $to, $emailType, $subject, $message);
    $stmt->execute();
    $stmt->close();
    
    $result = mail($to, $subject, $message, $headers);
    
    // Update email log
    if ($result) {
        $stmt = $db->prepare("UPDATE email_logs SET status = 'sent', sent_at = NOW() WHERE recipient_email = ? AND subject = ? ORDER BY email_log_id DESC LIMIT 1");
        $status = 'sent';
        $stmt->bind_param("ss", $to, $subject);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $db->prepare("UPDATE email_logs SET status = 'failed', error_message = 'mail() function failed' WHERE recipient_email = ? AND subject = ? ORDER BY email_log_id DESC LIMIT 1");
        $errorMsg = 'mail() function failed';
        $stmt->bind_param("ss", $to, $subject);
        $stmt->execute();
        $stmt->close();
    }
    
    return $result;
}

/**
 * Get system setting
 */
function getSetting($key, $default = null) {
    $db = getDB();
    $stmt = $db->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = dbFetchColumn($stmt);
    $stmt->close();
    return $result !== false ? $result : $default;
}

/**
 * Set system setting
 */
function setSetting($key, $value, $description = null) {
    $db = getDB();
    $userId = getCurrentUserId();
    $stmt = $db->prepare("
        INSERT INTO system_settings (setting_key, setting_value, setting_description, updated_by)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            setting_value = VALUES(setting_value),
            setting_description = VALUES(setting_description),
            updated_by = VALUES(updated_by),
            updated_at = NOW()
    ");
    $stmt->bind_param("sssi", $key, $value, $description, $userId);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

/**
 * JSON response helper
 */
function jsonResponse($success, $message, $data = null, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

