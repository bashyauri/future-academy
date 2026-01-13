# Cloudinary Setup Guide

I've switched your video storage from Cloudflare R2 to **Cloudinary** for better video handling, auto-transcoding, and easier integration. Here's what to do next:

## Step 1: Install Cloudinary Laravel Package

Run this command in your project root:

```bash
composer require cloudinary-labs/cloudinary-laravel
```

## Step 2: Get Cloudinary Credentials

1. Sign up for free at: https://cloudinary.com/users/register/free
   - Free tier includes 25GB storage (vs R2's 10GB)
   - Auto video transcoding (Cloudinary's killer feature)
   - Built-in CDN (no separate setup needed)

2. Get your API credentials from: https://console.cloudinary.com/settings/api

3. Copy your **CLOUDINARY_URL** (format: `cloudinary://api_key:api_secret@cloud_name`)

## Step 3: Update .env

Update `.env` with your Cloudinary credentials:

```env
CLOUDINARY_URL=cloudinary://your-api-key:your-api-secret@your-cloud-name
```

Replace:
- `your-api-key` → Your API Key from Cloudinary console
- `your-api-secret` → Your API Secret (keep this secret!)
- `your-cloud-name` → Your Cloud Name

## Step 4: Publish Config (if needed)

```bash
php artisan vendor:publish --provider="CloudinaryLabs\CloudinaryLaravel\CloudinaryServiceProvider"
```

## What's Changed

### Files Updated:
- **`app/Services/VideoSigningService.php`** → Now uses Cloudinary API
- **`app/Filament/Resources/LessonResource/Schemas/LessonForm.php`** → Video upload uses `disk('cloudinary')`
- **`config/video.php`** → Updated for Cloudinary settings (auto-transcoding, thumbnail generation)
- **`config/filesystems.php`** → R2 disk replaced with Cloudinary disk
- **`.env`** → R2 credentials replaced with Cloudinary credentials

### Key Benefits:
✅ **25GB Free Storage** (vs R2's 10GB)
✅ **Auto Video Transcoding** (Cloudinary transcodes to optimal formats on upload)
✅ **Built-in CDN** (no extra setup)
✅ **Authenticated Delivery** (signed URLs prevent hotlinking)
✅ **Auto Thumbnail Generation** (Cloudinary creates thumbnails automatically)

## Testing Upload

1. Go to Filament Admin → Lessons → Create/Edit Lesson
2. Set "Video Type" to "Local Upload"
3. Upload a video file (MP4 or MOV)
4. Cloudinary will auto-transcode it
5. Save the lesson and view it — the signed URL will play the transcoded version

## Security Notes

- **CLOUDINARY_URL** contains your API secret — NEVER commit to git
- Already in `.gitignore` via env files (safe)
- Authenticated delivery prevents direct linking to videos
- Videos are private by default in Cloudinary

## Troubleshooting

If you get "Disk [cloudinary] not configured":
- Make sure `.env` has `CLOUDINARY_URL` set
- Run `php artisan config:clear` to refresh config cache
- Restart Laravel dev server

If upload hangs:
- Check file size is under 500MB (config limit)
- Ensure `CLOUDINARY_URL` credentials are correct
- Try uploading a smaller test video first
