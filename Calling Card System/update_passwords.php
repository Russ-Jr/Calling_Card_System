<?php
/**
 * Password Update Script
 * Calling Card System
 * 
 * This script updates passwords for admins and users in the database.
 * Run this script once to set initial passwords or update existing ones.
 */

$servername = "localhost";
$username = "ndasphilsinc";
$password = "%aa}gX)ig=Yh";
$database = "ndasphilsinc_callingcard_db"; 

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8mb4");

// Define users and their passwords
$users = [
    'superadmin' => 'superadmin123',  // Super admin default password     // Example admin password
    // Add more admins here as needed
    // 'admin2' => 'password2',
];

echo "<h2>Updating Admin Passwords</h2>";

// Loop through the users and hash their passwords
foreach ($users as $username => $plainPassword) {
    $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);
    
    $sql = "UPDATE admins SET password = ? WHERE username = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("ss", $hashedPassword, $username);
        
        if ($stmt->execute()) {
            echo "✓ Password for <strong>$username</strong> successfully updated.<br>";
        } else {
            echo "✗ Error updating password for <strong>$username</strong>: " . $stmt->error . "<br>";
        }
        
        $stmt->close();
    } else {
        echo "✗ Error preparing statement for <strong>$username</strong>: " . $conn->error . "<br>";
    }
}

echo "<br><h2>Password Update Complete</h2>";
echo "<p><strong>Note:</strong> Please delete or secure this file after use for security purposes.</p>";

$conn->close();

?>

