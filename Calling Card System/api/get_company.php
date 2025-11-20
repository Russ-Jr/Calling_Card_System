<?php
/**
 * Get Company API
 * Calling Card System
 */

require_once '../config/config.php';
require_once '../includes/functions.php';

requireSuperAdmin();

header('Content-Type: application/json');

$id = $_GET['id'] ?? null;

if (!$id) {
    jsonResponse(false, 'Company ID is required', null, 400);
}

$db = getDB();
$stmt = $db->prepare("SELECT * FROM company WHERE company_id = ?");
$stmt->bind_param('i', $id); // MySQLi bind
$stmt->execute();
$company = dbFetchOne($stmt); // Use helper to fetch and free result
$stmt->close();

if ($company) {
    jsonResponse(true, 'Company found', $company);
} else {
    jsonResponse(false, 'Company not found', null, 404);
}
?>