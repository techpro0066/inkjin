<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends ResetPassword
{
    /**
     * Build the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $resetUrl = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        $expireMinutes = (int) config(
            'auth.passwords.'.config('auth.defaults.passwords').'.expire',
            60
        );

        $firstName = trim((string) ($notifiable->first_name ?? ''));
        if ($firstName === '') {
            $name = trim((string) ($notifiable->name ?? ''));
            if ($name !== '') {
                $firstName = trim((string) strtok($name, ' ')) ?: $name;
            }
        }
        if ($firstName === '') {
            $firstName = 'there';
        }

        return (new MailMessage)
            ->subject('Reset your Bookpay password')
            ->view('emails.reset-password', [
                'resetUrl' => $resetUrl,
                'user' => $notifiable,
                'userFirstName' => $firstName,
                'expireMinutes' => $expireMinutes,
                'count' => $expireMinutes,
            ]);
    }
}
