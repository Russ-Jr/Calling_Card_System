# Password Hash Setup - Calling Card System

## Default Super Admin Credentials

**Username**: `superadmin`  
**Password**: `superadmin123`

⚠️ **IMPORTANT**: Change this password immediately after first login!

## Password Hash

The password hash in the database schema is:
```
$2y$10$6sgPtrClUJHqaHqgLrut9.AL0UaTkAUuQ/A0Z/bFbpTn9giAvAZYy
```

This is a bcrypt hash generated using PHP's `password_hash()` function with `PASSWORD_BCRYPT` algorithm.

## How It Works

### Login Process (index.php)

1. User enters username and password
2. System queries database for user/admin with matching username
3. Uses `password_verify($password, $hash)` to verify password
4. If verified, creates session and redirects

### Password Verification

The system uses the `verifyPassword()` function from `includes/functions.php`:

```php
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}
```

This function uses PHP's built-in `password_verify()` which:
- Automatically handles bcrypt hashes
- Compares plain text password with stored hash
- Returns `true` if password matches, `false` otherwise

## Generating New Password Hashes

### Method 1: Using PHP Script

Run the included script:
```bash
php generate_password_hash.php
```

### Method 2: Using PHP Command Line

```bash
php -r "echo password_hash('your_password_here', PASSWORD_BCRYPT);"
```

### Method 3: Using PHP Code

```php
<?php
$password = 'your_password_here';
$hash = password_hash($password, PASSWORD_BCRYPT);
echo $hash;
?>
```

## Updating Password in Database

### For Super Admin

```sql
UPDATE admins 
SET password = '$2y$10$...your_new_hash...' 
WHERE username = 'superadmin';
```

### For Regular Admin

```sql
UPDATE admins 
SET password = '$2y$10$...your_new_hash...' 
WHERE admin_id = [admin_id];
```

### For User

```sql
UPDATE users 
SET password = '$2y$10$...your_new_hash...' 
WHERE user_id = [user_id];
```

## Password Requirements

- Minimum length: 6 characters (configurable in `config/config.php`)
- Algorithm: bcrypt (PASSWORD_BCRYPT)
- Cost factor: 10 (default, can be increased for stronger hashing)

## Security Notes

1. **Never store plain text passwords** - Always hash passwords before storing
2. **Use password_verify()** - Never compare hashes directly
3. **Change default passwords** - Always change default credentials after installation
4. **Use strong passwords** - Encourage users to use strong passwords
5. **Hash algorithm** - bcrypt is secure and recommended by PHP

## Testing Login

After importing the database schema, you can test login with:

1. Navigate to: `https://tito.ndasphilsinc.com/callingcard/index.php`
2. Enter:
   - Username: `superadmin`
   - Password: `superadmin123`
3. You should be redirected to the super admin dashboard

## Troubleshooting

### Login Not Working

1. **Check password hash format**: Should start with `$2y$10$`
2. **Verify database**: Ensure password hash was inserted correctly
3. **Check PHP version**: Requires PHP 5.5+ for password functions
4. **Verify function**: Check that `password_verify()` is working

### Generate New Hash

If you need to reset the password:

```php
<?php
// Generate new hash
$newPassword = 'new_password_here';
$hash = password_hash($newPassword, PASSWORD_BCRYPT);

// Update in database
// UPDATE admins SET password = '$hash' WHERE username = 'superadmin';
?>
```

## Default User Passwords

New users created through the system get:
- **Default password**: `123456`
- **Password is hashed** before storing in database
- **Password is sent via email** to the user

---

**Last Updated**: 2024  
**Hash Algorithm**: bcrypt (PASSWORD_BCRYPT)  
**Cost Factor**: 10

