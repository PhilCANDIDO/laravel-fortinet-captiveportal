<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Carbon\Carbon;

class NotificationService
{
    /**
     * Send guest validation email
     */
    public function sendGuestValidationEmail(User $user, string $password): void
    {
        if (!$user->isGuest()) {
            Log::warning('Attempted to send guest email to non-guest', [
                'user_id' => $user->id,
                'user_type' => $user->user_type,
            ]);
            return;
        }
        
        $validationEnabled = \App\Models\Setting::isGuestEmailValidationEnabled();
        $locale = $this->getUserLocale($user);
        
        // If validation is disabled, send credentials email without validation link
        if (!$validationEnabled) {
            $this->sendGuestCredentialsEmail($user, $password);
            return;
        }
        
        // Validation is enabled, check for token
        if (!$user->validation_token) {
            Log::warning('No validation token for guest user', [
                'user_id' => $user->id,
            ]);
            return;
        }
        
        // Generate validation URL
        $expirationMinutes = \App\Models\Setting::getGuestValidationDelayMinutes();
        $validationUrl = URL::temporarySignedRoute(
            'guest.validate',
            Carbon::now()->addMinutes($expirationMinutes),
            ['token' => $user->validation_token]
        );
        
        // Send validation email
        Mail::send("emails.guest-validation.{$locale}", [
            'user' => $user,
            'password' => $password,
            'username' => $user->fortigate_username,
            'validationUrl' => $validationUrl,
            'expiresIn' => $expirationMinutes,
        ], function ($message) use ($user, $locale) {
            $message->to($user->email, $user->name)
                    ->subject($this->getSubject('guest_validation', $locale));
        });
        
        Log::info('Guest validation email sent', [
            'user_id' => $user->id,
            'email' => $user->email,
            'locale' => $locale,
        ]);
    }
    
    /**
     * Send guest credentials email (no validation required)
     */
    public function sendGuestCredentialsEmail(User $user, string $password): void
    {
        $locale = $this->getUserLocale($user);
        $captivePortalUrl = \App\Models\FortiGateSettings::current()->captive_portal_url;
        
        // Send credentials email without validation link
        Mail::send("emails.guest-credentials.{$locale}", [
            'user' => $user,
            'password' => $password,
            'username' => $user->fortigate_username,
            'captivePortalUrl' => $captivePortalUrl,
        ], function ($message) use ($user, $locale) {
            $message->to($user->email, $user->name)
                    ->subject($this->getSubject('guest_credentials', $locale));
        });
        
        Log::info('Guest credentials email sent', [
            'user_id' => $user->id,
            'email' => $user->email,
            'locale' => $locale,
        ]);
    }
    
    /**
     * Send welcome email with credentials
     */
    public function sendWelcomeEmail(User $user, string $password): void
    {
        $locale = $this->getUserLocale($user);
        
        Mail::send("emails.welcome.{$locale}", [
            'user' => $user,
            'password' => $password,
            'loginUrl' => route('login'),
            'userType' => $user->getUserTypeLabel(),
            'expiresAt' => $user->expires_at,
        ], function ($message) use ($user, $locale) {
            $message->to($user->email, $user->name)
                    ->subject($this->getSubject('welcome', $locale));
        });
        
        Log::info('Welcome email sent', [
            'user_id' => $user->id,
            'email' => $user->email,
            'user_type' => $user->user_type,
        ]);
    }
    
    /**
     * Send expiration reminder
     */
    public function sendExpirationReminder(User $user, int $daysRemaining): void
    {
        $locale = $this->getUserLocale($user);
        
        Mail::send("emails.expiration-reminder.{$locale}", [
            'user' => $user,
            'daysRemaining' => $daysRemaining,
            'expiresAt' => $user->expires_at,
            'userType' => $user->getUserTypeLabel(),
        ], function ($message) use ($user, $locale, $daysRemaining) {
            $subject = $this->getSubject('expiration_reminder', $locale);
            $subject = str_replace(':days', $daysRemaining, $subject);
            $message->to($user->email, $user->name)
                    ->subject($subject);
        });
        
        Log::info('Expiration reminder sent', [
            'user_id' => $user->id,
            'email' => $user->email,
            'days_remaining' => $daysRemaining,
        ]);
    }
    
    /**
     * Send account expired notification
     */
    public function sendAccountExpiredEmail(User $user): void
    {
        $locale = $this->getUserLocale($user);
        
        Mail::send("emails.account-expired.{$locale}", [
            'user' => $user,
            'userType' => $user->getUserTypeLabel(),
        ], function ($message) use ($user, $locale) {
            $message->to($user->email, $user->name)
                    ->subject($this->getSubject('account_expired', $locale));
        });
        
        Log::info('Account expired email sent', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);
    }
    
    /**
     * Send account suspended notification
     */
    public function sendAccountSuspendedEmail(User $user, string $reason = null): void
    {
        $locale = $this->getUserLocale($user);
        
        Mail::send("emails.account-suspended.{$locale}", [
            'user' => $user,
            'reason' => $reason,
        ], function ($message) use ($user, $locale) {
            $message->to($user->email, $user->name)
                    ->subject($this->getSubject('account_suspended', $locale));
        });
        
        Log::info('Account suspended email sent', [
            'user_id' => $user->id,
            'email' => $user->email,
            'reason' => $reason,
        ]);
    }
    
    /**
     * Send account reactivated notification
     */
    public function sendAccountReactivatedEmail(User $user): void
    {
        $locale = $this->getUserLocale($user);
        
        Mail::send("emails.account-reactivated.{$locale}", [
            'user' => $user,
            'loginUrl' => route('login'),
        ], function ($message) use ($user, $locale) {
            $message->to($user->email, $user->name)
                    ->subject($this->getSubject('account_reactivated', $locale));
        });
        
        Log::info('Account reactivated email sent', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);
    }
    
    /**
     * Send password reset email
     */
    public function sendPasswordResetEmail(User $user, string $token): void
    {
        $locale = $this->getUserLocale($user);
        
        $resetUrl = URL::temporarySignedRoute(
            'password.reset',
            Carbon::now()->addHour(),
            ['token' => $token, 'email' => $user->email]
        );
        
        Mail::send("emails.password-reset.{$locale}", [
            'user' => $user,
            'resetUrl' => $resetUrl,
        ], function ($message) use ($user, $locale) {
            $message->to($user->email, $user->name)
                    ->subject($this->getSubject('password_reset', $locale));
        });
        
        Log::info('Password reset email sent', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);
    }
    
    /**
     * Send guest sponsor notification
     */
    public function sendSponsorNotification(User $guest): void
    {
        if (!$guest->sponsor_email) {
            return;
        }
        
        $locale = $this->getUserLocale($guest);
        
        Mail::send("emails.sponsor-notification.{$locale}", [
            'guest' => $guest,
            'validUntil' => $guest->expires_at,
        ], function ($message) use ($guest, $locale) {
            $message->to($guest->sponsor_email, $guest->sponsor_name)
                    ->subject($this->getSubject('sponsor_notification', $locale));
        });
        
        Log::info('Sponsor notification sent', [
            'guest_id' => $guest->id,
            'sponsor_email' => $guest->sponsor_email,
        ]);
    }
    
    /**
     * Send bulk import report
     */
    public function sendBulkImportReport(string $email, array $results, string $locale = 'fr'): void
    {
        Mail::send("emails.bulk-import-report.{$locale}", [
            'results' => $results,
            'successCount' => $results['success_count'] ?? 0,
            'errorCount' => $results['error_count'] ?? 0,
            'errors' => $results['errors'] ?? [],
        ], function ($message) use ($email, $locale) {
            $message->to($email)
                    ->subject($this->getSubject('bulk_import_report', $locale));
            
            // Attach CSV report if available
            if (isset($results['report_file'])) {
                $message->attach($results['report_file']);
            }
        });
        
        Log::info('Bulk import report sent', [
            'to' => $email,
            'success_count' => $results['success_count'] ?? 0,
            'error_count' => $results['error_count'] ?? 0,
        ]);
    }
    
    /**
     * Get user's preferred locale
     */
    protected function getUserLocale(User $user): string
    {
        // Check if user has a preferred language stored
        // For now, use the application default
        $locale = config('app.locale', 'fr');
        
        // Ensure the locale is supported
        $supportedLocales = ['fr', 'en', 'it', 'es'];
        if (!in_array($locale, $supportedLocales)) {
            $locale = 'fr';
        }
        
        return $locale;
    }
    
    /**
     * Get email subject by key and locale
     */
    protected function getSubject(string $key, string $locale): string
    {
        $subjects = [
            'fr' => [
                'guest_validation' => 'Validation de votre compte invité',
                'guest_credentials' => 'Vos identifiants de connexion WiFi',
                'welcome' => 'Bienvenue - Vos identifiants de connexion',
                'expiration_reminder' => 'Votre accès expire dans :days jours',
                'account_expired' => 'Votre compte a expiré',
                'account_suspended' => 'Votre compte a été suspendu',
                'account_reactivated' => 'Votre compte a été réactivé',
                'password_reset' => 'Réinitialisation de votre mot de passe',
                'sponsor_notification' => 'Notification de parrainage d\'invité',
                'bulk_import_report' => 'Rapport d\'importation en masse',
            ],
            'en' => [
                'guest_validation' => 'Guest Account Validation',
                'guest_credentials' => 'Your WiFi Login Credentials',
                'welcome' => 'Welcome - Your Login Credentials',
                'expiration_reminder' => 'Your access expires in :days days',
                'account_expired' => 'Your account has expired',
                'account_suspended' => 'Your account has been suspended',
                'account_reactivated' => 'Your account has been reactivated',
                'password_reset' => 'Password Reset',
                'sponsor_notification' => 'Guest Sponsorship Notification',
                'bulk_import_report' => 'Bulk Import Report',
            ],
            'it' => [
                'guest_validation' => 'Convalida account ospite',
                'welcome' => 'Benvenuto - Le tue credenziali di accesso',
                'expiration_reminder' => 'Il tuo accesso scade tra :days giorni',
                'account_expired' => 'Il tuo account è scaduto',
                'account_suspended' => 'Il tuo account è stato sospeso',
                'account_reactivated' => 'Il tuo account è stato riattivato',
                'password_reset' => 'Reimpostazione password',
                'sponsor_notification' => 'Notifica di sponsorizzazione ospite',
                'bulk_import_report' => 'Rapporto importazione di massa',
            ],
            'es' => [
                'guest_validation' => 'Validación de cuenta de invitado',
                'welcome' => 'Bienvenido - Sus credenciales de acceso',
                'expiration_reminder' => 'Su acceso expira en :days días',
                'account_expired' => 'Su cuenta ha expirado',
                'account_suspended' => 'Su cuenta ha sido suspendida',
                'account_reactivated' => 'Su cuenta ha sido reactivada',
                'password_reset' => 'Restablecer contraseña',
                'sponsor_notification' => 'Notificación de patrocinio de invitado',
                'bulk_import_report' => 'Informe de importación masiva',
            ],
        ];
        
        return $subjects[$locale][$key] ?? $subjects['fr'][$key] ?? $key;
    }
}