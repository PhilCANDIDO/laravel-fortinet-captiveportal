<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SuspiciousActivityNotification extends Notification
{
    use Queueable;

    protected $activity;
    protected $ipAddress;

    public function __construct($activity, $ipAddress)
    {
        $this->activity = $activity;
        $this->ipAddress = $ipAddress;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Security Alert: Suspicious Activity Detected')
            ->greeting('Security Alert!')
            ->line('We detected suspicious activity on your admin account.')
            ->line('Activity: ' . $this->activity)
            ->line('IP Address: ' . $this->ipAddress)
            ->line('Time: ' . now()->format('Y-m-d H:i:s'))
            ->line('If this was you, you can safely ignore this email.')
            ->line('If this wasn\'t you, please secure your account immediately:')
            ->action('Secure Your Account', route('admin.profile'))
            ->line('We recommend changing your password and reviewing your recent activity.');
    }
}