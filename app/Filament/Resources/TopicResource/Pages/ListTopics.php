<?php

namespace App\Filament\Resources\TopicResource\Pages;

use App\Filament\Resources\TopicResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTopics extends ListRecords
{
    protected static string $resource = TopicResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-o-plus-circle')
                ->label('Create Topic'),
        ];
    }

    public function getTitle(): string
    {
        return 'Topics';
    }

    public function getHeading(): string
    {
        return 'Manage Topics';
    }

    public function getSubheading(): ?string
    {
        return 'Organize topics within subjects for better content structure.';
    }
}
