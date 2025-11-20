# Database Quick Reference Guide

## Table Summary

| Table Name | Purpose | Key Relationships |
|------------|---------|-------------------|
| `admins` | Admin accounts | Creates users, updates company/products |
| `users` | Card holder members | Has social media, additional data |
| `admin_social_media` | Admin social links | Belongs to admin |
| `user_social_media` | User social links | Belongs to user |
| `company` | Company main info | Has addresses, contacts, emails, social media |
| `company_addresses` | Company addresses | Belongs to company |
| `company_contacts` | Company contacts | Belongs to company |
| `company_emails` | Company emails | Belongs to company |
| `company_social_media` | Company social links | Belongs to company |
| `products` | Product images | Created by admin |
| `system_settings` | System configuration | Updated by admin |
| `user_additional_data` | Optional user data | Belongs to user |
| `activity_logs` | Activity audit trail | Tracks admin/user actions |
| `email_logs` | Email sending logs | Tracks credential emails |

## Common Queries

### Get User Dashboard Data
```sql
SELECT 
    u.*,
    c.logo_path AS company_logo,
    c.company_name
FROM users u
CROSS JOIN company c
WHERE u.user_id = ? AND u.is_active = 1;
```

### Get User Social Media
```sql
SELECT * FROM user_social_media 
WHERE user_id = ? AND is_active = 1 
ORDER BY display_order;
```

### Get Company Section Data
```sql
-- Company Info
SELECT * FROM company WHERE is_active = 1 LIMIT 1;

-- Addresses
SELECT * FROM company_addresses 
WHERE company_id = ? AND is_active = 1 
ORDER BY display_order;

-- Contacts
SELECT * FROM company_contacts 
WHERE company_id = ? AND is_active = 1 
ORDER BY display_order;

-- Emails
SELECT * FROM company_emails 
WHERE company_id = ? AND is_active = 1 
ORDER BY display_order;

-- Social Media
SELECT * FROM company_social_media 
WHERE company_id = ? AND is_active = 1 
ORDER BY display_order;
```

### Get Products
```sql
SELECT * FROM products 
WHERE is_active = 1 
ORDER BY display_order;
```

### Get All Users (Admin Dashboard)
```sql
SELECT 
    u.*,
    a.username AS created_by_username
FROM users u
LEFT JOIN admins a ON u.created_by = a.admin_id
WHERE u.is_active = 1
ORDER BY u.created_at DESC;
```

### Find User by NFC UID
```sql
SELECT * FROM users 
WHERE nfc_uid = ? AND is_active = 1;
```

### Find User by NDEF URL Data
```sql
-- After decrypting the NDEF URL parameter
SELECT * FROM users 
WHERE CONCAT(LOWER(REPLACE(first_name, ' ', '')), 
             LOWER(REPLACE(last_name, ' ', '')), 
             user_id) = ? 
AND is_active = 1;
```

### Admin Login
```sql
SELECT * FROM admins 
WHERE username = ? AND is_active = 1;
```

### User Login
```sql
SELECT * FROM users 
WHERE username = ? AND is_active = 1;
```

## Field Naming Conventions

- **IDs**: `{table}_id` (e.g., `user_id`, `admin_id`)
- **Foreign Keys**: Descriptive name (e.g., `created_by`, `company_id`)
- **Timestamps**: `created_at`, `updated_at`, `last_login`
- **Status Flags**: `is_active` (TINYINT(1), 1=active, 0=inactive)
- **Order Fields**: `display_order` (INT, for sorting)

## Data Types

- **IDs**: `INT AUTO_INCREMENT PRIMARY KEY`
- **Names**: `VARCHAR(100)` or `VARCHAR(255)`
- **Emails**: `VARCHAR(255)`
- **Passwords**: `VARCHAR(255)` (for hashed passwords)
- **URLs**: `VARCHAR(500)`
- **Text**: `TEXT` or `VARCHAR(255)` depending on length
- **Timestamps**: `TIMESTAMP DEFAULT CURRENT_TIMESTAMP`
- **Booleans**: `TINYINT(1)` (1=true, 0=false)
- **Coordinates**: `DECIMAL(10,8)` for latitude, `DECIMAL(11,8)` for longitude

## Important Notes

1. **Username Format**: `firstname+lastname+year` (no spaces, lowercase)
2. **Default Password**: `123456` (must be hashed before storage)
3. **NDEF URL Format**: `user/dashboard.php?{encrypted_data}`
4. **Encryption**: AES-256 (32-byte key, 16-byte IV)
5. **NFC Card Type**: NTAG213
6. **Base URL**: `https://tito.ndasphilsinc.com/callingcard/`

## Required Indexes

All foreign keys are indexed. Additional indexes on:
- `username` fields (for login lookups)
- `email` fields (for unique constraints and lookups)
- `nfc_uid` fields (for card lookups)
- `is_active` fields (for filtering active records)

## Default Values

- **Passwords**: Should be hashed using `password_hash()` in PHP
- **is_active**: Default `1` (active)
- **display_order**: Default `0`
- **created_at**: Auto-set to current timestamp
- **updated_at**: Auto-updated on record modification

