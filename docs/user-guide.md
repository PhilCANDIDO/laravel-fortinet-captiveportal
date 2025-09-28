# User Guide

## Laravel Fortinet Captive Portal - Guest Access

**Version:** 1.0.0  
**Last Updated:** August 2025  
**Available Languages:** 🇫🇷 Français | 🇬🇧 English | 🇮🇹 Italiano | 🇪🇸 Español

---

## Table of Contents

1. [Welcome](#welcome)
2. [Getting Started](#getting-started)
3. [Registration Process](#registration-process)
4. [Email Validation](#email-validation)
5. [Connecting to the Network](#connecting-to-the-network)
6. [Understanding Your Access](#understanding-your-access)
7. [Frequently Asked Questions](#frequently-asked-questions)
8. [Troubleshooting](#troubleshooting)
9. [Support](#support)

---

## Welcome

Welcome to our network captive portal! This system provides secure, temporary internet access for guests visiting our facilities. This guide will walk you through the process of registering and connecting to our network.

### What is a Captive Portal?

A captive portal is a web page that users must view and interact with before accessing the Internet. It ensures:
- Secure network access
- User accountability
- Compliance with usage policies
- Protection of network resources

---

## Getting Started

### Prerequisites

Before you begin, you'll need:
- A valid email address
- A device with WiFi capability (laptop, smartphone, tablet)
- A web browser (Chrome, Firefox, Safari, Edge)

### Available Networks

Look for one of these network names (SSID):
- **Guest-WiFi** - Primary guest network
- **Visitor-Network** - Alternative guest access

### Language Selection

The portal supports multiple languages. You can change the language at any time:
1. Look for the language selector (flag icons) at the top of the page
2. Click on your preferred language:
   - 🇫🇷 Français
   - 🇬🇧 English
   - 🇮🇹 Italiano
   - 🇪🇸 Español

---

## Registration Process

### Step 1: Connect to Guest Network

1. Open your device's WiFi settings
2. Select the guest network (e.g., "Guest-WiFi")
3. Wait for the captive portal to appear automatically
   - If it doesn't appear, open a browser and try to visit any website
   - You'll be redirected to the registration page

### Step 2: Access Registration Page

The registration page will display automatically when you connect. If not:
- Open your web browser
- Navigate to any website (e.g., google.com)
- You'll be redirected to: `http://captiveportal.test/guest/register`

### Step 3: Fill Registration Form

Complete all required fields:

| Field | Description | Required |
|-------|-------------|----------|
| **First Name** | Your first name | ✅ Yes |
| **Last Name** | Your last name | ✅ Yes |
| **Email** | Valid email address | ✅ Yes |
| **Company** | Your organization | ❌ Optional |
| **Visit Reason** | Purpose of visit | ❌ Optional |
| **Contact Person** | Who you're visiting | ❌ Optional |

#### Important Notes:
- Use a valid email address that you can access immediately
- The email will be used for validation and sending credentials
- Information is kept confidential and used only for network access

### Step 4: Accept Terms and Conditions

1. Read the Acceptable Use Policy (Charter)
2. Check the box: "I accept the terms and conditions"
3. The charter is available in your selected language

### Step 5: Submit Registration

1. Click the **"Register"** button
2. Wait for the system to process your registration
3. Your credentials will be displayed on screen

### Step 6: Save Your Credentials

**IMPORTANT:** Save your login credentials immediately!

You'll receive:
- **Username:** Usually in format `guest-XXXX`
- **Password:** Auto-generated secure password

**Options to save:**
- Take a screenshot
- Write them down
- The same information will be sent to your email

---

## Email Validation

### Why Validation is Required

Email validation ensures:
- You are who you claim to be
- You have access to the provided email
- Prevention of unauthorized access
- Compliance with security policies

### Validation Process

#### Time Limit: 30 Minutes ⏰

You have **30 minutes** from registration to validate your email, or your account will be automatically disabled.

#### Steps to Validate:

1. **Check Your Email**
   - Look for an email from "Fortinet Captive Portal"
   - Subject: "Validate your guest account"
   - Check spam/junk folder if not in inbox

2. **Click Validation Link**
   - Open the email
   - Click the "Validate Account" button
   - Or copy and paste the validation link into your browser

3. **Confirmation**
   - You'll see a confirmation page
   - Your account is now active for 24 hours
   - You can now use your credentials to connect

### If You Don't Receive the Email

Try these steps:
1. Check your spam/junk folder
2. Verify you entered the correct email address
3. Wait 2-3 minutes (emails may be delayed)
4. Try registering again with the same email
5. Contact IT support if issues persist

### What Happens If You Don't Validate?

If you don't validate within 30 minutes:
- Your account will be automatically disabled
- You'll need to register again
- Previous credentials will no longer work

---

## Connecting to the Network

### Automatic Connection

After successful registration and validation:

1. **Success Page Features:**
   - Your credentials are displayed
   - A "Connect Now" button appears
   - 10-second automatic redirect countdown

2. **Auto-Connection:**
   - Click "Connect Now" or wait for auto-redirect
   - FortiGate login page opens in a new tab
   - Credentials may be pre-filled automatically

### Manual Connection

If automatic connection doesn't work:

1. **Open FortiGate Login Page**
   - Usually appears when trying to access any website
   - Or navigate to the portal URL provided

2. **Enter Credentials**
   - **Username:** Your guest username (e.g., guest-1234)
   - **Password:** The password provided during registration

3. **Click Login**
   - You'll be connected to the internet
   - A success message confirms your connection

### Connection Indicators

You're successfully connected when:
- ✅ Websites load normally
- ✅ Email and apps work
- ✅ No more redirect to captive portal
- ✅ Network icon shows connected status

---

## Understanding Your Access

### Access Duration

| User Type | Access Duration | Notes |
|-----------|----------------|-------|
| **Guest** | 24 hours | Automatic expiration after 24 hours |
| **Extended Guest** | Variable | Set by administrator if needed |

### Access Limitations

Your guest account provides:
- ✅ Internet browsing
- ✅ Email access
- ✅ Cloud services
- ✅ Video conferencing

Restrictions may include:
- ❌ Peer-to-peer file sharing
- ❌ Certain streaming services
- ❌ Gaming platforms
- ❌ VPN connections (depends on policy)

### Account Expiration

**What happens after 24 hours:**
1. Your internet access will stop
2. You'll be redirected to the portal again
3. You'll need to register again for continued access
4. Previous credentials will no longer work

**Expiration Notifications:**
- You may receive an email before expiration
- Browser may show portal redirect when expired

---

## Frequently Asked Questions

### General Questions

**Q: How long does my access last?**
A: Guest access is valid for 24 hours from validation.

**Q: Can I extend my access?**
A: You'll need to register again after 24 hours. For longer access, contact the IT administrator.

**Q: Is my connection secure?**
A: Yes, the network uses enterprise-grade security. However, always use HTTPS websites for sensitive data.

**Q: Can I use multiple devices?**
A: Typically, credentials work on one device at a time. Register separately for each device.

### Registration Issues

**Q: I didn't receive my validation email**
A: Check spam folder, verify email address, and ensure you can receive emails from noreply@example.com.

**Q: Can I register with the same email twice?**
A: Yes, after your previous access expires, you can register again with the same email.

**Q: I forgot my password**
A: Check the email sent during registration. If lost, you'll need to register again.

### Connection Problems

**Q: The portal page doesn't appear**
A: Try opening a browser and navigating to http://example.com. Disable VPN if active.

**Q: My credentials don't work**
A: Ensure you validated your email within 30 minutes. Check for typos in username/password.

**Q: I keep getting redirected to the portal**
A: Your session may have expired. Try logging in again or register for new access.

---

## Troubleshooting

### Common Issues and Solutions

#### 1. Cannot Access Registration Page

**Symptoms:** Browser doesn't redirect to portal

**Solutions:**
- Clear browser cache and cookies
- Try a different browser
- Disable VPN or proxy
- Manually navigate to http://captiveportal.test/guest/register

#### 2. Registration Form Won't Submit

**Symptoms:** Clicking Register does nothing

**Solutions:**
- Ensure all required fields are filled
- Check email format is valid
- Accept terms and conditions
- Try a different browser
- Disable browser extensions

#### 3. Validation Email Not Received

**Symptoms:** No email after registration

**Solutions:**
- Check spam/junk folder
- Verify email address spelling
- Whitelist sender domain
- Wait 5 minutes
- Contact support if still not received

#### 4. Invalid Credentials Error

**Symptoms:** Login fails with correct credentials

**Solutions:**
- Ensure email was validated
- Check 30-minute validation window
- Verify no spaces before/after credentials
- Ensure CAPS LOCK is off
- Try copy-paste from email

#### 5. Connection Drops Frequently

**Symptoms:** Need to re-login often

**Solutions:**
- Check WiFi signal strength
- Disable power saving on device
- Stay within network range
- Avoid switching between networks

#### 6. Slow Internet Speed

**Symptoms:** Pages load slowly

**Solutions:**
- Check signal strength
- Move closer to access point
- Reduce number of open tabs/apps
- Network may be congested (peak hours)

### Browser-Specific Issues

#### Chrome/Edge
- Clear cache: Settings → Privacy → Clear browsing data
- Disable extensions temporarily
- Try Incognito/Private mode

#### Safari
- Clear cache: Develop → Empty Caches
- Disable pop-up blocker for portal
- Allow cookies from portal site

#### Firefox
- Clear cache: Settings → Privacy & Security → Clear Data
- Disable Enhanced Tracking Protection for portal
- Check network proxy settings

---

## Support

### Self-Service Resources

Before contacting support, try:
1. This user guide
2. FAQ section above
3. Troubleshooting steps
4. Re-registration if been more than 24 hours

### Contact Information

**IT Support**
- Email: support@emerging-it.fr
- Phone: +33 (0)1 XX XX XX XX
- Hours: Monday-Friday, 9:00 AM - 6:00 PM CET

**When Contacting Support, Provide:**
- Your name and email used for registration
- Device type (laptop/phone/tablet)
- Operating system (Windows/Mac/iOS/Android)
- Browser being used
- Error messages (screenshots helpful)
- Time of issue occurrence

### On-Site Assistance

If you're on-site and need immediate help:
1. Visit the IT Help Desk (Building A, Room 102)
2. Call internal extension: 1234
3. Ask reception for IT assistance

### Emergency Access

For urgent business needs outside support hours:
- Contact security desk
- Provide business justification
- Temporary access may be granted

---

## Privacy and Security

### Data Protection

Your information is:
- Used only for network access
- Stored securely and encrypted
- Deleted after retention period
- Never shared with third parties
- Compliant with GDPR regulations

### Best Practices

While using our guest network:
- ✅ Use HTTPS websites when possible
- ✅ Keep your device's OS and browser updated
- ✅ Log out when finished
- ✅ Don't share your credentials
- ❌ Avoid accessing sensitive data on public WiFi
- ❌ Don't download suspicious files
- ❌ Don't disable your device's firewall

### Acceptable Use Policy

By using our network, you agree to:
- Use the network legally and ethically
- Respect intellectual property rights
- Not attempt to bypass security measures
- Not engage in harmful or illegal activities
- Report security issues to IT

---

## Quick Reference Card

### Registration Checklist
- [ ] Connect to Guest WiFi
- [ ] Complete registration form
- [ ] Accept terms and conditions
- [ ] Save credentials
- [ ] Check email
- [ ] Validate within 30 minutes
- [ ] Connect with credentials

### Important URLs
- Registration: `http://captiveportal.test/guest/register`
- Support Email: support@emerging-it.fr

### Key Information
- **Access Duration:** 24 hours
- **Validation Window:** 30 minutes
- **Username Format:** guest-XXXX
- **Password:** Auto-generated

---

**Thank you for using our guest network!**

**© 2025 Emerging IT - All rights reserved**