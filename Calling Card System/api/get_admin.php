<?php
/**
 * Get Admin API
 * Calling Card System
 */

require_once '../config/config.php';
require_once '../includes/functions.php';

requireSuperAdmin();

header('Content-Type: application/json');

$id = $_GET['id'] ?? null;

if (!$id) {
    jsonResponse(false, 'Admin ID is required', null, 400);
}

$db = getDB();
$stmt = $db->prepare("SELECT admin_id, username, first_name, middle_name, last_name, email, contact_number, company_id FROM admins WHERE admin_id = ?");
$stmt->bind_param('i', $id); // MySQLi bind
$stmt->execute();
$admin = dbFetchOne($stmt); // Use helper to fetch and free result
$stmt->close();

if ($admin) {
    jsonResponse(true, 'Admin found', $admin);
} else {
    jsonResponse(false, 'Admin not found', null, 404);
}
?>