<?php
/**
 * Load Testing Script for Future Academy Practice Quiz
 * Tests performance under concurrent user load
 *
 * Usage: php load-test.php [num_concurrent_users] [duration_seconds]
 * Example: php load-test.php 50 300
 */

require 'vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Promise;

class LoadTester
{
    private $client;
    private $baseUrl;
    private $results = [];
    private $errors = [];

    public function __construct($baseUrl = 'http://future-academy.test')
    {
        $this->baseUrl = $baseUrl;
        $this->client = new Client([
            'base_uri' => $baseUrl,
            'timeout' => 30,
            'connect_timeout' => 10,
        ]);
    }

    public function testAnswerSelection($concurrentUsers = 10, $durationSeconds = 60)
    {
        echo "\n=== Future Academy Load Test ===\n";
        echo "Base URL: {$this->baseUrl}\n";
        echo "Concurrent Users: $concurrentUsers\n";
        echo "Duration: $durationSeconds seconds\n";
        echo "Testing endpoint: /practice/quiz\n\n";

        $startTime = time();
        $endTime = $startTime + $durationSeconds;
        $requestCount = 0;
        $promises = [];

        // Step 1: Get a quiz page to extract session data
        echo "[1/4] Initializing quiz session...\n";
        try {
            $response = $this->client->get('/practice/quiz?shuffle=0&subject=1');
            if ($response->getStatusCode() !== 200) {
                echo "❌ Failed to load quiz page (Status: {$response->getStatusCode()})\n";
                return false;
            }
            echo "✓ Quiz page loaded (Status 200)\n\n";
        } catch (\Exception $e) {
            echo "❌ Error loading quiz page: " . $e->getMessage() . "\n";
            return false;
        }

        // Step 2: Simulate concurrent answer selections
        echo "[2/4] Starting concurrent requests...\n";
        $startedAt = microtime(true);

        while (time() < $endTime) {
            // Create concurrent requests
            for ($i = 0; $i < $concurrentUsers; $i++) {
                $optionId = rand(1, 4); // Assuming 4 options per question

                $promises[] = $this->client->postAsync('/livewire/message/practice.practice-quiz', [
                    'headers' => [
                        'X-Livewire' => true,
                    ],
                    'json' => [
                        'fingerprint' => [
                            'name' => 'practice.practice-quiz',
                            'path' => '/practice/quiz',
                            'method' => 'GET',
                            'v' => '3',
                        ],
                        'updates' => [
                            [
                                'type' => 'callMethod',
                                'payload' => [
                                    'name' => 'selectAnswer',
                                    'params' => [$optionId],
                                ],
                            ],
                        ],
                    ],
                ])->then(function ($response) use (&$requestCount) {
                    $requestCount++;
                    $this->recordResult($response->getStatusCode(), $response->getHeader('Content-Length')[0] ?? 0);
                    return $response;
                })->otherwise(function ($reason) {
                    $this->recordError($reason);
                });
            }

            // Wait for all promises to complete with timeout
            if (!empty($promises)) {
                try {
                    Promise\Utils::settle($promises)->wait();
                    $promises = [];
                } catch (\Exception $e) {
                    echo "⚠️  Some requests failed: " . $e->getMessage() . "\n";
                }
            }
        }

        $duration = microtime(true) - $startedAt;

        // Step 3: Analyze results
        echo "\n[3/4] Analyzing results...\n";
        $this->analyzeResults($requestCount, $duration);

        // Step 4: Generate report
        echo "\n[4/4] Generating report...\n";
        $this->generateReport($requestCount, $duration);

        return true;
    }

    private function recordResult($statusCode, $contentLength)
    {
        if (!isset($this->results[$statusCode])) {
            $this->results[$statusCode] = ['count' => 0, 'totalBytes' => 0];
        }
        $this->results[$statusCode]['count']++;
        $this->results[$statusCode]['totalBytes'] += $contentLength;
    }

    private function recordError($reason)
    {
        $errorMsg = (string)$reason;
        if (!isset($this->errors[$errorMsg])) {
            $this->errors[$errorMsg] = 0;
        }
        $this->errors[$errorMsg]++;
    }

    private function analyzeResults($totalRequests, $duration)
    {
        echo "✓ Total requests: $totalRequests\n";
        echo "✓ Duration: " . number_format($duration, 2) . " seconds\n";
        echo "✓ Requests/second: " . number_format($totalRequests / $duration, 2) . "\n";

        echo "\nStatus Code Distribution:\n";
        foreach ($this->results as $code => $data) {
            $percentage = ($data['count'] / $totalRequests) * 100;
            $size = $data['totalBytes'] / (1024 * 1024); // Convert to MB
            echo "  $code: {$data['count']} requests (" . number_format($percentage, 1) . "%) - " . number_format($size, 2) . " MB\n";
        }

        if (!empty($this->errors)) {
            echo "\nErrors:\n";
            foreach ($this->errors as $error => $count) {
                echo "  - $error: $count occurrences\n";
            }
        }
    }

    private function generateReport($totalRequests, $duration)
    {
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'base_url' => $this->baseUrl,
            'total_requests' => $totalRequests,
            'duration_seconds' => number_format($duration, 2),
            'requests_per_second' => number_format($totalRequests / $duration, 2),
            'success_rate' => isset($this->results[200]) ? number_format(($this->results[200]['count'] / $totalRequests) * 100, 2) . '%' : '0%',
            'status_codes' => $this->results,
            'errors' => $this->errors,
        ];

        $filename = 'load-test-results-' . date('Y-m-d-H-i-s') . '.json';
        file_put_contents($filename, json_encode($report, JSON_PRETTY_PRINT));

        echo "\n✓ Report saved to: $filename\n";

        // Print summary
        echo "\n=== Load Test Summary ===\n";
        echo "Total Requests: " . $report['total_requests'] . "\n";
        echo "Duration: " . $report['duration_seconds'] . " seconds\n";
        echo "Throughput: " . $report['requests_per_second'] . " req/s\n";
        echo "Success Rate: " . $report['success_rate'] . "\n";

        if ($report['requests_per_second'] > 10) {
            echo "\n✓ Performance: EXCELLENT (>10 req/s)\n";
        } elseif ($report['requests_per_second'] > 5) {
            echo "\n⚠️  Performance: GOOD (5-10 req/s)\n";
        } else {
            echo "\n❌ Performance: POOR (<5 req/s)\n";
        }
    }
}

// Main execution
$concurrentUsers = isset($argv[1]) ? (int)$argv[1] : 10;
$durationSeconds = isset($argv[2]) ? (int)$argv[2] : 60;

$tester = new LoadTester();
$success = $tester->testAnswerSelection($concurrentUsers, $durationSeconds);

exit($success ? 0 : 1);
