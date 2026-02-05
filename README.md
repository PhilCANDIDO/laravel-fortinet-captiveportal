# Laravel Fortinet Captive Portal

[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=flat&logo=laravel)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3%2B-777BB4?style=flat&logo=php)](https://php.net)
[![FortiOS](https://img.shields.io/badge/FortiOS-7.6.3-EE3124?style=flat)](https://www.fortinet.com)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

A comprehensive multi-language captive portal management system for Fortinet FortiGate firewalls, providing granular network access control with time-based user management and email validation.

## 🎯 Overview

This Laravel-based application provides a professional web interface for managing WiFi and network access through FortiGate captive portals. It differentiates access levels across three user profiles (Employees, Consultants, Guests) with specific validity durations and complete audit traceability.

### Key Features

- 🌍 **Multi-language Support**: French (default), English, Italian, Spanish
- 🔐 **Advanced Security**: ANSSI-compliant passwords, MFA with Google Authenticator
- 👥 **Three User Types**: Employees (permanent), Consultants (temporary), Guests (24h)
- ✉️ **Email Validation**: Required for guests within 30 minutes
- 📊 **Complete Audit Trail**: All actions logged with Excel export capability
- 🔄 **Real-time FortiGate Sync**: Immediate user provisioning and deauthentication
- 📱 **Responsive Design**: Mobile-first approach with Tailwind CSS

## 🚀 Quick Start

### Prerequisites

- PHP 8.3 or higher
- MySQL 8.0+
- Redis 7.0+ (optional, for caching/queues)
- Composer 2.x
- Node.js 18+ and npm
- FortiGate with FortiOS 7.6.3+

### Installation

1. **Clone the repository**
```bash
git clone https://github.com/your-org/laravel-fortinet-captiveportal.git
cd laravel-fortinet-captiveportal
```

2. **Install dependencies**
```bash
composer install
npm install
```

3. **Environment setup**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Configure your `.env` file**
```env
# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=captive_portal
DB_USERNAME=your_username
DB_PASSWORD=your_password

# FortiGate API
FORTIGATE_API_URL=https://your-fortigate-ip
FORTIGATE_API_TOKEN=your-api-token
FORTIGATE_USER_GROUP=portal_users

# Email
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=your-email
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@example.com

# Application
APP_LOCALE=fr
APP_TIMEZONE=Europe/Paris
```

5. **Run migrations and seeders**
```bash
php artisan migrate
php artisan db:seed
```

6. **Build frontend assets**
```bash
npm run build
```

7. **Start the development server**
```bash
php artisan serve
```

Visit `http://localhost:8000` to access the application.

## 👥 User Types

### Employees
- **Access**: Permanent, no expiration
- **Creation**: Admin only
- **Features**: Full network access without time restrictions

### Consultants
- **Access**: Temporary with configurable expiration date
- **Creation**: Admin only with sponsor information
- **Features**: Time-limited access for external contractors

### Guests
- **Access**: 24-hour validity
- **Creation**: Self-registration or admin
- **Features**: Email validation required within 30 minutes

## 🔑 Key Features

### Guest Self-Registration
1. Access the registration page (`/guest/register`)
2. Fill in personal information and accept charter
3. Receive credentials immediately
4. Validate email within 30 minutes
5. Connect to network using provided credentials

### Admin Panel
- **Dashboard**: Real-time statistics and system health
- **User Management**: Create, modify, suspend users by type
- **Audit Logs**: Complete activity tracking with Excel export
- **Settings**: FortiGate configuration, charter management, system parameters

### Security Features
- **Multi-Factor Authentication (MFA)**: Google Authenticator for admins
- **Password Policy**: ANSSI-compliant (12+ chars for guests, 16+ for others)
- **Session Security**: 15-minute timeout, concurrent session prevention
- **Account Lockout**: Progressive lockout after failed attempts
- **Audit Trail**: Complete logging of all actions

### FortiGate Integration
- **API Version**: FortiOS 7.6.3 REST API
- **Features**:
  - Real-time user provisioning
  - Session monitoring
  - Force disconnection (deauthentication)
  - Health monitoring with circuit breaker
- **Portal Integration**: Dynamic URL generation with auto-authentication

## 📁 Project Structure

```
laravel-fortinet-captiveportal/
├── app/
│   ├── Http/
│   │   ├── Controllers/      # Web controllers
│   │   ├── Livewire/         # Livewire components
│   │   └── Middleware/       # Auth, locale, rate limiting
│   ├── Models/               # Eloquent models
│   ├── Services/             # Business logic
│   │   ├── FortiGateService.php
│   │   ├── NotificationService.php
│   │   ├── PortalDataService.php
│   │   └── MfaService.php
│   └── Jobs/                 # Queue jobs
├── database/
│   ├── migrations/           # Database schema
│   └── seeders/             # Initial data
├── resources/
│   ├── views/               # Blade templates
│   ├── lang/                # Translations (FR/EN/IT/ES)
│   └── js/css/              # Frontend assets
├── routes/
│   ├── web.php              # Public routes
│   ├── admin.php            # Admin routes
│   └── guest.php            # Guest routes
└── tests/                   # Unit and feature tests
```

## 🛠 Development

### Available Commands

```bash
# Development
php artisan serve              # Start development server
npm run dev                    # Watch frontend assets
php artisan queue:work         # Process queue jobs
php artisan horizon            # Start Horizon dashboard

# Database
php artisan migrate            # Run migrations
php artisan migrate:fresh      # Reset and migrate
php artisan db:seed           # Seed database

# Testing
php artisan test              # Run all tests
php artisan test --coverage   # With coverage report

# Maintenance
php artisan users:expire      # Manually expire users
php artisan cache:clear       # Clear application cache
```

### Tech Stack

- **Backend**: Laravel 12 with PHP 8.3+
- **Frontend**: TALL Stack (Tailwind CSS, Alpine.js, Laravel, Livewire 3.x)
- **UI Components**: Flowbite 2.x
- **Database**: MySQL 8.0+ with soft deletes
- **Cache/Queue**: Redis 7.0+ with Laravel Horizon
- **Email**: Laravel Mail with multi-language templates
- **Export**: Laravel Excel for audit logs

## 🔒 Security

### Password Requirements
- **Guests**: 12+ characters with complexity
- **Employees/Consultants**: 16+ characters with complexity
- **Administrators**: 16+ characters + mandatory MFA

### Security Headers
- HSTS enforcement
- XSS protection
- CSRF protection on all forms
- Content Security Policy
- Rate limiting on sensitive endpoints

### Compliance
- ANSSI password policy compliance
- GDPR-compliant data retention
- Complete audit trail for compliance reporting

## 📊 API Documentation

### Public Endpoints
- `GET /guest/register` - Registration form
- `POST /guest/register` - Create guest account
- `GET /guest/validate/{token}` - Email validation
- `GET /landing` - Post-authentication landing page

### Admin Endpoints (Authenticated)
- `GET /admin/dashboard` - Admin dashboard
- `GET /admin/guests` - Guest management
- `POST /admin/users/{id}/suspend` - Suspend user
- `POST /admin/users/{id}/deauth` - Force disconnect
- `GET /admin/audit/export` - Export audit logs

### Complete API Endpoints

#### Guest Routes
- `GET /` - Redirects to guest registration
- `GET /guest/register` - Registration form
- `POST /guest/register` - Create guest account
- `GET /guest/validate/{token}` - Email validation
- `GET /guest/success` - Success page with credentials
- `GET /landing` - Post-authentication landing page
- `GET /locale/{locale}` - Change language (fr, en, it, es)

#### Admin Routes (Authenticated)
- `GET /admin/login` - Admin login form
- `POST /admin/login` - Admin authentication
- `POST /admin/logout` - Admin logout
- `GET /admin/dashboard` - Main dashboard
- `GET /admin/employees` - Employee management
- `GET /admin/consultants` - Consultant management
- `GET /admin/guests` - Guest management
- `POST /admin/users/create` - Create new user
- `PUT /admin/users/{id}` - Update user
- `POST /admin/users/{id}/suspend` - Suspend user
- `POST /admin/users/{id}/activate` - Activate user
- `DELETE /admin/users/{id}` - Delete user
- `POST /admin/users/{id}/deauth` - Force disconnect user
- `GET /admin/audit` - View audit logs
- `GET /admin/audit/export` - Export audit logs to Excel
- `GET /admin/settings` - System settings
- `POST /admin/settings/fortigate` - Update FortiGate config
- `POST /admin/settings/fortigate/test` - Test FortiGate connection
- `GET /admin/profile` - Admin profile
- `POST /admin/profile/password` - Change password

#### MFA Routes (Admin)
- `GET /admin/mfa/setup` - Setup Google Authenticator
- `POST /admin/mfa/enable` - Enable MFA
- `POST /admin/mfa/verify` - Verify TOTP code
- `POST /admin/mfa/disable` - Disable MFA
- `GET /admin/mfa/recovery-codes` - View backup codes
- `POST /admin/mfa/recovery-codes` - Regenerate backup codes

## 🚢 Deployment

### Production Deployment Script

The project includes an idempotent deployment script (`deploy.sh`) for Continuous Delivery:

```bash
# Standard deployment from main branch
./deploy.sh --branch=main

# Preview deployment steps without executing
./deploy.sh --dry-run

# Force reset local changes (useful when local files were modified)
./deploy.sh --branch=main --force-git-reset

# Custom PHP-FPM service and web user
./deploy.sh --php-fpm-service=php8.3-fpm --webuser=www-data

# Skip npm build (faster deploys when frontend unchanged)
./deploy.sh --branch=main --skip-npm

# Setup daily backup cron job
./deploy.sh --backup-cron
```

#### Deployment Script Options

| Option | Description | Default |
|--------|-------------|---------|
| `--branch=BRANCH` | Git branch to deploy | `main` |
| `--php-fpm-service=NAME` | PHP-FPM service name | `php-fpm` |
| `--webuser=USER` | Web server user | `nginx` |
| `--force-git-reset` | Force reset to remote branch | `false` |
| `--skip-npm` | Skip npm install and build | `false` |
| `--backup-cron` | Setup daily backup cron at 23:00 | `false` |
| `--dry-run` | Preview steps without executing | `false` |

#### What the Script Does

1. **Checks requirements** - Verifies php, composer, git, npm are available
2. **Creates directories** - Ensures storage and cache directories exist
3. **Sets permissions** - Applies correct ownership and permissions
4. **Pulls code** - Fetches and pulls latest from specified branch
5. **Installs PHP deps** - Runs `composer install --no-dev --optimize-autoloader`
6. **Builds frontend** - Runs `npm ci && npm run build`
7. **Runs migrations** - Executes `php artisan migrate --force`
8. **Optimizes Laravel** - Caches config, routes, views, and events
9. **Restarts services** - Reloads PHP-FPM, Horizon, and queue workers

#### Deployment Logs

All deployment actions are logged to `storage/logs/deploy-YYYY-MM-DD.log` for audit and troubleshooting.

### Docker Deployment

```bash
docker-compose up -d
```

The application includes Docker Compose configuration with:
- Nginx reverse proxy with SSL
- PHP-FPM application container
- MySQL database container
- Redis cache container
- Laravel Horizon for queue monitoring

### Production Checklist

- [ ] Set `APP_ENV=production` and `APP_DEBUG=false`
- [ ] Configure SSL certificates
- [ ] Set strong passwords and API keys
- [ ] Configure FortiGate firewall rules
- [ ] Set up database backups
- [ ] Configure monitoring (Prometheus metrics on `/metrics`)
- [ ] Enable log rotation
- [ ] Configure email server
- [ ] Test MFA for all admin accounts

## 📖 Documentation

Additional documentation can be found in the `docs/` directory:
- Installation Guide - Detailed setup instructions
- User Guide - End-user documentation
- Admin Guide - Administrator documentation
- API Reference - Complete API documentation

### FortiGate Configuration

#### Prerequisites
- FortiOS version 7.6.3 or higher
- API REST enabled
- Administrator profile with permissions:
  - User & Device → User → User Definition (Read/Write)
  - User & Device → User → User Group (Read)
  - System → Administrator (for API token)

#### Creating API Token
1. System → Administrators → Create New → REST API Admin
2. Set Trusted Hosts to your Laravel server IP
3. Select appropriate Administrator Profile
4. Generate and copy the API token

#### User Group Setup
1. User & Device → User → User Groups
2. Create group "portal_users" (or your preferred name)
3. Configure session timeout as needed

## 🤝 Contributing

Contributions are welcome! Please read our [Contributing Guide](CONTRIBUTING.md) for details on our code of conduct and the process for submitting pull requests.

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

For issues and questions:
- Create an issue on [GitHub Issues](https://github.com/your-org/laravel-fortinet-captiveportal/issues)
- Check the [troubleshooting guide](docs/troubleshooting.md)
- Contact the support team at support@emerging-it.fr

## 🙏 Acknowledgments

- Laravel Framework
- Fortinet FortiGate API
- TALL Stack community
- All contributors

---

## 👨‍💻 Author

**Philippe CANDIDO**
- 📧 Email: [philippe.candido@emerging-it.fr](mailto:philippe.candido@emerging-it.fr)
- 💼 LinkedIn: [www.linkedin.com/in/philippe-candido-0056831](https://www.linkedin.com/in/philippe-candido-0056831)
- 🏢 Company: [Emerging IT](https://emerging-it.fr)

---

**Developed with ❤️ by Emerging IT**