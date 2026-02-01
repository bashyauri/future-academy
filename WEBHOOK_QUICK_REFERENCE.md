# 🚀 Webhook Quick Reference

## 📍 Important URLs

```
Webhook Logs:     https://yourdomain.com/webhook-logs
Test Webhook:     https://yourdomain.com/test-webhook.php
Sync Codes:       https://yourdomain.com/sync-subscriptions
Clear Cache:      https://yourdomain.com/clear

Webhook Endpoint: https://yourdomain.com/webhooks/paystack
```

## 🔧 Quick Commands

```bash
# View today's webhook logs
tail -f storage/logs/webhook-$(date +%Y-%m-%d).log

# Search for errors
grep ERROR storage/logs/webhook-*.log

# Find subscription codes
grep "SUB_" storage/logs/webhook-*.log

# Count webhooks today
grep -c "WEBHOOK RECEIVED" storage/logs/webhook-$(date +%Y-%m-%d).log
```

## ✅ Checklist: Setting Up Webhooks

- [ ] Go to https://dashboard.paystack.com/#/settings/developer
- [ ] Add webhook URL: `https://yourdomain.com/webhooks/paystack`
- [ ] Enable events:
  - [ ] subscription.create
  - [ ] charge.success
  - [ ] subscription.disable
  - [ ] subscription.not_renew
  - [ ] charge.failed
- [ ] Click "Test" button to send test event
- [ ] Check logs at `/webhook-logs` - should see webhook received
- [ ] Make test payment
- [ ] Verify subscription code is SUB_xxx not FA-xxx

## 🐛 Quick Troubleshooting

| Problem | Solution |
|---------|----------|
| No logs appearing | Check webhook URL in Paystack, check storage/logs permissions |
| Signature validation failing | Check PAYSTACK_SECRET_KEY in .env, remove quotes/spaces |
| Events not processing | Enable events in Paystack dashboard |
| Still seeing FA-xxx codes | Check if subscription.create event enabled, view logs for errors |

## 📊 Log Emoji Guide

| Emoji | Meaning |
|-------|---------|
| 🔍 | Webhook received |
| ✅ | Success |
| ❌ | Error |
| ⚠️ | Warning |
| 💰 | Payment processed |
| 🎉 | New subscription |
| 📝 | Update operation |
| 💾 | Database operation |

## 🎯 Success Indicators

✅ Webhooks working when you see:
```
========== WEBHOOK RECEIVED ==========
✅ Signature verified successfully
📥 Webhook Event Details
✅ Event processed successfully
========== WEBHOOK COMPLETED ==========
```

## 🔗 Support Resources

- Full Guide: `WEBHOOK_DEBUGGING_GUIDE.md`
- Implementation: `WEBHOOK_LOGGING_SUMMARY.md`
- Paystack Docs: https://paystack.com/docs/payments/webhooks/
- Dashboard: https://dashboard.paystack.com
