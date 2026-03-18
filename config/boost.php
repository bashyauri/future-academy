<?php

return [
    /*
     * Query optimization settings
     */
    'query' => [
        'default_page_size' => 15,
        'max_page_size' => 100,
        'cache_filters' => true,
    ],

    /*
     * Database caching settings
     */
    'cache' => [
        'enabled' => true,
        'ttl' => 3600, // 1 hour
        'prefix' => 'query_',
        'driver' => env('BOOST_CACHE_DRIVER', 'file'),
    ],

    /*
     * Lazy loading settings
     */
    'lazy_load' => [
        'enabled' => true,
        'auto_eager_load' => false,
    ],

    /*
     * Performance monitoring
     */
    'monitoring' => [
        'enabled' => env('BOOST_MONITORING', false),
        'log_slow_queries' => true,
        'slow_query_threshold' => 100, // milliseconds
        'log_channel' => 'performance',
    ],
];
