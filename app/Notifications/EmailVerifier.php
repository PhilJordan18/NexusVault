<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

final class EmailVerifier extends VerifyEmail
{
    public function toMail($notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);
        return (new MailMessage)->subject('Verify Email Address - NexusVault')->view('emails.email-verification', ['url' => $verificationUrl, 'name' => $notifiable->name]);
    }
}
