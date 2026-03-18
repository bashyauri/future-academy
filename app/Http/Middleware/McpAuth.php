<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class McpAuth
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!config('mcp-server.enabled')) {
            return response()->json(['error' => 'MCP server is disabled'], 403);
        }

        if (config('mcp-server.security.require_auth')) {
            $token = $request->header('X-MCP-Token');
            if (!$token || $token !== config('mcp-server.security.token')) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        }

        // Check allowed hosts
        $allowedHosts = config('mcp-server.security.allowed_hosts');
        $clientIp = $request->ip();

        if (!in_array($clientIp, $allowedHosts) && !in_array('*', $allowedHosts)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        return $next($request);
    }
}
