<?php
/**
 * Logout Page
 * Calling Card System
 */

require_once 'config/config.php';
require_once 'includes/functions.php';

// Log activity before logout
if (isLoggedIn()) {
    logActivity(getCurrentUserType(), getCurrentUserId(), 'logout', 'User logged out');
}

// Destroy session
session_destroy();

// Redirect to login
header('Location: ' . SITE_URL . 'index.php');
exit;

