<?php
/**
 * NFC API Endpoints
 * Calling Card System
 * For VB.NET NFC Bridge Integration
 */

require_once '../config/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// This API will be called by VB.NET application for NFC operations
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'register_nfc':
        // Register NFC UID for a user
        requireAdmin();
        
        $userId = $_POST['user_id'] ?? null;
        $nfcUid = sanitize($_POST['nfc_uid'] ?? '');
        
        if (!$userId || empty($nfcUid)) {
            jsonResponse(false, 'User ID and NFC UID are required', null, 400);
        }
        
        $db = getDB();
        $companyId = getCurrentCompanyId();
        
        // Verify user belongs to admin's company
        $stmt = $db->prepare("SELECT * FROM users WHERE user_id = ? AND company_id = ? AND is_active = 1");
        $stmt->execute([$userId, $companyId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            jsonResponse(false, 'User not found or access denied', null, 403);
        }
        
        // Check if NFC UID already exists
        $stmt = $db->prepare("SELECT user_id FROM users WHERE nfc_uid = ? AND user_id != ?");
        $stmt->execute([$nfcUid, $userId]);
        if ($stmt->fetch()) {
            jsonResponse(false, 'NFC UID already registered to another user', null, 400);
        }
        
        try {
            // Generate NDEF URL
            $ndefUrl = generateNDEFUrl($user['first_name'], $user['last_name'], $user['user_id']);
            
            // Update user with NFC UID and NDEF URL
            $stmt = $db->prepare("
                UPDATE users 
                SET nfc_uid = ?, ndef_url = ?, updated_at = NOW()
                WHERE user_id = ?
            ");
            $stmt->execute([$nfcUid, $ndefUrl, $userId]);
            
            logActivity('admin', getCurrentUserId(), 'register_nfc', "Registered NFC card for user ID: $userId");
            
            // Return NDEF URL for VB.NET to write to card
            jsonResponse(true, 'NFC registered successfully', [
                'ndef_url' => $ndefUrl,
                'encrypted_data' => parseNDEFData($ndefUrl) // This needs to be adjusted based on your encryption
            ]);
        } catch (Exception $e) {
            jsonResponse(false, 'Error: ' . $e->getMessage(), null, 500);
        }
        break;
        
    case 'get_user_by_uid':
        // Get user by NFC UID (for verification)
        $nfcUid = sanitize($_GET['uid'] ?? '');
        
        if (empty($nfcUid)) {
            jsonResponse(false, 'NFC UID is required', null, 400);
        }
        
        $db = getDB();
        $stmt = $db->prepare("SELECT user_id, first_name, last_name, email FROM users WHERE nfc_uid = ? AND is_active = 1");
        $stmt->execute([$nfcUid]);
        $user = $stmt->fetch();
        
        if ($user) {
            jsonResponse(true, 'User found', $user);
        } else {
            jsonResponse(false, 'User not found', null, 404);
        }
        break;
        
    default:
        jsonResponse(false, 'Invalid action', null, 400);
}

?>