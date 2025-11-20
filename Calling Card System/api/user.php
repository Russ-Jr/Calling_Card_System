<?php
/**
 * User API Endpoints
 * Calling Card System
 */

require_once '../config/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$db = getDB();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'user_login':
        // User login for account tab
        $username = sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            jsonResponse(false, 'Username and password are required', null, 400);
        }
        
        $stmt = $db->prepare("SELECT user_id, password, company_id FROM users WHERE username = ? AND is_active = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && verifyPassword($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_type'] = 'user';
            $_SESSION['company_id'] = $user['company_id'];
            $_SESSION['user_logged_in'] = true;
            
            // Update last login
            $db->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?")->execute([$user['user_id']]);
            
            logActivity('user', $user['user_id'], 'user_account_login', 'User logged in via account tab');
            jsonResponse(true, 'Login successful');
        } else {
            jsonResponse(false, 'Invalid credentials', null, 401);
        }
        break;
        
    case 'update_profile':
        if (!isLoggedIn() || getCurrentUserType() !== 'user') {
            jsonResponse(false, 'Please login first', null, 401);
        }
        
        $firstName = sanitize($_POST['first_name'] ?? '');
        $middleName = sanitize($_POST['middle_name'] ?? '');
        $lastName = sanitize($_POST['last_name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $contact = sanitize($_POST['contact_number'] ?? '');
        
        if (empty($firstName) || empty($lastName) || empty($email)) {
            jsonResponse(false, 'Required fields are missing', null, 400);
        }
        
        if (!isValidEmail($email)) {
            jsonResponse(false, 'Invalid email address', null, 400);
        }
        
        try {
            $stmt = $db->prepare("
                UPDATE users 
                SET first_name = ?, middle_name = ?, last_name = ?, email = ?, contact_number = ?, updated_at = NOW()
                WHERE user_id = ?
            ");
            $stmt->execute([$firstName, $middleName, $lastName, $email, $contact, getCurrentUserId()]);
            
            logActivity('user', getCurrentUserId(), 'update_profile', 'Updated user profile');
            jsonResponse(true, 'Profile updated successfully');
        } catch (Exception $e) {
            jsonResponse(false, 'Error: ' . $e->getMessage(), null, 500);
        }
        break;
        
    case 'change_password':
        if (!isLoggedIn() || getCurrentUserType() !== 'user') {
            jsonResponse(false, 'Please login first', null, 401);
        }
        
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        
        if (empty($currentPassword) || empty($newPassword)) {
            jsonResponse(false, 'All password fields are required', null, 400);
        }
        
        if (strlen($newPassword) < MIN_PASSWORD_LENGTH) {
            jsonResponse(false, 'Password must be at least ' . MIN_PASSWORD_LENGTH . ' characters', null, 400);
        }
        
        // Verify current password
        $stmt = $db->prepare("SELECT password FROM users WHERE user_id = ?");
        $stmt->execute([getCurrentUserId()]);
        $user = $stmt->fetch();
        
        if (!$user || !verifyPassword($currentPassword, $user['password'])) {
            jsonResponse(false, 'Current password is incorrect', null, 401);
        }
        
        try {
            $stmt = $db->prepare("
                UPDATE users 
                SET password = ?, updated_at = NOW()
                WHERE user_id = ?
            ");
            $stmt->execute([hashPassword($newPassword), getCurrentUserId()]);
            
            logActivity('user', getCurrentUserId(), 'change_password', 'User changed password');
            jsonResponse(true, 'Password changed successfully');
        } catch (Exception $e) {
            jsonResponse(false, 'Error: ' . $e->getMessage(), null, 500);
        }
        break;
        
    default:
        jsonResponse(false, 'Invalid action', null, 400);
}

?>