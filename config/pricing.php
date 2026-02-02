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
            'amount' => 12000, // ₦12,000/year
            'duration' => 365, // days
            'description' => 'Full year access to all content',
            'features' => [
                'All lessons and videos',
                'Unlimited practice exams',
                'Mock exams (JAMB format)',
                'Progress tracking',
                'Detailed analytics',
                'Annual renewal',
                'Best value - save ₦2,000/year',
            ],
        ],
    ],

    // Pricing for parent plans (same as individual for now)
    'parent_plans' => [
        'monthly' => [
            'name' => 'Parent Monthly Plan',
            'amount' => 2000, // ₦2,000/month - covers all linked children
            'duration' => 30,
            'description' => 'Covers all your linked students',
            'features' => [
                'Access for all linked students',
                'All lessons and videos',
                'Unlimited practice exams',
                'Mock exams (JAMB format)',
                'Family progress dashboard',
                'Monthly renewal',
            ],
        ],
        'yearly' => [
            'name' => 'Parent Yearly Plan',
            'amount' => 12000, // ₦12,000/year - covers all linked children
            'duration' => 365,
            'description' => 'Full year access for all students',
            'features' => [
                'Access for all linked students',
                'All lessons and videos',
                'Unlimited practice exams',
                'Mock exams (JAMB format)',
                'Family progress dashboard',
                'Detailed analytics per student',
                'Annual renewal',
                'Best value - save ₦2,000/year',
            ],
        ],
    ],

    /**
     * Get pricing for a specific plan
     * Usage: config('pricing.plans.monthly.amount') => 2000
     * Usage: config('pricing.plans.yearly.amount') => 12000
     */
];
