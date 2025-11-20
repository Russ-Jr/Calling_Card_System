# VB.NET Calling Card System - Setup Guide

## Overview
This VB.NET application provides a WebView interface to the PHP admin dashboard and NFC Bridge functionality for registering NFC cards.

## Requirements

### NuGet Packages
Install the following NuGet packages:
1. **Microsoft.Web.WebView2** - For WebView2 control
2. **Newtonsoft.Json** - For JSON parsing
3. **System.Net.Http** - For HTTP requests

### Hardware
- ACR122u NFC Reader (or compatible PC/SC reader)
- NTAG213 NFC Cards

### Software
- Windows 10/11
- Visual Studio 2019 or later
- .NET Framework 4.7.2 or later
- WebView2 Runtime (usually installed automatically)

## Installation Steps

### 1. Create New Project
1. Open Visual Studio
2. Create new **Windows Forms App (.NET Framework)** project
3. Name it: **CallingCard**

### 2. Install NuGet Packages
1. Right-click on project → **Manage NuGet Packages**
2. Install:
   - `Microsoft.Web.WebView2`
   - `Newtonsoft.Json`

### 3. Add Form
1. Rename `Form1.vb` to `FormCallCard.vb`
2. Copy the code from `FormCallCard.vb` to your form
3. Copy the designer code to `FormCallCard.Designer.vb`

### 4. Add Controls to Form
In the Form Designer, add:
- **WebView2** control (from Toolbox)
- **Label** control (for status) - Name: `lblStatus`
- **Button** control (for NFC registration) - Name: `btnRegisterNFC`

### 5. Configure WebView2
1. Set WebView2 to **Dock = Fill**
2. Set status label at bottom
3. Set button at bottom right

### 6. Update Configuration
In `FormCallCard.vb`, verify these constants:
```vb
Private Const WEB_URL As String = "https://tito.ndasphilsinc.com/callingcard/"
Private Const API_URL As String = "https://tito.ndasphilsinc.com/callingcard/api/"
```

## How It Works

### 1. WebView Integration
- Application opens and navigates to the PHP login page
- After login, displays the admin dashboard
- JavaScript in the dashboard can trigger NFC registration

### 2. NFC Registration Flow
1. Admin clicks "Register NFC" button in dashboard for a user
2. JavaScript sends message to VB.NET application
3. VB.NET application:
   - Waits for NFC card tap
   - Reads NFC UID
   - Sends UID to PHP API (`api/nfc.php`)
   - Receives NDEF URL from API
   - Writes NDEF URL to NFC card
   - Confirms success

### 3. NFC Card Writing
- Uses NTAG213 format
- Writes NDEF message starting at page 4
- Encodes URL with proper URI identifier codes

## API Integration

### Register NFC Endpoint
```
POST: https://tito.ndasphilsinc.com/callingcard/api/nfc.php
Parameters:
  - action: "register_nfc"
  - user_id: [User ID]
  - nfc_uid: [NFC Card UID]

Response:
{
  "success": true,
  "message": "NFC registered successfully",
  "data": {
    "ndef_url": "https://tito.ndasphilsinc.com/callingcard/user/dashboard.php?data=..."
  }
}
```

## JavaScript Integration

The PHP dashboard should include this JavaScript to communicate with VB.NET:

```javascript
// Send message to VB.NET when Register NFC button is clicked
function registerNFC(userId) {
    if (window.chrome && window.chrome.webview) {
        window.chrome.webview.postMessage(JSON.stringify({
            action: 'registerNFC',
            userId: userId
        }));
    } else {
        alert('NFC registration requires the desktop application.');
    }
}
```

## Troubleshooting

### WebView2 Not Loading
- Ensure WebView2 Runtime is installed
- Check internet connection
- Verify URL is accessible

### NFC Reader Not Detected
- Ensure ACR122u driver is installed
- Check reader is connected via USB
- Verify winscard.dll is available (usually in System32)

### NFC Card Read/Write Fails
- Ensure card is NTAG213 compatible
- Check card is not locked
- Verify card is properly positioned on reader
- Try removing and re-tapping the card

### API Connection Issues
- Verify PHP API is accessible
- Check firewall settings
- Verify SSL certificate is valid
- Check API endpoint URL is correct

## Testing

1. **Test WebView**:
   - Run application
   - Should navigate to login page
   - Login with admin credentials
   - Dashboard should load

2. **Test NFC Reader**:
   - Tap a card on the reader
   - Check status label shows UID
   - Verify card is detected

3. **Test Full Flow**:
   - Create a user in admin dashboard
   - Click "Register NFC" for that user
   - Tap NFC card when prompted
   - Verify card is registered and NDEF URL is written

## Security Notes

- NFC UID is sent to PHP API over HTTPS
- NDEF URL contains encrypted user data
- API requires admin authentication
- All communications should be over HTTPS

## File Structure

```
CallingCard/
├── FormCallCard.vb          # Main form code
├── FormCallCard.Designer.vb  # Form designer code
├── My Project/              # Project settings
└── bin/                     # Compiled output
```

## Additional Features

You can extend the application with:
- Card reading functionality (read existing NDEF URLs)
- Batch card registration
- Card verification
- Logging and error reporting
- Settings configuration UI

## Support

For issues:
1. Check Windows Event Viewer for errors
2. Verify NFC reader drivers
3. Test API endpoints directly
4. Check PHP error logs
5. Verify database connectivity

---

**Version**: 1.0.0  
**Last Updated**: 2024  
**Framework**: .NET Framework 4.7.2+  
**WebView**: Microsoft Edge WebView2

