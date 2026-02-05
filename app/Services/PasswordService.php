<?php

namespace App\Services;

use App\Models\AdminUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class PasswordService
{
    public function getPasswordRules(): array
    {
        return [
            'required',
            'string',
            Password::min(16)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised(),
        ];
    }
    
    public function validatePassword(string $password): array
    {
        $validator = Validator::make(
            ['password' => $password],
            ['password' => $this->getPasswordRules()]
        );
        
        if ($validator->fails()) {
            return [
                'valid' => false,
                'errors' => $validator->errors()->get('password'),
            ];
        }
        
        return [
            'valid' => true,
            'errors' => [],
        ];
    }
    
    public function checkPasswordStrength(string $password): array
    {
        $strength = 0;
        $feedback = [];
        
        if (strlen($password) >= 16) {
            $strength += 20;
        } else {
            $feedback[] = 'Password must be at least 16 characters long';
        }
        
        if (strlen($password) >= 20) {
            $strength += 10;
        }
        
        if (preg_match('/[a-z]/', $password)) {
            $strength += 15;
        } else {
            $feedback[] = 'Add lowercase letters';
        }
        
        if (preg_match('/[A-Z]/', $password)) {
            $strength += 15;
        } else {
            $feedback[] = 'Add uppercase letters';
        }
        
        if (preg_match('/[0-9]/', $password)) {
            $strength += 15;
        } else {
            $feedback[] = 'Add numbers';
        }
        
        if (preg_match('/[^A-Za-z0-9]/', $password)) {
            $strength += 15;
        } else {
            $feedback[] = 'Add special characters';
        }
        
        if (preg_match('/(.)\1{2,}/', $password)) {
            $strength -= 10;
            $feedback[] = 'Avoid repeating characters';
        }
        
        if (preg_match('/(?:012|123|234|345|456|567|678|789|890|abc|bcd|cde|def)/i', $password)) {
            $strength -= 10;
            $feedback[] = 'Avoid sequential characters';
        }
        
        $commonPasswords = ['password', 'admin', 'fortinet', 'portal', '123456'];
        foreach ($commonPasswords as $common) {
            if (stripos($password, $common) !== false) {
                $strength -= 20;
                $feedback[] = 'Avoid common words';
                break;
            }
        }
        
        $strength = max(0, min(100, $strength));
        
        $level = match (true) {
            $strength >= 80 => 'strong',
            $strength >= 60 => 'good',
            $strength >= 40 => 'fair',
            default => 'weak',
        };
        
        return [
            'strength' => $strength,
            'level' => $level,
            'feedback' => $feedback,
        ];
    }
    
    public function isPasswordReused(AdminUser $user, string $password): bool
    {
        if (Hash::check($password, $user->password)) {
            return true;
        }
        
        return $user->hasUsedPassword($password);
    }
    
    public function changePassword(AdminUser $user, string $newPassword): bool
    {
        if ($this->isPasswordReused($user, $newPassword)) {
            throw new \Exception('This password has been used recently. Please choose a different password.');
        }
        
        $validation = $this->validatePassword($newPassword);
        if (!$validation['valid']) {
            throw new \Exception('Password does not meet security requirements: ' . implode(', ', $validation['errors']));
        }
        
        $user->savePasswordToHistory();
        
        $user->password = $newPassword;
        $user->save();
        
        return true;
    }
    
    public function generateSecurePassword(int $length = 20): string
    {
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $symbols = '@$!%*?&';
        
        $password = '';
        
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $symbols[random_int(0, strlen($symbols) - 1)];
        
        $allChars = $uppercase . $lowercase . $numbers . $symbols;
        
        for ($i = strlen($password); $i < $length; $i++) {
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }
        
        return str_shuffle($password);
    }
    
    public function isPasswordExpired(AdminUser $user): bool
    {
        return $user->isPasswordExpired();
    }
    
    public function getDaysUntilExpiration(AdminUser $user): ?int
    {
        if (!$user->password_expires_at) {
            return null;
        }
        
        $daysUntilExpiration = now()->diffInDays($user->password_expires_at, false);
        
        return max(0, $daysUntilExpiration);
    }
}