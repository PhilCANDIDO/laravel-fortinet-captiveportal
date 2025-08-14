# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Laravel-based captive portal management system for Fortinet FortiGate firewalls. This web application provides multilingual user authentication and management for WiFi/network access control via FortiOS 7.6.3 API integration.

## Tech Stack

- **Backend**: Laravel 12 with PHP 8.3+
- **Frontend**: TALL Stack (Tailwind CSS, Alpine.js, Laravel, Livewire 3.x)
- **UI Components**: Flowbite 2.x
- **Database**: MySQL 8.0+
- **Cache/Queue**: Redis 7.0+ with Laravel Horizon
- **Languages**: French (default), English, Italian, Spanish

## Development Commands

```bash
# Laravel setup (once implemented)
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate

# Development
php artisan serve          # Start development server
npm run dev                # Watch frontend assets
php artisan queue:work     # Process queue jobs
php artisan horizon        # Start Horizon queue dashboard

# Database
php artisan migrate        # Run migrations
php artisan migrate:fresh  # Reset and migrate
php artisan db:seed        # Seed database

# Testing
php artisan test           # Run all tests
php artisan test --filter TestName  # Run specific test
php artisan test --coverage # With coverage report

# Production build
npm run build             # Build frontend assets
php artisan config:cache  # Cache configuration
php artisan route:cache   # Cache routes
```

## Architecture

### User Types & Authentication Flow
1. **Employees**: Permanent access, no expiration
2. **Consultants**: Temporary access with expiration date
3. **Guests**: 24-hour access, requires email validation within 30 minutes

### Key Services Architecture
- **FortiGateService**: Handles all FortiGate API interactions (user CRUD, session management)
- **UserService**: Business logic for user management and validation
- **NotificationService**: Email notifications with multi-language support
- **AuditService**: Comprehensive logging with Excel export capability

### Queue Jobs
- **DeleteUnvalidatedUserJob**: Removes unvalidated guests after 30 minutes
- **ExpireUserJob**: Deactivates expired consultant accounts
- **CleanupAuditLogsJob**: Maintains audit log retention policy

### FortiGate API Integration
- Base URL: `https://{fortigate-ip}/api/v2/`
- Authentication: Bearer token in headers
- Key endpoints:
  - `POST /cmdb/user/local` - Create user
  - `PUT /cmdb/user/local/{username}` - Update user
  - `DELETE /cmdb/user/local/{username}` - Delete user
  - `GET /monitor/user/info` - Get active sessions
- Implements retry logic and circuit breaker pattern

### Database Schema
- `users`: Guest and consultant accounts
- `admin_users`: Administrative accounts with MFA
- `audit_logs`: Complete action traceability
- `settings`: System configuration (charter texts, retention periods)
- `password_reset_tokens`: Secure password recovery

### Security Requirements
- ANSSI-compliant password policy (12+ chars, complexity rules)
- MFA for admin accounts
- Session timeout: 15 minutes inactivity
- Rate limiting on all endpoints
- Input validation and sanitization
- HTTPS-only with HSTS headers

## Project Structure

```
app/
├── Http/
│   ├── Controllers/     # Web controllers
│   ├── Livewire/        # Livewire components
│   └── Middleware/      # Auth, locale, rate limiting
├── Models/              # Eloquent models
├── Services/            # Business logic services
├── Jobs/               # Queue jobs
└── Exceptions/         # Custom exceptions

resources/
├── views/              # Blade templates
├── lang/               # FR, EN, IT, ES translations
└── js/css/             # Frontend assets

database/
├── migrations/         # Schema definitions
└── seeders/           # Initial data

tests/
├── Unit/              # Unit tests (80% coverage target)
├── Feature/           # Integration tests
└── Performance/       # Load testing scenarios
```

## Important Implementation Notes

1. **Email Validation Flow**: Guests must validate email within 30 minutes or account is automatically deleted
2. **Multilingual**: All user-facing text must support FR, EN, IT, ES. French is default
3. **Audit Logging**: Every action must be logged with user, IP, timestamp, and details
4. **FortiGate Sync**: User changes must immediately sync with FortiGate via API
5. **Charter Acceptance**: Users must accept terms before account activation
6. **Excel Exports**: Audit logs and user lists must be exportable to Excel format
7. **Performance**: Must handle 500 concurrent users without degradation

## Environment Variables

Key environment variables to configure:
- `FORTIGATE_API_URL`: FortiGate API endpoint
- `FORTIGATE_API_TOKEN`: Authentication token
- `FORTIGATE_USER_GROUP`: User group name in FortiGate
- `MAIL_*`: SMTP configuration for notifications
- `REDIS_*`: Redis connection for cache/queues
- `APP_LOCALE`: Default locale (fr)
- `AUDIT_RETENTION_DAYS`: Audit log retention period

## Testing Strategy

1. **Unit Tests**: Test services, models, and helpers in isolation
2. **Feature Tests**: Test complete user workflows (registration, validation, expiration)
3. **API Tests**: Mock FortiGate API responses for integration testing
4. **Browser Tests**: Livewire component interactions with Dusk
5. **Performance Tests**: Load testing with JMeter/Artillery for 500 concurrent users

## Common Development Tasks

First read the [instructions.md](.claude/instructions.md), contents development instructionbs.

- Adding new language: Update `config/app.php` locales, create `resources/lang/{locale}` directory
- Modifying FortiGate integration: Update `app/Services/FortiGateService.php`
- Adding audit events: Use `AuditLog::create()` in relevant controllers/services
- Email template changes: Update views in `resources/views/emails/`
- Charter text updates: Modify via Settings management interface

## Deployment

Application deploys via Docker Compose with:
- Nginx reverse proxy with SSL
- PHP-FPM application container
- MySQL database container
- Redis cache/queue container
- Laravel Horizon for queue monitoring

Production checklist:
1. Set `APP_ENV=production` and `APP_DEBUG=false`
2. Configure SSL certificates for HTTPS
3. Set strong `APP_KEY` and database passwords
4. Configure firewall rules for FortiGate API access
5. Set up backup strategy for database and uploads
6. Configure monitoring (Prometheus metrics exposed on /metrics)