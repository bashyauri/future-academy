#!/bin/bash

# Webhook Log Viewer - Quick access to webhook logs
# Usage: ./view-webhook-logs.sh [lines]

LINES=${1:-50}
TODAY=$(date +%Y-%m-%d)
LOG_FILE="storage/logs/webhook-$TODAY.log"

echo "=================================================="
echo "🔍 Paystack Webhook Logs Viewer"
echo "=================================================="
echo ""

if [ -f "$LOG_FILE" ]; then
    echo "📄 Viewing last $LINES lines from: $LOG_FILE"
    echo "=================================================="
    echo ""
    tail -n "$LINES" "$LOG_FILE"
    echo ""
    echo "=================================================="
    echo "📊 Statistics:"
    echo "- Total webhooks today: $(grep -c 'WEBHOOK RECEIVED' "$LOG_FILE" 2>/dev/null || echo 0)"
    echo "- Successful: $(grep -c 'Event processed successfully' "$LOG_FILE" 2>/dev/null || echo 0)"
    echo "- Errors: $(grep -c 'ERROR' "$LOG_FILE" 2>/dev/null || echo 0)"
    echo "- Signature failures: $(grep -c 'Invalid.*signature' "$LOG_FILE" 2>/dev/null || echo 0)"
    echo ""

    # Show recent subscription codes
    RECENT_SUBS=$(grep -oP 'SUB_[a-z0-9]+' "$LOG_FILE" 2>/dev/null | sort -u | tail -5)
    if [ ! -z "$RECENT_SUBS" ]; then
        echo "🎟️ Recent subscription codes:"
        echo "$RECENT_SUBS"
        echo ""
    fi

    echo "💡 Tip: Run './view-webhook-logs.sh 100' to see last 100 lines"
    echo "💡 Tip: Run 'tail -f $LOG_FILE' to watch live"
else
    echo "⚠️  Log file not found: $LOG_FILE"
    echo ""
    echo "Possible reasons:"
    echo "1. No webhooks received today"
    echo "2. Webhooks not configured in Paystack"
    echo "3. Storage directory not writable"
    echo ""
    echo "📋 Available webhook logs:"
    ls -lh storage/logs/webhook-*.log 2>/dev/null || echo "  (none found)"
    echo ""
    echo "🔧 Next steps:"
    echo "1. Visit: https://yourdomain.com/test-webhook.php"
    echo "2. Configure webhooks in Paystack dashboard"
    echo "3. Send a test webhook from Paystack"
fi

echo "=================================================="
