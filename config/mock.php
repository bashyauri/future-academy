<?php

return [
    'formats' => [
        'jamb' => [
            'overall' => [
                'time_limit' => 100,
            ],
            'per_subject' => [
                [
                    'match' => ['english'],
                    'questions' => 70,
                    'time' => null,
                ],
            ],
            'default' => [
                'questions' => 50,
                'time' => null,
            ],
        ],

        'ssce' => [
            'overall' => [
                'sum_subject_time' => true,
            ],
            'per_subject' => [
                [
                    'match' => ['english'],
                    'questions' => 110,
                    'time' => 50,
                ],
                [
                    'match' => ['math', 'further'],
                    'questions' => 60,
                    'time' => 50,
                ],
            ],
            'default' => [
                'questions' => 60,
                'time' => 35,
            ],
        ],

        'default' => [
            'overall' => [
                'time_limit' => 100,
            ],
            'default' => [
                'questions' => 50,
                'time' => null,
            ],
        ],
    ],
];
