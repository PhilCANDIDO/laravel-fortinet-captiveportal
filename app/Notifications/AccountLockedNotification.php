<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountLockedNotification extends Notification
{
    use Queueable;

    protected $attempts;
    protected $ipAddress;

    public function __construct($attempts, $ipAddress)
    {
        $this->attempts = $attempts;
        $this->ipAddress = $ipAddress;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Security Alert: Your Admin Account Has Been Locked')
            ->greeting('Security Alert!')
            ->line('Your admin account has been temporarily locked due to multiple failed login attempts.')
            ->line('Details:')
            ->line('• Failed attempts: ' . $this->attempts)
            ->line('• IP Address: ' . $this->ipAddress)
            ->line('• Locked at: ' . now()->format('Y-m-d H:i:s'))
            ->line('Your account will be automatically unlocked after 30 minutes, or you can contact a system administrator.')
            ->line('If this wasn\'t you, please contact security immediately.')
            ->action('Contact Support', config('app.url') . '/support');
    }
}