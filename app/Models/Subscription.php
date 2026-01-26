<?php

namespace App\Models;

use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class Subscription extends Model
{
    protected $guarded = [
    ];

    protected $casts = [
        'starts_at' => 'date',
        'ends_at' => 'date',
        'is_active' => 'boolean',
    ];
    protected static function booted()
    {
        static::created(function ($subscription) {
                Log::info('Subscription created', $subscription->toArray());
        });
        static::updated(function ($subscription) {
                Log::info('Subscription updated', $subscription->toArray());
        });
        static::deleted(function ($subscription) {
                Log::info('Subscription deleted', $subscription->toArray());
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
