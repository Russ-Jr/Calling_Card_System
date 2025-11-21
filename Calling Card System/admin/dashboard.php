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

    <div id="accountModal" class="modal" style="display:none;">
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
                
                <div id="password-tab" class="tab-content" style="display:none;">
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
                        <a href="<?php echo htmlspecialchars($social['url']); ?>" target="_blank" class="social-icon" title="<?php echo htmlspecialchars($social['platform_name']); ?>">
                            <?php if (!empty($social['platform_icon'])): ?>
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
                <p><strong>Name:</strong> <?php echo htmlspecialchars(trim(($currentAdmin['first_name'] ?? '') . ' ' . ($currentAdmin['middle_name'] ?? '') . ' ' . ($currentAdmin['last_name'] ?? ''))); ?></p>
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
                                <td><?php echo (int)$user['user_id']; ?></td>
                                <td><?php echo htmlspecialchars(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))); ?></td>
                                <td><?php echo htmlspecialchars($user['email'] ?? ''); ?></td>
                                <td>
                                    <?php if (!empty($user['nfc_uid'])): ?>
                                        <?php echo htmlspecialchars($user['nfc_uid']); ?>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Not Registered</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo !empty($user['is_active']) ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo !empty($user['is_active']) ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn-sm btn-edit" onclick="editUser(<?php echo (int)$user['user_id']; ?>)">Edit</button>
                                    <?php if (empty($user['nfc_uid'])): ?>
                                        <button class="btn-sm btn-primary" onclick="registerNFC(<?php echo (int)$user['user_id']; ?>)">Register NFC</button>
                                    <?php endif; ?>
                                    <button class="btn-sm btn-delete" onclick="deleteUser(<?php echo (int)$user['user_id']; ?>)">Delete</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="6">No card holders found.</td>
                            </tr>
                            <?php endif; ?>
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
                            <p><?php echo htmlspecialchars($address['full_address'] ?? $address['address_line1'] ?? ''); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($companyContacts)): ?>
                    <div class="contacts">
                        <h4>Contacts</h4>
                        <?php foreach ($companyContacts as $contact): ?>
                            <p><?php echo htmlspecialchars($contact['display_label'] ?? $contact['contact_type'] ?? 'Contact'); ?>: <?php echo htmlspecialchars($contact['contact_number'] ?? ''); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($companyEmails)): ?>
                    <div class="emails">
                        <h4>Emails</h4>
                        <?php foreach ($companyEmails as $email): ?>
                            <p><?php echo htmlspecialchars($email['display_label'] ?? 'Email'); ?>: <a href="mailto:<?php echo htmlspecialchars($email['email_address'] ?? ''); ?>"><?php echo htmlspecialchars($email['email_address'] ?? ''); ?></a></p>
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
                        <?php
                            $imgSrc = !empty($product['image_path']) ? $product['image_path'] : SITE_URL . 'assets/img/no-image.png';
                            $imgAlt = htmlspecialchars($product['image_alt_text'] ?? $product['product_name'] ?? 'Product');
                        ?>
                        <img src="<?php echo htmlspecialchars($imgSrc); ?>" alt="<?php echo $imgAlt; ?>">
                        <div class="product-meta">
                            <h4><?php echo htmlspecialchars($product['product_name'] ?? ''); ?></h4>
                            <?php if (!empty($product['price'])): ?>
                                <p class="price"><?php echo htmlspecialchars($product['price']); ?></p>
                            <?php endif; ?>
                        </div>
                        <?php if ($isLoggedIn): ?>
                            <div class="product-actions">
                                <button class="btn-sm btn-edit" onclick="editProduct(<?php echo (int)$product['product_id']; ?>)">Edit</button>
                                <button class="btn-sm btn-delete" onclick="deleteProduct(<?php echo (int)$product['product_id']; ?>)">Delete</button>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($products)): ?>
                    <p>No products to display.</p>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <script src="<?php echo SITE_URL; ?>assets/js/admin.js"></script>
    <script src="<?php echo SITE_URL; ?>assets/js/dashboard.js"></script>
    <script>
        // Provide lightweight fallbacks for functions expected by the markup
        // if the external JS files are not loaded or don't provide them.
        (function() {
            if (typeof toggleAccountTab === 'undefined') {
                window.toggleAccountTab = function() {
                    var modal = document.getElementById('accountModal');
                    if (!modal) return;
                    modal.style.display = (modal.style.display === 'block') ? 'none' : 'block';
                };
            }

            if (typeof closeAccountModal === 'undefined') {
                window.closeAccountModal = function() {
                    var modal = document.getElementById('accountModal');
                    if (!modal) return;
                    modal.style.display = 'none';
                };
            }

            if (typeof showAccountTab === 'undefined') {
                window.showAccountTab = function(tab) {
                    var profileTab = document.getElementById('profile-tab');
                    var passwordTab = document.getElementById('password-tab');
                    var buttons = document.querySelectorAll('.tab-btn');
                    buttons.forEach(function(b){ b.classList.remove('active'); });
                    if (tab === 'profile') {
                        if (profileTab) profileTab.style.display = 'block';
                        if (passwordTab) passwordTab.style.display = 'none';
                        buttons[0] && buttons[0].classList.add('active');
                    } else {
                        if (profileTab) profileTab.style.display = 'none';
                        if (passwordTab) passwordTab.style.display = 'block';
                        buttons[1] && buttons[1].classList.add('active');
                    }
                };
            }

            // Basic placeholders for form handlers to avoid JS errors if admin.js is missing.
            if (typeof adminLogin === 'undefined') {
                window.adminLogin = function(e) {
                    e.preventDefault();
                    alert('Login action is handled by admin.js (or implement AJAX endpoint).');
                };
            }

            if (typeof updateProfile === 'undefined') {
                window.updateProfile = function(e) {
                    e.preventDefault();
                    alert('Profile update is handled by admin.js (or implement AJAX endpoint).');
                };
            }

            if (typeof changePassword === 'undefined') {
                window.changePassword = function(e) {
                    e.preventDefault();
                    alert('Change password is handled by admin.js (or implement AJAX endpoint).');
                };
            }

            if (typeof editProfilePicture === 'undefined') {
                window.editProfilePicture = function() {
                    alert('Edit profile picture feature not implemented in this fallback. Use admin.js for full behavior.');
                };
            }

            if (typeof addSocialMedia === 'undefined') {
                window.addSocialMedia = function(type) {
                    alert('Add social media (' + (type || '') + ') feature is handled by admin.js.');
                };
            }

            if (typeof openUserModal === 'undefined') {
                window.openUserModal = function() {
                    alert('Open user modal - implement in admin.js to add/edit card holders.');
                };
            }

            if (typeof editUser === 'undefined') {
                window.editUser = function(id) {
                    alert('Edit user ' + id + ' - implement in admin.js.');
                };
            }

            if (typeof deleteUser === 'undefined') {
                window.deleteUser = function(id) {
                    if (!confirm('Are you sure you want to delete this user?')) return;
                    alert('Delete user ' + id + ' - implement AJAX call in admin.js to remove from server.');
                };
            }

            if (typeof openCompanyModal === 'undefined') {
                window.openCompanyModal = function() {
                    alert('Open company edit modal - implement in admin.js.');
                };
            }

            if (typeof openProductModal === 'undefined') {
                window.openProductModal = function() {
                    alert('Open product modal - implement in admin.js.');
                };
            }

            if (typeof editProduct === 'undefined') {
                window.editProduct = function(id) {
                    alert('Edit product ' + id + ' - implement in admin.js.');
                };
            }

            if (typeof deleteProduct === 'undefined') {
                window.deleteProduct = function(id) {
                    if (!confirm('Delete this product?')) return;
                    alert('Delete product ' + id + ' - implement in admin.js.');
                };
            }

            // Provide a default registerNFC fallback (will be overridden by WebView2 integration below)
            if (typeof registerNFC === 'undefined') {
                window.registerNFC = function(userId) {
                    if (!confirm('Register NFC for user #' + userId + '?')) return;
                    alert('NFC registration is handled by the desktop integration (WebView2) or server-side endpoint. Implement in admin.js.');
                };
            }
        })();

        // WebView2 Communication Script
        (function() {
            // Override registerNFC function to communicate with VB.NET WebView2 host if available
            if (window.chrome && window.chrome.webview && window.chrome.webview.postMessage) {
                var originalRegisterNFC = window.registerNFC;
                window.registerNFC = function(userId) {
                    if (confirm('Please tap the NFC card on the reader. Click OK when ready.')) {
                        try {
                            window.chrome.webview.postMessage(JSON.stringify({
                                action: 'registerNFC',
                                userId: userId
                            }));
                        } catch (err) {
                            console.error('Failed to post message to WebView2 host', err);
                            // Fallback to original behaviour if present
                            if (typeof originalRegisterNFC === 'function') {
                                originalRegisterNFC(userId);
                            }
                        }
                    }
                };
            }
        })();
    </script>
    <?php if (!empty($company['map_latitude']) && !empty($company['map_longitude'])): ?>
        <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY"></script>
        <script>
            function initMap() {
                const lat = parseFloat(<?php echo json_encode($company['map_latitude']); ?>);
                const lng = parseFloat(<?php echo json_encode($company['map_longitude']); ?>);
                const map = new google.maps.Map(document.getElementById('companyMap'), {
                    center: {lat: lat, lng: lng},
                    zoom: 15
                });
                new google.maps.Marker({
                    position: {lat: lat, lng: lng},
                    map: map
                });
            }
            if (typeof google !== 'undefined' && google.maps) {
                initMap();
            } else {
                // If the API script hasn't loaded yet, wait until it does
                window.addEventListener('load', function() {
                    if (typeof google !== 'undefined' && google.maps) {
                        initMap();
                    }
                });
            }
        </script>
    <?php endif; ?>
</body>
</html>