<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;
use Carbon\Carbon;

class AdminPasswordResetNotification extends Notification
{
    use Queueable;

    protected $token;

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $url = $this->resetUrl($notifiable);
        
        return (new MailMessage)
            ->subject('Admin Password Reset Request')
            ->line('You are receiving this email because we received a password reset request for your admin account.')
            ->action('Reset Password', $url)
            ->line('This password reset link will expire in 30 minutes.')
            ->line('If you did not request a password reset, no further action is required.')
            ->line('For security reasons, this action has been logged.');
    }

    protected function resetUrl($notifiable)
    {
        return URL::temporarySignedRoute(
            'admin.password.reset',
            Carbon::now()->addMinutes(30),
            [
                'token' => $this->token,
                'email' => $notifiable->email,
            ]
        );
    }
}