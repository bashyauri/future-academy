<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRoles extends ListRecords
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-o-plus-circle')
                ->label('Create New Role')
                ->visible(fn() => auth()->user()?->hasRole('super-admin') ?? false),
        ];
    }

    public function getTitle(): string
    {
        return 'Role Management';
    }

    public function getHeading(): string
    {
        return 'Manage Roles & Permissions';
    }

    public function getSubheading(): ?string
    {
        return 'Create and manage roles with specific permissions for your LMS.';
    }
}
