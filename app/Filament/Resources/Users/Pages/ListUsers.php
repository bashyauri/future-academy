<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-o-plus-circle')
                ->label('Add New User')
                ->visible(fn() => auth()->user()?->can('manage users') ?? false),
        ];
    }

    public function getTitle(): string
    {
        return 'User Management';
    }

    public function getHeading(): string
    {
        return 'Manage System Users';
    }

    public function getSubheading(): ?string
    {
        return 'View and manage all users with different roles and permissions.';
    }
}
