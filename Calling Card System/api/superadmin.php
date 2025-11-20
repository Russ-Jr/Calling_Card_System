<?php
/**
 * Super Admin API Endpoints
 * Calling Card System
 */

require_once '../config/config.php';
require_once '../includes/functions.php';

requireSuperAdmin();

header('Content-Type: application/json');

$db = getDB();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'save_company':
        $companyId = $_POST['company_id'] ?? null;
        $companyName = sanitize($_POST['company_name'] ?? '');
        $userId = getCurrentUserId();
        
        if (empty($companyName)) {
            jsonResponse(false, 'Company name is required', null, 400);
        }
        
        try {
            if ($companyId) {
                // Update existing company
                $stmt = $db->prepare("
                    UPDATE company 
                    SET company_name = ?, updated_by = ?, updated_at = NOW()
                    WHERE company_id = ?
                ");
                $stmt->bind_param("sii", $companyName, $userId, $companyId);
                $stmt->execute();
                $stmt->close();
                
                logActivity('admin', $userId, 'update_company', "Updated company: $companyName");
                jsonResponse(true, 'Company updated successfully');
            } else {
                // Create new company
                $stmt = $db->prepare("
                    INSERT INTO company (company_name, created_by)
                    VALUES (?, ?)
                ");
                $stmt->bind_param("si", $companyName, $userId);
                $stmt->execute();
                $stmt->close();
                
                logActivity('admin', $userId, 'create_company', "Created company: $companyName");
                jsonResponse(true, 'Company created successfully');
            }
        } catch (Exception $e) {
            jsonResponse(false, 'Error: ' . $e->getMessage(), null, 500);
        }
        break;
        
    case 'delete_company':
        $companyId = $_POST['company_id'] ?? null;
        
        if (!$companyId) {
            jsonResponse(false, 'Company ID is required', null, 400);
        }
        
        try {
            // Check if company has admins
            $stmt = $db->prepare("SELECT COUNT(*) FROM admins WHERE company_id = ? AND is_active = 1");
            $stmt->bind_param("i", $companyId);
            $stmt->execute();
            $adminCount = dbFetchColumn($stmt);
            $stmt->close();
            
            // Check if company has users
            $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE company_id = ? AND is_active = 1");
            $stmt->bind_param("i", $companyId);
            $stmt->execute();
            $userCount = dbFetchColumn($stmt);
            $stmt->close();
            
            if ($adminCount > 0 || $userCount > 0) {
                jsonResponse(false, 'Cannot delete company with active admins or users', null, 400);
            }
            
            $stmt = $db->prepare("UPDATE company SET is_active = 0 WHERE company_id = ?");
            $stmt->bind_param("i", $companyId);
            $stmt->execute();
            $stmt->close();
            
            logActivity('admin', getCurrentUserId(), 'delete_company', "Deleted company ID: $companyId");
            jsonResponse(true, 'Company deleted successfully');
        } catch (Exception $e) {
            jsonResponse(false, 'Error: ' . $e->getMessage(), null, 500);
        }
        break;
        
    case 'save_admin':
        $adminId = $_POST['admin_id'] ?? null;
        $companyId = $_POST['company_id'] ?? null;
        $username = sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $firstName = sanitize($_POST['first_name'] ?? '');
        $middleName = sanitize($_POST['middle_name'] ?? '');
        $lastName = sanitize($_POST['last_name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $contact = sanitize($_POST['contact_number'] ?? '');
        $currentUserId = getCurrentUserId();
        
        if (empty($username) || empty($firstName) || empty($lastName) || empty($email)) {
            jsonResponse(false, 'Required fields are missing', null, 400);
        }
        
        if (!$companyId) {
            jsonResponse(false, 'Company is required', null, 400);
        }
        
        if (!isValidEmail($email)) {
            jsonResponse(false, 'Invalid email address', null, 400);
        }
        
        try {
            if ($adminId) {
                // Update existing admin
                if (!empty($password)) {
                    // Update WITH password
                    $hashed = hashPassword($password);
                    $stmt = $db->prepare("
                        UPDATE admins 
                        SET company_id=?, username=?, first_name=?, middle_name=?, last_name=?, email=?, contact_number=?, password=?, updated_at=NOW()
                        WHERE admin_id=?
                    ");
                    // Types: i (company), s (user), s (first), s (mid), s (last), s (email), s (contact), s (pass), i (id)
                    $stmt->bind_param("isssssssi", $companyId, $username, $firstName, $middleName, $lastName, $email, $contact, $hashed, $adminId);
                } else {
                    // Update WITHOUT password
                    $stmt = $db->prepare("
                        UPDATE admins 
                        SET company_id=?, username=?, first_name=?, middle_name=?, last_name=?, email=?, contact_number=?, updated_at=NOW()
                        WHERE admin_id=?
                    ");
                    // Types: i (company), s (user), s (first), s (mid), s (last), s (email), s (contact), i (id)
                    $stmt->bind_param("issssssi", $companyId, $username, $firstName, $middleName, $lastName, $email, $contact, $adminId);
                }
                $stmt->execute();
                $stmt->close();
                
                logActivity('admin', $currentUserId, 'update_admin', "Updated admin: $username");
                jsonResponse(true, 'Admin updated successfully');
            } else {
                // Create new admin
                if (empty($password)) {
                    jsonResponse(false, 'Password is required for new admin', null, 400);
                }
                
                // Check if username exists
                $stmt = $db->prepare("SELECT COUNT(*) FROM admins WHERE username = ?");
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $exists = dbFetchColumn($stmt);
                $stmt->close();
                
                if ($exists > 0) {
                    jsonResponse(false, 'Username already exists', null, 400);
                }
                
                // Check if email exists
                $stmt = $db->prepare("SELECT COUNT(*) FROM admins WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $emailExists = dbFetchColumn($stmt);
                $stmt->close();
                
                if ($emailExists > 0) {
                    jsonResponse(false, 'Email already exists', null, 400);
                }
                
                $stmt = $db->prepare("
                    INSERT INTO admins (company_id, username, password, first_name, middle_name, last_name, email, contact_number, role, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'admin', ?)
                ");
                $hashed = hashPassword($password);
                // Types: i (comp), s (user), s (pass), s (first), s (mid), s (last), s (email), s (contact), i (creator)
                $stmt->bind_param("isssssssi", $companyId, $username, $hashed, $firstName, $middleName, $lastName, $email, $contact, $currentUserId);
                $stmt->execute();
                $stmt->close();
                
                logActivity('admin', $currentUserId, 'create_admin', "Created admin: $username");
                jsonResponse(true, 'Admin created successfully');
            }
        } catch (Exception $e) {
            jsonResponse(false, 'Error: ' . $e->getMessage(), null, 500);
        }
        break;
        
    case 'delete_admin':
        $adminId = $_POST['admin_id'] ?? null;
        
        if (!$adminId) {
            jsonResponse(false, 'Admin ID is required', null, 400);
        }
        
        try {
            $stmt = $db->prepare("UPDATE admins SET is_active = 0 WHERE admin_id = ?");
            $stmt->bind_param("i", $adminId);
            $stmt->execute();
            $stmt->close();
            
            logActivity('admin', getCurrentUserId(), 'delete_admin', "Deleted admin ID: $adminId");
            jsonResponse(true, 'Admin deleted successfully');
        } catch (Exception $e) {
            jsonResponse(false, 'Error: ' . $e->getMessage(), null, 500);
        }
        break;
        
    default:
        jsonResponse(false, 'Invalid action', null, 400);
}
?>