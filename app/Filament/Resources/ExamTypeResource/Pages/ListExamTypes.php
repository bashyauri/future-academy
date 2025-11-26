<?php

namespace App\Filament\Resources\ExamTypeResource\Pages;

use App\Filament\Resources\ExamTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListExamTypes extends ListRecords
{
    protected static string $resource = ExamTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-o-plus-circle')
                ->label('Create Exam Type'),
        ];
    }

    public function getTitle(): string
    {
        return 'Exam Types';
    }

    public function getHeading(): string
    {
        return 'Manage Exam Types';
    }

    public function getSubheading(): ?string
    {
        return 'Configure examination types like WAEC, NECO, JAMB, etc.';
    }
}
