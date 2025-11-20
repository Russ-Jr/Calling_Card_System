# Database Schema Summary - Calling Card System

## Files Created

1. **database_schema.sql** - Complete MySQL database schema with:
   - 14 tables
   - Foreign key relationships
   - Indexes for performance
   - Default data inserts
   - Views for common queries
   - Stored procedure for user creation

2. **DATABASE_SCHEMA_DOCUMENTATION.md** - Comprehensive documentation including:
   - Detailed table descriptions
   - Relationship diagrams
   - Workflow explanations
   - Security considerations
   - Implementation notes

3. **DATABASE_QUICK_REFERENCE.md** - Quick reference guide with:
   - Table summary
   - Common SQL queries
   - Field naming conventions
   - Data type guidelines

## Database Overview

### Total Tables: 14

#### Core Tables (4)
- `admins` - Administrator accounts
- `users` - Card holder members/employees
- `company` - Company information
- `system_settings` - System configuration

#### Relationship Tables (8)
- `admin_social_media` - Admin social media links
- `user_social_media` - User social media links
- `company_addresses` - Multiple company addresses
- `company_contacts` - Multiple company contact numbers
- `company_emails` - Multiple company email addresses
- `company_social_media` - Company social media links
- `products` - Product images
- `user_additional_data` - Optional user data fields

#### Logging Tables (2)
- `activity_logs` - System activity audit trail
- `email_logs` - Email sending logs

## Key Features

### ✅ Admin Module Support
- Admin authentication and profile management
- Admin NFC card support (public view)
- User creation and management
- Company section management
- Product image management

### ✅ User Module Support
- User authentication
- User profile management
- NFC card registration (UID and NDEF URL)
- Social media management
- Public dashboard data

### ✅ NFC Integration
- NFC UID storage
- NDEF URL storage (encrypted)
- Support for NTAG213 cards
- URL format: `user/dashboard.php?{encrypted_data}`

### ✅ Company Section
- Multiple addresses (branches)
- Multiple contact numbers
- Multiple email addresses
- Social media links
- Map location (latitude/longitude)
- Company logo

### ✅ Security Features
- Password hashing support
- Encrypted NDEF URLs
- Activity logging
- Email logging
- Foreign key constraints

## Database Relationships

```
Admin (1) ──→ (N) Users
Admin (1) ──→ (N) Admin Social Media
Admin (1) ──→ (N) Products
Admin (1) ──→ (N) Company Updates
Admin (1) ──→ (N) System Settings Updates

User (1) ──→ (N) User Social Media
User (1) ──→ (N) User Additional Data

Company (1) ──→ (N) Company Addresses
Company (1) ──→ (N) Company Contacts
Company (1) ──→ (N) Company Emails
Company (1) ──→ (N) Company Social Media
```

## Next Steps

1. **Import Schema**: Run `database_schema.sql` on your MySQL server
2. **Update Default Admin**: Change default admin password
3. **Configure SMTP**: Update SMTP settings in `system_settings`
4. **Update Base URL**: Set correct site URL in `system_settings`
5. **Test Connections**: Verify database connectivity from PHP
6. **Implement Authentication**: Create login system using the schema
7. **Implement NFC Integration**: Connect VB.NET app to database for NFC operations

## System Requirements Mapping

| Requirement | Database Solution |
|-------------|-------------------|
| Admin login | `admins` table with username/password |
| User registration | `users` table with auto-generated username |
| NFC UID storage | `nfc_uid` field in `admins` and `users` |
| NDEF URL storage | `ndef_url` field in `admins` and `users` |
| Profile pictures | `profile_picture` field (file path) |
| Social media links | Separate tables for admin/user/company |
| Company details | `company` table with related address/contact/email tables |
| Product images | `products` table with `display_order` |
| Logo management | `company.logo_path` field |
| Email credentials | SMTP settings in `system_settings` |
| Activity tracking | `activity_logs` and `email_logs` tables |

## Important Implementation Notes

1. **Password Hashing**: Always use PHP `password_hash()` before storing passwords
2. **NDEF URL Encryption**: Use AES encryption as shown in VB.NET code reference
3. **File Uploads**: Store file paths, not actual files in database
4. **Username Generation**: Format: `strtolower($firstname . $lastname) . $year`
5. **Default Password**: `123456` (must be hashed and sent via email)
6. **NFC Card Type**: NTAG213 (4-byte pages, starts at page 4)

## Support

For detailed information, refer to:
- **DATABASE_SCHEMA_DOCUMENTATION.md** - Full documentation
- **DATABASE_QUICK_REFERENCE.md** - Quick queries and reference
- **database_schema.sql** - Complete SQL schema

---

**Database Name**: `calling_card_system`  
**Character Set**: `utf8mb4`  
**Collation**: `utf8mb4_unicode_ci`  
**Engine**: `InnoDB`

