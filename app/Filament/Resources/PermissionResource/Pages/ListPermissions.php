<?php

namespace App\Filament\Resources\PermissionResource\Pages;

use App\Filament\Resources\PermissionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPermissions extends ListRecords
{
    protected static string $resource = PermissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-o-plus-circle')
                ->label('Create New Permission')
                ->visible(fn() => auth()->user()?->hasRole('super-admin') ?? false),
        ];
    }

    public function getTitle(): string
    {
        return 'Permission Management';
    }

    public function getHeading(): string
    {
        return 'Manage Permissions';
    }

    public function getSubheading(): ?string
    {
        return 'Create and assign permissions to roles for granular access control.';
    }
}
