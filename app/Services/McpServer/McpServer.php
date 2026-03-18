<?php

namespace App\Services\McpServer;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Database\Eloquent\Model;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class McpServer
{
    protected array $config;

    public function __construct()
    {
        $this->config = config('mcp-server');
    }

    /**
     * Get server information
     */
    public function getServerInfo(): array
    {
        return [
            'name' => $this->config['server']['name'],
            'version' => $this->config['server']['version'],
            'description' => $this->config['server']['description'],
            'tools' => $this->getAvailableTools(),
            'resources' => $this->getAvailableResources(),
        ];
    }

    /**
     * Get available tools
     */
    public function getAvailableTools(): array
    {
        $tools = [];
        foreach ($this->config['tools'] as $tool => $config) {
            if ($config['enabled']) {
                $tools[$tool] = $config['description'];
            }
        }
        return $tools;
    }

    /**
     * Get available resources
     */
    public function getAvailableResources(): array
    {
        $resources = [];
        foreach ($this->config['resources'] as $resource => $config) {
            if ($config['enabled']) {
                $resources[$resource] = $config['description'];
            }
        }
        return $resources;
    }

    /**
     * List files in a directory
     */
    public function listFiles(string $directory = '.'): array
    {
        if (!$this->isAllowedDirectory($directory)) {
            return ['error' => 'Directory access denied'];
        }

        $path = base_path($directory);
        if (!File::exists($path)) {
            return ['error' => 'Directory not found'];
        }

        $files = [];
        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $item) {
                if ($iterator->getDepth() > 3) { // Limit depth
                    continue;
                }

                $relativePath = str_replace($path . DIRECTORY_SEPARATOR, '', $item->getPathname());
                $files[] = [
                    'path' => $relativePath,
                    'type' => $item->isDir() ? 'directory' : 'file',
                    'size' => $item->isFile() ? $item->getSize() : null,
                ];
            }
        } catch (\Exception $e) {
            Log::error('MCP: Error listing files', ['error' => $e->getMessage()]);
        }

        return $files;
    }

    /**
     * Read file contents
     */
    public function readFile(string $filePath, int $startLine = 1, int $endLine = null): array
    {
        if (!$this->isAllowedDirectory(dirname($filePath))) {
            return ['error' => 'File access denied'];
        }

        $fullPath = base_path($filePath);
        if (!File::exists($fullPath)) {
            return ['error' => 'File not found'];
        }

        if (File::size($fullPath) > $this->config['security']['max_file_size']) {
            return ['error' => 'File too large'];
        }

        try {
            $contents = File::get($fullPath);
            $lines = explode("\n", $contents);

            $endLine = $endLine ?? count($lines);
            $startLine = max(1, $startLine);
            $endLine = min($endLine, count($lines));

            $selectedLines = array_slice($lines, $startLine - 1, $endLine - $startLine + 1, true);

            return [
                'file' => $filePath,
                'start_line' => $startLine,
                'end_line' => $endLine,
                'content' => implode("\n", $selectedLines),
            ];
        } catch (\Exception $e) {
            Log::error('MCP: Error reading file', ['error' => $e->getMessage()]);
            return ['error' => 'Error reading file'];
        }
    }

    /**
     * Get project information
     */
    public function getProjectInfo(): array
    {
        return [
            'name' => config('app.name'),
            'environment' => config('app.env'),
            'debug' => config('app.debug'),
            'url' => config('app.url'),
            'version' => app('ComposerVersion'),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'models' => $this->getModelsList(),
            'routes' => $this->getRoutesSummary(),
        ];
    }

    /**
     * Get list of models
     */
    protected function getModelsList(): array
    {
        $models = [];
        $modelPath = app_path('Models');

        if (File::exists($modelPath)) {
            $files = File::files($modelPath);
            foreach ($files as $file) {
                if ($file->getExtension() === 'php') {
                    $models[] = $file->getFilenameWithoutExtension();
                }
            }
        }

        return $models;
    }

    /**
     * Get routes summary
     */
    protected function getRoutesSummary(): array
    {
        $routes = \Illuminate\Support\Facades\Route::getRoutes();
        $summary = [
            'total' => count($routes),
            'by_method' => [],
        ];

        foreach ($routes as $route) {
            $methods = $route->methods();
            foreach ($methods as $method) {
                if ($method !== 'HEAD') {
                    $summary['by_method'][$method] = ($summary['by_method'][$method] ?? 0) + 1;
                }
            }
        }

        return $summary;
    }

    /**
     * Check if directory is allowed for access
     */
    protected function isAllowedDirectory(string $directory): bool
    {
        if (!$this->config['security']['allowed_directories']) {
            return false;
        }

        foreach ($this->config['security']['allowed_directories'] as $allowed) {
            if (strpos($directory, $allowed) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Log MCP activity
     */
    public function log(string $action, array $data = []): void
    {
        if ($this->config['logging']['enabled']) {
            Log::channel($this->config['logging']['channel'])->info("MCP: {$action}", $data);
        }
    }
}
