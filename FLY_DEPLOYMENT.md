# Deploy to Fly.io (SQLite)

## Prerequisites

-   Fly CLI: `npm i -g flyctl` or download from https://fly.io/docs/hands-on/install-flyctl/
-   Fly account: `fly auth signup` and `fly auth login`

## Setup

1. Create app and volume

```bash
fly launch --no-deploy
fly volumes create data --size 1 --region ewr
```

2. Set environment variables

```bash
fly secrets set APP_ENV=production APP_DEBUG=false
fly secrets set APP_KEY=$(php artisan key:generate --show)
```

3. Deploy

```bash
fly deploy
```

## Notes

-   SQLite persists in the mounted volume at `/app/database`.
-   Exposed port is `8080` (configured in `fly.toml`).
-   Start script creates DB file, clears config, migrates, then serves.
-   For logs:

```bash
fly logs --since 1h
```

## Common fixes

-   Permissions:

```bash
fly ssh console -C "chmod -R 775 storage bootstrap/cache"
```

-   Recreate volume if needed:

```bash
fly volumes list
fly volumes destroy data
fly volumes create data --size 1 --region ewr
```
