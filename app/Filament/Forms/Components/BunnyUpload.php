<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Field;

class BunnyUpload extends Field
{
    protected string $view = 'filament.components.bunny-upload';

    public static function make(?string $name = null): static
    {
        return parent::make($name)
            ->label('Bunny Video URL or ID')
            ->helperText('Paste from your Bunny dashboard')
            ->columnSpanFull();
    }

    public function getChildComponents(?string $key = null): array
    {
        return [];
    }
}

