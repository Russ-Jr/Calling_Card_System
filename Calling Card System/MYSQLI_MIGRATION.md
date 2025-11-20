# MySQLi Migration Guide

## Overview
The system has been migrated from PDO to MySQLi to match your preferred database connection style.

## Changes Made

### 1. Database Connection (`config/database.php`)
- Changed from PDO to MySQLi
- Uses global `$conn` variable
- Connection style matches your example

### 2. Password Hashing (`includes/functions.php`)
- Changed from `PASSWORD_BCRYPT` to `PASSWORD_DEFAULT`
- Matches your password update script style

### 3. Helper Functions Added
- `dbFetchOne($stmt)` - Fetch single row
- `dbFetchAll($stmt)` - Fetch all rows
- `dbFetchColumn($stmt)` - Fetch single column value

### 4. Updated Files
- ✅ `config/database.php` - MySQLi connection
- ✅ `includes/functions.php` - Helper functions and updated database calls
- ✅ `index.php` - Updated login to use MySQLi
- ✅ `update_passwords.php` - Password update script created

## Usage Pattern

### Old PDO Style:
```php
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();
```

### New MySQLi Style:
```php
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$user = dbFetchOne($stmt);
$stmt->close();
```

## Password Update Script

Use `update_passwords.php` to update passwords:

```php
$users = [
    'superadmin' => 'superadmin123',
    'camp1admin' => '123456',
    // Add more as needed
];
```

Run: `php update_passwords.php` or access via browser.

## Files Still Using PDO

The following files may still need updating to MySQLi:
- `api/*.php` - API endpoints
- `admin/dashboard.php` - Admin dashboard
- `superadmin/dashboard.php` - Super admin dashboard
- `user/dashboard.php` - User dashboard

These files will need to be updated to use MySQLi prepared statements with `bind_param()` instead of PDO's `execute([...])`.

## Migration Checklist

- [x] Database connection updated to MySQLi
- [x] Helper functions created
- [x] Password hashing updated
- [x] Login page updated
- [x] Core functions updated (logActivity, getSetting, etc.)
- [ ] API endpoints need updating
- [ ] Dashboard pages need updating
- [ ] All other database queries need updating

## Quick Reference

### Binding Parameters
```php
// String
$stmt->bind_param("s", $string);

// Integer
$stmt->bind_param("i", $integer);

// Multiple
$stmt->bind_param("sis", $string1, $integer, $string2);
```

### Fetching Results
```php
// Single row
$row = dbFetchOne($stmt);

// All rows
$rows = dbFetchAll($stmt);

// Single column
$value = dbFetchColumn($stmt);
```

### Always Close Statements
```php
$stmt->close();
```

---

**Note**: Update remaining files as needed. The core infrastructure is now using MySQLi.

