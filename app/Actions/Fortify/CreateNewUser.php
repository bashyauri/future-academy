<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
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
            ],
            'password' => $this->passwordRules(),
            'account_type' => ['required', 'string', 'in:student,guardian,teacher,uploader'],
        ])->validate();

        $accountType = $input['account_type'] ?? 'student';

        // Guardians don't get trial - they must subscribe to manage children
        $trialEndsAt = $accountType === 'guardian' ? null : now()->addMonth();

        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
            'account_type' => $accountType,
            'trial_ends_at' => $trialEndsAt,
        ]);
    }
}
