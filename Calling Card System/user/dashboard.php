<?php
/**
 * User Dashboard
 * Calling Card System - Mobile Portrait Design
 */

require_once '../config/config.php';
require_once '../includes/functions.php';

// Get user data from NDEF URL or session
$user = null;
$company = null;
$userData = null;

if (isset($_GET['data'])) {
    // Decrypt NDEF data
    $encryptedData = $_GET['data'];
    $decryptedData = parseNDEFData($encryptedData);
    
    if ($decryptedData) {
        // Extract user identifier from decrypted data
        // Format: firstname+lastname+user_id
        $db = getDB();
        
        // Try to find user by matching the pattern
        // This is a simplified approach - you may need to adjust based on your encryption
        $stmt = $db->query("SELECT * FROM users WHERE is_active = 1");
        $allUsers = $stmt->fetchAll();
        
        foreach ($allUsers as $u) {
            $userIdentifier = strtolower(str_replace(' ', '', $u['first_name']) . str_replace(' ', '', $u['last_name']) . $u['user_id']);
            if ($userIdentifier === $decryptedData) {
                $user = $u;
                break;
            }
        }
    }
} elseif (isLoggedIn() && getCurrentUserType() === 'user') {
    // User is logged in via account tab
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE user_id = ? AND is_active = 1");
    $stmt->execute([getCurrentUserId()]);
    $user = $stmt->fetch();
}

if (!$user) {
    die('User not found or invalid access');
}

// Get company info
$db = getDB();
$stmt = $db->prepare("SELECT * FROM company WHERE company_id = ? AND is_active = 1");
$stmt->execute([$user['company_id']]);
$company = $stmt->fetch();

// Get user social media
$userSocialMedia = $db->prepare("SELECT * FROM user_social_media WHERE user_id = ? AND is_active = 1 ORDER BY display_order");
$userSocialMedia->execute([$user['user_id']]);
$userSocialMedia = $userSocialMedia->fetchAll();

// Get company addresses
$companyAddresses = $db->prepare("SELECT * FROM company_addresses WHERE company_id = ? AND is_active = 1 ORDER BY display_order");
$companyAddresses->execute([$user['company_id']]);
$companyAddresses = $companyAddresses->fetchAll();

// Get company contacts
$companyContacts = $db->prepare("SELECT * FROM company_contacts WHERE company_id = ? AND is_active = 1 ORDER BY display_order");
$companyContacts->execute([$user['company_id']]);
$companyContacts = $companyContacts->fetchAll();

// Get company emails
$companyEmails = $db->prepare("SELECT * FROM company_emails WHERE company_id = ? AND is_active = 1 ORDER BY display_order");
$companyEmails->execute([$user['company_id']]);
$companyEmails = $companyEmails->fetchAll();

// Get company social media
$companySocialMedia = $db->prepare("SELECT * FROM company_social_media WHERE company_id = ? AND is_active = 1 ORDER BY display_order");
$companySocialMedia->execute([$user['company_id']]);
$companySocialMedia = $companySocialMedia->fetchAll();

// Get products
$products = $db->prepare("SELECT * FROM products WHERE company_id = ? AND is_active = 1 ORDER BY display_order");
$products->execute([$user['company_id']]);
$products = $products->fetchAll();

// Check if user is logged in (for editing)
$isLoggedIn = isLoggedIn() && getCurrentUserType() === 'user' && getCurrentUserId() == $user['user_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?> - Calling Card</title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/dashboard.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/user.css">
    <style>
        /* Prevent horizontal scrolling and ensure portrait mode */
        html, body {
            width: 100%;
            height: 100%;
            overflow-x: hidden;
            position: fixed;
        }
        body {
            -webkit-overflow-scrolling: touch;
        }
    </style>
</head>
<body class="user-dashboard">
    <!-- Header -->
    <header class="dashboard-header">
        <div class="header-left">
            <?php if ($company && $company['logo_path']): ?>
                <img src="<?php echo htmlspecialchars($company['logo_path']); ?>" alt="Logo" class="logo">
            <?php elseif ($company): ?>
                <span class="logo-text"><?php echo htmlspecialchars($company['company_name']); ?></span>
            <?php endif; ?>
        </div>
        <div class="header-right">
            <button class="btn-account" onclick="toggleAccountTab()">Account</button>
        </div>
    </header>

    <!-- Account Tab Modal -->
    <div id="accountModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAccountModal()">&times;</span>
            <?php if (!$isLoggedIn): ?>
                <!-- Login Form -->
                <h2>Login</h2>
                <form id="userLoginForm" onsubmit="userLogin(event)">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" required>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn-primary">Login</button>
                    </div>
                </form>
            <?php else: ?>
                <!-- Account Management -->
                <h2>Account Settings</h2>
                <div class="tabs">
                    <button class="tab-btn active" onclick="showAccountTab('profile')">Profile</button>
                    <button class="tab-btn" onclick="showAccountTab('password')">Change Password</button>
                </div>
                
                <div id="profile-tab" class="tab-content active">
                    <form id="profileForm" onsubmit="updateProfile(event)">
                        <div class="form-group">
                            <label>First Name</label>
                            <input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Middle Name</label>
                            <input type="text" name="middle_name" value="<?php echo htmlspecialchars($user['middle_name'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Last Name</label>
                            <input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Contact Number</label>
                            <input type="text" name="contact_number" value="<?php echo htmlspecialchars($user['contact_number'] ?? ''); ?>">
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn-primary">Update Profile</button>
                        </div>
                    </form>
                </div>
                
                <div id="password-tab" class="tab-content">
                    <form id="passwordForm" onsubmit="changePassword(event)">
                        <div class="form-group">
                            <label>Current Password</label>
                            <input type="password" name="current_password" required>
                        </div>
                        <div class="form-group">
                            <label>New Password</label>
                            <input type="password" name="new_password" required minlength="6">
                        </div>
                        <div class="form-group">
                            <label>Confirm New Password</label>
                            <input type="password" name="confirm_password" required minlength="6">
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn-primary">Change Password</button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Main Content -->
    <div class="dashboard-container">
        <!-- User Profile Section -->
        <section class="profile-section">
            <div class="profile-picture">
                <?php if ($user['profile_picture']): ?>
                    <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile">
                <?php else: ?>
                    <div class="profile-placeholder"><?php echo strtoupper(substr($user['first_name'], 0, 1)); ?></div>
                <?php endif; ?>
                <?php if ($isLoggedIn): ?>
                    <button class="btn-edit-picture" onclick="editProfilePicture()">Edit</button>
                <?php endif; ?>
            </div>
            
            <div class="social-media-section">
                <h3>Social Media</h3>
                <div class="social-icons">
                    <?php foreach ($userSocialMedia as $social): ?>
                        <a href="<?php echo htmlspecialchars($social['url']); ?>" target="_blank" class="social-icon">
                            <?php if ($social['platform_icon']): ?>
                                <img src="<?php echo htmlspecialchars($social['platform_icon']); ?>" alt="<?php echo htmlspecialchars($social['platform_name']); ?>">
                            <?php else: ?>
                                <span><?php echo htmlspecialchars($social['platform_name']); ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                    <?php if ($isLoggedIn): ?>
                        <button class="btn-add-social" onclick="addSocialMedia('user')">+</button>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="personal-details">
                <h3>Personal Details</h3>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($user['first_name'] . ' ' . ($user['middle_name'] ?? '') . ' ' . $user['last_name']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                <?php if ($user['contact_number']): ?>
                    <p><strong>Contact:</strong> <?php echo htmlspecialchars($user['contact_number']); ?></p>
                <?php endif; ?>
            </div>
        </section>

        <!-- Company Section -->
        <?php if ($company): ?>
        <section class="company-section">
            <h2><?php echo htmlspecialchars($company['company_name']); ?></h2>
            
            <?php if (!empty($companyAddresses)): ?>
                <div class="addresses">
                    <h4>Addresses</h4>
                    <?php foreach ($companyAddresses as $address): ?>
                        <p><?php echo htmlspecialchars($address['full_address'] ?? $address['address_line1']); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($companyContacts)): ?>
                <div class="contacts">
                    <h4>Contacts</h4>
                    <?php foreach ($companyContacts as $contact): ?>
                        <p><a href="tel:<?php echo htmlspecialchars($contact['contact_number']); ?>"><?php echo htmlspecialchars($contact['contact_number']); ?></a></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($companyEmails)): ?>
                <div class="emails">
                    <h4>Emails</h4>
                    <?php foreach ($companyEmails as $email): ?>
                        <p><a href="mailto:<?php echo htmlspecialchars($email['email_address']); ?>"><?php echo htmlspecialchars($email['email_address']); ?></a></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($companySocialMedia)): ?>
                <div class="company-social">
                    <h4>Follow Us</h4>
                    <div class="social-icons">
                        <?php foreach ($companySocialMedia as $social): ?>
                            <a href="<?php echo htmlspecialchars($social['url']); ?>" target="_blank" class="social-icon">
                                <?php if ($social['platform_icon']): ?>
                                    <img src="<?php echo htmlspecialchars($social['platform_icon']); ?>" alt="<?php echo htmlspecialchars($social['platform_name']); ?>">
                                <?php else: ?>
                                    <span><?php echo htmlspecialchars($social['platform_name']); ?></span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($company['map_latitude'] && $company['map_longitude']): ?>
                <div class="map-container">
                    <h4>Location</h4>
                    <div id="companyMap" style="width: 100%; height: 250px;"></div>
                </div>
            <?php endif; ?>
        </section>
        <?php endif; ?>

        <!-- Products Section -->
        <?php if (!empty($products)): ?>
        <section class="products-section">
            <h2>Products</h2>
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-item">
                        <img src="<?php echo htmlspecialchars($product['image_path']); ?>" 
                             alt="<?php echo htmlspecialchars($product['image_alt_text'] ?? $product['product_name'] ?? 'Product'); ?>" 
                             onclick="viewProduct('<?php echo htmlspecialchars($product['image_path']); ?>')">
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
    </div>

    <script src="<?php echo SITE_URL; ?>assets/js/dashboard.js"></script>
    <script src="<?php echo SITE_URL; ?>assets/js/user.js"></script>
    <?php if ($company && $company['map_latitude'] && $company['map_longitude']): ?>
        <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY"></script>
        <script>
            function initMap() {
                const map = new google.maps.Map(document.getElementById('companyMap'), {
                    center: {lat: <?php echo $company['map_latitude']; ?>, lng: <?php echo $company['map_longitude']; ?>},
                    zoom: 15,
                    gestureHandling: 'cooperative'
                });
                new google.maps.Marker({
                    position: {lat: <?php echo $company['map_latitude']; ?>, lng: <?php echo $company['map_longitude']; ?>},
                    map: map
                });
            }
            initMap();
        </script>
    <?php endif; ?>
</body>
</html>

