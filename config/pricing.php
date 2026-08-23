<?php

return [
    'currency' => 'NGN', // Nigerian Naira
    'symbol' => '₦',

    'plans' => [
        'monthly' => [
            'name' => 'Monthly Plan',
            'amount' => 2000, // ₦2,000/month
            'duration' => 30, // days
            'description' => 'Access all content for one month',
            'features' => [
                'All lessons and videos',
                'Unlimited practice exams',
                'Mock exams (JAMB format)',
                'Progress tracking',
                'Monthly renewal',
            ],
        ],
        'yearly' => [
            'name' => 'Yearly Plan',
            'amount' => 10000, // ₦10,000/year
            'duration' => 365, // days
            'description' => 'Full year access to all content',
            'features' => [
                'All lessons and videos',
                'Unlimited practice exams',
                'Mock exams (JAMB format)',
                'Progress tracking',
                'Detailed analytics',
                'Annual renewal',
                'Best value - save ₦14,000/year',
            ],
        ],
    ],

    /**
     * Get pricing for a specific plan
     * Usage: config('pricing.plans.monthly.amount') => 2000
     * Usage: config('pricing.plans.yearly.amount') => 10000
     *
     * Note: Guardians purchase premium access per linked student using the same pricing.
     * They must select which student to purchase for each time.
     */
];
