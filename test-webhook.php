<?php
/**
 * Test Webhook Endpoint - Verify webhook is accessible
 *
 * Visit this URL to test: https://yourdomain.com/test-webhook.php
 * This will help diagnose webhook connectivity issues
 */

// Get the webhook URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$webhookUrl = $protocol . $_SERVER['HTTP_HOST'] . '/webhooks/paystack';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Webhook Configuration Test</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .code { background: #f4f4f4; padding: 10px; border-radius: 3px; font-family: monospace; margin: 10px 0; }
        h1 { color: #333; }
        h2 { color: #555; margin-top: 30px; }
        ol { line-height: 2; }
        .section { border: 1px solid #ddd; padding: 20px; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <h1>🔍 Paystack Webhook Configuration Test</h1>

    <div class="info">
        <strong>Your Webhook URL:</strong>
        <div class="code"><?php echo $webhookUrl; ?></div>
    </div>

    <div class="section">
        <h2>📋 Configuration Checklist</h2>
        <ol>
            <li>
                <strong>Configure in Paystack Dashboard:</strong><br>
                Go to <a href="https://dashboard.paystack.com/#/settings/developer" target="_blank">Paystack Settings → Webhooks</a><br>
                Add this URL: <code><?php echo $webhookUrl; ?></code>
            </li>
            <li>
                <strong>Enable These Events:</strong>
                <ul>
                    <li>✓ subscription.create</li>
                    <li>✓ charge.success</li>
                    <li>✓ subscription.disable</li>
                    <li>✓ subscription.not_renew</li>
                    <li>✓ charge.failed</li>
                    <li>✓ invoice.payment_failed</li>
                </ul>
            </li>
            <li>
                <strong>Test Webhook:</strong> Use the "Test" button in Paystack dashboard to send a test event
            </li>
            <li>
                <strong>Check Logs:</strong> View webhook logs at:
                <div class="code">storage/logs/webhook-<?php echo date('Y-m-d'); ?>.log</div>
            </li>
        </ol>
    </div>

    <div class="section">
        <h2>📝 View Webhook Logs</h2>
        <p>Check the webhook log file to see if webhooks are being received:</p>

        <?php
        $logFile = __DIR__ . '/storage/logs/webhook-' . date('Y-m-d') . '.log';

        if (file_exists($logFile)) {
            echo '<div class="success">✅ Webhook log file exists!</div>';

            // Get last 50 lines
            $lines = file($logFile);
            $lastLines = array_slice($lines, -50);

            echo '<div class="code" style="max-height: 400px; overflow-y: auto; white-space: pre-wrap;">';
            echo '<strong>Last 50 lines of webhook log:</strong><br><br>';
            echo htmlspecialchars(implode('', $lastLines));
            echo '</div>';
        } else {
            echo '<div class="error">⚠️ No webhook log file found yet. File will be created when first webhook is received.</div>';
            echo '<p>Expected location: <code>' . htmlspecialchars($logFile) . '</code></p>';
        }
        ?>
    </div>

    <div class="section">
        <h2>🧪 Test Webhook Manually</h2>
        <p>Send a test POST request to your webhook endpoint:</p>
        <div class="code">
curl -X POST <?php echo $webhookUrl; ?> \
  -H "Content-Type: application/json" \
  -H "x-paystack-signature: test" \
  -d '{"event":"subscription.create","data":{"customer":{"email":"test@example.com"}}}'
        </div>
        <p><small>Note: This will fail signature validation but will still create a log entry showing the webhook endpoint is accessible.</small></p>
    </div>

    <div class="section">
        <h2>🔧 Troubleshooting</h2>
        <ul style="line-height: 2;">
            <li><strong>No logs appearing?</strong>
                <ul>
                    <li>Check file permissions on <code>storage/logs/</code> directory (should be writable)</li>
                    <li>Verify webhook URL is accessible from outside (not blocked by firewall)</li>
                    <li>Confirm webhook URL is correctly set in Paystack dashboard</li>
                </ul>
            </li>
            <li><strong>Signature validation failing?</strong>
                <ul>
                    <li>Verify <code>PAYSTACK_SECRET_KEY</code> in your <code>.env</code> file matches Paystack dashboard</li>
                    <li>Check for spaces or quotes in the secret key</li>
                </ul>
            </li>
            <li><strong>Events not processing?</strong>
                <ul>
                    <li>Check specific event logs in webhook log file</li>
                    <li>Verify events are enabled in Paystack dashboard</li>
                    <li>Look for error messages in the log</li>
                </ul>
            </li>
        </ul>
    </div>

    <div class="info">
        <strong>💡 Pro Tip:</strong> Keep this page open and refresh after making a test payment or triggering webhook events from Paystack dashboard to see real-time logs.
    </div>
</body>
</html>
