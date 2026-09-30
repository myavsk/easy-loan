# 💰 Easy Loan - Advanced QR Code Management System

A complete PHP-based loan referral management system with bulk QR code generation, admin panel, and automated customer routing.

## ✨ Features

### Admin Panel (admin.php)
- 🔐 **Password-Protected Access** (Default: admin@123)
- 🏷️ **Set Target Portal URL** - Manage main loan portal URL
- 👤 **Agent Management** - Add/Delete agents
- 📦 **Bulk QR Generation** - Generate QR codes in batches (QR_001 to QR_100, etc.)
- 📥 **Download QR ZIP** - Download all QR codes as ZIP file for printing
- 🔗 **Assign QR to Agent** - Map QR codes to agents
- 📋 **QR Code Table** - View all QR codes with status, assigned agent, live links, and images
- 👥 **Agent List** - Manage all agents
- 🎨 **Responsive UI** - Works perfectly on desktop and mobile

### User Landing Page (index.php)
- 📱 **QR Code Scanning** - Automatic routing via `?qr=QR_001`
- 🤖 **Auto-Detection** - Finds assigned agent from QR code
- ✅ **Customer Form** - Name, Mobile, Loan Amount
- 🔄 **Smart Redirect** - Auto-redirect to portal with customer data
- 📊 **Application Logging** - All submissions logged to applications.log

## 🚀 Installation

### Prerequisites
- PHP 7.0+
- Web server (Apache, Nginx, etc.)
- Write permissions for `qr_codes/` directory

### Setup Steps

1. **Clone/Download Repository**
   ```bash
   git clone https://github.com/myavsk/easy-loan.git
   cd easy-loan
   ```

2. **Set Permissions**
   ```bash
   chmod 755 .
   chmod 666 data.json
   ```

3. **Access Admin Panel**
   - URL: `https://your-domain.com/admin.php`
   - Password: `admin@123`

4. **Generate First QR Codes**
   - Prefix: `QR`
   - Start: `1`
   - End: `100`
   - Click "Generate QR"

5. **Add Your First Agent**
   - Code: `AG101`
   - Name: `Your Agent Name`
   - Click "Add Agent"

6. **Assign QR to Agent**
   - Select QR Code: `QR_001`
   - Select Agent: `AG101`
   - Click "Assign QR"

7. **Download QR Codes**
   - Click "Download ZIP" to get all QR images
   - Print and distribute to agents

## 📊 Data Flow

```
Printed QR Code Scanned
        ↓
index.php?qr=QR_001
        ↓
Looks up QR_001 in data.json
        ↓
Finds assigned Agent (AG101)
        ↓
Shows Agent Name (Read-only)
        ↓
Customer fills form
        ↓
Validation checks
        ↓
Redirect to Portal URL with parameters:
  - customer_name
  - customer_phone
  - loan_amount
  - agent_code
  - agent_name
        ↓
Application logged to applications.log
```

## 🔒 Security

- ✅ Session-based authentication
- ✅ Input validation & sanitization
- ✅ XSS prevention
- ✅ Session timeout (1 hour)
- ✅ File upload safety

## 📁 File Structure

```
easy-loan/
├── admin.php          # Admin panel
├── index.php          # User landing page
├── config.php         # Configuration & helpers
├── data.json          # Data storage
├── qr_codes/          # QR code images
├── applications.log   # Application submissions
├── .gitignore         # Git ignore file
└── README.md          # This file
```

## 🔧 Configuration

### Change Admin Password
Edit `admin.php` line with `DEFAULT_PASSWORD`:
```php
define('DEFAULT_PASSWORD', 'your_new_password');
```

### Change Session Timeout
Edit `config.php`:
```php
define('SESSION_TIMEOUT', 7200); // 2 hours
```

## 📱 Deployment

### Render.com
1. Connect GitHub repo
2. Set build command: `echo "Build complete"`
3. Set start command: `echo "Use web server"`
4. Deploy

### Railway.app
1. Connect GitHub repo
2. Add PHP buildpack
3. Deploy

### Heroku
1. Add Procfile with PHP support
2. Deploy via GitHub

### Traditional Hosting
1. Upload via FTP/SFTP
2. Set permissions: `chmod 755 . && chmod 666 data.json`
3. Access `admin.php`

## 💾 Backup & Restore

### Backup
```bash
cp data.json data.json.backup
cp applications.log applications.log.backup
```

### Restore
```bash
cp data.json.backup data.json
cp applications.log.backup applications.log
```

## 🐛 Troubleshooting

### QR images not generating
- Check internet connection (needs API access)
- Verify `qr_codes/` directory exists
- Check write permissions

### ZIP download fails
- Verify ZipArchive extension is enabled
- Check PHP version (7.0+)

### Portal redirect not working
- Verify portal URL is correct
- Check URL is accessible
- Test with `http://` not just `https://`

## 📊 Monitoring

### View Applications
```bash
cat applications.log
```

### Check QR Status
Admin panel → QR Code Management Table

## 🤝 Support

For issues:
1. Check README
2. Verify file permissions
3. Check PHP version
4. Review applications.log

## 📄 License

MIT License - Feel free to use and modify

---

**Made with ❤️ for Easy Loan System**

**Version:** 2.0 - Advanced QR Management
