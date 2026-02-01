<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Webhook Logs - Future Academy</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .header h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 24px;
        }

        .controls {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .controls form {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .form-group label {
            font-size: 14px;
            color: #666;
            font-weight: 500;
        }

        .form-group input,
        .form-group select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
        }

        .btn-primary {
            background: #4F46E5;
            color: white;
        }

        .btn-primary:hover {
            background: #4338CA;
        }

        .btn-secondary {
            background: #6B7280;
            color: white;
        }

        .btn-secondary:hover {
            background: #4B5563;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .stat-card h3 {
            font-size: 14px;
            color: #666;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-card .value {
            font-size: 32px;
            font-weight: bold;
            color: #333;
        }

        .stat-card.success .value {
            color: #10B981;
        }

        .stat-card.error .value {
            color: #EF4444;
        }

        .stat-card.warning .value {
            color: #F59E0B;
        }

        .log-container {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            border-radius: 10px;
            overflow-x: auto;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .log-content {
            font-family: 'Courier New', Courier, monospace;
            font-size: 13px;
            line-height: 1.6;
            white-space: pre-wrap;
            word-break: break-all;
        }

        .log-info {
            background: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .log-info p {
            color: #666;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .subs-list {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .subs-list h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 18px;
        }

        .sub-codes {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .sub-code {
            background: #EFF6FF;
            color: #1E40AF;
            padding: 6px 12px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 13px;
        }

        .error-message {
            background: #FEE2E2;
            color: #991B1B;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .error-message h2 {
            margin-bottom: 10px;
        }

        .auto-refresh {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .auto-refresh input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        @media (max-width: 768px) {
            .controls form {
                flex-direction: column;
                align-items: stretch;
            }

            .stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Webhook Logs Viewer</h1>
            <p style="color: #666; margin-top: 5px;">Monitor Paystack webhook events in real-time</p>
        </div>

        @if(isset($error))
            <div class="error-message">
                <h2>⚠️ {{ $error }}</h2>

                @if(!empty($availableDates))
                    <p style="margin-top: 10px;">Available dates:</p>
                    <div style="margin-top: 10px; display: flex; gap: 10px; flex-wrap: wrap;">
                        @foreach($availableDates as $availDate)
                            <a href="?date={{ $availDate }}"
                               style="background: white; padding: 8px 16px; border-radius: 5px; color: #1E40AF; text-decoration: none;">
                                {{ $availDate }}
                            </a>
                        @endforeach
                    </div>
                @else
                    <p style="margin-top: 10px;">
                        No webhook logs found. Make sure webhooks are configured in Paystack dashboard:<br>
                        <strong>{{ url('/webhooks/paystack') }}</strong>
                    </p>
                @endif
            </div>
        @else
            <div class="controls">
                <form method="GET">
                    <div class="form-group">
                        <label>Date</label>
                        <select name="date" onchange="this.form.submit()">
                            @foreach($availableDates as $availDate)
                                <option value="{{ $availDate }}" {{ $availDate == $date ? 'selected' : '' }}>
                                    {{ $availDate }} {{ $availDate == date('Y-m-d') ? '(Today)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Lines to show</label>
                        <select name="lines" onchange="this.form.submit()">
                            <option value="50" {{ $lines == 50 ? 'selected' : '' }}>Last 50</option>
                            <option value="100" {{ $lines == 100 ? 'selected' : '' }}>Last 100</option>
                            <option value="200" {{ $lines == 200 ? 'selected' : '' }}>Last 200</option>
                            <option value="500" {{ $lines == 500 ? 'selected' : '' }}>Last 500</option>
                            <option value="1000" {{ $lines == 1000 ? 'selected' : '' }}>Last 1000</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary">🔄 Refresh</button>

                    <div class="form-group auto-refresh">
                        <input type="checkbox" id="autoRefresh" onchange="toggleAutoRefresh()">
                        <label for="autoRefresh" style="cursor: pointer;">Auto-refresh (30s)</label>
                    </div>
                </form>
            </div>

            <div class="stats">
                <div class="stat-card">
                    <h3>Total Webhooks</h3>
                    <div class="value">{{ $stats['total'] }}</div>
                </div>

                <div class="stat-card success">
                    <h3>Successful</h3>
                    <div class="value">{{ $stats['successful'] }}</div>
                </div>

                <div class="stat-card error">
                    <h3>Errors</h3>
                    <div class="value">{{ $stats['errors'] }}</div>
                </div>

                <div class="stat-card warning">
                    <h3>Signature Failures</h3>
                    <div class="value">{{ $stats['signature_failures'] }}</div>
                </div>
            </div>

            @if(!empty($recentSubs))
                <div class="subs-list">
                    <h3>🎟️ Recent Subscription Codes</h3>
                    <div class="sub-codes">
                        @foreach($recentSubs as $sub)
                            <span class="sub-code">{{ $sub }}</span>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="log-info">
                <p><strong>Showing:</strong> Last {{ $lines }} lines (Total: {{ $totalLines }} lines in file)</p>
                <p><strong>Date:</strong> {{ $date }}</p>
                <p><strong>File:</strong> storage/logs/webhook-{{ $date }}.log</p>
            </div>

            <div class="log-container">
                <div class="log-content">{{ $content }}</div>
            </div>

            <div style="text-align: center; padding: 20px;">
                <a href="{{ route('dashboard') }}" class="btn btn-secondary">← Back to Dashboard</a>
                <a href="{{ route('sync.subscriptions') }}" class="btn btn-primary">🔄 Sync Subscriptions</a>
            </div>
        @endif
    </div>

    <script>
        let autoRefreshInterval = null;

        function toggleAutoRefresh() {
            const checkbox = document.getElementById('autoRefresh');

            if (checkbox.checked) {
                // Start auto-refresh every 30 seconds
                autoRefreshInterval = setInterval(() => {
                    window.location.reload();
                }, 30000);

                // Show notification
                alert('Auto-refresh enabled! Page will reload every 30 seconds.');
            } else {
                // Stop auto-refresh
                if (autoRefreshInterval) {
                    clearInterval(autoRefreshInterval);
                    autoRefreshInterval = null;
                }
            }
        }

        // Restore auto-refresh state from localStorage
        window.addEventListener('DOMContentLoaded', () => {
            const savedState = localStorage.getItem('autoRefresh') === 'true';
            const checkbox = document.getElementById('autoRefresh');

            if (savedState) {
                checkbox.checked = true;
                toggleAutoRefresh();
            }
        });

        // Save auto-refresh state to localStorage
        document.getElementById('autoRefresh')?.addEventListener('change', (e) => {
            localStorage.setItem('autoRefresh', e.target.checked);
        });

        // Auto-scroll to bottom on page load
        window.addEventListener('load', () => {
            const logContainer = document.querySelector('.log-container');
            if (logContainer) {
                logContainer.scrollTop = logContainer.scrollHeight;
            }
        });
    </script>
</body>
</html>
