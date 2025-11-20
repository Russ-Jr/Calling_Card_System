<?php
/**
 * Password Hash Generator
 * Run this script to generate bcrypt hashes for passwords
 */

// Generate hash for superadmin password
$password = 'superadmin123';
$hash = password_hash($password, PASSWORD_BCRYPT);

echo "Password: " . $password . "\n";
echo "Hash: " . $hash . "\n\n";

// Verify the hash
if (password_verify($password, $hash)) {
    echo "✓ Hash verification successful!\n";
} else {
    echo "✗ Hash verification failed!\n";
}

echo "\n";
echo "Copy this hash to database_schema.sql:\n";
echo $hash . "\n";

