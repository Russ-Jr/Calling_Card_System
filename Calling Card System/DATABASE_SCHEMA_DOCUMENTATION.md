# Calling Card System - Database Schema Documentation

## Overview
This document provides a comprehensive explanation of the database schema for the Calling Card PHP System. The system uses MySQL database hosted on a cloud server (cPanel).

## Database Structure

### Core Tables

#### 1. **admins** Table
Stores administrator account information.

**Purpose**: Manages admin accounts that access the VB.NET WebView dashboard.

**Key Fields**:
- `admin_id`: Primary key
- `username`: Unique admin username for login
- `password`: Hashed password (use PHP `password_hash()`)
- `nfc_uid`: NFC Card UID for admin's card (allows public view when tapped)
- `ndef_url`: NDEF URL stored on admin NFC card
- `profile_picture`: Path to admin profile picture

**Relationships**:
- One admin can create many users (`users.created_by`)
- One admin can have many social media links (`admin_social_media`)

**Usage**:
- Admin login authentication
- Admin profile display in dashboard
- Admin public view when NFC card is tapped

---

#### 2. **users** Table
Stores card holder member/employee information.

**Purpose**: Manages all card holder members (employees) who have NFC cards.

**Key Fields**:
- `user_id`: Primary key
- `username`: Auto-generated format: `firstname+lastname+year` (e.g., "johnsmith2024")
- `password`: Default "123456" (hashed), sent via email
- `nfc_uid`: NFC Card UID read from the card
- `ndef_url`: Encrypted NDEF URL format: `user/dashboard.php?{firstname+lastname+user_id}`
- `account_created_year`: Year when account was created (used in username generation)

**Relationships**:
- Many users created by one admin (`created_by` → `admins.admin_id`)
- One user can have many social media links (`user_social_media`)
- One user can have additional data (`user_additional_data`)

**Usage**:
- User registration flow
- User dashboard display
- NFC card registration process

**Username Generation Logic**:
```php
$username = strtolower($firstname . $lastname) . $year;
// Example: "john" + "smith" + "2024" = "johnsmith2024"
```

---

#### 3. **admin_social_media** Table
Stores admin's social media links.

**Purpose**: Manages editable social media icons/links for admin profile.

**Key Fields**:
- `platform_name`: Name of platform (Facebook, Gmail, LinkedIn, etc.)
- `platform_icon`: Icon class or image path
- `url`: Social media profile URL
- `display_order`: Order for displaying icons

**Usage**:
- Display social media icons below admin profile picture
- Admin can add/edit/delete social media links

---

#### 4. **user_social_media** Table
Stores user's social media links.

**Purpose**: Manages editable social media icons/links for user profiles.

**Key Fields**: Same as `admin_social_media`

**Usage**:
- Display social media icons below user profile picture
- User can add/edit/delete social media links (when logged in)

---

#### 5. **company** Table
Stores main company information.

**Purpose**: Central company data displayed on all user dashboards.

**Key Fields**:
- `company_name`: Company name
- `map_latitude` / `map_longitude`: GPS coordinates for map
- `map_location_text`: Text description of location
- `logo_path`: Path to company logo (displayed in header)

**Relationships**:
- One company has many addresses (`company_addresses`)
- One company has many contacts (`company_contacts`)
- One company has many emails (`company_emails`)
- One company has many social media links (`company_social_media`)

**Usage**:
- Company section displayed on all user dashboards
- Admin can modify company details
- Logo displayed in header of all dashboards

---

#### 6. **company_addresses** Table
Stores multiple company addresses (branches).

**Purpose**: Allows admin to add multiple company addresses.

**Key Fields**:
- `address_type`: Type of address (main, branch, etc.)
- `address_line1`, `address_line2`: Address lines
- `city`, `state_province`, `postal_code`, `country`: Location details
- `full_address`: Complete formatted address
- `display_order`: Order for displaying addresses

**Usage**:
- Display company addresses in company section
- Admin can add multiple branch addresses

---

#### 7. **company_contacts** Table
Stores multiple company contact numbers.

**Purpose**: Allows admin to add multiple company contact numbers.

**Key Fields**:
- `contact_type`: Type (phone, mobile, fax, etc.)
- `contact_number`: The actual contact number
- `display_label`: Label (e.g., "Main Office", "Sales")

**Usage**:
- Display company contacts in company section
- Admin can add multiple contact numbers

---

#### 8. **company_emails** Table
Stores multiple company email addresses.

**Purpose**: Allows admin to add multiple company emails.

**Key Fields**:
- `email_address`: Company email
- `display_label`: Label (e.g., "General", "Sales", "Support")

**Usage**:
- Display company emails in company section
- Admin can add multiple email addresses

---

#### 9. **company_social_media** Table
Stores company social media links.

**Purpose**: Manages company social media platforms.

**Key Fields**: Similar to `admin_social_media`

**Usage**:
- Display company social media in company section
- Admin can add/edit company social media

---

#### 10. **products** Table
Stores product images.

**Purpose**: Manages product images uploaded by admin.

**Key Fields**:
- `image_path`: Path to product image file
- `image_alt_text`: Alt text for accessibility
- `display_order`: Order for displaying images (arrangeable)

**Usage**:
- Display product images in product section
- Admin can upload/reorder/delete product images
- Images are clickable for full view

---

#### 11. **system_settings** Table
Stores system-wide configuration.

**Purpose**: Manages system settings like SMTP, encryption keys, etc.

**Key Fields**:
- `setting_key`: Unique setting identifier
- `setting_value`: Setting value
- `setting_description`: Description of the setting

**Predefined Settings**:
- `site_url`: Base URL of the website
- `smtp_host`, `smtp_port`, `smtp_username`, `smtp_password`: SMTP configuration
- `smtp_from_email`, `smtp_from_name`: Email sender details
- `default_user_password`: Default password for new users (123456)
- `encryption_key`: AES encryption key (32 bytes)
- `encryption_iv`: AES encryption IV (16 bytes)
- `nfc_card_type`: NFC card type (NTAG213)

**Usage**:
- System configuration management
- SMTP email settings
- Encryption keys for NDEF URL encryption

---

#### 12. **user_additional_data** Table
Stores optional additional data for users.

**Purpose**: Flexible storage for optional user data fields.

**Key Fields**:
- `field_name`: Name of the field
- `field_value`: Value of the field
- `field_type`: Type of field (text, number, date, etc.)

**Usage**:
- Store optional user data mentioned in requirements
- Flexible schema for future extensions

---

#### 13. **activity_logs** Table (Optional)
Stores system activity logs.

**Purpose**: Audit trail for system activities.

**Key Fields**:
- `user_type`: Type of user (admin or user)
- `user_id`: ID of the user who performed action
- `action`: Action performed (login, create_user, update_profile, etc.)
- `description`: Detailed description
- `ip_address`: IP address of the user
- `user_agent`: Browser/user agent information

**Usage**:
- System auditing
- Security monitoring
- Debugging

---

#### 14. **email_logs** Table (Optional)
Stores email sending logs.

**Purpose**: Track email sending for credential emails.

**Key Fields**:
- `recipient_email`: Email address of recipient
- `email_type`: Type of email (credentials, password_reset, etc.)
- `status`: Status (sent, failed, pending)
- `error_message`: Error message if failed

**Usage**:
- Track credential emails sent to users
- Debug email delivery issues

---

## Database Relationships Diagram

```
admins
  ├── 1:N → users (created_by)
  ├── 1:N → admin_social_media
  ├── 1:N → company (updated_by)
  ├── 1:N → products (created_by)
  └── 1:N → system_settings (updated_by)

users
  ├── N:1 → admins (created_by)
  ├── 1:N → user_social_media
  └── 1:N → user_additional_data

company
  ├── 1:N → company_addresses
  ├── 1:N → company_contacts
  ├── 1:N → company_emails
  └── 1:N → company_social_media
```

---

## Key Workflows

### 1. User Registration Flow

1. Admin enters user details in admin dashboard
2. User record created in `users` table (without NFC data)
3. Admin clicks "NDEF URL Registration" button
4. VB.NET application waits for NFC card tap
5. NFC UID read and sent to database
6. `users` table updated with `nfc_uid`
7. NDEF URL generated: `user/dashboard.php?{firstname+lastname+user_id}`
8. NDEF URL encrypted using AES (from `system_settings`)
9. Encrypted NDEF URL written to NFC card
10. `users.ndef_url` updated with encrypted URL
11. User account credentials generated:
    - Username: `firstname+lastname+year`
    - Password: `123456` (from `system_settings`)
12. Credentials sent via SMTP email
13. `email_logs` entry created

### 2. Admin Login Flow

1. Admin enters username/password in account tab
2. Query `admins` table for matching username
3. Verify password hash
4. Update `admins.last_login`
5. Create session
6. Log activity in `activity_logs`

### 3. User Dashboard Access Flow

1. User taps NFC card on phone
2. Phone reads NDEF URL from card
3. Browser opens URL: `user/dashboard.php?{encrypted_data}`
4. PHP decrypts the data to get user identifier
5. Query `users` table to get user data
6. Display user dashboard with:
   - Logo from `company.logo_path`
   - User profile from `users` table
   - User social media from `user_social_media`
   - Company section from `company` and related tables
   - Products from `products` table

### 4. Admin Public View Flow

1. Admin taps admin NFC card on phone
2. Phone reads NDEF URL from admin card
3. Browser opens admin public dashboard
4. Query `admins` table for admin data
5. Display dashboard similar to user dashboard but:
   - Hide employee list
   - Hide sensitive admin details
   - Show only public information

---

## Security Considerations

1. **Password Hashing**: Always use PHP `password_hash()` and `password_verify()`
2. **SQL Injection**: Use prepared statements (PDO or mysqli)
3. **XSS Protection**: Sanitize all user inputs and escape outputs
4. **NDEF URL Encryption**: Use AES encryption (32-byte key, 16-byte IV) as shown in VB.NET code
5. **Session Management**: Implement secure session handling
6. **Access Control**: Verify admin/user login before allowing edits

---

## Indexes

The schema includes indexes on:
- Foreign keys for faster joins
- Username and email fields for faster lookups
- NFC UID fields for card lookups
- Timestamp fields for sorting/filtering

---

## Default Data

The schema includes:
- Default admin account (username: `admin`, password: `admin123` - **CHANGE THIS**)
- Default company record
- System settings with default values

---

## Notes for Implementation

1. **Password Storage**: The default password "123456" in the schema should be hashed using PHP's `password_hash()` function before storing.

2. **NDEF URL Format**: The NDEF URL format should be:
   ```
   https://tito.ndasphilsinc.com/callingcard/user/dashboard.php?{encrypted_data}
   ```
   Where `{encrypted_data}` is the AES-encrypted string of `firstname+lastname+user_id`.

3. **File Uploads**: All file paths (profile pictures, logos, product images) should be stored relative to a base upload directory.

4. **SMTP Configuration**: Store SMTP credentials securely. Consider encrypting the password in `system_settings`.

5. **Year in Username**: Use `YEAR(NOW())` or `date('Y')` to get the current year for username generation.

6. **Display Order**: Use `display_order` fields to allow drag-and-drop reordering in the admin interface.

---

## Future Enhancements

Consider adding:
- Password reset functionality
- Email verification
- Two-factor authentication
- API endpoints for mobile apps
- Analytics tracking
- Backup/restore functionality

