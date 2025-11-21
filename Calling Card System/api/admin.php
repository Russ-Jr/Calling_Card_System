<?php
/**
 * Admin API Endpoints
 * Calling Card System
 */

require_once '../config/config.php';
require_once '../includes/functions.php';

requireAdmin();

header('Content-Type: application/json');

$db = getDB();
$companyId = getCurrentCompanyId();
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$currentUserId = getCurrentUserId();

switch ($action) {
    // ==========================================
    // AUTHENTICATION & PROFILE
    // ==========================================
    case 'admin_login':
        $username = sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            jsonResponse(false, 'Username and password are required', null, 400);
        }
        
        $stmt = $db->prepare("SELECT admin_id, password FROM admins WHERE username = ? AND company_id = ? AND is_active = 1");
        $stmt->bind_param("si", $username, $companyId);
        $stmt->execute();
        $admin = dbFetchOne($stmt);
        $stmt->close();
        
        if ($admin && verifyPassword($password, $admin['password'])) {
            if ($admin['admin_id'] != $currentUserId) {
                 jsonResponse(false, 'Invalid credentials (User mismatch)', null, 401);
            }
            $_SESSION['admin_logged_in'] = true;
            logActivity('admin', $currentUserId, 'admin_account_login', 'Admin logged in via account tab');
            jsonResponse(true, 'Login successful');
        } else {
            jsonResponse(false, 'Invalid credentials', null, 401);
        }
        break;

    case 'update_profile':
        if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) jsonResponse(false, 'Please login first', null, 401);
        
        $adminId = $currentUserId;
        $firstName = sanitize($_POST['first_name'] ?? '');
        $middleName = sanitize($_POST['middle_name'] ?? '');
        $lastName = sanitize($_POST['last_name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $contact = sanitize($_POST['contact_number'] ?? '');
        
        if (empty($firstName) || empty($lastName) || empty($email)) {
            jsonResponse(false, 'Required fields are missing', null, 400);
        }

        try {
            $stmt = $db->prepare("UPDATE admins SET first_name=?, middle_name=?, last_name=?, email=?, contact_number=?, updated_at=NOW() WHERE admin_id=?");
            $stmt->bind_param("sssssi", $firstName, $middleName, $lastName, $email, $contact, $adminId);
            $stmt->execute();
            $stmt->close();
            logActivity('admin', $adminId, 'update_profile', "Updated profile for admin ID: $adminId");
            jsonResponse(true, 'Profile updated successfully');
        } catch (Exception $e) {
            jsonResponse(false, 'Error: ' . $e->getMessage(), null, 500);
        }
        break;

    case 'upload_profile_picture':
        if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) jsonResponse(false, 'Please login first', null, 401);
        
        $file = $_FILES['profile_picture'] ?? null;
        if ($file && $file['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/profiles/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            
            $fileName = 'admin_' . $currentUserId . '_' . time() . '_' . basename($file['name']);
            $targetPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $stmt = $db->prepare("UPDATE admins SET profile_picture = ? WHERE admin_id = ?");
                $stmt->bind_param("si", $targetPath, $currentUserId);
                $stmt->execute();
                $stmt->close();
                jsonResponse(true, 'Profile picture updated');
            } else {
                jsonResponse(false, 'Failed to move uploaded file', null, 500);
            }
        } else {
            jsonResponse(false, 'No valid file uploaded', null, 400);
        }
        break;

    case 'change_password':
        if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) jsonResponse(false, 'Please login first', null, 401);
        
        $adminId = $currentUserId;
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) jsonResponse(false, 'All fields are required', null, 400);
        if ($newPassword !== $confirmPassword) jsonResponse(false, 'New password and confirmation do not match', null, 400);

        try {
            $stmt = $db->prepare("SELECT password FROM admins WHERE admin_id = ?");
            $stmt->bind_param("i", $adminId);
            $stmt->execute();
            $admin = dbFetchOne($stmt);
            $stmt->close();
            
            if (!$admin || !verifyPassword($currentPassword, $admin['password'])) jsonResponse(false, 'Invalid current password', null, 401);
            
            $hashed = hashPassword($newPassword);
            $stmt = $db->prepare("UPDATE admins SET password = ?, updated_at = NOW() WHERE admin_id = ?");
            $stmt->bind_param("si", $hashed, $adminId);
            $stmt->execute();
            $stmt->close();
            jsonResponse(true, 'Password changed successfully');
        } catch (Exception $e) {
            jsonResponse(false, 'Error: ' . $e->getMessage(), null, 500);
        }
        break;

    // ==========================================
    // SOCIAL MEDIA MANAGEMENT (NEW)
    // ==========================================
    case 'get_social':
        if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) jsonResponse(false, 'Please login first', null, 401);
        
        $socialId = $_POST['social_id'] ?? null;
        $type = $_POST['type'] ?? 'admin';
        
        if (!$socialId) jsonResponse(false, 'ID required', null, 400);

        if ($type === 'company') {
            $stmt = $db->prepare("SELECT * FROM company_social_media WHERE social_media_id = ? AND company_id = ?");
            $stmt->bind_param("ii", $socialId, $companyId);
        } else {
            $stmt = $db->prepare("SELECT * FROM admin_social_media WHERE social_media_id = ? AND admin_id = ?");
            $stmt->bind_param("ii", $socialId, $currentUserId);
        }
        
        $stmt->execute();
        $social = dbFetchOne($stmt);
        $stmt->close();

        if ($social) {
            jsonResponse(true, 'Social media loaded', ['social' => $social]);
        } else {
            jsonResponse(false, 'Not found', null, 404);
        }
        break;

    case 'create_social':
    case 'update_social':
        if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) jsonResponse(false, 'Please login first', null, 401);

        $socialId = $_POST['social_id'] ?? null;
        $type = $_POST['type'] ?? 'admin';
        $platformName = sanitize($_POST['platform_name'] ?? '');
        $url = sanitize($_POST['url'] ?? '');
        $order = (int)($_POST['display_order'] ?? 0);
        $isActive = $_POST['is_active'] ?? 1;
        
        // File upload logic for icon
        $iconPath = null;
        $iconFile = $_FILES['platform_icon'] ?? null;
        
        // Check ownership if updating
        if ($socialId) {
             if ($type === 'company') {
                $stmt = $db->prepare("SELECT platform_icon FROM company_social_media WHERE social_media_id = ? AND company_id = ?");
                $stmt->bind_param("ii", $socialId, $companyId);
            } else {
                $stmt = $db->prepare("SELECT platform_icon FROM admin_social_media WHERE social_media_id = ? AND admin_id = ?");
                $stmt->bind_param("ii", $socialId, $currentUserId);
            }
            $stmt->execute();
            $existing = dbFetchOne($stmt);
            $stmt->close();
            
            if (!$existing) jsonResponse(false, 'Record not found or access denied', null, 403);
            $iconPath = $existing['platform_icon'];
        }

        if ($iconFile && $iconFile['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/icons/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $fileName = $type . '_' . time() . '_' . basename($iconFile['name']);
            $newPath = $uploadDir . $fileName;
            if (move_uploaded_file($iconFile['tmp_name'], $newPath)) {
                $iconPath = $newPath;
            }
        }

        if ($type === 'company') {
            if ($socialId) {
                $stmt = $db->prepare("UPDATE company_social_media SET platform_name=?, url=?, platform_icon=?, display_order=?, is_active=?, updated_at=NOW() WHERE social_media_id=? AND company_id=?");
                $stmt->bind_param("sssiiii", $platformName, $url, $iconPath, $order, $isActive, $socialId, $companyId);
            } else {
                $stmt = $db->prepare("INSERT INTO company_social_media (company_id, platform_name, url, platform_icon, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isssii", $companyId, $platformName, $url, $iconPath, $order, $isActive);
            }
        } else {
            // Admin
            if ($socialId) {
                $stmt = $db->prepare("UPDATE admin_social_media SET platform_name=?, url=?, platform_icon=?, display_order=?, is_active=?, updated_at=NOW() WHERE social_media_id=? AND admin_id=?");
                $stmt->bind_param("sssiiii", $platformName, $url, $iconPath, $order, $isActive, $socialId, $currentUserId);
            } else {
                $stmt = $db->prepare("INSERT INTO admin_social_media (admin_id, platform_name, url, platform_icon, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isssii", $currentUserId, $platformName, $url, $iconPath, $order, $isActive);
            }
        }
        
        $stmt->execute();
        $stmt->close();
        jsonResponse(true, 'Social media saved');
        break;

    // ==========================================
    // USER / CARD HOLDER MANAGEMENT
    // ==========================================
    
    case 'get_user':
        if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) jsonResponse(false, 'Please login first', null, 401);
        
        $userId = $_POST['user_id'] ?? null;
        if (!$userId) jsonResponse(false, 'User ID required', null, 400);

        $stmt = $db->prepare("SELECT * FROM users WHERE user_id = ? AND company_id = ?");
        $stmt->bind_param("ii", $userId, $companyId);
        $stmt->execute();
        $user = dbFetchOne($stmt);
        $stmt->close();

        if ($user) {
            unset($user['password']);
            jsonResponse(true, 'User found', ['user' => $user]);
        } else {
            jsonResponse(false, 'User not found', null, 404);
        }
        break;

    case 'create_user':
    case 'update_user':
    case 'save_user':
        if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) jsonResponse(false, 'Please login first', null, 401);

        $userId = $_POST['user_id'] ?? null;
        if ($action === 'create_user') $userId = null;

        $firstName = sanitize($_POST['first_name'] ?? '');
        $middleName = sanitize($_POST['middle_name'] ?? '');
        $lastName = sanitize($_POST['last_name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $contact = sanitize($_POST['contact_number'] ?? '');
        $isActive = $_POST['is_active'] ?? 1;
        $username = sanitize($_POST['username'] ?? ''); 
        
        if (empty($firstName) || empty($lastName) || empty($email)) {
            jsonResponse(false, 'Required fields are missing', null, 400);
        }

        if (!$userId && empty($username)) {
            $username = generateUsername($firstName, $lastName);
        }

        try {
            if ($userId) {
                $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND user_id != ?");
                $stmt->bind_param("si", $email, $userId);
                $stmt->execute();
                if (dbFetchColumn($stmt) > 0) jsonResponse(false, 'Email already exists', null, 400);
                $stmt->close();

                $stmt = $db->prepare("UPDATE users SET first_name=?, middle_name=?, last_name=?, email=?, contact_number=?, is_active=?, updated_at=NOW() WHERE user_id=? AND company_id=?");
                $stmt->bind_param("sssssiii", $firstName, $middleName, $lastName, $email, $contact, $isActive, $userId, $companyId);
                $stmt->execute();
                $stmt->close();
                
                jsonResponse(true, 'User updated successfully');
            } else {
                $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                if (dbFetchColumn($stmt) > 0) jsonResponse(false, 'Email already exists', null, 400);
                $stmt->close();

                $defaultPass = hashPassword('123456'); 
                $stmt = $db->prepare("INSERT INTO users (company_id, username, password, first_name, middle_name, last_name, email, contact_number, is_active, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isssssssii", $companyId, $username, $defaultPass, $firstName, $middleName, $lastName, $email, $contact, $isActive, $currentUserId);
                $stmt->execute();
                $stmt->close();
                
                jsonResponse(true, 'User created successfully');
            }
        } catch (Exception $e) {
            jsonResponse(false, 'Error: ' . $e->getMessage(), null, 500);
        }
        break;

    case 'delete_user':
        if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) jsonResponse(false, 'Please login first', null, 401);
        
        $userId = $_POST['user_id'] ?? null;
        if (!$userId) jsonResponse(false, 'User ID is required', null, 400);
        
        $stmt = $db->prepare("UPDATE users SET is_active = 0 WHERE user_id = ? AND company_id = ?");
        $stmt->bind_param("ii", $userId, $companyId);
        $stmt->execute();
        $stmt->close();
        
        jsonResponse(true, 'User deleted successfully');
        break;

    // ==========================================
    // COMPANY MANAGEMENT
    // ==========================================
    
    case 'get_company':
        $stmt = $db->prepare("SELECT * FROM company WHERE company_id = ?");
        $stmt->bind_param("i", $companyId);
        $stmt->execute();
        $comp = dbFetchOne($stmt);
        $stmt->close();

        if ($comp) {
            $stmt = $db->prepare("SELECT * FROM company_addresses WHERE company_id = ? AND is_active = 1");
            $stmt->bind_param("i", $companyId);
            $stmt->execute();
            $comp['addresses'] = dbFetchAll($stmt);
            $stmt->close();

            $stmt = $db->prepare("SELECT * FROM company_contacts WHERE company_id = ? AND is_active = 1");
            $stmt->bind_param("i", $companyId);
            $stmt->execute();
            $comp['contacts'] = dbFetchAll($stmt);
            $stmt->close();

            $stmt = $db->prepare("SELECT * FROM company_emails WHERE company_id = ? AND is_active = 1");
            $stmt->bind_param("i", $companyId);
            $stmt->execute();
            $comp['emails'] = dbFetchAll($stmt);
            $stmt->close();

            jsonResponse(true, 'Company data loaded', ['company' => $comp]);
        } else {
            jsonResponse(false, 'Company not found', null, 404);
        }
        break;

    case 'update_company':
    case 'save_company_info':
        if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) jsonResponse(false, 'Please login first', null, 401);
        
        $logoFile = $_FILES['logo'] ?? null; 
        if (!$logoFile) $logoFile = $_FILES['company_logo'] ?? null; 

        $logoPath = null;
        $companyName = sanitize($_POST['company_name'] ?? '');
        $tagline = sanitize($_POST['company_tagline'] ?? '');
        $mapLat = $_POST['map_latitude'] ?? null;
        $mapLong = $_POST['map_longitude'] ?? null;
        
        $stmt = $db->prepare("SELECT logo_path FROM company WHERE company_id = ?");
        $stmt->bind_param("i", $companyId);
        $stmt->execute();
        $curr = dbFetchOne($stmt);
        $stmt->close();
        
        if ($curr) $logoPath = $curr['logo_path'];

        if ($logoFile && $logoFile['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/logos/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $fileName = $companyId . '_' . time() . '_' . basename($logoFile['name']);
            $newLogoPath = $uploadDir . $fileName;

            if (move_uploaded_file($logoFile['tmp_name'], $newLogoPath)) {
                $logoPath = $newLogoPath;
            }
        }
        
        try {
            $stmt = $db->prepare("UPDATE company SET company_name = ?, logo_path = ?, map_latitude = ?, map_longitude = ?, updated_by = ?, updated_at = NOW() WHERE company_id = ?");
            $stmt->bind_param("ssddii", $companyName, $logoPath, $mapLat, $mapLong, $currentUserId, $companyId);
            $stmt->execute();
            $stmt->close();

            jsonResponse(true, 'Company information updated successfully');
        } catch (Exception $e) {
            jsonResponse(false, 'Error: ' . $e->getMessage(), null, 500);
        }
        break;

    // ==========================================
    // PRODUCT MANAGEMENT
    // ==========================================

    case 'get_product':
        if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) jsonResponse(false, 'Please login first', null, 401);
        
        $productId = $_POST['product_id'] ?? null;
        if (!$productId) jsonResponse(false, 'Product ID required', null, 400);

        $stmt = $db->prepare("SELECT * FROM products WHERE product_id = ? AND company_id = ?");
        $stmt->bind_param("ii", $productId, $companyId);
        $stmt->execute();
        $prod = dbFetchOne($stmt);
        $stmt->close();

        if ($prod) {
            jsonResponse(true, 'Product loaded', ['product' => $prod]);
        } else {
            jsonResponse(false, 'Product not found', null, 404);
        }
        break;

    case 'create_product':
    case 'update_product':
    case 'save_product':
        if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) jsonResponse(false, 'Please login first', null, 401);
        
        $productId = $_POST['product_id'] ?? null;
        if ($action === 'create_product') $productId = null;

        $name = sanitize($_POST['product_name'] ?? '');
        $desc = sanitize($_POST['description'] ?? $_POST['product_description'] ?? '');
        $price = sanitize($_POST['price'] ?? '');
        $alt = sanitize($_POST['image_alt_text'] ?? '');
        $isActive = $_POST['is_active'] ?? 1;
        
        $imageFile = $_FILES['image'] ?? $_FILES['product_image'] ?? null;
        $imagePath = null;
        
        if (empty($name)) jsonResponse(false, 'Product name is required', null, 400);

        if ($productId) {
            $stmt = $db->prepare("SELECT image_path FROM products WHERE product_id = ? AND company_id = ?");
            $stmt->bind_param("ii", $productId, $companyId);
            $stmt->execute();
            $curr = dbFetchOne($stmt);
            $stmt->close();
            if (!$curr) jsonResponse(false, 'Product not found', null, 403);
            $imagePath = $curr['image_path'];
        }

        if ($imageFile && $imageFile['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/products/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $fileName = $companyId . '_' . time() . '_' . basename($imageFile['name']);
            $newPath = $uploadDir . $fileName;
            if (move_uploaded_file($imageFile['tmp_name'], $newPath)) {
                $imagePath = $newPath;
            }
        }

        if ($productId) {
            $stmt = $db->prepare("UPDATE products SET product_name=?, product_description=?, price=?, image_path=?, image_alt_text=?, is_active=?, updated_by=?, updated_at=NOW() WHERE product_id=?");
            $stmt->bind_param("sssssiii", $name, $desc, $price, $imagePath, $alt, $isActive, $currentUserId, $productId);
            $stmt->execute();
        } else {
            $stmt = $db->prepare("INSERT INTO products (company_id, product_name, product_description, price, image_path, image_alt_text, is_active, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssssii", $companyId, $name, $desc, $price, $imagePath, $alt, $isActive, $currentUserId);
            $stmt->execute();
        }
        $stmt->close();
        jsonResponse(true, 'Product saved successfully');
        break;

    case 'delete_product':
        if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) jsonResponse(false, 'Please login first', null, 401);
        
        $productId = $_POST['product_id'] ?? null;
        $stmt = $db->prepare("UPDATE products SET is_active = 0 WHERE product_id = ? AND company_id = ?");
        $stmt->bind_param("ii", $productId, $companyId);
        $stmt->execute();
        $stmt->close();
        jsonResponse(true, 'Product deleted');
        break;
        
    case 'register_nfc_manual':
        if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) jsonResponse(false, 'Please login first', null, 401);
        
        $userId = $_POST['user_id'] ?? null;
        $uid = sanitize($_POST['nfc_uid'] ?? '');
        
        if (!$userId || !$uid) jsonResponse(false, 'Missing Data', null, 400);
        
        $stmt = $db->prepare("SELECT count(*) FROM users WHERE nfc_uid = ? AND user_id != ?");
        $stmt->bind_param("si", $uid, $userId);
        $stmt->execute();
        if (dbFetchColumn($stmt) > 0) jsonResponse(false, 'NFC UID already registered to another user', null, 400);
        $stmt->close();
        
        $stmt = $db->prepare("UPDATE users SET nfc_uid = ? WHERE user_id = ? AND company_id = ?");
        $stmt->bind_param("sii", $uid, $userId, $companyId);
        $stmt->execute();
        $stmt->close();
        jsonResponse(true, 'NFC Registered');
        break;

    default:
        jsonResponse(false, 'Invalid action: ' . htmlspecialchars($action), null, 400);
}
?>