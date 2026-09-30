#!/bin/bash

echo "Easy Loan System - Deployment Script"
echo "====================================="

# Create necessary directories
mkdir -p qr_codes
chmod 755 qr_codes

# Set file permissions
chmod 644 data.json 2>/dev/null || true
chmod 644 applications.log 2>/dev/null || true
chmod 644 .htaccess

# Create symlink if needed
if [ ! -d public ]; then
  ln -s . public
fi

echo "✓ Directories created"
echo "✓ Permissions set"
echo "✓ Ready for deployment"
echo ""
echo "Admin Panel: /admin.php"
echo "User Page: /index.php"
echo "Password: admin@123"
