// User Dashboard JavaScript

// User Login
function userLogin(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    formData.append('action', 'user_login');
    
    fetch('<?php echo SITE_URL; ?>api/user.php', {
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

// Update User Profile
function updateProfile(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    formData.append('action', 'update_profile');
    
    fetch('<?php echo SITE_URL; ?>api/user.php', {
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
    
    fetch('<?php echo SITE_URL; ?>api/user.php', {
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

