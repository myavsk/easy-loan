# 🚀 Easy Loan System - Deployment Guide

## Quick Start Options

### Option 1: Render.com (Recommended for Beginners)

1. Go to [render.com](https://render.com)
2. Sign up with GitHub account
3. Click "New" → "Web Service"
4. Connect your GitHub repo `myavsk/easy-loan`
5. Choose Python as runtime
6. Build Command: `chmod +x deploy.sh && ./deploy.sh`
7. Start Command: `php -S 0.0.0.0:8000`
8. Deploy

### Option 2: Railway.app (Easiest)

1. Go to [railway.app](https://railway.app)
2. Connect GitHub
3. Select `myavsk/easy-loan` repo
4. Railway auto-detects PHP
5. Set environment: `PORT=3000`
6. Deploy (automatic)

### Option 3: Heroku (with buildpack)

1. Install Heroku CLI
2. Run:
   ```bash
   heroku login
   heroku create your-app-name
   git push heroku main
   ```
3. Done!

### Option 4: cPanel Hosting

1. Upload files via FTP
2. Set permissions:
   ```bash
   chmod 755 .
   chmod 666 data.json
   chmod 666 applications.log
   ```
3. Run `install.php` once
4. Access `admin.php`

### Option 5: VPS (DigitalOcean/Linode/AWS)

1. SSH into server
2. Install PHP:
   ```bash
   sudo apt update
   sudo apt install php php-fpm php-zip php-curl nginx -y
   ```
3. Clone repo:
   ```bash
   cd /var/www
   git clone https://github.com/myavsk/easy-loan.git
   cd easy-loan
   chmod 755 .
   chmod 666 data.json
   ```
4. Configure Nginx (use included `nginx.conf`)
5. Start PHP-FPM and Nginx
6. Access via your domain

## File Permissions

```bash
chmod 755 .                    # Directory readable
chmod 666 data.json            # Data writable
chmod 666 applications.log     # Log writable
chmod 755 qr_codes             # QR directory
chmod 644 .htaccess            # Apache config readable
```

## Environment Variables (Optional)

```bash
PHP_MEMORY_LIMIT=256M
MAX_UPLOAD_SIZE=50M
DEFAULT_PASSWORD=your_secure_password
```

## Health Check

After deployment, visit:
```
https://your-domain.com/health-check.php
```

You should see:
```json
{
  "status": "ok",
  "php_version": "8.1.x",
  "data_file_exists": true,
  "qr_dir_writable": true
}
```

## URLs After Deployment

- Admin Panel: `https://your-domain.com/admin.php`
- User Page: `https://your-domain.com/index.php?qr=QR_001`
- Health Check: `https://your-domain.com/health-check.php`
- Installation: `https://your-domain.com/install.php`

## First Time Setup

1. Visit `/install.php` (auto-creates files)
2. Go to `/admin.php`
3. Login: `admin@123`
4. Update Portal URL
5. Add Agents
6. Generate QR Codes
7. Assign QR to Agents

## Monitoring

### View Applications Log
```bash
tail -f applications.log
```

### Database Backup
```bash
cp data.json data.json.$(date +%Y%m%d_%H%M%S).backup
```

## SSL/HTTPS

Most hosting providers provide free SSL:
- Render: Automatic
- Railway: Automatic
- Heroku: Built-in
- cPanel: Let's Encrypt (free)
- VPS: Certbot with Let's Encrypt

## Performance Tips

1. Enable Gzip compression
2. Cache QR images
3. Use CDN for images
4. Optimize database queries
5. Monitor with health-check.php

## Troubleshooting

### 404 errors
- Check .htaccess is present
- Enable mod_rewrite on Apache
- Check nginx.conf for Nginx

### QR images not generating
- Check internet connectivity
- Verify `qr_codes/` directory exists
- Check write permissions

### ZIP download fails
- Verify ZipArchive extension
- Check PHP version (7.0+)
- Check temp directory permissions

## Support

- Check README.md
- Review applications.log
- Run health-check.php
- Check file permissions

---

**Successfully Deployed! 🎉**
