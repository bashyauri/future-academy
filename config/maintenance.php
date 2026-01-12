<?php

return [
    // Whether the HTTP endpoint (if present) is allowed; we removed the route by default.
    'allow_http_endpoint' => (bool) env('ALLOW_ARTISAN_HTTP', false),

    // Whether DB-affecting commands are allowed in production.
    'allow_db_commands' => (bool) env('ALLOW_DB_COMMANDS', false),

    // Allowed artisan commands exposed in the UI.
    'allowed_commands' => [
        'optimize' => 'Optimize (compile & cache)',
        'optimize:clear' => 'Optimize: Clear (reset all caches)',
        'cache:clear' => 'Cache: Clear',
        'config:clear' => 'Config: Clear',
        'view:clear' => 'Views: Clear',
        'route:clear' => 'Routes: Clear',
        'event:clear' => 'Events: Clear',
        'queue:restart' => 'Queue: Restart',
        'storage:link' => 'Storage: Link',
        'migrate' => 'Migrate (run pending migrations)',
        'migrate:rollback' => 'Migrate: Rollback (last batch)',
        'db:seed' => 'DB: Seed',
    ],
];
