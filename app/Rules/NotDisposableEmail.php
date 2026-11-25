<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class NotDisposableEmail implements ValidationRule
{
    /**
     * Known disposable / temporary email domains commonly used by spambots.
     *
     * @var array<int, string>
     */
    protected const DISPOSABLE_DOMAINS = [
        'mailinator.com',
        'guerrillamail.com',
        'guerrillamail.net',
        'guerrillamail.biz',
        'guerrillamail.org',
        'grr.la',
        'sharklasers.com',
        'tempmail.com',
        'temp-mail.org',
        '10minutemail.com',
        '10minutemail.net',
        'yopmail.com',
        'yopmail.fr',
        'trashmail.com',
        'trashmail.net',
        'dispostable.com',
        'throwawaymail.com',
        'getairmail.com',
        'mohmal.com',
        'burnermail.io',
        'fakeinbox.com',
        'crazymailing.com',
        'generator.email',
        'emailondeck.com',
        'inboxkitten.com',
        'mytemp.email',
        'nada.ltd',
        'dropmail.me',
        'tempail.com',
        'mailnesia.com',
        'disposablemail.com',
        'maildrop.cc',
        'harakirimail.com',
        'inboxbear.com',
        'mintemail.com',
        'throwawayemailaddresses.com',
        'tempinbox.com',
        'mailcatch.com',
        'trashmail.org',
        'fake-box.com',
        'guerrillamailblock.com',
        'pokemail.net',
        'spam4.me',
        'bccto.me',
        'chacuo.net',
        '0-mail.com',
        'dayrep.com',
        'teleworm.us',
        'rhyta.com',
        'jourrapide.com',
        'armyspy.com',
        'cuvox.de',
        'superrito.com',
        'einrot.com',
        'fleckens.hu',
        'gustr.com',
    ];

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! str_contains($value, '@')) {
            return;
        }

        $domain = strtolower(substr(strrchr($value, '@') ?: '', 1));

        if (empty($domain)) {
            return;
        }

        if (in_array($domain, self::DISPOSABLE_DOMAINS, true)) {
            $fail('Please provide a valid permanent email address (e.g. Gmail, Yahoo, Outlook).');
        }
    }
}
