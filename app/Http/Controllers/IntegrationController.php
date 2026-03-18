<?php

namespace App\Http\Controllers;

use App\Services\IntegrationService;
use App\Services\McpServer\McpServer;
use Illuminate\Http\JsonResponse;

class IntegrationController extends Controller
{
    /**
     * Get integration health and status
     */
    public function health(McpServer $mcp): JsonResponse
    {
        $integration = new IntegrationService($mcp);

        return response()->json([
            'status' => 'healthy',
            'data' => $integration->getHealthMetrics(),
        ]);
    }

    /**
     * Get project statistics
     */
    public function stats(McpServer $mcp): JsonResponse
    {
        $integration = new IntegrationService($mcp);

        return response()->json([
            'status' => 'success',
            'data' => $integration->getProjectStats(),
        ]);
    }

    /**
     * Get system recommendations
     */
    public function recommendations(McpServer $mcp): JsonResponse
    {
        $integration = new IntegrationService($mcp);

        return response()->json([
            'status' => 'success',
            'recommendations' => $integration->getRecommendations(),
        ]);
    }
}
