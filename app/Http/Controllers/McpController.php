<?php

namespace App\Http\Controllers;

use App\Services\McpServer\McpServer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class McpController extends Controller
{
    protected McpServer $mcp;

    public function __construct(McpServer $mcp)
    {
        $this->mcp = $mcp;
        $this->middleware('mcp.auth');
    }

    /**
     * Initialize MCP server connection
     */
    public function initialize(): JsonResponse
    {
        $this->mcp->log('Initialize', ['ip' => request()->ip()]);

        return response()->json([
            'status' => 'ready',
            'server' => $this->mcp->getServerInfo(),
        ]);
    }

    /**
     * Call a tool on the MCP server
     */
    public function callTool(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tool' => 'required|string',
            'arguments' => 'sometimes|array',
        ]);

        $tool = $validated['tool'];
        $arguments = $validated['arguments'] ?? [];

        $this->mcp->log('CallTool', ['tool' => $tool, 'args' => array_keys($arguments)]);

        try {
            $result = match ($tool) {
                'list_files' => $this->mcp->listFiles($arguments['directory'] ?? '.'),
                'read_file' => $this->mcp->readFile(
                    $arguments['path'] ?? '',
                    $arguments['start_line'] ?? 1,
                    $arguments['end_line'] ?? null
                ),
                'get_project_info' => $this->mcp->getProjectInfo(),
                default => ['error' => 'Tool not found'],
            };

            return response()->json([
                'status' => 'success',
                'tool' => $tool,
                'result' => $result,
            ]);
        } catch (\Exception $e) {
            $this->mcp->log('ToolError', ['tool' => $tool, 'error' => $e->getMessage()]);

            return response()->json([
                'status' => 'error',
                'tool' => $tool,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * List available resources
     */
    public function listResources(): JsonResponse
    {
        $this->mcp->log('ListResources');

        return response()->json([
            'status' => 'success',
            'resources' => $this->mcp->getAvailableResources(),
        ]);
    }

    /**
     * Read a resource
     */
    public function readResource(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'resource' => 'required|string',
            'query' => 'sometimes|string|max:500',
        ]);

        $resource = $validated['resource'];
        $query = $validated['query'] ?? null;

        $this->mcp->log('ReadResource', ['resource' => $resource, 'query' => $query]);

        try {
            $result = match ($resource) {
                'models' => $this->mcp->getProjectInfo()['models'],
                'documentation' => $this->getDocumentation($query),
                'code_samples' => $this->getCodeSamples($query),
                default => ['error' => 'Resource not found'],
            };

            return response()->json([
                'status' => 'success',
                'resource' => $resource,
                'result' => $result,
            ]);
        } catch (\Exception $e) {
            $this->mcp->log('ResourceError', ['resource' => $resource, 'error' => $e->getMessage()]);

            return response()->json([
                'status' => 'error',
                'resource' => $resource,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get server information
     */
    public function serverInfo(): JsonResponse
    {
        return response()->json([
            'status' => 'ready',
            'server' => $this->mcp->getServerInfo(),
        ]);
    }

    /**
     * Get documentation
     */
    protected function getDocumentation(string $query = null): array
    {
        $files = \Illuminate\Support\Facades\File::glob(base_path('*.md'));
        $docs = [];

        foreach ($files as $file) {
            $filename = basename($file);
            if (!$query || stripos($filename, $query) !== false) {
                $docs[] = [
                    'file' => $filename,
                    'size' => filesize($file),
                ];
            }
        }

        return $docs;
    }

    /**
     * Get code samples
     */
    protected function getCodeSamples(string $query = null): array
    {
        $samples = [];
        $paths = [
            'app/Models',
            'app/Services',
            'app/Http/Controllers',
        ];

        foreach ($paths as $path) {
            $fullPath = app_path(str_replace('app', '', $path));
            if (\Illuminate\Support\Facades\File::exists($fullPath)) {
                $files = \Illuminate\Support\Facades\File::files($fullPath);
                foreach ($files as $file) {
                    if ($file->getExtension() === 'php') {
                        if (!$query || stripos($file->getFilenameWithoutExtension(), $query) !== false) {
                            $samples[] = [
                                'path' => str_replace(base_path(), '', $file->getRealPath()),
                                'name' => $file->getFilenameWithoutExtension(),
                                'size' => $file->getSize(),
                            ];
                        }
                    }
                }
            }
        }

        return $samples;
    }
}
