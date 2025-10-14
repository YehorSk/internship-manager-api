<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Notifications\Messages\MailMessage;

class CustomResetPasswordNotification extends ResetPasswordNotification
{
    public function toMail($notifiable): MailMessage
    {
        $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
        $resetUrl = $frontendUrl . '/reset-password?token=' . $this->token . '&email=' . urlencode($notifiable->getEmailForPasswordReset());
        return (new MailMessage)
            ->line('')
            ->action('Obnoviť heslo', $resetUrl)
            ->line('Ak ste nepožiadali o obnovenie hesla, nie je potrebná žiadna ďalšia akcia.');
    }
}
