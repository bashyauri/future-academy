<?php

namespace App\Filament\Resources\SubjectResource\Pages;

use App\Filament\Resources\SubjectResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSubjects extends ListRecords
{
    protected static string $resource = SubjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-o-plus-circle')
                ->label('Create Subject'),
        ];
    }

    public function getTitle(): string
    {
        return 'Subjects';
    }

    public function getHeading(): string
    {
        return 'Manage Subjects';
    }

    public function getSubheading(): ?string
    {
        return 'Configure subjects for different exam types (WAEC, NECO, JAMB, etc.)';
    }
}
