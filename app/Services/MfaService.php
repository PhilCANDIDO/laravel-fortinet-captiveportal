<?php

namespace App\Services;

use App\Models\AdminUser;
use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Cache;

class MfaService
{
    protected Google2FA $google2fa;
    
    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }
    
    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey();
    }
    
    public function generateQrCode(AdminUser $user, string $secret): string
    {
        $companyName = config('app.name', 'Laravel Fortinet Portal');
        $companyEmail = $user->email;
        
        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            $companyName,
            $companyEmail,
            $secret
        );
        
        $renderer = new ImageRenderer(
            new RendererStyle(250),
            new SvgImageBackEnd()
        );
        
        $writer = new Writer($renderer);
        
        return $writer->writeString($qrCodeUrl);
    }
    
    public function verify(string $secret, string $code): bool
    {
        $window = 2;
        
        $timestamp = $this->google2fa->getTimestamp();
        $key = "2fa_code_{$secret}_{$code}_{$timestamp}";
        
        if (Cache::has($key)) {
            return false;
        }
        
        $isValid = $this->google2fa->verifyKey($secret, $code, $window);
        
        if ($isValid) {
            Cache::put($key, true, 60);
        }
        
        return $isValid;
    }
    
    public function enableFor(AdminUser $user, string $secret): array
    {
        $user->update([
            'google2fa_secret' => encrypt($secret),
            'google2fa_enabled' => true,
            'google2fa_enabled_at' => now(),
        ]);
        
        return $user->generateBackupCodes();
    }
    
    public function disableFor(AdminUser $user): void
    {
        $user->update([
            'google2fa_secret' => null,
            'google2fa_enabled' => false,
            'google2fa_enabled_at' => null,
            'google2fa_backup_codes' => null,
        ]);
    }
    
    public function verifyForUser(AdminUser $user, string $code): bool
    {
        if (!$user->google2fa_enabled || !$user->google2fa_secret) {
            return false;
        }
        
        $secret = decrypt($user->google2fa_secret);
        
        return $this->verify($secret, $code);
    }
    
    public function verifyBackupCode(AdminUser $user, string $code): bool
    {
        return $user->useBackupCode($code);
    }
    
    public function regenerateBackupCodes(AdminUser $user): array
    {
        return $user->generateBackupCodes();
    }
    
    public function isRequired(AdminUser $user): bool
    {
        return $user->google2fa_enabled;
    }
    
    public function getBackupCodesCount(AdminUser $user): int
    {
        return $user->getBackupCodesCount();
    }
}