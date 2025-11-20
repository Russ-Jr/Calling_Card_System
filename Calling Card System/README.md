# Calling Card System - PHP Implementation

A comprehensive NFC-based calling card system with Super Admin, Admin, and User modules.

## System Overview

This system consists of:
- **Super Admin Module**: Manages companies and admin accounts
- **Admin Module**: Manages users, company details, and products (accessed via VB.NET WebView)
- **User Module**: Public dashboard accessible via NFC card tap (mobile portrait design)

## File Structure

```
Calling Card System/
├── config/
│   ├── config.php          # System configuration
│   └── database.php         # Database connection
├── includes/
│   └── functions.php        # Helper functions
├── api/
│   ├── admin.php           # Admin API endpoints
│   ├── user.php            # User API endpoints
│   ├── nfc.php             # NFC operations API
│   ├── superadmin.php      # Super admin API
│   ├── get_company.php     # Get company data
│   └── get_admin.php       # Get admin data
├── superadmin/
│   └── dashboard.php       # Super admin dashboard
├── admin/
│   └── dashboard.php       # Admin dashboard
├── user/
│   └── dashboard.php       # User dashboard (mobile)
├── assets/
│   ├── css/
│   │   ├── admin.css       # Admin styles
│   │   ├── dashboard.css   # Dashboard styles
│   │   └── user.css        # User mobile styles
│   └── js/
│       ├── admin.js        # Admin JavaScript
│       ├── dashboard.js   # Dashboard JavaScript
│       └── user.js         # User JavaScript
├── uploads/                # Upload directory (auto-created)
│   ├── profiles/
│   ├── logos/
│   └── products/
├── index.php               # Login page
├── logout.php              # Logout handler
├── database_schema.sql     # Database schema
└── README.md              # This file
```

## Installation

### 1. Database Setup

1. Import the database schema:
   ```sql
   mysql -u your_username -p your_database < database_schema.sql
   ```

2. The schema includes:
   - Default super admin account:
     - Username: `superadmin`
     - Password: `superadmin123` (CHANGE THIS IMMEDIATELY)
   - All required tables with relationships

### 2. Configuration

1. Edit `config/database.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'your_db_username');
   define('DB_PASS', 'your_db_password');
   define('DB_NAME', 'calling_card_system');
   ```

2. Edit `config/config.php`:
   - Update `SITE_URL` to match your domain
   - Configure SMTP settings in database (via system_settings table)
   - Adjust file upload limits if needed

### 3. File Permissions

Ensure upload directories are writable:
```bash
chmod -R 755 uploads/
```

### 4. Web Server Configuration

- PHP 7.4 or higher required
- MySQL 5.7 or higher
- Apache/Nginx with mod_rewrite enabled
- OpenSSL extension for encryption

## Usage

### Super Admin Access

1. Login at `https://yourdomain.com/callingcard/index.php`
   - Username: `superadmin`
   - Password: `superadmin123` (change immediately)

2. Super Admin can:
   - Create companies
   - Create admin accounts for each company
   - Manage all companies and admins

### Admin Access

1. Admin logs in via VB.NET WebView application
2. Admin can:
   - Manage card holders (users)
   - Register NFC cards
   - Manage company information
   - Upload product images
   - Edit profile and password

### User Access

1. Users access their dashboard by tapping NFC card on phone
2. NDEF URL format: `user/dashboard.php?data={encrypted_data}`
3. Users can:
   - View their profile
   - View company information
   - View products
   - Edit profile and password (after login via account tab)

## NFC Integration

### VB.NET Integration

The VB.NET application should:
1. Read NFC UID from card
2. Call API endpoint: `api/nfc.php?action=register_nfc`
3. Send POST data: `user_id` and `nfc_uid`
4. Receive NDEF URL from API
5. Write NDEF URL to NFC card using VB.NET code

### API Endpoint

**Register NFC Card:**
```
POST api/nfc.php
Action: register_nfc
Parameters:
  - user_id: User ID
  - nfc_uid: NFC Card UID
```

**Response:**
```json
{
  "success": true,
  "message": "NFC registered successfully",
  "data": {
    "ndef_url": "https://yourdomain.com/user/dashboard.php?data=...",
    "encrypted_data": "..."
  }
}
```

## Security Features

1. **Password Hashing**: Uses PHP `password_hash()` with bcrypt
2. **SQL Injection Protection**: All queries use prepared statements
3. **XSS Protection**: All outputs are sanitized
4. **NDEF URL Encryption**: AES-256-CBC encryption
5. **Session Management**: Secure session handling
6. **Access Control**: Role-based access control

## Default Credentials

⚠️ **IMPORTANT**: Change these immediately after installation!

- **Super Admin**:
  - Username: `superadmin`
  - Password: `superadmin123`

- **New Users**:
  - Username: `firstname+lastname+year` (auto-generated)
  - Password: `123456` (sent via email)

## API Endpoints

### Super Admin
- `api/superadmin.php` - Company and admin management
- `api/get_company.php` - Get company data
- `api/get_admin.php` - Get admin data

### Admin
- `api/admin.php` - Admin operations
  - `admin_login` - Login via account tab
  - `update_profile` - Update admin profile
  - `change_password` - Change password
  - `delete_user` - Delete user
  - `delete_product` - Delete product

### User
- `api/user.php` - User operations
  - `user_login` - Login via account tab
  - `update_profile` - Update user profile
  - `change_password` - Change password

### NFC
- `api/nfc.php` - NFC operations
  - `register_nfc` - Register NFC card
  - `get_user_by_uid` - Get user by NFC UID

## Database Schema

See `database_schema.sql` for complete schema. Key tables:
- `admins` - Admin accounts (with role: super_admin or admin)
- `users` - Card holder members
- `company` - Company information
- `products` - Product images
- `activity_logs` - System activity logs
- `email_logs` - Email sending logs

## Features

### Super Admin
- ✅ Create and manage companies
- ✅ Create and manage admin accounts
- ✅ View system statistics
- ✅ Company and admin management interface

### Admin
- ✅ Profile management
- ✅ Card holder (user) management
- ✅ NFC card registration
- ✅ Company information management
- ✅ Product image management
- ✅ Social media management
- ✅ Account tab login for editing

### User
- ✅ Mobile-optimized dashboard
- ✅ Profile display
- ✅ Company information display
- ✅ Product gallery
- ✅ Account tab for profile/password editing
- ✅ Social media links

## Troubleshooting

### Database Connection Issues
- Check database credentials in `config/database.php`
- Verify database exists and user has permissions
- Check MySQL service is running

### Upload Issues
- Check `uploads/` directory permissions (755)
- Verify PHP `upload_max_filesize` and `post_max_size`
- Check directory exists and is writable

### NFC Issues
- Verify encryption keys match between PHP and VB.NET
- Check NDEF URL format matches expected pattern
- Verify user exists before registering NFC

### Session Issues
- Check PHP session configuration
- Verify session directory is writable
- Check session timeout settings

## Development Notes

1. **NDEF URL Encryption**: Uses AES-256-CBC with keys defined in `config/config.php`
2. **File Uploads**: Files stored in `uploads/` with subdirectories
3. **Password Policy**: Minimum 6 characters (configurable)
4. **Activity Logging**: All actions logged in `activity_logs` table
5. **Email Logging**: Email sends logged in `email_logs` table

## Future Enhancements

- [ ] Complete user management modals
- [ ] Complete company management modals
- [ ] Complete product management modals
- [ ] Social media management interface
- [ ] Profile picture upload functionality
- [ ] Email template system
- [ ] Advanced reporting
- [ ] Multi-language support

## Support

For issues or questions:
1. Check the documentation files
2. Review the database schema documentation
3. Check activity logs for errors
4. Verify API endpoints are accessible

## License

This system is proprietary software. All rights reserved.

---

**Version**: 1.0.0  
**Last Updated**: 2024  
**PHP Version**: 7.4+  
**MySQL Version**: 5.7+

