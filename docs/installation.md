# Installation Guide

## Laravel Fortinet Captive Portal

**Version:** 1.0.0  
**Last Updated:** August 2025  
**Compatible with:** FortiOS 7.6.3+, Laravel 12, PHP 8.3+

---

## Table of Contents

1. [System Requirements](#system-requirements)
2. [Pre-Installation Checklist](#pre-installation-checklist)
3. [Installation Methods](#installation-methods)
4. [Standard Installation](#standard-installation)
5. [Docker/Sail Installation](#dockersail-installation-recommended)
6. [FortiGate Configuration](#fortigate-configuration)
7. [Post-Installation Setup](#post-installation-setup)
8. [Troubleshooting](#troubleshooting)

---

## System Requirements

### Server Requirements

- **PHP:** 8.3 or higher with extensions:
  - BCMath, Ctype, cURL, DOM, Fileinfo
  - JSON, Mbstring, OpenSSL, PCRE, PDO
  - Tokenizer, XML, Redis (optional)
- **Database:** MySQL 8.0+ or MariaDB 10.3+
- **Web Server:** Apache 2.4+ or Nginx 1.18+
- **Node.js:** 18.x or higher
- **Composer:** 2.x
- **Redis:** 7.0+ (optional, for caching and queues)

### FortiGate Requirements

- **FortiOS Version:** 7.6.3 or higher
- **API Access:** REST API enabled
- **Admin Privileges:** Required for API token generation
- **Network Access:** HTTPS connectivity between Laravel server and FortiGate

### Recommended Specifications

- **CPU:** 2+ cores
- **RAM:** 4GB minimum, 8GB recommended
- **Storage:** 20GB minimum
- **Network:** Stable connection to FortiGate management interface

---

## Pre-Installation Checklist

Before starting the installation, ensure you have:

- [ ] Server meeting minimum requirements
- [ ] FortiGate device with API access
- [ ] Database server installed and running
- [ ] SMTP server credentials for email notifications
- [ ] Domain name configured (optional but recommended)
- [ ] SSL certificate (for production)
- [ ] Backup of any existing data

---

## Installation Methods

Choose one of the following installation methods:

1. **Standard Installation** - Traditional PHP/MySQL setup
2. **Docker/Sail Installation** - Containerized setup (recommended)

---

## Standard Installation

### Step 1: Clone the Repository

```bash
# Clone the repository
git clone https://github.com/PhilCANDIDO/laravel-fortinet-captiveportal.git
cd laravel-fortinet-captiveportal
```

### Step 2: Install PHP Dependencies

```bash
# Install Composer dependencies
composer install --optimize-autoloader
```

### Step 3: Install Frontend Dependencies

```bash
# Install Node.js dependencies
npm install

# Build frontend assets
npm run build
```

### Step 4: Environment Configuration

```bash
# Copy the example environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### Step 5: Configure Environment Variables

Edit the `.env` file with your specific settings:

```env
# Application Settings
APP_NAME="Fortinet Captive Portal"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
APP_LOCALE=fr
APP_TIMEZONE=Europe/Paris

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=captive_portal
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

# FortiGate API Configuration
FORTIGATE_API_URL=https://your-fortigate-ip
FORTIGATE_API_TOKEN=your-api-token
FORTIGATE_USER_GROUP=portal_users
FORTIGATE_VERIFY_SSL=true

# Email Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=noreply@example.com
MAIL_PASSWORD=your-smtp-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="${APP_NAME}"

# Redis Configuration (optional)
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Session Configuration
SESSION_DRIVER=file
SESSION_LIFETIME=15
SESSION_ENCRYPT=true

# Queue Configuration
QUEUE_CONNECTION=database
```

### Step 6: Database Setup

```bash
# Create the database (MySQL example)
mysql -u root -p
CREATE DATABASE captive_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'captive_user'@'localhost' IDENTIFIED BY 'strong_password';
GRANT ALL PRIVILEGES ON captive_portal.* TO 'captive_user'@'localhost';
FLUSH PRIVILEGES;
exit;

# Run database migrations
php artisan migrate

# Seed initial data (optional)
php artisan db:seed
```

### Step 7: Storage and Cache Setup

```bash
# Create storage link
php artisan storage:link

# Clear and optimize caches
php artisan optimize:clear
php artisan optimize
```

### Step 8: Queue Worker Setup

For production, set up a supervisor configuration:

```ini
# /etc/supervisor/conf.d/captive-portal.conf
[program:captive-portal-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/application/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/application/storage/logs/worker.log
```

### Step 9: Web Server Configuration

#### Apache Configuration

```apache
<VirtualHost *:443>
    ServerName captiveportal.example.com
    DocumentRoot /path/to/application/public

    <Directory /path/to/application/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    SSLEngine on
    SSLCertificateFile /path/to/certificate.crt
    SSLCertificateKeyFile /path/to/private.key

    ErrorLog ${APACHE_LOG_DIR}/captiveportal-error.log
    CustomLog ${APACHE_LOG_DIR}/captiveportal-access.log combined
</VirtualHost>
```

#### Nginx Configuration

```nginx
server {
    listen 443 ssl http2;
    server_name captiveportal.example.com;
    root /path/to/application/public;

    ssl_certificate /path/to/certificate.crt;
    ssl_certificate_key /path/to/private.key;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### Step 10: Set Permissions

```bash
# Set proper ownership
chown -R www-data:www-data /path/to/application

# Set directory permissions
find /path/to/application -type d -exec chmod 755 {} \;

# Set file permissions
find /path/to/application -type f -exec chmod 644 {} \;

# Set storage and cache permissions
chmod -R 775 storage bootstrap/cache
```

---

## Docker/Sail Installation (Recommended)

### Step 1: Prerequisites

Ensure Docker and Docker Compose are installed:

```bash
# Check Docker version
docker --version

# Check Docker Compose version
docker-compose --version
```

### Step 2: Clone and Setup

```bash
# Clone the repository
git clone https://github.com/PhilCANDIDO/laravel-fortinet-captiveportal.git
cd laravel-fortinet-captiveportal

# Copy environment file
cp .env.example .env
```

### Step 3: Configure Sail Environment

Edit `.env` for Sail:

```env
# Application Settings
APP_NAME="Fortinet Captive Portal"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://captiveportal.test
APP_PORT=80

# Database Configuration (Sail defaults)
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=captive_portal
DB_USERNAME=sail
DB_PASSWORD=password

# Redis Configuration (Sail defaults)
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

# Mail (using Mailpit for development)
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
```

### Step 4: Install Sail

```bash
# Install Composer dependencies with Sail
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v $(pwd):/var/www/html \
    -w /var/www/html \
    laravelsail/php83-composer:latest \
    composer install --ignore-platform-reqs
```

### Step 5: Start Sail

```bash
# Start all services
./vendor/bin/sail up -d

# Generate application key
./vendor/bin/sail artisan key:generate

# Run migrations
./vendor/bin/sail artisan migrate

# Install and build frontend assets
./vendor/bin/sail npm install
./vendor/bin/sail npm run build
```

### Step 6: Configure Hosts File

Add to `/etc/hosts` (Linux/Mac) or `C:\Windows\System32\drivers\etc\hosts` (Windows):

```
127.0.0.1 captiveportal.test
```

---

## FortiGate Configuration

### Step 1: Enable REST API

1. Log in to FortiGate web interface
2. Navigate to **System > Feature Visibility**
3. Enable **REST API**

### Step 2: Create API User

1. Go to **System > Administrators**
2. Click **Create New > REST API Admin**
3. Configure:
   - **Username:** `captive_portal_api`
   - **Comments:** Laravel Captive Portal Integration
   - **Administrator Profile:** Super_Admin (or custom profile with required permissions)
   - **Trusted Hosts:** Add your Laravel server IP

### Step 3: Generate API Token

1. After creating the API admin, you'll receive an API token
2. **Important:** Copy and save this token immediately (shown only once)
3. Add the token to your `.env` file:
   ```env
   FORTIGATE_API_TOKEN=your-generated-token-here
   ```

### Step 4: Create User Group

1. Navigate to **User & Device > User Groups**
2. Create new group:
   - **Name:** `portal_users`
   - **Type:** Firewall
3. Configure any specific policies for this group

### Step 5: Test Connection

```bash
# Standard installation
php artisan fortigate:test

# Sail installation
./vendor/bin/sail artisan fortigate:test
```

---

## Post-Installation Setup

### Step 1: Create Admin User

```bash
# Standard installation
php artisan admin:create

# Sail installation
./vendor/bin/sail artisan admin:create
```

Follow the prompts to create your first administrator account.

### Step 2: Configure System Settings

1. Log in to admin panel: `https://your-domain.com/admin`
2. Navigate to **Settings > General**
3. Configure:
   - Email validation requirements
   - Charter text (multi-language)
   - Audit log retention period
   - Session timeout

### Step 3: Setup Laravel Horizon

```bash
# Install Horizon (if not already installed)
php artisan horizon:install

# Start Horizon
php artisan horizon
```

For production, use Supervisor to keep Horizon running:

```ini
[program:horizon]
process_name=%(program_name)s
command=php /path/to/application/artisan horizon
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/path/to/application/storage/logs/horizon.log
```

### Step 4: Configure Scheduled Tasks

Add to crontab:

```bash
# Edit crontab
crontab -e

# Add Laravel scheduler
* * * * * cd /path/to/application && php artisan schedule:run >> /dev/null 2>&1
```

### Step 5: Enable Multi-Factor Authentication

1. Log in as admin
2. Go to **Profile > Security**
3. Click **Enable MFA**
4. Scan QR code with Google Authenticator
5. Save backup codes securely

### Step 6: Test Email Notifications

```bash
# Send test email
php artisan mail:test admin@example.com
```

### Step 7: Security Hardening

1. Ensure HTTPS is enforced
2. Set strong database passwords
3. Configure firewall rules
4. Enable rate limiting
5. Review and adjust session timeouts

---

## Troubleshooting

### Common Issues and Solutions

#### Database Connection Failed

```bash
# Test database connection
php artisan db:show

# Check credentials in .env
# Ensure database server is running
# Verify network connectivity
```

#### FortiGate API Connection Failed

```bash
# Test FortiGate connection
php artisan fortigate:test

# Verify:
# - API token is correct
# - FortiGate IP is reachable
# - HTTPS certificate (if FORTIGATE_VERIFY_SSL=true)
# - Firewall rules allow connection
```

#### Email Not Sending

```bash
# Test mail configuration
php artisan tinker
>>> Mail::raw('Test email', function($message) {
>>>     $message->to('test@example.com')->subject('Test');
>>> });

# Check:
# - SMTP credentials
# - Firewall allows outbound SMTP
# - Queue workers are running
```

#### Permission Errors

```bash
# Fix storage permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

#### Queue Jobs Not Processing

```bash
# Check queue worker status
php artisan queue:work --stop-when-empty

# For Horizon
php artisan horizon:status

# Ensure supervisor is running
supervisorctl status
```

#### Blank Page or 500 Error

```bash
# Check Laravel logs
tail -f storage/logs/laravel.log

# Check web server logs
tail -f /var/log/apache2/error.log  # Apache
tail -f /var/log/nginx/error.log     # Nginx

# Enable debug mode temporarily
# Set APP_DEBUG=true in .env
```

### Getting Help

If you encounter issues not covered here:

1. Check the [GitHub Issues](https://github.com/PhilCANDIDO/laravel-fortinet-captiveportal/issues)
2. Review the [Admin Guide](admin-guide.md) for configuration details
3. Contact support at support@emerging-it.fr

---

## Next Steps

- Read the [Admin Guide](admin-guide.md) for system administration
- Review the [User Guide](user-guide.md) for end-user documentation
- Check the [API Reference](api-reference.md) for technical integration

---

**© 2025 Emerging IT - Philippe CANDIDO**