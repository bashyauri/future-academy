<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, TwoFactorAuthenticatable;

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
        'stream',
        'selected_subjects',
        'exam_types',
        'has_completed_onboarding',
        'trial_ends_at',
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
            'is_active' => 'boolean',
            'selected_subjects' => 'array',
            'exam_types' => 'array',
            'has_completed_onboarding' => 'boolean',
            'trial_ends_at' => 'datetime',
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
            ->map(fn ($word) => Str::substr($word, 0, 1))
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
            if (! in_array($primaryRole, $existing, true)) {
                $existing[] = $primaryRole;
            }
            $user->syncRoles(array_values(array_unique($existing)));
        });
        static::saved(function (User $user) {
            $primaryRole = $user->account_type ?: 'student';
            $existing = $user->roles()->pluck('name')->all();
            if (! in_array($primaryRole, $existing, true)) {
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

    // Enrollments and subjects
    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function enrolledSubjects()
    {
        return $this->belongsToMany(Subject::class, 'enrollments')
            ->withPivot('is_active', 'enrolled_at', 'enrolled_by')
            ->withTimestamps()
            ->wherePivot('is_active', true)
            ->where('subjects.is_active', true);
    }

    // Parent-Student relationships
    public function children()
    {
        return $this->belongsToMany(User::class, 'parent_student', 'parent_id', 'student_id')
            ->withPivot('is_active', 'linked_at')
            ->withTimestamps()
            ->wherePivot('is_active', true);
    }

    public function parents()
    {
        return $this->belongsToMany(User::class, 'parent_student', 'student_id', 'parent_id')
            ->withPivot('is_active', 'linked_at')
            ->withTimestamps()
            ->wherePivot('is_active', true);
    }

    // Quiz attempts and answers
    public function quizAttempts()
    {
        return $this->hasMany(QuizAttempt::class);
    }

    /**
     * Get all subscriptions for the user.
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Get the user's current active subscription.
     */
    public function currentSubscription()
    {
        return $this->hasOne(Subscription::class)->where('status', 'active')->latest('ends_at');
    }

    public function userAnswers()
    {
        return $this->hasMany(UserAnswer::class);
    }

    // Video progress
    public function videoProgress()
    {
        return $this->hasMany(VideoProgress::class);
    }

    // Check if user is a parent
    public function isParent(): bool
    {
        return $this->account_type === 'guardian';
    }

    // Check if user is a teacher
    public function isTeacher(): bool
    {
        return in_array($this->account_type, ['teacher', 'uploader']);
    }

    // Check if user is a student
    public function isStudent(): bool
    {
        return $this->account_type === 'student';
    }

    public function canUseGuardianContext(): bool
    {
        return $this->hasRole('guardian') || $this->isParent();
    }

    public function canUseStudentContext(): bool
    {
        return $this->hasRole('student') || $this->isStudent();
    }

    public function resolveActiveRoleContext(): ?string
    {
        $requestedContext = session('active_role_context');

        if ($requestedContext === 'guardian' && $this->canUseGuardianContext()) {
            return 'guardian';
        }

        if ($requestedContext === 'student' && $this->canUseStudentContext()) {
            return 'student';
        }

        if ($this->canUseGuardianContext() && ! $this->canUseStudentContext()) {
            return 'guardian';
        }

        if ($this->canUseStudentContext()) {
            return 'student';
        }

        if ($this->canUseGuardianContext()) {
            return 'guardian';
        }

        return null;
    }

    public function onTrial(): bool
    {
        if ($this->isParent()) {
            return false;
        }

        return $this->trial_ends_at && now()->lt($this->trial_ends_at);
    }

    /**
     * Check if user has an active subscription.
     */
    public function hasActiveSubscription(): bool
    {
        $subscription = $this->currentSubscription;

        return $subscription && $subscription->ends_at && now()->lt($subscription->ends_at);
    }

    /**
     * Check if user can access Filament admin panel
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasRole(['super-admin', 'admin', 'teacher']) && $this->is_active;
    }

    /**
     * Send email verification notification.
     */
    public function sendEmailVerificationNotification()
    {
        $this->notify(new VerifyEmail);
    }
}
