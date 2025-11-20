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
    case 'admin_login':
        // Admin login for account tab
        $username = sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            jsonResponse(false, 'Username and password are required', null, 400);
        }
        
        // Use username to find the admin instead of current session ID, for better security/context
        $stmt = $db->prepare("SELECT admin_id, password FROM admins WHERE username = ? AND company_id = ? AND is_active = 1");
        // FIX: Replaced execute with bind_param
        $stmt->bind_param("si", $username, $companyId);
        $stmt->execute();
        $admin = dbFetchOne($stmt);
        $stmt->close();
        
        if ($admin && verifyPassword($password, $admin['password'])) {
            // Re-validate the admin ID, in case the session has expired/changed
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
            $stmt = $db->prepare("
                UPDATE admins 
                SET first_name=?, middle_name=?, last_name=?, email=?, contact_number=?, updated_at=NOW()
                WHERE admin_id=?
            ");
            // FIX: Replaced execute with bind_param
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
            // Verify current password
            $stmt = $db->prepare("SELECT password FROM admins WHERE admin_id = ?");
            // FIX: Replaced execute with bind_param
            $stmt->bind_param("i", $adminId);
            $stmt->execute();
            $admin = dbFetchOne($stmt);
            $stmt->close();
            
            if (!$admin || !verifyPassword($currentPassword, $admin['password'])) {
                jsonResponse(false, 'Invalid current password', null, 401);
            }
            
            // Update password
            $hashed = hashPassword($newPassword);
            $stmt = $db->prepare("UPDATE admins SET password = ?, updated_at = NOW() WHERE admin_id = ?");
            // FIX: Replaced execute with bind_param
            $stmt->bind_param("si", $hashed, $adminId);
            $stmt->execute();
            $stmt->close();

            logActivity('admin', $adminId, 'change_password', 'Admin changed password');
            jsonResponse(true, 'Password changed successfully');
        } catch (Exception $e) {
            jsonResponse(false, 'Error: ' . $e->getMessage(), null, 500);
        }
        break;

    case 'save_user':
        // Requires admin to be logged in via account tab
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
        $hashed = '';
        
        if (empty($firstName) || empty($lastName) || empty($email)) {
            jsonResponse(false, 'Required fields are missing', null, 400);
        }

        try {
            if ($userId) {
                // Update existing user
                
                // Email uniqueness check (excluding current user)
                $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND user_id != ?");
                $stmt->bind_param("si", $email, $userId);
                $stmt->execute();
                $emailExists = dbFetchColumn($stmt); // FIX: Used dbFetchColumn
                $stmt->close();
                
                if ($emailExists > 0) {
                    jsonResponse(false, 'Email already exists for another user', null, 400);
                }

                if (!empty($password)) {
                    $hashed = hashPassword($password);
                    $stmt = $db->prepare("
                        UPDATE users 
                        SET first_name=?, middle_name=?, last_name=?, email=?, contact_number=?, password=?, updated_at=NOW()
                        WHERE user_id=? AND company_id=?
                    ");
                    // FIX: Replaced execute with bind_param
                    $stmt->bind_param("ssssssii", $firstName, $middleName, $lastName, $email, $contact, $hashed, $userId, $companyId);
                } else {
                    $stmt = $db->prepare("
                        UPDATE users 
                        SET first_name=?, middle_name=?, last_name=?, email=?, contact_number=?, updated_at=NOW()
                        WHERE user_id=? AND company_id=?
                    ");
                    // FIX: Replaced execute with bind_param
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
                
                // Email uniqueness check
                $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $emailExists = dbFetchColumn($stmt); // FIX: Used dbFetchColumn
                $stmt->close();
                
                if ($emailExists > 0) {
                    jsonResponse(false, 'Email already exists', null, 400);
                }

                $stmt = $db->prepare("
                    INSERT INTO users (company_id, username, password, first_name, middle_name, last_name, email, contact_number, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $hashed = hashPassword($password);
                // FIX: Replaced execute with bind_param
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
            // Verify user belongs to admin's company
            $stmt = $db->prepare("SELECT user_id FROM users WHERE user_id = ? AND company_id = ?");
            // FIX: Replaced execute with bind_param
            $stmt->bind_param("ii", $userId, $companyId);
            $stmt->execute();
            $user = dbFetchOne($stmt); // FIX: Used dbFetchOne
            $stmt->close();
            
            if (!$user) {
                jsonResponse(false, 'User not found or access denied', null, 403);
            }
            
            $stmt = $db->prepare("UPDATE users SET is_active = 0 WHERE user_id = ?");
            // FIX: Replaced execute with bind_param
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $stmt->close();
            
            logActivity('admin', $currentUserId, 'delete_user', "Deleted user ID: $userId");
            jsonResponse(true, 'Card holder deleted successfully');
        } catch (Exception $e) {
            jsonResponse(false, 'Error: ' . $e->getMessage(), null, 500);
        }
        break;

    case 'save_company_info':
        if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
            jsonResponse(false, 'Please login first', null, 401);
        }
        
        $logoFile = $_FILES['company_logo'] ?? null;
        $logoPath = null;
        $companyName = sanitize($_POST['company_name'] ?? '');
        $mapLat = $_POST['map_latitude'] ?? null;
        $mapLong = $_POST['map_longitude'] ?? null;
        $oldLogo = $_POST['old_logo_path'] ?? null;

        // Fetch current company info for old logo path
        $stmt = $db->prepare("SELECT logo_path FROM company WHERE company_id = ?");
        // FIX: Replaced execute with bind_param
        $stmt->bind_param("i", $companyId);
        $stmt->execute();
        $company = dbFetchOne($stmt); // FIX: Used dbFetchOne
        $stmt->close();
        
        if ($company && $company['logo_path']) {
            $logoPath = $company['logo_path'];
        }

        if ($logoFile && $logoFile['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/logos/';
            $fileName = $companyId . '_' . time() . '_' . basename($logoFile['name']);
            $newLogoPath = $uploadDir . $fileName;

            if (move_uploaded_file($logoFile['tmp_name'], $newLogoPath)) {
                $logoPath = $newLogoPath;
                // Delete old logo if it's different and exists
                if ($oldLogo && $oldLogo !== $logoPath && file_exists($oldLogo)) {
                    deleteFile($oldLogo);
                }
            } else {
                jsonResponse(false, 'Failed to upload logo file', null, 500);
            }
        }
        
        try {
            $stmt = $db->prepare("
                UPDATE company 
                SET company_name = ?, logo_path = ?, map_latitude = ?, map_longitude = ?, updated_by = ?, updated_at = NOW()
                WHERE company_id = ?
            ");
            // FIX: Replaced execute with bind_param (Note: map lat/long should be treated as strings 's' in bind_param if they are floating point numbers)
            $stmt->bind_param("ssddii", $companyName, $logoPath, $mapLat, $mapLong, $currentUserId, $companyId);
            $stmt->execute();
            $stmt->close();

            logActivity('admin', $currentUserId, 'update_company_info', "Updated company info for ID: $companyId");
            jsonResponse(true, 'Company information updated successfully');
        } catch (Exception $e) {
            jsonResponse(false, 'Error: ' . $e->getMessage(), null, 500);
        }
        break;

    case 'save_product':
        if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
            jsonResponse(false, 'Please login first', null, 401);
        }
        
        $productId = $_POST['product_id'] ?? null;
        $name = sanitize($_POST['product_name'] ?? '');
        $desc = sanitize($_POST['product_description'] ?? '');
        $alt = sanitize($_POST['image_alt_text'] ?? '');
        $productType = sanitize($_POST['product_type'] ?? 'image');
        $imageFile = $_FILES['product_image'] ?? null;
        $imagePath = $_POST['old_image_path'] ?? null;
        
        if (empty($name)) {
            jsonResponse(false, 'Product name is required', null, 400);
        }

        if ($productId) {
            // Verify product belongs to admin's company and fetch old path
            $stmt = $db->prepare("SELECT image_path FROM products WHERE product_id = ? AND company_id = ?");
            // FIX: Replaced execute with bind_param
            $stmt->bind_param("ii", $productId, $companyId);
            $stmt->execute();
            $product = dbFetchOne($stmt); // FIX: Used dbFetchOne
            $stmt->close();

            if (!$product) {
                jsonResponse(false, 'Product not found or access denied', null, 403);
            }
            $imagePath = $product['image_path'];
            
            // Handle file upload
            if ($imageFile && $imageFile['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '../uploads/products/';
                $fileName = $companyId . '_' . $productId . '_' . time() . '_' . basename($imageFile['name']);
                $newImagePath = $uploadDir . $fileName;

                if (move_uploaded_file($imageFile['tmp_name'], $newImagePath)) {
                    // Delete old file if it exists
                    if ($imagePath && file_exists($imagePath)) {
                        deleteFile($imagePath);
                    }
                    $imagePath = $newImagePath;
                } else {
                    jsonResponse(false, 'Failed to upload product image', null, 500);
                }
            }

            $stmt = $db->prepare("
                UPDATE products 
                SET product_name = ?, product_description = ?, image_path = ?, image_alt_text = ?, product_type = ?, updated_by = ?, updated_at = NOW()
                WHERE product_id = ?
            ");
            // FIX: Replaced execute with bind_param
            $stmt->bind_param("sssssii", $name, $desc, $imagePath, $alt, $productType, $currentUserId, $productId);
            $stmt->execute();
            $stmt->close();

            logActivity('admin', $currentUserId, 'update_product', "Updated product ID: $productId");
            jsonResponse(true, 'Product updated successfully');
        } else {
            // Create new product
            if (!$imageFile || $imageFile['error'] !== UPLOAD_ERR_OK) {
                jsonResponse(false, 'Product image is required for new product', null, 400);
            }
            
            // Insert the product first to get an ID
            $stmt = $db->prepare("
                INSERT INTO products (company_id, product_name, product_description, image_alt_text, product_type, created_by)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            // FIX: Replaced execute with bind_param
            $stmt->bind_param("issssi", $companyId, $name, $desc, $alt, $productType, $currentUserId);
            $stmt->execute();
            $newProductId = $db->insert_id;
            $stmt->close();

            // Handle file upload using the new ID
            if ($newProductId) {
                $uploadDir = '../uploads/products/';
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
                    // Cleanup product entry if file upload failed
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
            // Verify product belongs to admin's company
            $stmt = $db->prepare("SELECT image_path FROM products WHERE product_id = ? AND company_id = ?");
            // FIX: Replaced execute with bind_param
            $stmt->bind_param("ii", $productId, $companyId);
            $stmt->execute();
            $product = dbFetchOne($stmt); // FIX: Used dbFetchOne
            $stmt->close();
            
            if (!$product) {
                jsonResponse(false, 'Product not found or access denied', null, 403);
            }
            
            // Delete file
            if ($product['image_path'] && file_exists($product['image_path'])) {
                deleteFile($product['image_path']);
            }
            
            $stmt = $db->prepare("UPDATE products SET is_active = 0 WHERE product_id = ?");
            // FIX: Replaced execute with bind_param
            $stmt->bind_param("i", $productId);
            $stmt->execute();
            $stmt->close();
            
            logActivity('admin', $currentUserId, 'delete_product', "Deleted product ID: $productId");
            jsonResponse(true, 'Product deleted successfully');
        } catch (Exception $e) {
            jsonResponse(false, 'Error: ' . $e->getMessage(), null, 500);
        }
        break;
        
    default:
        jsonResponse(false, 'Invalid action', null, 400);
}
?>