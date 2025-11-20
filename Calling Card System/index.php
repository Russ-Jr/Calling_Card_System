<?php
/**
 * Login Page
 * Calling Card System
 */

require_once 'config/config.php';
require_once 'includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isSuperAdmin()) {
        header('Location: ' . SITE_URL . 'superadmin/dashboard.php');
    } elseif (isAdmin()) {
        header('Location: ' . SITE_URL . 'admin/dashboard.php');
    } else {
        header('Location: ' . SITE_URL . 'user/dashboard.php');
    }
    exit;
}

$error = '';
$success = '';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        $db = getDB();
        
        // Check admin (super admin or admin)
        $stmt = $db->prepare("
            SELECT admin_id, username, password, role, first_name, last_name, email, company_id, is_active
            FROM admins
            WHERE username = ? AND is_active = 1
        ");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $admin = dbFetchOne($stmt);
        $stmt->close();
        
        if ($admin && verifyPassword($password, $admin['password'])) {
            // Admin login successful
            $_SESSION['user_id'] = $admin['admin_id'];
            $_SESSION['user_type'] = $admin['role'];
            $_SESSION['username'] = $admin['username'];
            $_SESSION['full_name'] = $admin['first_name'] . ' ' . $admin['last_name'];
            $_SESSION['company_id'] = $admin['company_id'];
            
            // Update last login
            $stmt = $db->prepare("UPDATE admins SET last_login = NOW() WHERE admin_id = ?");
            $stmt->bind_param("i", $admin['admin_id']);
            $stmt->execute();
            $stmt->close();
            
            // Log activity
            logActivity('admin', $admin['admin_id'], 'login', 'Admin logged in');
            
            // Redirect based on role
            if ($admin['role'] === 'super_admin') {
                header('Location: ' . SITE_URL . 'superadmin/dashboard.php');
            } else {
                header('Location: ' . SITE_URL . 'admin/dashboard.php');
            }
            exit;
        } else {
            // Check user
            $stmt = $db->prepare("
                SELECT user_id, username, password, first_name, last_name, email, company_id, is_active
                FROM users
                WHERE username = ? AND is_active = 1
            ");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $user = dbFetchOne($stmt);
            $stmt->close();
            
            if ($user && verifyPassword($password, $user['password'])) {
                // User login successful
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_type'] = 'user';
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['company_id'] = $user['company_id'];
                
                // Update last login
                $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
                $stmt->bind_param("i", $user['user_id']);
                $stmt->execute();
                $stmt->close();
                
                // Log activity
                logActivity('user', $user['user_id'], 'login', 'User logged in');
                
                header('Location: ' . SITE_URL . 'user/dashboard.php');
                exit;
            } else {
                $error = 'Invalid username or password';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Calling Card System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 40px;
            width: 100%;
            max-width: 400px;
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header h1 {
            color: #333;
            margin-bottom: 10px;
        }
        .login-header p {
            color: #666;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 500;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        .btn-login {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
        }
        .alert {
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .alert-error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        .alert-success {
            background: #efe;
            color: #3c3;
            border: 1px solid #cfc;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Calling Card System</h1>
            <p>Please login to continue</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autofocus>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" name="login" class="btn-login">Login</button>
        </form>
    </div>
</body>
</html>

