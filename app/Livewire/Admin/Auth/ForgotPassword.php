<?php

namespace App\Livewire\Admin\Auth;

use App\Models\AdminUser;
use App\Services\AuditService;
use App\Notifications\AdminPasswordResetNotification;
use Livewire\Component;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ForgotPassword extends Component
{
    public $email = '';
    
    protected $rules = [
        'email' => 'required|email|exists:admin_users,email',
    ];
    
    public function sendResetLink(AuditService $auditService)
    {
        $this->validate();
        
        $user = AdminUser::where('email', $this->email)->first();
        
        if (!$user) {
            $this->addError('email', 'We cannot find a user with that email address.');
            return;
        }
        
        // Generate a signed token
        $token = Str::random(64);
        
        // Store the token
        DB::table('admin_password_reset_tokens')->updateOrInsert(
            ['email' => $this->email],
            [
                'email' => $this->email,
                'token' => Hash::make($token),
                'created_at' => now(),
            ]
        );
        
        // Send notification
        $user->notify(new AdminPasswordResetNotification($token));
        
        // Log the event
        $auditService->logPasswordReset($this->email);
        
        session()->flash('status', 'We have emailed your password reset link!');
        
        $this->reset('email');
    }
    
    public function render()
    {
        return view('livewire.admin.auth.forgot-password')
            ->extends('layouts.guest')
            ->section('content');
    }
}