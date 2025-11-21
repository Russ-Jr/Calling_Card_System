<?php
/**
 * Admin API Endpoints
 * Calling Card System
 */

require_once '../config/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

db = getDB();
$companyId = getCurrentCompanyId();
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$currentUserId = getCurrentUserId();

// Allow admin_login to be called without an existing authenticated session.
if ($action !== 'admin_login') {
    requireAdmin();
}

switch ($action) {
    case 'admin_login':
        // Admin login for account tab
        $username = sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            jsonResponse(false, 'Username and password are required', null, 400);
        }

        $stmt = $db->prepare("SELECT admin_id, password, company_id FROM admins WHERE username = ? AND is_active = 1 LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $admin = dbFetchOne($stmt);
        $stmt->close();

        if ($admin && verifyPassword($password, $admin['password'])) {
            // Set session values
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $admin['admin_id'];
            $_SESSION['company_id'] = $admin['company_id'];
            logActivity('admin', $admin['admin_id'], 'admin_account_login', 'Admin logged in via account tab');
            jsonResponse(true, 'Login successful', ['admin_id' => $admin['admin_id']]);
        } else {
            jsonResponse(false, 'Invalid credentials', null, 401);
        }
        break;

    case 'update_profile':
        if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
            jsonResponse(false, 'Please login first', null, 401);
        }

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
            $stmt = $db->prepare(
                "UPDATE admins SET first_name=?, middle_name=?, last_name=?, email=?, contact_number=?, updated_at=NOW() WHERE admin_id=?"
            );
            $stmt->bind_param("sssssi", $firstName, $middleName, $lastName, $email, $contact, $adminId);
            $stmt->execute();
            $stmt->close();

            logActivity('admin', $adminId, 'update_profile', "Updated profile for admin ID: $adminId");
            jsonResponse(true, 'Profile updated successfully');
        } catch (Exception $e) {
            jsonResponse(false, 'Error: ' . $e->getMessage(), null, 500);
        }
        break;

    case 'change_password':
        if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
            jsonResponse(false, 'Please login first', null, 401);
        }

        $adminId = $currentUserId;
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            jsonResponse(false, 'All fields are required', null, 400);
        }
        if ($newPassword !== $confirmPassword) {
            jsonResponse(false, 'New password and confirmation do not match', null, 400);
        }

        try {
            $stmt = $db->prepare("SELECT password FROM admins WHERE admin_id = ?");
            $stmt->bind_param("i", $adminId);
            $stmt->execute();
            $admin = dbFetchOne($stmt);
            $stmt->close();

            if (!$admin || !verifyPassword($currentPassword, $admin['password'])) {
                jsonResponse(false, 'Invalid current password', null, 401);
            }

            $hashed = hashPassword($newPassword);
            $stmt = $db->prepare("UPDATE admins SET password = ?, updated_at = NOW() WHERE admin_id = ?");
            $stmt->bind_param("si", $hashed, $adminId);
            $stmt->execute();
            $stmt->close();

            logActivity('admin', $adminId, 'change_password', 'Admin changed password');
            jsonResponse(true, 'Password changed successfully');
        } catch (Exception $e) {
            jsonResponse(false, 'Error: ' . $e->getMessage(), null, 500);
        }
        break;

    // User create/update aliases
    case 'create_user':
    case 'update_user':
    case 'save_user':
        if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
            jsonResponse(false, 'Please login first', null, 401);
        }

        $userId = $_POST['user_id'] ?? null;
        $firstName = sanitize($_POST['first_name'] ?? '');
        $middleName = sanitize($_POST['middle_name'] ?? '');
        $lastName = sanitize($_POST['last_name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $contact = sanitize($_POST['contact_number'] ?? '');
        $username = sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($firstName) || empty($lastName) || empty($email)) {
            jsonResponse(false, 'Required fields are missing', null, 400);
        }

        try {
            if ($userId) {
                // Update existing user
                $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM users WHERE email = ? AND user_id != ?");
                $stmt->bind_param("si", $email, $userId);
                $stmt->execute();
                $row = dbFetchOne($stmt);
                $stmt->close();
                $emailExists = $row['cnt'] ?? 0;

                if ($emailExists > 0) {
                    jsonResponse(false, 'Email already exists for another user', null, 400);
                }

                if (!empty($password)) {
                    $hashed = hashPassword($password);
                    $stmt = $db->prepare("UPDATE users SET first_name=?, middle_name=?, last_name=?, email=?, contact_number=?, password=?, updated_at=NOW() WHERE user_id=? AND company_id=?");
                    $stmt->bind_param("ssssssii", $firstName, $middleName, $lastName, $email, $contact, $hashed, $userId, $companyId);
                } else {
                    $stmt = $db->prepare("UPDATE users SET first_name=?, middle_name=?, last_name=?, email=?, contact_number=?, updated_at=NOW() WHERE user_id=? AND company_id=?");
                    $stmt->bind_param("sssssii", $firstName, $middleName, $lastName, $email, $contact, $userId, $companyId);
                }
                $stmt->execute();
                $stmt->close();

                logActivity('admin', $currentUserId, 'update_user', "Updated user ID: $userId");
                jsonResponse(true, 'Card holder updated successfully');
            } else {
                // Create new user
                if (empty($password)) {
                    jsonResponse(false, 'Password is required for new user', null, 400);
                }

                $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM users WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $row = dbFetchOne($stmt);
                $stmt->close();
                $emailExists = $row['cnt'] ?? 0;

                if ($emailExists > 0) {
                    jsonResponse(false, 'Email already exists', null, 400);
                }

                $stmt = $db->prepare("INSERT INTO users (company_id, username, password, first_name, middle_name, last_name, email, contact_number, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $hashed = hashPassword($password);
                $stmt->bind_param("isssssssi", $companyId, $username, $hashed, $firstName, $middleName, $lastName, $email, $contact, $currentUserId);
                $stmt->execute();
                $stmt->close();

                logActivity('admin', $currentUserId, 'create_user', "Created new user: $email");
                jsonResponse(true, 'New card holder created successfully');
            }
        } catch (Exception $e) {
            jsonResponse(false, 'Error: ' . $e->getMessage(), null, 500);
        }
        break;

    case 'delete_user':
        if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
            jsonResponse(false, 'Please login first', null, 401);
        }

        $userId = $_POST['user_id'] ?? null;
        if (!$userId) {
            jsonResponse(false, 'User ID is required', null, 400);
        }

        try {
            $stmt = $db->prepare("SELECT user_id FROM users WHERE user_id = ? AND company_id = ?");
            $stmt->bind_param("ii", $userId, $companyId);
            $stmt->execute();
            $user = dbFetchOne($stmt);
            $stmt->close();

            if (!$user) {
                jsonResponse(false, 'User not found or access denied', null, 403);
            }

            $stmt = $db->prepare("UPDATE users SET is_active = 0 WHERE user_id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $stmt->close();

            logActivity('admin', $currentUserId, 'delete_user', "Deleted user ID: $userId");
            jsonResponse(true, 'Card holder deleted successfully');
        } catch (Exception $e) {
            jsonResponse(false, 'Error: ' . $e->getMessage(), null, 500);
        }
        break;

    // Company - support both save_company_info and update_company as aliases
    case 'save_company_info':
    case 'update_company':
        if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
            jsonResponse(false, 'Please login first', null, 401);
        }

        $logoFile = $_FILES['company_logo'] ?? $_FILES['logo'] ?? null;
        $logoPath = null;
        $companyName = sanitize($_POST['company_name'] ?? '');
        $mapLat = $_POST['map_latitude'] ?? null;
        $mapLong = $_POST['map_longitude'] ?? null;
        $oldLogo = $_POST['old_logo_path'] ?? null;

        $stmt = $db->prepare("SELECT logo_path FROM company WHERE company_id = ?");
        $stmt->bind_param("i", $companyId);
        $stmt->execute();
        $company = dbFetchOne($stmt);
        $stmt->close();

        if ($company && $company['logo_path']) {
            $logoPath = $company['logo_path'];
        }

        if ($logoFile && $logoFile['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/logos/'; if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $fileName = $companyId . '_' . time() . '_' . basename($logoFile['name']);
            $newLogoPath = $uploadDir . $fileName;

            if (move_uploaded_file($logoFile['tmp_name'], $newLogoPath)) {
                $logoPath = $newLogoPath;
                if ($oldLogo && $oldLogo !== $logoPath && file_exists($oldLogo)) deleteFile($oldLogo);
            } else {
                jsonResponse(false, 'Failed to upload logo file', null, 500);
            }
        }

        try {
            $stmt = $db->prepare("UPDATE company SET company_name = ?, logo_path = ?, map_latitude = ?, map_longitude = ?, updated_by = ?, updated_at = NOW() WHERE company_id = ?");
            $stmt->bind_param("ssssii", $companyName, $logoPath, $mapLat, $mapLong, $currentUserId, $companyId);
            $stmt->execute();
            $stmt->close();

            logActivity('admin', $currentUserId, 'update_company_info', "Updated company info for ID: $companyId");
            jsonResponse(true, 'Company information updated successfully');
        } catch (Exception $e) {
            jsonResponse(false, 'Error: ' . $e->getMessage(), null, 500);
        }
        break;

    case 'get_company':
        $stmt = $db->prepare("SELECT * FROM company WHERE company_id = ?");
        $stmt->bind_param("i", $companyId);
        $stmt->execute();
        $company = dbFetchOne($stmt);
        $stmt->close();

        $stmt = $db->prepare("SELECT * FROM company_addresses WHERE company_id = ? AND is_active = 1 ORDER BY display_order");
        $stmt->bind_param("i", $companyId);
        $stmt->execute();
        $addresses = dbFetchAll($stmt);
        $stmt->close();

        $stmt = $db->prepare("SELECT * FROM company_contacts WHERE company_id = ? AND is_active = 1 ORDER BY display_order");
        $stmt->bind_param("i", $companyId);
        $stmt->execute();
        $contacts = dbFetchAll($stmt);
        $stmt->close();

        $stmt = $db->prepare("SELECT * FROM company_emails WHERE company_id = ? AND is_active = 1 ORDER BY display_order");
        $stmt->bind_param("i", $companyId);
        $stmt->execute();
        $emails = dbFetchAll($stmt);
        $stmt->close();

        jsonResponse(true, 'Company fetched', ['company' => $company, 'addresses' => $addresses, 'contacts' => $contacts, 'emails' => $emails]);
        break;

    case 'get_user':
        $userId = $_POST['user_id'] ?? null;
        if (!$userId) jsonResponse(false, 'User ID required', null, 400);
        $stmt = $db->prepare("SELECT * FROM users WHERE user_id = ? AND company_id = ? LIMIT 1");
        $stmt->bind_param("ii", $userId, $companyId);
        $stmt->execute();
        $user = dbFetchOne($stmt);
        $stmt->close();
        if (!$user) jsonResponse(false, 'User not found', null, 404);
        jsonResponse(true, 'User fetched', ['user' => $user]);
        break;

    case 'get_product':
        $productId = $_POST['product_id'] ?? null;
        if (!$productId) jsonResponse(false, 'Product ID required', null, 400);
        $stmt = $db->prepare("SELECT * FROM products WHERE product_id = ? AND company_id = ? LIMIT 1");
        $stmt->bind_param("ii", $productId, $companyId);
        $stmt->execute();
        $product = dbFetchOne($stmt);
        $stmt->close();
        if (!$product) jsonResponse(false, 'Product not found', null, 404);
        jsonResponse(true, 'Product fetched', ['product' => $product]);
        break;

    // Product aliases
    case 'create_product':
    case 'update_product':
    case 'save_product':
        if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
            jsonResponse(false, 'Please login first', null, 401);
        }

        $productId = $_POST['product_id'] ?? null;
        $name = sanitize($_POST['product_name'] ?? '');
        $desc = sanitize($_POST['product_description'] ?? $_POST['description'] ?? '');
        $alt = sanitize($_POST['image_alt_text'] ?? '');
        $productType = sanitize($_POST['product_type'] ?? 'image');
        $imageFile = $_FILES['product_image'] ?? $_FILES['image'] ?? null;
        $imagePath = $_POST['old_image_path'] ?? null;

        if (empty($name)) {
            jsonResponse(false, 'Product name is required', null, 400);
        }

        if ($productId) {
            $stmt = $db->prepare("SELECT image_path FROM products WHERE product_id = ? AND company_id = ?");
            $stmt->bind_param("ii", $productId, $companyId);
            $stmt->execute();
            $product = dbFetchOne($stmt);
            $stmt->close();

            if (!$product) {
                jsonResponse(false, 'Product not found or access denied', null, 403);
            }
            $imagePath = $product['image_path'];

            if ($imageFile && $imageFile['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '../uploads/products/'; if (!is_dir($uploadDir)) mkdir($uploadDir,0755,true);
                $fileName = $companyId . '_' . $productId . '_' . time() . '_' . basename($imageFile['name']);
                $newImagePath = $uploadDir . $fileName;

                if (move_uploaded_file($imageFile['tmp_name'], $newImagePath)) {
                    if ($imagePath && file_exists($imagePath)) deleteFile($imagePath);
                    $imagePath = $newImagePath;
                } else {
                    jsonResponse(false, 'Failed to upload product image', null, 500);
                }
            }

            $stmt = $db->prepare("UPDATE products SET product_name = ?, product_description = ?, image_path = ?, image_alt_text = ?, product_type = ?, updated_by = ?, updated_at = NOW() WHERE product_id = ?");
            $stmt->bind_param("sssssii", $name, $desc, $imagePath, $alt, $productType, $currentUserId, $productId);
            $stmt->execute();
            $stmt->close();

            logActivity('admin', $currentUserId, 'update_product', "Updated product ID: $productId");
            jsonResponse(true, 'Product updated successfully');
        } else {
            if (!$imageFile || $imageFile['error'] !== UPLOAD_ERR_OK) {
                jsonResponse(false, 'Product image is required for new product', null, 400);
            }

            $stmt = $db->prepare("INSERT INTO products (company_id, product_name, product_description, image_alt_text, product_type, created_by) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssi", $companyId, $name, $desc, $alt, $productType, $currentUserId);
            $stmt->execute();
            $newProductId = $db->insert_id;
            $stmt->close();

            if ($newProductId) {
                $uploadDir = '../uploads/products/'; if (!is_dir($uploadDir)) mkdir($uploadDir,0755,true);
                $fileName = $companyId . '_' . $newProductId . '_' . time() . '_' . basename($imageFile['name']);
                $imagePath = $uploadDir . $fileName;

                if (move_uploaded_file($imageFile['tmp_name'], $imagePath)) {
                    $stmt = $db->prepare("UPDATE products SET image_path = ? WHERE product_id = ?");
                    $stmt->bind_param("si", $imagePath, $newProductId);
                    $stmt->execute();
                    $stmt->close();

                    logActivity('admin', $currentUserId, 'create_product', "Created new product ID: $newProductId");
                    jsonResponse(true, 'Product created successfully');
                } else {
                    $db->query("DELETE FROM products WHERE product_id = $newProductId");
                    jsonResponse(false, 'Failed to upload product image', null, 500);
                }
            } else {
                jsonResponse(false, 'Failed to create product entry', null, 500);
            }
        }
        break;

    case 'delete_product':
        if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
            jsonResponse(false, 'Please login first', null, 401);
        }

        $productId = $_POST['product_id'] ?? null;
        if (!$productId) {
            jsonResponse(false, 'Product ID is required', null, 400);
        }

        try {
            $stmt = $db->prepare("SELECT image_path FROM products WHERE product_id = ? AND company_id = ?");
            $stmt->bind_param("ii", $productId, $companyId);
            $stmt->execute();
            $product = dbFetchOne($stmt);
            $stmt->close();

            if (!$product) {
                jsonResponse(false, 'Product not found or access denied', null, 403);
            }

            if ($product['image_path'] && file_exists($product['image_path'])) deleteFile($product['image_path']);

            $stmt = $db->prepare("UPDATE products SET is_active = 0 WHERE product_id = ?");
            $stmt->bind_param("i", $productId);
            $stmt->execute();
            $stmt->close();

            logActivity('admin', $currentUserId, 'delete_product', "Deleted product ID: $productId");
            jsonResponse(true, 'Product deleted successfully');
        } catch (Exception $e) {
            jsonResponse(false, 'Error: ' . $e->getMessage(), null, 500);
        }
        break;

    // Social media CRUD for admin/company
    case 'create_social':
    case 'update_social':
        if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
            jsonResponse(false, 'Please login first', null, 401);
        }
        $type = $_POST['type'] ?? 'admin';
        $socialId = $_POST['social_id'] ?? null;
        $platformName = sanitize($_POST['platform_name'] ?? '');
        $url = sanitize($_POST['url'] ?? '');
        $displayOrder = intval($_POST['display_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? intval($_POST['is_active']) : 1;
        $iconFile = $_FILES['platform_icon'] ?? null;

        if (empty($platformName) || empty($url)) jsonResponse(false, 'Platform name and URL are required', null, 400);

        if ($type === 'company') { $table = 'company_social_media'; $ownerField = 'company_id'; $ownerId = $companyId; } else { $table = 'admin_social_media'; $ownerField = 'admin_id'; $ownerId = $currentUserId; }

        $iconPath = null;
        if ($iconFile && $iconFile['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/socials/'; if (!is_dir($uploadDir)) mkdir($uploadDir,0755,true);
            $fileName = $ownerId . '_' . time() . '_' . basename($iconFile['name']);
            $newPath = $uploadDir . $fileName;
            if (move_uploaded_file($iconFile['tmp_name'], $newPath)) $iconPath = $newPath;
        }

        try {
            if ($socialId) {
                $stmt = $db->prepare("SELECT * FROM $table WHERE social_media_id = ? AND $ownerField = ? LIMIT 1");
                $stmt->bind_param("ii", $socialId, $ownerId);
                $stmt->execute();
                $existing = dbFetchOne($stmt);
                $stmt->close();

                if (!$existing) jsonResponse(false, 'Social media not found or access denied', null, 403);

                if ($iconPath) {
                    if (!empty($existing['platform_icon']) && file_exists($existing['platform_icon'])) deleteFile($existing['platform_icon']);
                    $stmt = $db->prepare("UPDATE $table SET platform_name = ?, url = ?, platform_icon = ?, display_order = ?, is_active = ?, updated_at = NOW() WHERE social_media_id = ?");
                    $stmt->bind_param("sssiii", $platformName, $url, $iconPath, $displayOrder, $isActive, $socialId);
                } else {
                    $stmt = $db->prepare("UPDATE $table SET platform_name = ?, url = ?, display_order = ?, is_active = ?, updated_at = NOW() WHERE social_media_id = ?");
                    $stmt->bind_param("ssiii", $platformName, $url, $displayOrder, $isActive, $socialId);
                }
                $stmt->execute();
                $stmt->close();

                jsonResponse(true, 'Social media updated');
            } else {
                $stmt = $db->prepare("INSERT INTO $table ($ownerField, platform_name, url, platform_icon, display_order, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $stmt->bind_param("isssii", $ownerId, $platformName, $url, $iconPath, $displayOrder, $isActive);
                $stmt->execute();
                $stmt->close();
                jsonResponse(true, 'Social media added');
            }
        } catch (Exception $e) {
            jsonResponse(false, 'Error: ' . $e->getMessage(), null, 500);
        }
        break;

    case 'get_social':
        $type = $_POST['type'] ?? 'admin';
        $socialId = $_POST['social_id'] ?? null;
        if (!$socialId) jsonResponse(false, 'Social ID required', null, 400);
        if ($type === 'company') { $table = 'company_social_media'; $ownerField = 'company_id'; $ownerId = $companyId; } else { $table = 'admin_social_media'; $ownerField = 'admin_id'; $ownerId = $currentUserId; }
        $stmt = $db->prepare("SELECT * FROM $table WHERE social_media_id = ? AND $ownerField = ? LIMIT 1");
        $stmt->bind_param("ii", $socialId, $ownerId);
        $stmt->execute();
        $social = dbFetchOne($stmt);
        $stmt->close();
        if (!$social) jsonResponse(false, 'Social media not found', null, 404);
        jsonResponse(true, 'Social fetched', ['social' => $social]);
        break;

    case 'delete_social':
        $type = $_POST['type'] ?? 'admin';
        $socialId = $_POST['social_id'] ?? null;
        if (!$socialId) jsonResponse(false, 'Social ID required', null, 400);
        if ($type === 'company') { $table = 'company_social_media'; $ownerField = 'company_id'; $ownerId = $companyId; } else { $table = 'admin_social_media'; $ownerField = 'admin_id'; $ownerId = $currentUserId; }
        $stmt = $db->prepare("SELECT * FROM $table WHERE social_media_id = ? AND $ownerField = ? LIMIT 1");
        $stmt->bind_param("ii", $socialId, $ownerId);
        $stmt->execute();
        $s = dbFetchOne($stmt);
        $stmt->close();
        if (!$s) jsonResponse(false, 'Social media not found', null, 404);
        $stmt = $db->prepare("UPDATE $table SET is_active = 0 WHERE social_media_id = ?");
        $stmt->bind_param("i", $socialId);
        $stmt->execute();
        $stmt->close();
        jsonResponse(true, 'Social media deleted');
        break;

    case 'register_nfc_manual':
        if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) jsonResponse(false, 'Please login first', null, 401);
        $userId = $_POST['user_id'] ?? null;
        $nfcUid = sanitize($_POST['nfc_uid'] ?? '');
        if (!$userId || empty($nfcUid)) jsonResponse(false, 'User ID and NFC UID required', null, 400);
        $stmt = $db->prepare("SELECT user_id FROM users WHERE user_id = ? AND company_id = ?");
        $stmt->bind_param("ii", $userId, $companyId);
        $stmt->execute();
        $u = dbFetchOne($stmt);
        $stmt->close();
        if (!$u) jsonResponse(false, 'User not found or access denied', null, 403);
        $stmt = $db->prepare("UPDATE users SET nfc_uid = ? WHERE user_id = ?");
        $stmt->bind_param("si", $nfcUid, $userId);
        $stmt->execute();
        $stmt->close();
        jsonResponse(true, 'NFC UID registered');
        break;

    case 'upload_profile_picture':
        if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) jsonResponse(false, 'Please login first', null, 401);
        $file = $_FILES['profile_picture'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) jsonResponse(false, 'No file uploaded', null, 400);
        $uploadDir = '../uploads/profiles/'; if (!is_dir($uploadDir)) mkdir($uploadDir,0755,true);
        $fileName = $currentUserId . '_' . time() . '_' . basename($file['name']);
        $dest = $uploadDir . $fileName;
        if (!move_uploaded_file($file['tmp_name'], $dest)) jsonResponse(false, 'Failed to move uploaded file', null, 500);
        $stmt = $db->prepare("UPDATE admins SET profile_picture = ? WHERE admin_id = ?");
        $stmt->bind_param("si", $dest, $currentUserId);
        $stmt->execute();
        $stmt->close();
        jsonResponse(true, 'Profile picture uploaded', ['path' => $dest]);
        break;

    default:
        jsonResponse(false, 'Invalid action', null, 400);
}