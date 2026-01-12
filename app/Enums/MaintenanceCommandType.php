<?php

namespace App\Enums;

enum MaintenanceCommandType: string
{
    case OPTIMIZE = 'optimize';
    case OPTIMIZE_CLEAR = 'optimize:clear';
    case CACHE_CLEAR = 'cache:clear';
    case CONFIG_CLEAR = 'config:clear';
    case VIEW_CLEAR = 'view:clear';
    case ROUTE_CLEAR = 'route:clear';
    case EVENT_CLEAR = 'event:clear';
    case QUEUE_RESTART = 'queue:restart';
    case STORAGE_LINK = 'storage:link';
    case MIGRATE = 'migrate';
    case MIGRATE_ROLLBACK = 'migrate:rollback';
    case DB_SEED = 'db:seed';

    public function label(): string
    {
        return match($this) {
            self::OPTIMIZE => 'Optimize (compile & cache)',
            self::OPTIMIZE_CLEAR => 'Optimize: Clear (reset all caches)',
            self::CACHE_CLEAR => 'Cache: Clear',
            self::CONFIG_CLEAR => 'Config: Clear',
            self::VIEW_CLEAR => 'Views: Clear',
            self::ROUTE_CLEAR => 'Routes: Clear',
            self::EVENT_CLEAR => 'Events: Clear',
            self::QUEUE_RESTART => 'Queue: Restart',
            self::STORAGE_LINK => 'Storage: Link',
            self::MIGRATE => 'Migrate (run pending migrations)',
            self::MIGRATE_ROLLBACK => 'Migrate: Rollback (last batch)',
            self::DB_SEED => 'DB: Seed',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::OPTIMIZE => 'primary',
            self::MIGRATE_ROLLBACK => 'danger',
            self::QUEUE_RESTART => 'warning',
            self::STORAGE_LINK, self::DB_SEED => 'success',
            self::MIGRATE => 'info',
            default => 'gray',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::OPTIMIZE => 'heroicon-o-rocket-launch',
            self::CACHE_CLEAR, self::OPTIMIZE_CLEAR => 'heroicon-o-arrow-path',
            self::MIGRATE, self::MIGRATE_ROLLBACK => 'heroicon-o-circle-stack',
            self::STORAGE_LINK => 'heroicon-o-link',
            self::QUEUE_RESTART => 'heroicon-o-queue-list',
            default => 'heroicon-o-command-line',
        };
    }

    public function isDestructive(): bool
    {
        return in_array($this, [
            self::MIGRATE_ROLLBACK,
            self::DB_SEED,
        ]);
    }

    public function requiresForce(): bool
    {
        return in_array($this, [
            self::MIGRATE,
            self::MIGRATE_ROLLBACK,
            self::DB_SEED,
        ]);
    }
}
