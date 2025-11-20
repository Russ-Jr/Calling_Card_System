// Dashboard JavaScript Functions

// Account Tab
function toggleAccountTab() {
    const modal = document.getElementById('accountModal');
    modal.style.display = 'block';
}

function closeAccountModal() {
    document.getElementById('accountModal').style.display = 'none';
}

function showAccountTab(tabName) {
    document.querySelectorAll('#accountModal .tab-content').forEach(tab => tab.classList.remove('active'));
    document.querySelectorAll('#accountModal .tab-btn').forEach(btn => btn.classList.remove('active'));
    document.getElementById(tabName + '-tab').classList.add('active');
    event.target.classList.add('active');
}

// Admin Login
function adminLogin(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    formData.append('action', 'admin_login');
    
    // FIX: Changed 'api/admin.php' to '../api/admin.php'
    fetch('../api/admin.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message);
        }
    });
}

// Update Profile
function updateProfile(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    formData.append('action', 'update_profile');
    
    // FIX: Changed 'api/admin.php' to '../api/admin.php'
    fetch('../api/admin.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        alert(data.message);
        if (data.success) {
            location.reload();
        }
    });
}

// Change Password
function changePassword(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    
    if (formData.get('new_password') !== formData.get('confirm_password')) {
        alert('New passwords do not match');
        return;
    }
    
    formData.append('action', 'change_password');
    
    // FIX: Changed 'api/admin.php' to '../api/admin.php'
    fetch('../api/admin.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        alert(data.message);
        if (data.success) {
            e.target.reset();
        }
    });
}

// User Management
function openUserModal(userId = null) {
    // Implementation for user modal
    alert('User modal - to be implemented');
}

function editUser(userId) {
    openUserModal(userId);
}

function deleteUser(userId) {
    if (confirm('Are you sure you want to delete this user?')) {
        // FIX: Changed 'api/admin.php' to '../api/admin.php'
        fetch('../api/admin.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'delete_user', user_id: userId})
        })
        .then(r => r.json())
        .then(data => {
            alert(data.message);
            if (data.success) location.reload();
        });
    }
}

function registerNFC(userId) {
    if (confirm('Please tap the NFC card on the reader. Click OK when ready.')) {
        // Send message to VB.NET WebView application
        if (window.chrome && window.chrome.webview && window.chrome.webview.postMessage) {
            window.chrome.webview.postMessage(JSON.stringify({
                action: 'registerNFC',
                userId: userId
            }));
        } else {
            // Fallback for browser testing
            alert('NFC registration requires the VB.NET desktop application. User ID: ' + userId);
        }
    }
}

// Company Management
function openCompanyModal() {
    alert('Company modal - to be implemented');
}

// Product Management
function openProductModal() {
    alert('Product modal - to be implemented');
}

function editProduct(productId) {
    alert('Edit product - to be implemented');
}

function deleteProduct(productId) {
    if (confirm('Are you sure you want to delete this product?')) {
        // FIX: Changed 'api/admin.php' to '../api/admin.php'
        fetch('../api/admin.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'delete_product', product_id: productId})
        })
        .then(r => r.json())
        .then(data => {
            alert(data.message);
            if (data.success) location.reload();
        });
    }
}

function viewProduct(imagePath) {
    const modal = document.createElement('div');
    modal.className = 'product-view-modal';
    modal.innerHTML = `
        <span class="close" onclick="this.parentElement.remove()">&times;</span>
        <img src="${imagePath}" alt="Product">
    `;
    document.body.appendChild(modal);
    modal.style.display = 'block';
    
    modal.onclick = function(e) {
        if (e.target === modal) {
            modal.remove();
        }
    };
}

// Social Media
function addSocialMedia(type) {
    alert('Add social media - to be implemented');
}

function editProfilePicture() {
    alert('Edit profile picture - to be implemented');
}

// Close modals when clicking outside
window.onclick = function(event) {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
}