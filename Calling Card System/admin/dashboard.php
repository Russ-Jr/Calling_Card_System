<?php
/**
 * Admin Dashboard
 * Calling Card System
 */

require_once '../config/config.php';
require_once '../includes/functions.php';

requireAdmin();

$db = getDB();
$companyId = getCurrentCompanyId();
$currentUserId = getCurrentUserId();

if (!$companyId) {
    header('Location: ' . SITE_URL . 'index.php');
    exit;
}

// 1. Get current admin info
$stmt = $db->prepare("SELECT * FROM admins WHERE admin_id = ?");
$stmt->bind_param('i', $currentUserId);
$stmt->execute();
$currentAdmin = dbFetchOne($stmt);
$stmt->close(); // Close statement to prevent "Commands out of sync"

// 2. Get company info
$stmt = $db->prepare("SELECT * FROM company WHERE company_id = ?");
$stmt->bind_param('i', $companyId);
$stmt->execute();
$company = dbFetchOne($stmt);
$stmt->close();

// 3. Get admin social media
$stmt = $db->prepare("SELECT * FROM admin_social_media WHERE admin_id = ? AND is_active = 1 ORDER BY display_order");
$stmt->bind_param('i', $currentUserId);
$stmt->execute();
$adminSocialMedia = dbFetchAll($stmt);
$stmt->close();

// 4. Get users (card holders)
$stmt = $db->prepare("
    SELECT u.*, 
           COUNT(DISTINCT usm.social_media_id) as social_count
    FROM users u
    LEFT JOIN user_social_media usm ON u.user_id = usm.user_id AND usm.is_active = 1
    WHERE u.company_id = ? AND u.is_active = 1
    GROUP BY u.user_id
    ORDER BY u.created_at DESC
");
$stmt->bind_param('i', $companyId);
$stmt->execute();
$users = dbFetchAll($stmt);
$stmt->close();

// 5. Get company addresses
$stmt = $db->prepare("SELECT * FROM company_addresses WHERE company_id = ? AND is_active = 1 ORDER BY display_order");
$stmt->bind_param('i', $companyId);
$stmt->execute();
$companyAddresses = dbFetchAll($stmt);
$stmt->close();

// 6. Get company contacts
$stmt = $db->prepare("SELECT * FROM company_contacts WHERE company_id = ? AND is_active = 1 ORDER BY display_order");
$stmt->bind_param('i', $companyId);
$stmt->execute();
$companyContacts = dbFetchAll($stmt);
$stmt->close();

// 7. Get company emails
$stmt = $db->prepare("SELECT * FROM company_emails WHERE company_id = ? AND is_active = 1 ORDER BY display_order");
$stmt->bind_param('i', $companyId);
$stmt->execute();
$companyEmails = dbFetchAll($stmt);
$stmt->close();

// 8. Get company social media
$stmt = $db->prepare("SELECT * FROM company_social_media WHERE company_id = ? AND is_active = 1 ORDER BY display_order");
$stmt->bind_param('i', $companyId);
$stmt->execute();
$companySocialMedia = dbFetchAll($stmt);
$stmt->close();

// 9. Get products
$stmt = $db->prepare("SELECT * FROM products WHERE company_id = ? AND is_active = 1 ORDER BY display_order");
$stmt->bind_param('i', $companyId);
$stmt->execute();
$products = dbFetchAll($stmt);
$stmt->close();

// Check if admin is logged in (for editing features)
$isLoggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Calling Card System</title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/admin.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/dashboard.css">
</head>
<body>
    <header class="dashboard-header">
        <div class="header-left">
            <?php if (!empty($company['logo_path'])): ?>
                <img src="<?php echo htmlspecialchars($company['logo_path']); ?>" alt="Logo" class="logo">
            <?php else: ?>
                <span class="logo-text"><?php echo htmlspecialchars($company['company_name'] ?? ''); ?></span>
            <?php endif; ?>
        </div>
        <div class="header-right">
            <button class="btn-account" onclick="toggleAccountTab()">Account</button>
        </div>
    </header>

    <div id="accountModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAccountModal()">&times;</span>
            <?php if (!$isLoggedIn): ?>
                <h2>Admin Login</h2>
                <form id="adminLoginForm" onsubmit="adminLogin(event)">
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
                <h2>Account Settings</h2>
                <div class="tabs">
                    <button class="tab-btn active" onclick="showAccountTab('profile')">Profile</button>
                    <button class="tab-btn" onclick="showAccountTab('password')">Change Password</button>
                </div>
                
                <div id="profile-tab" class="tab-content active">
                    <form id="profileForm" onsubmit="updateProfile(event)">
                        <div class="form-group">
                            <label>First Name</label>
                            <input type="text" name="first_name" value="<?php echo htmlspecialchars($currentAdmin['first_name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Middle Name</label>
                            <input type="text" name="middle_name" value="<?php echo htmlspecialchars($currentAdmin['middle_name'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Last Name</label>
                            <input type="text" name="last_name" value="<?php echo htmlspecialchars($currentAdmin['last_name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($currentAdmin['email'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Contact Number</label>
                            <input type="text" name="contact_number" value="<?php echo htmlspecialchars($currentAdmin['contact_number'] ?? ''); ?>">
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

    <div class="dashboard-container">
        <section class="profile-section">
            <div class="profile-picture">
                <?php if (!empty($currentAdmin['profile_picture'])): ?>
                    <img src="<?php echo htmlspecialchars($currentAdmin['profile_picture']); ?>" alt="Profile">
                <?php else: ?>
                    <div class="profile-placeholder"><?php echo strtoupper(substr($currentAdmin['first_name'] ?? 'A', 0, 1)); ?></div>
                <?php endif; ?>
                <?php if ($isLoggedIn): ?>
                    <button class="btn-edit-picture" onclick="editProfilePicture()">Edit</button>
                <?php endif; ?>
            </div>
            
            <div class="social-media-section">
                <h3>Social Media</h3>
                <div class="social-icons">
                    <?php foreach ($adminSocialMedia as $social): ?>
                        <a href="<?php echo htmlspecialchars($social['url']); ?>" target="_blank" class="social-icon">
                            <?php if ($social['platform_icon']): ?>
                                <img src="<?php echo htmlspecialchars($social['platform_icon']); ?>" alt="<?php echo htmlspecialchars($social['platform_name']); ?>">
                            <?php else: ?>
                                <span><?php echo htmlspecialchars($social['platform_name']); ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                    <?php if ($isLoggedIn): ?>
                        <button class="btn-add-social" onclick="addSocialMedia('admin')">+</button>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="personal-details">
                <h3>Personal Details</h3>
                <p><strong>Name:</strong> <?php echo htmlspecialchars(($currentAdmin['first_name'] ?? '') . ' ' . ($currentAdmin['middle_name'] ?? '') . ' ' . ($currentAdmin['last_name'] ?? '')); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($currentAdmin['email'] ?? ''); ?></p>
                <?php if (!empty($currentAdmin['contact_number'])): ?>
                    <p><strong>Contact:</strong> <?php echo htmlspecialchars($currentAdmin['contact_number']); ?></p>
                <?php endif; ?>
            </div>
        </section>

        <?php if ($isLoggedIn): ?>
            <section class="card-holders-section">
                <div class="section-header">
                    <h2>Card Holders</h2>
                    <button class="btn-primary" onclick="openUserModal()">Add New Card Holder</button>
                </div>
                
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>NFC UID</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['user_id']; ?></td>
                                <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo $user['nfc_uid'] ? htmlspecialchars($user['nfc_uid']) : '<span class="badge badge-danger">Not Registered</span>'; ?></td>
                                <td>
                                    <span class="badge <?php echo $user['is_active'] ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn-sm btn-edit" onclick="editUser(<?php echo $user['user_id']; ?>)">Edit</button>
                                    <?php if (!$user['nfc_uid']): ?>
                                        <button class="btn-sm btn-primary" onclick="registerNFC(<?php echo $user['user_id']; ?>)">Register NFC</button>
                                    <?php endif; ?>
                                    <button class="btn-sm btn-delete" onclick="deleteUser(<?php echo $user['user_id']; ?>)">Delete</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>

        <section class="company-section">
            <div class="section-header">
                <h2>Company Information</h2>
                <?php if ($isLoggedIn): ?>
                    <button class="btn-primary" onclick="openCompanyModal()">Edit Company</button>
                <?php endif; ?>
            </div>
            
            <div class="company-details">
                <h3><?php echo htmlspecialchars($company['company_name'] ?? ''); ?></h3>
                
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
                            <p><?php echo htmlspecialchars($contact['display_label'] ?? $contact['contact_type']); ?>: <?php echo htmlspecialchars($contact['contact_number']); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($companyEmails)): ?>
                    <div class="emails">
                        <h4>Emails</h4>
                        <?php foreach ($companyEmails as $email): ?>
                            <p><?php echo htmlspecialchars($email['display_label'] ?? 'Email'); ?>: <a href="mailto:<?php echo htmlspecialchars($email['email_address']); ?>"><?php echo htmlspecialchars($email['email_address']); ?></a></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($company['map_latitude']) && !empty($company['map_longitude'])): ?>
                    <div class="map-container">
                        <h4>Location</h4>
                        <div id="companyMap" style="width: 100%; height: 300px;"></div>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="products-section">
            <div class="section-header">
                <h2>Products</h2>
                <?php if ($isLoggedIn): ?>
                    <button class="btn-primary" onclick="openProductModal()">Add Product</button>
                <?php endif; ?>
            </div>
            
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-item">
                        <img src="<?php echo htmlspecialchars($product['image_path']); ?>" alt="<?php echo htmlspecialchars($product['image_alt_text'] ?? $product['product_name'] ?? 'Product'); ?>" onclick="viewProduct('<?php echo htmlspecialchars($product['image_path']); ?>')">
                        <?php if ($isLoggedIn): ?>
                            <div class="product-actions">
                                <button class="btn-sm btn-edit" onclick="editProduct(<?php echo $product['product_id']; ?>)">Edit</button>
                                <button class="btn-sm btn-delete" onclick="deleteProduct(<?php echo $product['product_id']; ?>)">Delete</button>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>

    <script src="<?php echo SITE_URL; ?>assets/js/admin.js"></script>
    <script src="<?php echo SITE_URL; ?>assets/js/dashboard.js"></script>
    <script>
        // WebView2 Communication Script
        (function() {
            // Override registerNFC function to communicate with VB.NET
            if (typeof registerNFC !== 'undefined') {
                const originalRegisterNFC = registerNFC;
                registerNFC = function(userId) {
                    // Check if running in WebView2
                    if (window.chrome && window.chrome.webview && window.chrome.webview.postMessage) {
                        if (confirm('Please tap the NFC card on the reader. Click OK when ready.')) {
                            window.chrome.webview.postMessage(JSON.stringify({
                                action: 'registerNFC',
                                userId: userId
                            }));
                        }
                    } else {
                        // Fallback for browser
                        originalRegisterNFC(userId);
                    }
                };
            }
        })();
    </script>
    <?php if (!empty($company['map_latitude']) && !empty($company['map_longitude'])): ?>
        <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY"></script>
        <script>
            function initMap() {
                const map = new google.maps.Map(document.getElementById('companyMap'), {
                    center: {lat: <?php echo $company['map_latitude']; ?>, lng: <?php echo $company['map_longitude']; ?>},
                    zoom: 15
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