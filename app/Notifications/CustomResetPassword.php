<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class CustomResetPassword extends ResetPasswordNotification implements
  ShouldQueue
{
  use Queueable;

  /**
   * Build the mail representation of the notification.
   *
   * @param  mixed  $notifiable
   * @return \Illuminate\Notifications\Messages\MailMessage
   */
  public function toMail($notifiable)
  {
    $url = url(
      route(
        'password.reset',
        [
          'token' => $this->token,
          'email' => $notifiable->getEmailForPasswordReset(),
        ],
        false,
      ),
    );

    return (new MailMessage())
      ->subject('Redefinição de Senha - ACADEPOL')
      ->markdown('emails.auth.reset-password', [
        'user' => $notifiable,
        'url' => $url,
        'count' => config(
          'auth.passwords.' . config('auth.defaults.passwords') . '.expire',
        ),
      ]);
  }
}
