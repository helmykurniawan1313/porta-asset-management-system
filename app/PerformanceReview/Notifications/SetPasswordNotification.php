<?php

namespace App\PerformanceReview\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class SetPasswordNotification extends Notification
{
    /**
     * Intentionally does NOT implement ShouldQueue — sent synchronously
     * since no queue worker is guaranteed to be running in this deployment.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        /** @var User $notifiable */
        $url = URL::temporarySignedRoute(
            'password.setup',
            now()->addDays(7),
            ['user' => $notifiable->getKey()]
        );

        return (new MailMessage)
            ->subject('Set up your Performance Review account password')
            ->line('An account has been created for you in the Performance Review System.')
            ->action('Set Your Password', $url)
            ->line('This link will expire in 7 days. If you did not expect this email, no action is required.');
    }
}
