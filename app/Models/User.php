<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'account_type',
        'avatar',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean'
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn($word) => Str::substr($word, 0, 1))
            ->implode('');
    }
    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (blank($user->account_type)) {
                $user->account_type = 'student';
            }
            if (is_null($user->is_active)) {
                $user->is_active = true;
            }
        });

        static::created(function (User $user) {
            $primaryRole = $user->account_type ?: 'student';
            $existing = $user->roles()->pluck('name')->all();
            if (!in_array($primaryRole, $existing, true)) {
                $existing[] = $primaryRole;
            }
            $user->syncRoles(array_values(array_unique($existing)));
        });
        static::saved(function (User $user) {
            $primaryRole = $user->account_type ?: 'student';
            $existing = $user->roles()->pluck('name')->all();
            if (!in_array($primaryRole, $existing, true)) {
                $existing[] = $primaryRole;
                $user->syncRoles(array_values(array_unique($existing)));
            }
        });
    }

    // Progress tracking
    public function progress()
    {
        return $this->hasMany(UserProgress::class);
    }

    public function lessonProgress()
    {
        return $this->progress()->where('type', 'lesson');
    }

    public function quizProgress()
    {
        return $this->progress()->where('type', 'quiz');
    }

    public function hasActiveSubscription(): bool
    {
        // For now, return true for all users
        // Later implement: return $this->subscriptions()->active()->exists();
        return true;
    }
}
