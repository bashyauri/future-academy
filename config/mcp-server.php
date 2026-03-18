<?php

return [
    /*
     * Enable/disable MCP server
     * Disabled by default (development only)
     * Only enable if you need AI tool integration
     */
    'enabled' => env('MCP_SERVER_ENABLED', false),

    /*
     * MCP Server configuration
     */
    'server' => [
        'name' => 'Future Academy MCP Server',
        'version' => '1.0.0',
        'description' => 'Model Context Protocol server for Future Academy LMS integration with AI tools',
    ],

    /*
     * Available tools that MCP clients can use
     */
    'tools' => [
        'query_database' => [
            'enabled' => true,
            'description' => 'Execute database queries safely with validation',
        ],
        'list_files' => [
            'enabled' => true,
            'description' => 'List files in the project structure',
        ],
        'read_file' => [
            'enabled' => true,
            'description' => 'Read file contents with line limiting for safety',
        ],
        'analyze_code' => [
            'enabled' => true,
            'description' => 'Analyze code for issues and suggestions',
        ],
        'get_project_info' => [
            'enabled' => true,
            'description' => 'Get information about the project structure and configuration',
        ],
    ],

    /*
     * Resource types that MCP can expose
     */
    'resources' => [
        'models' => [
            'enabled' => true,
            'description' => 'Application models and their schemas',
        ],
        'documentation' => [
            'enabled' => true,
            'description' => 'Project documentation and guides',
        ],
        'code_samples' => [
            'enabled' => true,
            'description' => 'Code examples and patterns used in the project',
        ],
    ],

    /*
     * Security settings
     */
    'security' => [
        'require_auth' => env('MCP_REQUIRE_AUTH', false),
        'allowed_hosts' => explode(',', env('MCP_ALLOWED_HOSTS', 'localhost,127.0.0.1')),
        'max_file_size' => 1024 * 1024 * 5, // 5MB
        'readonly_mode' => env('MCP_READONLY_MODE', true),
        'allowed_directories' => [
            'app',
            'database',
            'routes',
            'resources',
            'config',
            'tests',
        ],
    ],

    /*
     * Logging
     */
    'logging' => [
        'enabled' => env('MCP_LOGGING_ENABLED', true),
        'channel' => 'mcp',
    ],
];
