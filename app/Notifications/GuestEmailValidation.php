<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class GuestEmailValidation extends Notification implements ShouldQueue
{
    use Queueable;

    protected $password;
    protected $validationUrl;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $password, string $validationUrl)
    {
        $this->password = $password;
        $this->validationUrl = $validationUrl;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $locale = app()->getLocale();
        
        return (new MailMessage)
            ->subject(__('notifications.guest_validation.subject'))
            ->greeting(__('notifications.guest_validation.greeting', ['name' => $notifiable->first_name]))
            ->line(__('notifications.guest_validation.line1'))
            ->line(__('notifications.guest_validation.line2'))
            ->line(__('notifications.guest_validation.credentials'))
            ->line(__('notifications.guest_validation.username', ['username' => $notifiable->fortigate_username ?? $notifiable->email]))
            ->line(__('notifications.guest_validation.password', ['password' => $this->password]))
            ->action(__('notifications.guest_validation.action'), $this->validationUrl)
            ->line(__('notifications.guest_validation.line3'))
            ->line(__('notifications.guest_validation.line4'))
            ->salutation(__('notifications.guest_validation.salutation'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'guest_validation',
            'user_id' => $notifiable->id,
            'validation_url' => $this->validationUrl,
        ];
    }
}