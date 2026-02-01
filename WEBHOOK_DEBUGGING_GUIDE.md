# 🔍 Webhook Debugging Guide

## Quick Diagnosis

### 1. Check if Webhooks are Being Received

**View Today's Webhook Logs:**
```bash
tail -f storage/logs/webhook-$(date +%Y-%m-%d).log
```

**Or via browser:**
Visit: `https://yourdomain.com/test-webhook.php`

### 2. Look for These Log Patterns

#### ✅ Successful Webhook Receipt
```
[INFO] ========== WEBHOOK RECEIVED ==========
[INFO] ✅ Signature verified successfully
[INFO] 📥 Webhook Event Details
[INFO] ✅ Event processed successfully: subscription.create
[INFO] ========== WEBHOOK COMPLETED ==========
```

#### ❌ Webhook Not Arriving
**No logs at all?**
- Webhook URL not configured in Paystack dashboard
- Webhook URL blocked by firewall
- SSL/HTTPS issues
- Wrong URL in Paystack dashboard

#### ❌ Signature Validation Failure
```
[ERROR] ❌ Invalid Paystack webhook signature
```
**Fix:** Check `PAYSTACK_SECRET_KEY` in `.env` matches Paystack dashboard

#### ❌ Missing Data
```
[ERROR] ❌ subscription.create missing required fields
```
**Check:** What data Paystack is sending in the `full_data` field

## Common Issues and Solutions

### Issue 1: No Logs Appearing

**Symptoms:** No webhook logs in `storage/logs/webhook-*.log`

**Possible Causes:**
1. Webhooks not configured in Paystack
2. Webhook URL incorrect
3. File permissions issue

**Solutions:**
```bash
# Check storage permissions
chmod -R 775 storage/logs
chown -R www-data:www-data storage/logs

# Test webhook endpoint is accessible
curl -I https://yourdomain.com/webhooks/paystack

# Should return: HTTP/1.1 405 Method Not Allowed (this is OK - means route exists)
```

### Issue 2: Signature Validation Failing

**Symptoms:** 
```
[ERROR] ❌ Invalid Paystack webhook signature
```

**Solutions:**
1. Verify secret key in `.env`:
   ```bash
   grep PAYSTACK_SECRET_KEY .env
   ```

2. Check for extra spaces or quotes:
   ```env
   # ❌ Wrong
   PAYSTACK_SECRET_KEY=" sk_test_xxxxx "
   
   # ✅ Correct
   PAYSTACK_SECRET_KEY=sk_test_xxxxx
   ```

3. Regenerate secret if needed in Paystack dashboard

### Issue 3: Events Not Processing

**Symptoms:**
```
[INFO] ⚠️ Unhandled Paystack webhook event
```

**Solutions:**
1. Check which events are enabled in Paystack dashboard
2. Required events:
   - ✅ subscription.create
   - ✅ charge.success
   - ✅ subscription.disable
   - ✅ subscription.not_renew

### Issue 4: Subscription Code Not Updating

**Symptoms:** Still seeing `FA-xxx` codes instead of `SUB_xxx`

**Debug in logs:**
```bash
grep "subscription.create" storage/logs/webhook-*.log
grep "SUB_" storage/logs/webhook-*.log
grep "FA-" storage/logs/webhook-*.log
```

**Check:**
1. Is `subscription.create` event being received?
2. Does webhook log show the `SUB_xxx` code?
3. Look for "Subscription updated with real SUB code" message
4. Check for any database errors

## Testing Webhooks

### Method 1: Paystack Dashboard Test

1. Go to https://dashboard.paystack.com/#/settings/developer
2. Find your webhook URL
3. Click "Test" button
4. Select "subscription.create" event
5. Check logs immediately after

### Method 2: Manual cURL Test

```bash
# Test webhook endpoint (will fail signature but shows endpoint works)
curl -X POST https://yourdomain.com/webhooks/paystack \
  -H "Content-Type: application/json" \
  -H "x-paystack-signature: invalid" \
  -d '{
    "event": "subscription.create",
    "data": {
      "customer": {"email": "test@example.com"},
      "reference": "test_ref",
      "subscription": {"subscription_code": "SUB_test123"}
    }
  }'

# Then check logs
tail storage/logs/webhook-$(date +%Y-%m-%d).log
```

### Method 3: Real Payment Test

1. Make a real test payment with Paystack test card:
   - Card: 4084084084084081
   - CVV: 408
   - Expiry: Any future date
   - PIN: 0000

2. Monitor logs in real-time:
   ```bash
   tail -f storage/logs/webhook-$(date +%Y-%m-%d).log
   ```

## Log Interpretation Guide

### Log Emoji Guide
- 🔍 `========== WEBHOOK RECEIVED ==========` - New webhook started
- ✅ Success operation
- ❌ Error occurred
- ⚠️ Warning or unhandled event
- 📥 Incoming data
- 💰 Processing payment (charge.success)
- 🎉 New subscription (subscription.create)
- ⏸️ Subscription paused (subscription.not_renew)
- 🛑 Subscription cancelled (subscription.disable)
- 🔔 Failure event
- 📋 Data extraction
- 🔐 Signature verification
- 💾 Database operation
- 📝 Update operation
- ➕ Create operation

### Example Successful Flow

```log
[2026-02-01 10:30:45] local.INFO: ========== WEBHOOK RECEIVED ==========
[2026-02-01 10:30:45] local.INFO: ✅ Signature verified successfully
[2026-02-01 10:30:45] local.INFO: 📥 Webhook Event Details
  event: subscription.create
  subscription_code: SUB_z45vpbrayvnj7pu
  customer_email: user@example.com
  reference: FA-XRQUZJOJD56H-1769924971
  
[2026-02-01 10:30:45] local.INFO: 🎉 Processing subscription.create
[2026-02-01 10:30:45] local.INFO: 📋 Extracted subscription data
[2026-02-01 10:30:45] local.INFO: ✅ User found {"user_id":123}
[2026-02-01 10:30:45] local.INFO: 🔍 Looking for existing subscription by reference
[2026-02-01 10:30:45] local.INFO: 📝 Found existing subscription by reference
  subscription_id: 456
  old_subscription_code: FA-XRQUZJOJD56H-1769924971
  new_subscription_code: SUB_z45vpbrayvnj7pu
  
[2026-02-01 10:30:45] local.INFO: ✅ Subscription updated with real SUB code from webhook
[2026-02-01 10:30:45] local.INFO: ✅ Event processed successfully: subscription.create
[2026-02-01 10:30:45] local.INFO: ========== WEBHOOK COMPLETED ==========
```

## Monitoring Commands

### Watch Logs Live
```bash
# All webhook activity
tail -f storage/logs/webhook-$(date +%Y-%m-%d).log

# Only errors
tail -f storage/logs/webhook-$(date +%Y-%m-%d).log | grep ERROR

# Only successful subscriptions
tail -f storage/logs/webhook-$(date +%Y-%m-%d).log | grep "subscription.create"

# Only SUB codes
tail -f storage/logs/webhook-$(date +%Y-%m-%d).log | grep "SUB_"
```

### Search Historical Logs
```bash
# Find all subscription.create events
grep "subscription.create" storage/logs/webhook-*.log

# Find specific subscription code
grep "SUB_z45vpbrayvnj7pu" storage/logs/webhook-*.log

# Find all errors in last 7 days
find storage/logs -name "webhook-*.log" -mtime -7 -exec grep ERROR {} +

# Count webhooks received today
grep "WEBHOOK RECEIVED" storage/logs/webhook-$(date +%Y-%m-%d).log | wc -l
```

## Quick Fixes

### Immediate Actions if Webhooks Not Working

1. **Verify webhook configuration:**
   ```bash
   # Check route is registered
   php artisan route:list | grep webhook
   # Should show: POST webhooks/paystack
   ```

2. **Test endpoint accessibility:**
   ```bash
   curl -X POST https://yourdomain.com/webhooks/paystack
   # Should return: "Invalid signature" (this is good - endpoint exists)
   ```

3. **Check Paystack dashboard:**
   - Settings → Developer → Webhooks
   - Verify URL: `https://yourdomain.com/webhooks/paystack`
   - Events enabled: subscription.create, charge.success, etc.

4. **Manual sync as backup:**
   ```bash
   # Via artisan (if you have CLI access)
   php artisan subscriptions:sync-codes
   
   # Via browser (shared hosting)
   # Visit: https://yourdomain.com/sync-subscriptions
   ```

## Need More Help?

1. **Share your logs:**
   ```bash
   # Get last 100 lines
   tail -100 storage/logs/webhook-$(date +%Y-%m-%d).log
   ```

2. **Check database:**
   ```sql
   SELECT id, subscription_code, reference, created_at 
   FROM subscriptions 
   WHERE subscription_code LIKE 'FA-%' 
   ORDER BY created_at DESC 
   LIMIT 10;
   ```

3. **Verify Paystack webhook history:**
   - Paystack Dashboard → Settings → Developer → Webhooks
   - Click "View Logs" to see what Paystack tried to send
   - Check for failed deliveries

## Success Indicators

✅ **Webhooks Working Correctly When:**
1. Log file exists: `storage/logs/webhook-YYYY-MM-DD.log`
2. See "WEBHOOK RECEIVED" entries
3. Signature verification passes (✅)
4. Events processed successfully
5. Database updated (FA-xxx → SUB_xxx)
6. No recurring errors

🎉 **You'll know everything is working when:**
- New payments immediately show SUB_xxx codes in database
- Webhook logs show successful processing
- No manual sync needed
