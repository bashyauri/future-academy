<?php

namespace App\Models;

use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;


class Subscription extends Model
{
    protected $guarded = [];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'next_billing_date' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Scopes for common queries
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')->where('is_active', true);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeExpired($query)
    {
        return $query->where('ends_at', '<', now());
    }

    public function scopeRecurring($query)
    {
        return $query->where('type', 'recurring');
    }

    public function scopeOneTime($query)
    {
        return $query->where('type', 'one_time');
    }

    /**
     * Check if subscription is currently active
     */
    public function isCurrentlyActive(): bool
    {
        return $this->is_active &&
               $this->status === 'active' &&
               ($this->ends_at === null || $this->ends_at->isFuture());
    }

    /**
     * Check if subscription is expired
     */
    public function isExpired(): bool
    {
        return $this->ends_at && $this->ends_at->isPast();
    }

    /**
     * Check if subscription can be renewed (for recurring)
     */
    public function canRenew(): bool
    {
        return $this->type === 'recurring' &&
               $this->authorization_code !== null &&
               ($this->status === 'active' || $this->status === 'cancelled');
    }

    /**
     * Check if subscription can be cancelled
     */
    public function canBeCancelled(): bool
    {
        return $this->is_active && $this->status === 'active';
    }

    protected static function booted()
    {
        static::created(function ($subscription) {
            Log::info('Subscription created', [
                'id' => $subscription->id,
                'user_id' => $subscription->user_id,
                'plan' => $subscription->plan,
                'status' => $subscription->status,
            ]);
        });

        static::updated(function ($subscription) {
            Log::info('Subscription updated', [
                'id' => $subscription->id,
                'user_id' => $subscription->user_id,
                'status' => $subscription->status,
                'is_active' => $subscription->is_active,
            ]);
        });

        static::deleted(function ($subscription) {
            Log::warning('Subscription deleted', [
                'id' => $subscription->id,
                'user_id' => $subscription->user_id,
            ]);
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
