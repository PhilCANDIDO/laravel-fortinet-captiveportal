# Administrator Guide

## Laravel Fortinet Captive Portal - System Administration

**Version:** 1.0.0  
**Last Updated:** August 2025  
**FortiOS Compatibility:** 7.6.3+

---

## Table of Contents

1. [Introduction](#introduction)
2. [Admin Portal Access](#admin-portal-access)
3. [Dashboard Overview](#dashboard-overview)
4. [User Management](#user-management)
5. [Security Features](#security-features)
6. [System Configuration](#system-configuration)
7. [Monitoring and Maintenance](#monitoring-and-maintenance)
8. [Audit and Compliance](#audit-and-compliance)
9. [Advanced Operations](#advanced-operations)
10. [Troubleshooting](#troubleshooting)
11. [Best Practices](#best-practices)

---

## Introduction

This guide provides comprehensive documentation for administrators of the Laravel Fortinet Captive Portal system. As an administrator, you have full control over user management, system configuration, and security settings.

### Administrator Roles

| Role | Permissions | Description |
|------|------------|-------------|
| **Super Admin** | Full access | Complete system control, MFA reset, all configurations |
| **Admin** | Limited admin | User management, basic configuration, no MFA reset |

### Key Responsibilities

- User account management (creation, modification, deletion)
- System configuration and maintenance
- Security policy enforcement
- Audit log review and compliance
- FortiGate integration management
- Email template and charter management

---

## Admin Portal Access

### Accessing the Admin Panel

1. Navigate to: `https://your-domain.com/admin`
2. You'll be redirected to the login page

### Login Process

#### Standard Login

1. Enter your admin email address
2. Enter your password
3. Click "Login"

#### With Multi-Factor Authentication (MFA)

1. Complete standard login
2. Enter 6-digit code from Google Authenticator
3. Optionally check "Remember this device for 30 days"
4. Click "Verify"

### First-Time Login

On first login, you'll be required to:
1. Change your default password
2. Set up Multi-Factor Authentication
3. Save backup codes securely

### Session Management

- **Session Timeout:** 15 minutes of inactivity
- **Concurrent Sessions:** Prevented by default
- **Session Extension:** Click "Extend Session" when warned

---

## Dashboard Overview

The dashboard provides real-time system insights and quick access to key functions.

### Dashboard Widgets

#### User Statistics
- **Total Users:** Count by type (Employee, Consultant, Guest)
- **Active Users:** Currently connected to network
- **Pending Validation:** Guests awaiting email validation
- **Expiring Soon:** Users expiring within 7 days

#### System Status
- **FortiGate Connection:** API status indicator
- **Queue Status:** Background job processing
- **Last Sync:** FortiGate synchronization time
- **System Health:** Performance metrics

#### Recent Activity
- Latest user registrations
- Recent authentication events
- System configuration changes
- Failed login attempts

### Quick Actions

From the dashboard, you can:
- Create new users
- View audit logs
- Access system settings
- Check queue monitor (Horizon)

---

## User Management

### User Types Overview

| Type | Duration | Creation Method | Email Validation |
|------|----------|----------------|------------------|
| **Employee** | Permanent | Admin only | Not required |
| **Consultant** | Temporary (configurable) | Admin only | Not required |
| **Guest** | 24 hours | Self-service or Admin | Required (30 min) |

### Managing Employees

#### Creating an Employee Account

1. Navigate to **Users → Employees**
2. Click **"Create Employee"**
3. Fill in required fields:
   - Name (First and Last)
   - Email address
   - Department (optional)
   - Phone numbers (optional)
4. Password is auto-generated
5. Click **"Create"**

#### Employee Account Features
- No expiration date
- Permanent network access
- No email validation required
- Can be suspended/reactivated

### Managing Consultants

#### Creating a Consultant Account

1. Navigate to **Users → Consultants**
2. Click **"Create Consultant"**
3. Fill in required fields:
   - Name and contact details
   - Company name
   - Sponsor information
   - **Expiration date** (required)
4. Set access duration (default: 1 month)
5. Click **"Create"**

#### Consultant Features
- Temporary access with expiration
- Sponsor tracking
- Expiration reminders (24h before)
- Auto-removal from FortiGate on expiry

### Managing Guests

#### Admin-Created Guest Accounts

1. Navigate to **Users → Guests**
2. Click **"Create Guest"**
3. Enter guest email
4. System generates credentials
5. Guest receives email with:
   - Login credentials
   - Validation link (if enabled)
   - 24-hour access notice

#### Guest Self-Registration

Guests can self-register at: `/guest/register`

**Admin Controls:**
- Enable/disable email validation requirement
- Customize registration form fields
- Set validation timeout (default: 30 minutes)
- Configure auto-deletion of unvalidated accounts

#### Guest Management Features

**Bulk Operations:**
- Select multiple guests
- Bulk delete expired accounts
- Export guest list to Excel

**Individual Actions:**
- View guest details
- Manually validate email
- Extend access duration
- Force disconnect from network
- Delete account

### User Actions

#### Viewing User Details

Click the **eye icon** to view:
- Personal information
- Account status
- FortiGate sync status
- Connection history
- Audit trail

#### Suspending/Activating Users

1. Click the **toggle icon**
2. Confirm suspension/activation
3. User is immediately:
   - Removed from/Added to FortiGate
   - Disconnected from network (if suspended)
   - Status updated in database

#### Deleting Users

1. Click the **trash icon**
2. Confirm deletion
3. System will:
   - Remove from FortiGate
   - Force disconnect active sessions
   - Log deletion in audit
   - Soft-delete from database

#### Force Disconnect (Deauthentication)

For immediate network disconnection:
1. Click **"Disconnect"** button
2. System queries active sessions
3. Sends deauth command to FortiGate
4. User must re-authenticate to reconnect

---

## Security Features

### Multi-Factor Authentication (MFA)

#### Setting Up MFA

1. Go to **Profile → Security**
2. Click **"Enable Two-Factor Authentication"**
3. Scan QR code with Google Authenticator
4. Enter verification code
5. Save backup codes (10 codes provided)

#### Managing MFA

**For Your Account:**
- View backup codes
- Regenerate backup codes
- Disable MFA (requires current code)

**For Other Admins (Super Admin only):**
- Reset MFA for locked-out admins
- Force MFA re-enrollment
- View MFA status of all admins

#### MFA Best Practices
- Enforce MFA for all administrators
- Store backup codes securely (not in email)
- Regenerate codes after use
- Test MFA regularly

### Password Policies

#### ANSSI-Compliant Requirements

| User Type | Minimum Length | Complexity | Expiration |
|-----------|---------------|------------|------------|
| **Admin** | 16 characters | High | 90 days |
| **Employee/Consultant** | 16 characters | High | Never |
| **Guest** | 12 characters | Medium | N/A (auto-generated) |

**Complexity Requirements:**
- Uppercase letters (A-Z)
- Lowercase letters (a-z)
- Numbers (0-9)
- Special characters (!@#$%^&*)

#### Password Management

**Password History:**
- Last 10 passwords remembered
- Cannot reuse recent passwords
- Applies to admin accounts

**Password Expiration:**
- 90-day expiration for admins
- 7-day warning before expiry
- Forced change on next login after expiry

**Account Lockout:**
- 5 failed attempts triggers lockout
- Progressive delays: 1, 5, 15, 60 minutes
- Super admin can unlock accounts

### Session Security

#### Session Configuration

**Timeout Settings:**
- Default: 15 minutes inactivity
- Configurable in Settings
- Warning at 2 minutes remaining

**Concurrent Session Prevention:**
- One session per admin
- New login terminates old session
- Optional: can be disabled in .env

#### Security Headers

Automatically applied:
- HSTS (HTTPS enforcement)
- X-Frame-Options (Clickjacking protection)
- X-Content-Type-Options (MIME sniffing protection)
- CSP (Content Security Policy)

---

## System Configuration

### FortiGate Configuration

#### Creating API Token in FortiGate

##### Step 1: Create API User

1. Log in to FortiGate web interface
2. Navigate to **System → Administrators**
3. Click **Create New → REST API Admin**
4. Configure the API user:
   ```
   Username: captive_portal_api
   Comments: Captive Portal API User
   Administrator Profile: Super_Admin (or custom profile)
   PKI Group: None
   CORS Allow Origin: Your application URL
   JSON Web Token: Disabled
   ```
   Or create it in CLI :
   ```
   config system api-user
      edit "captive_portal_api"
         set comments "Captive Portal API User"
         set accprofile "super_admin"        # Or change it by custom profile
         set cors-allow-origin "Your application URL"
      next
   end
   ```

5. Click **OK** to create the user

##### Step 2: Generate API Token

1. After creating the API user, you'll see a dialog with the API key
2. **IMPORTANT:** Copy this key immediately - it won't be shown again
3. The key format will be: `1xf5w1Q3N8drmcq7hQ7yQz8mmxN3px`
4. Store this key securely in your password manager

##### Step 3: Configure API User Permissions

1. Navigate to **System → Admin Profiles**
2. Create new profile: `CaptivePortal_API`
3. Set permissions:
   ```
   System:
   - User & Device: Read/Write
   - Admin: None
   
   WiFi & Switch Controller:
   - Monitor: Read
   - FortiAP: Read
   
   Log & Report:
   - Event Log: Read
   
   All other permissions: None
   ```
4. Edit your API user to use this profile

##### Step 4: Configure Trusted Hosts

1. Edit the API user
2. Under **Trusted Hosts**, add your application server:
   ```
   IP/Netmask: 192.168.1.0/255.255.255.0
   (or specific IP: 192.168.1.100/255.255.255.255)
   ```
3. This restricts API access to your application only

#### Customizing Captive Portal Login Page

##### Step 1: Access Portal Configuration

1. Navigate to **WiFi & Switch Controller → SSID**
2. Select your Guest WiFi SSID
3. Click **Edit**
4. Go to **Captive Portal** section

##### Step 2: Configure Portal Settings

1. **Portal Type:** Authentication
2. **Authentication Portal:** Local
3. **Portal Message:** Configure welcome text
4. **User Groups:** Select `portal_users` (or your configured group)

##### Step 3: Upload Custom Login Page

1. Navigate to **System → Replacement Messages**
2. Go to **Authentication → Captive Portal Login Page**
3. Select **Extended View**
4. Click **Choose File** and select `fortigate-login-page.html`
5. Or paste the HTML content directly:

```html
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=8; IE=EDGE">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style type="text/css">
      /* Include all CSS from fortigate-login-page.html */
    </style>
    <title>Firewall Authentication</title>
  </head>
  <body>
    <!-- HTML content from template -->
  </body>
  <script>
    // Update the Laravel application URL
    const LARAVEL_APP_URL = 'https://your-captiveportal-domain.com/guest/register';
    
    // Rest of the JavaScript code from template
  </script>
</html>
```

##### Step 4: Configure Portal Variables

**Important:** Update these variables in the template:

1. **LARAVEL_APP_URL:** Change line 244 to your application URL:
   ```javascript
   const LARAVEL_APP_URL = 'https://captiveportal.example.com/guest/register';
   ```

2. **Language Settings:** Customize text for your locale:
   - Line 229: "Première visite ?" → Your language
   - Line 232: "Créez votre compte invité..." → Your language
   - Line 235: "Créer un compte invité" → Your language

3. **Styling:** Adjust colors to match your branding:
   - Primary button: Line 45-47
   - Secondary button: Line 49-52
   - Guest section: Line 150-177

##### Step 5: Configure Portal Redirect

1. In FortiGate SSID settings, under **Captive Portal**:
2. **Success Action:** Redirect to specific URL
3. **Redirect URL:** 
   ```
   https://captiveportal.example.com/landing
   ```
4. **Session Timeout:** 86400 (24 hours for guests)

##### Step 6: Enable Portal Data Collection

The custom login page automatically:
- Captures FortiGate magic token
- Encodes portal data in base64
- Passes data to Laravel registration
- Supports auto-login after registration

**Portal Data Structure:**
```javascript
{
  portal_url: "Current FortiGate portal URL",
  auth_post_url: "FortiGate authentication endpoint",
  magic_value: "Session magic token",
  username_id: "Form field ID for username",
  password_id: "Form field ID for password"
}
```

##### Step 7: Test the Integration

1. Connect to Guest WiFi
2. Portal should redirect to custom login page
3. Click "Créer un compte invité"
4. Should redirect to Laravel with portal_data
5. After registration, auto-redirect back to FortiGate
6. Credentials auto-filled, user authenticated

#### Application Configuration in Laravel

##### Step 1: Configure Environment

Update `.env` file:
```env
# FortiGate API Configuration
FORTIGATE_API_URL=https://192.168.1.1
FORTIGATE_API_TOKEN=1xf5w1Q3N8drmcq7hQ7yQz8mmxN3px
FORTIGATE_USER_GROUP=portal_users
FORTIGATE_SSL_VERIFY=true
FORTIGATE_TIMEOUT=30
FORTIGATE_RETRY_TIMES=3
FORTIGATE_RETRY_DELAY=100

# Captive Portal URLs
CAPTIVE_PORTAL_URL=https://captiveportal.example.com
FORTIGATE_PORTAL_SUCCESS_URL=https://captiveportal.example.com/landing
```

##### Step 2: Test Configuration

1. Navigate to **Settings → FortiGate** in admin panel
2. Click **"Test Connection"**
3. Verify all checks pass:
   - ✓ API endpoint reachable
   - ✓ Authentication successful
   - ✓ User group exists
   - ✓ Test user created/deleted

##### Step 3: Monitor Integration

Check integration status in:
- **Dashboard:** FortiGate connection widget
- **Logs:** Laravel logs for API calls
- **Audit:** Track FortiGate sync operations

#### Troubleshooting FortiGate Integration

##### Common Issues and Solutions

**Issue: API Token Invalid**
- Solution: Regenerate token in FortiGate
- Verify token has no extra spaces
- Check trusted hosts configuration

**Issue: SSL Certificate Error**
- Solution: Export FortiGate certificate
- Add to Laravel's CA bundle
- Or disable SSL verification (dev only)

**Issue: User Not Created in FortiGate**
- Check API user permissions
- Verify user group exists
- Check FortiGate logs: `diagnose debug application httpsd -1`

**Issue: Captive Portal Not Redirecting**
- Verify SSID captive portal enabled
- Check firewall policies for guest VLAN
- Test with: `http://captive.apple.com`

**Issue: Auto-Login Not Working**
- Check magic token passing
- Verify portal_data encoding
- Confirm auto_username/auto_password parameters

##### Debug Commands

**FortiGate CLI Commands:**
```bash
# Check API user
show system api-user

# Monitor API requests
diagnose debug application httpsd -1
diagnose debug enable

# Check user database
show user local

# View active sessions
diagnose firewall auth list
```

**Laravel Commands:**
```bash
# Test FortiGate connection
php artisan fortigate:test

# Sync specific user
php artisan fortigate:sync-user guest-1234

# Clear FortiGate cache
php artisan cache:clear fortigate

# Debug API calls
tail -f storage/logs/fortigate.log
```

### Email Configuration

#### SMTP Settings

1. Navigate to **Settings → Email**
2. Configure SMTP:
   - Server address
   - Port (587 for TLS, 465 for SSL)
   - Username/Password
   - Encryption method
3. Test configuration
4. Save settings

#### Email Templates

Customize email templates for:
- Guest registration confirmation
- Email validation
- Password reset
- Account expiration notices

**Multi-language Support:**
- Templates for FR, EN, IT, ES
- Automatic language detection
- Manual language override option

### Charter Management

#### Editing Charter Text

1. Navigate to **Settings → Charter**
2. Select language tab
3. Edit charter text using WYSIWYG editor
4. Preview changes
5. Save for each language

#### Charter Features
- Rich text formatting
- Multi-language versions
- Version history
- Preview before saving
- Automatic display on registration

### System Settings

#### General Configuration

**Guest Registration:**
- Enable/disable self-registration
- Require email validation (on/off)
- Validation timeout (default: 30 minutes)
- Auto-deletion of unvalidated accounts

**Session Management:**
- Admin session timeout
- User session duration
- Remember me duration
- Concurrent session handling

**Audit Settings:**
- Log retention period (days)
- Auto-cleanup schedule
- Export format (Excel)
- Compression for archives

---

## Monitoring and Maintenance

### Laravel Horizon (Queue Monitor)

Access queue monitoring dashboard:
1. Click **"Queue Monitor"** in navigation
2. Opens Horizon dashboard

**Horizon Features:**
- Real-time job processing
- Failed job management
- Job metrics and throughput
- Worker status
- Job retry interface

### Background Jobs

#### Scheduled Jobs

| Job | Schedule | Purpose |
|-----|----------|---------|
| **ExpireUsersJob** | Hourly | Mark expired users |
| **DeleteExpiredGuestsJob** | Every 30 min | Remove expired guests |
| **DeleteUnvalidatedGuestJob** | On-demand | Remove unvalidated after 30 min |
| **SendExpirationReminderJob** | Daily 9 AM | Send expiry notices |
| **CleanupOldAuditLogs** | Daily 2 AM | Remove old audit logs |

#### Manual Job Execution

```bash
# Delete expired guests
php artisan guests:delete-expired

# Sync with FortiGate
php artisan users:sync-fortigate

# Send test email
php artisan mail:test user@example.com
```

### System Health Monitoring

#### Key Metrics to Monitor

**Performance:**
- Page load times
- Database query performance
- Queue processing speed
- API response times

**Resources:**
- CPU usage
- Memory consumption
- Disk space
- Network bandwidth

**FortiGate Integration:**
- API availability
- Sync success rate
- Failed operations
- Response times

#### Health Check Endpoints

- Application health: `/up`
- Queue health: Check Horizon dashboard
- Database health: Monitor connection pool
- Cache health: Redis/File cache status

### Database Maintenance

#### Regular Tasks

**Daily:**
- Check backup completion
- Monitor table sizes
- Review slow query log

**Weekly:**
- Analyze table statistics
- Check index usage
- Review disk space

**Monthly:**
- Optimize tables
- Archive old data
- Update statistics

#### Backup Procedures

**Automated Backups:**
```bash
# Add to crontab
0 2 * * * mysqldump captive_portal > /backup/captive_portal_$(date +\%Y\%m\%d).sql
```

**Manual Backup:**
```bash
# Full database backup
php artisan backup:run

# Specific tables
mysqldump captive_portal users audit_logs > backup.sql
```

---

## Audit and Compliance

### Audit Logging

#### What's Logged

**Authentication Events:**
- Admin login/logout
- Failed login attempts
- MFA events
- Password changes

**User Management:**
- User creation/modification/deletion
- Status changes (suspend/activate)
- FortiGate sync events
- Bulk operations

**Configuration Changes:**
- Settings modifications
- Charter updates
- FortiGate configuration

**System Events:**
- Job executions
- Email sends
- API calls
- Errors and exceptions

#### Viewing Audit Logs

1. Navigate to **Audit → Logs**
2. Filter options:
   - Date range
   - Event type
   - User/Admin
   - IP address
3. Search functionality
4. Export to Excel

#### Audit Log Retention

**Configuration:**
- Default: 365 days
- Configurable in Settings
- Automatic cleanup
- Archive before deletion option

### Compliance Features

#### GDPR Compliance

**Data Protection:**
- Encryption at rest and in transit
- Secure password storage (bcrypt)
- Token-based authentication
- Session encryption

**User Rights:**
- Data export capability
- Account deletion (soft delete)
- Audit trail of data access
- Consent tracking (charter acceptance)

#### ANSSI Security Standards

**Password Requirements:**
- Meets ANSSI complexity rules
- Enforced password history
- Account lockout policies
- MFA for privileged accounts

### Reporting

#### Available Reports

**User Reports:**
- Active users by type
- Expiring users
- Registration trends
- Failed validations

**Security Reports:**
- Failed login attempts
- Account lockouts
- MFA usage
- Password expirations

**System Reports:**
- FortiGate sync status
- Queue performance
- Email delivery rates
- Error frequency

#### Exporting Data

**Excel Export:**
1. Navigate to desired section
2. Apply filters if needed
3. Click **"Export to Excel"**
4. File downloads automatically

**Supported Exports:**
- User lists
- Audit logs
- System configurations
- Performance metrics

---

## Advanced Operations

### Command Line Operations

#### User Management Commands

```bash
# Create admin user
php artisan admin:create

# Delete expired guests manually
php artisan guests:delete-expired --force

# Sync all users with FortiGate
php artisan users:sync-fortigate

# Expire users manually
php artisan users:expire --days=0
```

#### System Maintenance Commands

```bash
# Clear all caches
php artisan optimize:clear

# Rebuild caches
php artisan optimize

# Run pending migrations
php artisan migrate

# Check system status
php artisan about
```

#### Debugging Commands

```bash
# Test FortiGate connection
php artisan fortigate:test

# Test email configuration
php artisan mail:test recipient@example.com

# View failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

### API Integration

#### FortiGate API Operations

**Direct API Calls:**
```php
// Get user from FortiGate
GET /api/v2/cmdb/user/local/{username}

// Update user status
PUT /api/v2/cmdb/user/local/{username}
{
    "status": "disable"
}

// Get active sessions
GET /api/v2/monitor/user/firewall/

// Force disconnect
POST /api/v2/monitor/user/firewall/deauth
```

### Database Operations

#### Direct Database Access

```sql
-- Find unvalidated guests
SELECT * FROM users 
WHERE user_type = 'guest' 
AND validated_at IS NULL
AND created_at < DATE_SUB(NOW(), INTERVAL 30 MINUTE);

-- Check expired users
SELECT * FROM users 
WHERE expires_at < NOW() 
AND status != 'expired';

-- Audit log analysis
SELECT event, COUNT(*) as count 
FROM audit_logs 
WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)
GROUP BY event
ORDER BY count DESC;
```

### Emergency Procedures

#### System Recovery

**If system is down:**
1. Check server resources (CPU, memory, disk)
2. Verify database connectivity
3. Check Laravel logs: `storage/logs/laravel.log`
4. Verify queue workers running
5. Test FortiGate connectivity

**If authentication fails:**
1. Check admin user exists in database
2. Verify password hash is valid
3. Check for account lockout
4. Reset MFA if necessary
5. Clear session data

#### Emergency Access

**Grant temporary admin access:**
```bash
# Create emergency admin
php artisan tinker
>>> $admin = new App\Models\AdminUser;
>>> $admin->name = 'Emergency Admin';
>>> $admin->email = 'emergency@example.com';
>>> $admin->password = Hash::make('TempPassword123!@#');
>>> $admin->role = 'super_admin';
>>> $admin->save();
```

**Disable MFA temporarily:**
```bash
php artisan tinker
>>> $admin = App\Models\AdminUser::where('email', 'admin@example.com')->first();
>>> $admin->two_factor_secret = null;
>>> $admin->two_factor_confirmed_at = null;
>>> $admin->save();
```

---

## Troubleshooting

### Common Issues

#### Users Can't Register

**Symptoms:** Registration form fails

**Check:**
- FortiGate API connectivity
- Database connection
- Email service status
- Validation settings
- Queue workers running

**Solution:**
```bash
php artisan fortigate:test
php artisan queue:restart
php artisan cache:clear
```

#### Email Validation Not Working

**Symptoms:** Validation emails not received

**Check:**
- SMTP configuration
- Queue processing
- Email logs
- Spam filters

**Solution:**
```bash
php artisan mail:test user@example.com
php artisan queue:work --stop-when-empty
tail -f storage/logs/laravel.log
```

#### FortiGate Sync Failures

**Symptoms:** Users not created in FortiGate

**Check:**
- API token validity
- Network connectivity
- User group exists
- API permissions

**Solution:**
```bash
php artisan fortigate:test
php artisan users:sync-fortigate
# Check sync_status in users table
```

#### Performance Issues

**Symptoms:** Slow page loads

**Check:**
- Database query performance
- Redis/Cache availability
- Queue backlog
- Server resources

**Solution:**
```bash
php artisan optimize
php artisan queue:clear
php artisan cache:clear
php artisan config:cache
```

### Error Messages

| Error | Cause | Solution |
|-------|-------|----------|
| "FortiGate API unreachable" | Network/firewall issue | Check connectivity, firewall rules |
| "User creation failed" | API/permission issue | Verify API token and user group |
| "Email send failed" | SMTP configuration | Check mail settings, test SMTP |
| "Session expired" | Timeout reached | Re-login, adjust timeout if needed |
| "MFA code invalid" | Time sync issue | Check device time, use backup code |

### Logging and Debugging

#### Log Locations

- **Laravel logs:** `storage/logs/laravel.log`
- **Queue logs:** `storage/logs/horizon.log`
- **Web server logs:** `/var/log/apache2/` or `/var/log/nginx/`
- **Mail logs:** `/var/log/mail.log`

#### Enable Debug Mode

**Temporary (development only):**
```bash
# In .env file
APP_DEBUG=true
LOG_LEVEL=debug
```

#### Monitoring Tools

- **Laravel Telescope:** For local debugging
- **Laravel Horizon:** Queue monitoring
- **New Relic/Datadog:** Production monitoring
- **ELK Stack:** Log aggregation

---

## Best Practices

### Security Best Practices

1. **Authentication:**
   - Enforce MFA for all admins
   - Use strong, unique passwords
   - Rotate API tokens regularly
   - Monitor failed login attempts

2. **Network Security:**
   - Use HTTPS everywhere
   - Implement firewall rules
   - Restrict FortiGate API access
   - Regular security updates

3. **Data Protection:**
   - Regular backups
   - Encrypt sensitive data
   - Audit log retention
   - GDPR compliance

### Operational Best Practices

1. **Monitoring:**
   - Daily dashboard review
   - Check queue health
   - Monitor disk space
   - Review audit logs

2. **Maintenance:**
   - Weekly backup verification
   - Monthly security updates
   - Quarterly password changes
   - Annual security audit

3. **Documentation:**
   - Keep configuration documented
   - Log all changes
   - Maintain runbooks
   - Update contact information

### User Management Best Practices

1. **Account Lifecycle:**
   - Prompt account creation
   - Regular expiration reviews
   - Timely deactivation
   - Audit trail maintenance

2. **Guest Management:**
   - Monitor registration patterns
   - Review validation failures
   - Clean expired accounts
   - Update charter regularly

3. **Communication:**
   - Clear onboarding instructions
   - Timely expiration notices
   - Support contact visibility
   - Multi-language support

---

## Appendix

### Quick Reference

#### Important URLs

- Admin Panel: `/admin`
- Guest Registration: `/guest/register`
- Queue Monitor: `/horizon`
- Health Check: `/up`

#### Default Settings

- Session Timeout: 15 minutes
- Guest Duration: 24 hours
- Validation Window: 30 minutes
- Password Expiry: 90 days
- Audit Retention: 365 days

#### Console Commands

```bash
# User Management
php artisan admin:create
php artisan guests:delete-expired
php artisan users:expire
php artisan users:sync-fortigate

# System Maintenance
php artisan optimize
php artisan queue:restart
php artisan horizon:terminate
php artisan cache:clear

# Testing
php artisan fortigate:test
php artisan mail:test
```

### Support Information

**Technical Support:**
- Email: support@emerging-it.fr
- Documentation: GitHub repository
- Issues: GitHub Issues page

**Emergency Contacts:**
- System Administrator: [Contact]
- Network Team: [Contact]
- Security Team: [Contact]

---

**© 2025 Emerging IT - Philippe CANDIDO**  
**All rights reserved**