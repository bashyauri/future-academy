# 🔧 Subscription Commands Reference

## Available Commands

### 1. Sync Subscription Codes
Syncs `FA-xxx` codes to real `SUB_xxx` codes from Paystack API.

```bash
# Interactive (asks for confirmation)
php artisan subscriptions:sync-codes

# Skip confirmation
php artisan subscriptions:sync-codes --force

# Silent mode
php artisan subscriptions:sync-codes --force --quiet
```

**What it does:**
1. Finds all subscriptions with `FA-xxx` codes
2. Queries Paystack API for each user's active subscription
3. Updates the subscription code to the real `SUB_xxx` from Paystack
4. Shows progress and summary

**Example Output:**
```
🔄 Starting subscription code sync...

Found 3 subscription(s) with FA-xxx codes:
┌────┬──────────────────┬────────────────────────────┬──────────────────────────────┬─────────────────────┐
│ ID │ User Email       │ FA Code                    │ Reference                    │ Created At          │
├────┼──────────────────┼────────────────────────────┼──────────────────────────────┼─────────────────────┤
│ 1  │ user@example.com │ FA-XRQUZJOJD56H-1769924971 │ FA-XRQUZJOJD56H-1769924971   │ 2026-02-01 10:30:00 │
└────┴──────────────────┴────────────────────────────┴──────────────────────────────┴─────────────────────┘

Do you want to sync these subscription codes from Paystack? (yes/no) [no]:
 > yes

 [████████████████████████████████████████] 3/3

✅ Updated: FA-XRQUZJOJD56H-1769924971 → SUB_z45vpbrayvnj7pu

📊 Sync Summary:
  ✅ Synced: 1
  ⚠️  Not found on Paystack: 2
  ❌ Failed: 0

💡 Tip: Check Paystack dashboard to verify subscriptions exist for these users.
```

---

### 2. Cleanup Invalid Subscriptions
Deletes all subscriptions with invalid `FA-xxx` codes from database.

```bash
# Interactive (asks for confirmation twice)
php artisan subscriptions:cleanup

# Skip confirmation
php artisan subscriptions:cleanup --force
```

**What it does:**
1. Finds all subscriptions with `FA-xxx` codes
2. Asks for confirmation (twice for safety)
3. Deletes them from database
4. Shows summary

**⚠️ WARNING:** This is destructive and cannot be undone!

**Example Output:**
```
🗑️  Finding invalid subscriptions...

Found 3 subscription(s) with invalid FA-xxx codes:
┌────┬──────────────────┬────────────────────────────┬─────────────────────┬──────────┐
│ ID │ User Email       │ FA Code                    │ Created At          │ Status   │
├────┼──────────────────┼────────────────────────────┼─────────────────────┼──────────┤
│ 1  │ user@example.com │ FA-XRQUZJOJD56H-1769924971 │ 2026-02-01 10:30:00 │ active   │
└────┴──────────────────┴────────────────────────────┴─────────────────────┴──────────┘

⚠️  WARNING: This action will permanently delete these subscriptions!

Do you want to delete these 3 subscription(s)? (yes/no) [no]:
 > yes

Are you absolutely sure? This cannot be undone! (yes/no) [no]:
 > yes

  ✅ Deleted: FA-XRQUZJOJD56H-1769924971

🎉 Successfully deleted 1 subscription(s)!
```

---

## 🌐 Web Interface (For Shared Hosting)

### Sync via Browser
```
https://yourdomain.com/sync-subscriptions
```
- Requires admin authentication
- Triggers `php artisan subscriptions:sync-codes` via web request
- Returns command output as HTML

### View Logs
```
https://yourdomain.com/webhook-logs
```
- See all webhook activity
- Subscription code updates
- Errors and warnings

---

## 🚀 Usage Scenarios

### Scenario 1: One-Time Cleanup
```bash
# 1. View what needs to be synced
php artisan subscriptions:sync-codes

# 2. If successful, clean up old FA-xxx codes
php artisan subscriptions:cleanup --force
```

### Scenario 2: Scheduled Sync (Cron Job)
Add to crontab to sync hourly:
```bash
0 * * * * cd /path/to/future-academy && php artisan subscriptions:sync-codes --force --quiet
```

### Scenario 3: Shared Hosting Without CLI
Use web interface instead:
1. Visit: `https://yourdomain.com/sync-subscriptions`
2. View results: `https://yourdomain.com/webhook-logs`

---

## 🐛 Troubleshooting

### "Command not found" Error
```bash
# Clear cache and try again
php artisan cache:clear
php artisan config:clear

# Verify command is registered
php artisan list | grep subscriptions
```

### "User not found" Warning
- Subscription exists in database but user was deleted
- Can be safely ignored or manually reviewed

### "No active subscription found on Paystack" Warning
- User email doesn't have subscription on Paystack
- May need to be created via webhook or manual sync

### Command Hangs or Times Out
- Paystack API may be slow
- Try with smaller batches or manually
- Check network connectivity

---

## 📊 Monitoring Commands

### Find all FA-xxx codes
```bash
php artisan tinker
>>> \App\Models\Subscription::where('subscription_code', 'LIKE', 'FA-%')->count()
```

### Find subscriptions that failed to sync
```bash
php artisan tinker
>>> \App\Models\Subscription::where('subscription_code', 'LIKE', 'FA-%')->get()
```

### Check sync history
```bash
grep "subscriptions:sync" storage/logs/laravel.log
```

---

## 🔍 Manual Sync via Database

If you need to manually update a subscription code:

```sql
-- Find subscriptions with FA codes
SELECT id, user_id, subscription_code, reference FROM subscriptions 
WHERE subscription_code LIKE 'FA-%';

-- Update to correct code (replace with real SUB_xxx from Paystack)
UPDATE subscriptions 
SET subscription_code = 'SUB_z45vpbrayvnj7pu' 
WHERE id = 1;
```

---

## 💡 Best Practices

1. **Always sync before deleting**
   - Run `sync-codes` first to get real codes
   - Only then run `cleanup` if needed

2. **Verify Paystack dashboard**
   - Before syncing, check Paystack for active subscriptions
   - Ensure subscriptions exist for users you're syncing

3. **Use --force only for automation**
   - Interactive mode is safer (double-checks everything)
   - Use `--force` only for cron jobs

4. **Monitor webhook logs**
   - Most codes should auto-update via webhooks
   - Manual sync is a fallback for missing webhooks

5. **Regular backups**
   - Before running cleanup, backup your database
   - Especially on production!

---

## ✅ Status Check

To verify everything is working:

```bash
# 1. Check if commands exist
php artisan list | grep subscriptions

# 2. Test sync on a sample
php artisan subscriptions:sync-codes

# 3. View webhook logs
# Visit: https://yourdomain.com/webhook-logs

# 4. Verify database
# Check subscriptions table for SUB_xxx codes
```

---

## 🆘 Need Help?

1. Check logs: `tail -f storage/logs/laravel.log`
2. View webhooks: `https://yourdomain.com/webhook-logs`
3. Review guide: `WEBHOOK_DEBUGGING_GUIDE.md`
