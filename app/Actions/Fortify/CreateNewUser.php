<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Rules\NotDisposableEmail;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     * Handles account type selection (student, guardian, teacher, uploader)
     * and assigns appropriate Spatie role.
     *
     * Trial access:
     * - Students: 48-hour trial
     * - Teachers/Uploaders: 48-hour trial
     * - Guardians: NO trial (must purchase subscription immediately)
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
                new NotDisposableEmail,
            ],
            'password' => $this->passwordRules(),
            'account_type' => ['required', 'string', 'in:student,guardian,school,community,teacher,uploader'],
            'website' => ['nullable', 'prohibited'],
            'system_url' => ['nullable', 'prohibited'],
        ])->validate();

        if (! empty($input['_form_time'])) {
            try {
                $formTime = (int) decrypt($input['_form_time']);
                $elapsed = time() - $formTime;
                if ($elapsed < 2 || $elapsed > 14400) {
                    throw ValidationException::withMessages([
                        'email' => 'Registration could not be validated. Please try again.',
                    ]);
                }
            } catch (DecryptException) {
                throw ValidationException::withMessages([
                    'email' => 'Invalid registration session. Please refresh and try again.',
                ]);
            }
        }

        $accountType = $input['account_type'] ?? 'student';

        // Guardians don't get trial - they must subscribe to manage children
        $trialEndsAt = $accountType === 'guardian' ? null : now()->addHours(48);

        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
            'account_type' => $accountType,
            'trial_ends_at' => $trialEndsAt,
        ]);
    }
}
