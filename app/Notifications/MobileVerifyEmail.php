<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class MobileVerifyEmail extends VerifyEmail
{
    /**
     * Generate the verification URL for the mobile API endpoint.
     *
     * This overrides the default Fortify web verification URL so that the
     * signed link in the email hits the public API route instead. Mobile
     * users do not have a web session cookie, so the standard Fortify route
     * would redirect them to the login page before verifying — this solves
     * that by using a session-less signed URL on the API.
     *
     * @param  mixed  $notifiable
     */
    protected function verificationUrl($notifiable): string
    {
        $expiration = Carbon::now()->addMinutes(
            Config::get('auth.verification.expire', 60)
        );

        return URL::temporarySignedRoute(
            'api.email.verify',
            $expiration,
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }
}
