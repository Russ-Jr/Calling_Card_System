<?php
/**
 * Super Admin Dashboard
 * Calling Card System
 */

require_once '../config/config.php';
require_once '../includes/functions.php';

requireSuperAdmin();

$db = getDB();
$currentAdmin = null;

// --- FIX 1: Use correct MySQLi Prepared Statement syntax ---
// Get current admin info
$stmt = $db->prepare("SELECT * FROM admins WHERE admin_id = ?");
$currentUserId = getCurrentUserId();
$stmt->bind_param("i", $currentUserId); // Bind parameter explicitly
$stmt->execute();
$currentAdmin = dbFetchOne($stmt); // Use your helper function
$stmt->close(); // Close the statement to free the connection

// --- FIX 2: Replace PDO 'fetchColumn' with MySQLi logic and explicit freeing ---
// Helper to get count and free result immediately
function getCount($db, $sql) {
    $result = $db->query($sql);
    if ($result) {
        $row = $result->fetch_row();
        $count = $row[0];
        $result->free(); // CRITICAL: Free result to prevent "Commands out of sync"
        return $count;
    }
    return 0;
}

// Get statistics
$stats = [
    'companies' => getCount($db, "SELECT COUNT(*) FROM company WHERE is_active = 1"),
    'admins' => getCount($db, "SELECT COUNT(*) FROM admins WHERE role = 'admin' AND is_active = 1"),
    'users' => getCount($db, "SELECT COUNT(*) FROM users WHERE is_active = 1"),
    'total_cards' => getCount($db, "SELECT COUNT(*) FROM users WHERE nfc_uid IS NOT NULL")
];

// --- FIX 3: Use fetch_all(MYSQLI_ASSOC) and free results ---
// Get companies with admin count
$companiesResult = $db->query("
    SELECT c.*, 
           COUNT(DISTINCT a.admin_id) as admin_count,
           COUNT(DISTINCT u.user_id) as user_count
    FROM company c
    LEFT JOIN admins a ON c.company_id = a.company_id AND a.is_active = 1
    LEFT JOIN users u ON c.company_id = u.company_id AND u.is_active = 1
    GROUP BY c.company_id
    ORDER BY c.created_at DESC
");
$companies = $companiesResult->fetch_all(MYSQLI_ASSOC);
$companiesResult->free(); // Free result

// Get all admins
$adminsResult = $db->query("
    SELECT a.*, c.company_name
    FROM admins a
    LEFT JOIN company c ON a.company_id = c.company_id
    WHERE a.role = 'admin'
    ORDER BY a.created_at DESC
");
$admins = $adminsResult->fetch_all(MYSQLI_ASSOC);
$adminsResult->free(); // Free result
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Dashboard - Calling Card System</title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/admin.css">
</head>
<body>
    <div class="admin-container">
        <header class="admin-header">
            <div class="header-content">
                <h1>Super Admin Dashboard</h1>
                <div class="header-actions">
                    <span>Welcome, <?php echo htmlspecialchars($currentAdmin['first_name'] ?? ''); ?></span>
                    <a href="<?php echo SITE_URL; ?>logout.php" class="btn-logout">Logout</a>
                </div>
            </div>
        </header>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Companies</h3>
                <p class="stat-number"><?php echo $stats['companies']; ?></p>
            </div>
            <div class="stat-card">
                <h3>Admins</h3>
                <p class="stat-number"><?php echo $stats['admins']; ?></p>
            </div>
            <div class="stat-card">
                <h3>Users</h3>
                <p class="stat-number"><?php echo $stats['users']; ?></p>
            </div>
            <div class="stat-card">
                <h3>Registered Cards</h3>
                <p class="stat-number"><?php echo $stats['total_cards']; ?></p>
            </div>
        </div>

        <div class="tabs">
            <button class="tab-btn active" onclick="showTab('companies')">Companies</button>
            <button class="tab-btn" onclick="showTab('admins')">Admins</button>
        </div>

        <div id="companies-tab" class="tab-content active">
            <div class="section-header">
                <h2>Companies</h2>
                <button class="btn-primary" onclick="openCompanyModal()">Add Company</button>
            </div>
            
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Company Name</th>
                            <th>Admins</th>
                            <th>Users</th>
                            <th>Created</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($companies as $company): ?>
                        <tr>
                            <td><?php echo $company['company_id']; ?></td>
                            <td><?php echo htmlspecialchars($company['company_name']); ?></td>
                            <td><?php echo $company['admin_count']; ?></td>
                            <td><?php echo $company['user_count']; ?></td>
                            <td><?php echo formatDate($company['created_at'], 'Y-m-d'); ?></td>
                            <td>
                                <span class="badge <?php echo $company['is_active'] ? 'badge-success' : 'badge-danger'; ?>">
                                    <?php echo $company['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn-sm btn-edit" onclick="editCompany(<?php echo $company['company_id']; ?>)">Edit</button>
                                <button class="btn-sm btn-delete" onclick="deleteCompany(<?php echo $company['company_id']; ?>)">Delete</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="admins-tab" class="tab-content">
            <div class="section-header">
                <h2>Admins</h2>
                <button class="btn-primary" onclick="openAdminModal()">Add Admin</button>
            </div>
            
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Company</th>
                            <th>Created</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($admins as $admin): ?>
                        <tr>
                            <td><?php echo $admin['admin_id']; ?></td>
                            <td><?php echo htmlspecialchars($admin['username']); ?></td>
                            <td><?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($admin['email']); ?></td>
                            <td><?php echo htmlspecialchars($admin['company_name'] ?? 'N/A'); ?></td>
                            <td><?php echo formatDate($admin['created_at'], 'Y-m-d'); ?></td>
                            <td>
                                <span class="badge <?php echo $admin['is_active'] ? 'badge-success' : 'badge-danger'; ?>">
                                    <?php echo $admin['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn-sm btn-edit" onclick="editAdmin(<?php echo $admin['admin_id']; ?>)">Edit</button>
                                <button class="btn-sm btn-delete" onclick="deleteAdmin(<?php echo $admin['admin_id']; ?>)">Delete</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="companyModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeCompanyModal()">&times;</span>
            <h2 id="companyModalTitle">Add Company</h2>
            <form id="companyForm" onsubmit="saveCompany(event)">
                <input type="hidden" id="company_id" name="company_id">
                <div class="form-group">
                    <label>Company Name *</label>
                    <input type="text" id="company_name" name="company_name" required>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="closeCompanyModal()">Cancel</button>
                    <button type="submit" class="btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>

    <div id="adminModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAdminModal()">&times;</span>
            <h2 id="adminModalTitle">Add Admin</h2>
            <form id="adminForm" onsubmit="saveAdmin(event)">
                <input type="hidden" id="admin_id" name="admin_id">
                <div class="form-group">
                    <label>Company *</label>
                    <select id="admin_company_id" name="company_id" required>
                        <option value="">Select Company</option>
                        <?php foreach ($companies as $company): ?>
                            <option value="<?php echo $company['company_id']; ?>"><?php echo htmlspecialchars($company['company_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Username *</label>
                    <input type="text" id="admin_username" name="username" required>
                </div>
                <div class="form-group">
                    <label>Password <?php echo isset($_GET['edit']) ? '(leave blank to keep current)' : '*'; ?></label>
                    <input type="password" id="admin_password" name="password" <?php echo !isset($_GET['edit']) ? 'required' : ''; ?>>
                </div>
                <div class="form-group">
                    <label>First Name *</label>
                    <input type="text" id="admin_first_name" name="first_name" required>
                </div>
                <div class="form-group">
                    <label>Middle Name</label>
                    <input type="text" id="admin_middle_name" name="middle_name">
                </div>
                <div class="form-group">
                    <label>Last Name *</label>
                    <input type="text" id="admin_last_name" name="last_name" required>
                </div>
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" id="admin_email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Contact Number</label>
                    <input type="text" id="admin_contact" name="contact_number">
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="closeAdminModal()">Cancel</button>
                    <button type="submit" class="btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>

    <script src="<?php echo SITE_URL; ?>assets/js/admin.js"></script>
    <script>
        // Tab switching
        function showTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.getElementById(tabName + '-tab').classList.add('active');
            event.target.classList.add('active');
        }

        // Company Modal
        function openCompanyModal(companyId = null) {
            const modal = document.getElementById('companyModal');
            const form = document.getElementById('companyForm');
            const title = document.getElementById('companyModalTitle');
            
            if (companyId) {
                title.textContent = 'Edit Company';
                // Load company data via AJAX
                fetch('<?php echo SITE_URL; ?>api/get_company.php?id=' + companyId)
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            document.getElementById('company_id').value = data.data.company_id;
                            document.getElementById('company_name').value = data.data.company_name;
                        }
                    });
            } else {
                title.textContent = 'Add Company';
                form.reset();
                document.getElementById('company_id').value = '';
            }
            modal.style.display = 'block';
        }

        function closeCompanyModal() {
            document.getElementById('companyModal').style.display = 'none';
        }

        function saveCompany(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            formData.append('action', 'save_company');
            
            fetch('<?php echo SITE_URL; ?>api/superadmin.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.message);
                }
            });
        }

        function editCompany(id) {
            openCompanyModal(id);
        }

        function deleteCompany(id) {
            if (confirm('Are you sure you want to delete this company? This will also delete all associated admins and users.')) {
                fetch('<?php echo SITE_URL; ?>api/superadmin.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({action: 'delete_company', company_id: id})
                })
                .then(r => r.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) location.reload();
                });
            }
        }

        // Admin Modal
        function openAdminModal(adminId = null) {
            const modal = document.getElementById('adminModal');
            const form = document.getElementById('adminForm');
            const title = document.getElementById('adminModalTitle');
            
            if (adminId) {
                title.textContent = 'Edit Admin';
                fetch('<?php echo SITE_URL; ?>api/get_admin.php?id=' + adminId)
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            const admin = data.data;
                            document.getElementById('admin_id').value = admin.admin_id;
                            document.getElementById('admin_company_id').value = admin.company_id || '';
                            document.getElementById('admin_username').value = admin.username;
                            document.getElementById('admin_first_name').value = admin.first_name;
                            document.getElementById('admin_middle_name').value = admin.middle_name || '';
                            document.getElementById('admin_last_name').value = admin.last_name;
                            document.getElementById('admin_email').value = admin.email;
                            document.getElementById('admin_contact').value = admin.contact_number || '';
                        }
                    });
            } else {
                title.textContent = 'Add Admin';
                form.reset();
                document.getElementById('admin_id').value = '';
            }
            modal.style.display = 'block';
        }

        function closeAdminModal() {
            document.getElementById('adminModal').style.display = 'none';
        }

        function saveAdmin(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            formData.append('action', 'save_admin');
            
            fetch('<?php echo SITE_URL; ?>api/superadmin.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.message);
                }
            });
        }

        function editAdmin(id) {
            openAdminModal(id);
        }

        function deleteAdmin(id) {
            if (confirm('Are you sure you want to delete this admin?')) {
                fetch('<?php echo SITE_URL; ?>api/superadmin.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({action: 'delete_admin', admin_id: id})
                })
                .then(r => r.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) location.reload();
                });
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const companyModal = document.getElementById('companyModal');
            const adminModal = document.getElementById('adminModal');
            if (event.target == companyModal) {
                companyModal.style.display = 'none';
            }
            if (event.target == adminModal) {
                adminModal.style.display = 'none';
            }
        }
    </script>
</body>
</html>