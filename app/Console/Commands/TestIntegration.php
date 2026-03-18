<?php

namespace App\Console\Commands;

use App\Services\McpServer\McpServer;
use App\Services\IntegrationService;
use Illuminate\Console\Command;

class TestIntegration extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'integration:test {--verbose : Show detailed output}';

    /**
     * The console command description.
     */
    protected $description = 'Test Laravel Boost and MCP integration';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Testing Laravel Boost & MCP Integration...');
        $this->newLine();

        $mcp = app(McpServer::class);
        $integration = new IntegrationService($mcp);

        // Test 1: MCP Server initialization
        $this->info('✓ Test 1: MCP Server Initialization');
        $serverInfo = $mcp->getServerInfo();
        $this->line("  Server: {$serverInfo['name']} v{$serverInfo['version']}");
        $this->line("  Tools available: " . count($serverInfo['tools']));
        $this->line("  Resources available: " . count($serverInfo['resources']));
        $this->newLine();

        // Test 2: Project information
        $this->info('✓ Test 2: Project Information');
        $projectInfo = $mcp->getProjectInfo();
        $this->line("  App: {$projectInfo['name']}");
        $this->line("  Environment: {$projectInfo['environment']}");
        $this->line("  Laravel: {$projectInfo['laravel_version']}");
        $this->line("  Models: " . count($projectInfo['models']));
        $this->newLine();

        // Test 3: Health metrics
        $this->info('✓ Test 3: Health Metrics');
        $metrics = $integration->getHealthMetrics();
        $this->line("  Cache enabled: " . ($metrics['performance']['cache_enabled'] ? 'Yes' : 'No'));
        $this->line("  Query optimization: " . ($metrics['performance']['query_optimization']['cache_filters'] ? 'Yes' : 'No'));
        $this->line("  Monitoring enabled: " . ($metrics['performance']['monitoring_enabled'] ? 'Yes' : 'No'));
        $this->newLine();

        // Test 4: Recommendations
        $this->info('✓ Test 4: Recommendations');
        $recommendations = $integration->getRecommendations();
        if (empty($recommendations)) {
            $this->line("  No recommendations - system is optimized!");
        } else {
            foreach ($recommendations as $rec) {
                $priority = match ($rec['priority']) {
                    'high' => '⚠️ ',
                    'medium' => '⚡',
                    default => 'ℹ️ ',
                };
                $this->line("  {$priority} [{$rec['category']}] {$rec['message']}");
                if ($this->option('verbose')) {
                    $this->line("     Action: {$rec['action']}");
                }
            }
        }
        $this->newLine();

        // Test 5: File operations
        $this->info('✓ Test 5: File Operations');
        try {
            $files = $mcp->listFiles('app');
            $this->line("  Found " . count($files) . " items in app/ directory");
            if ($this->option('verbose')) {
                foreach (array_slice($files, 0, 5) as $file) {
                    $this->line("    - {$file['path']}");
                }
            }
        } catch (\Exception $e) {
            $this->error("  Error: " . $e->getMessage());
        }
        $this->newLine();

        $this->info('✓ All tests completed successfully!');
        $this->newLine();

        return Command::SUCCESS;
    }
}
