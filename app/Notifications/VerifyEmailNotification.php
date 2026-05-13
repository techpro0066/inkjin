<?php

namespace App\Notifications;

use App\Support\EmailVerificationOtp;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailNotification extends VerifyEmail
{
    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $code = EmailVerificationOtp::issueAndReturnPlainCode($notifiable);
        $expiresMinutes = (int) config('auth.verification.expire', 60);

        return (new MailMessage)
            ->subject('Verify Your Email Address')
            ->view('emails.verify-email', [
                'code' => $code,
                'expiresMinutes' => max(1, $expiresMinutes),
                'user' => $notifiable,
            ]);
    }
}

