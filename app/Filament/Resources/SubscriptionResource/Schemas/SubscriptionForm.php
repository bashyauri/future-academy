<?php

namespace App\Filament\Resources\SubscriptionResource\Schemas;

use Filament\Forms;
use Filament\Schemas\Schema;

class SubscriptionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Select::make('user_id')
                ->relationship('user', 'email')
                ->searchable()
                ->required(),
            Forms\Components\TextInput::make('plan')->required(),
            Forms\Components\Select::make('type')
                ->options([
                    'recurring' => 'Recurring',
                    'one_time' => 'One Time',
                    'trial' => 'Trial',
                ])
                ->required(),
            Forms\Components\Select::make('status')
                ->options([
                    'active' => 'Active',
                    'inactive' => 'Inactive',
                    'pending' => 'Pending',
                    'cancelled' => 'Cancelled',
                    'expired' => 'Expired',
                    'failed' => 'Failed',
                ])
                ->required(),
            Forms\Components\TextInput::make('reference')
                ->required()
                ->disabled(fn ($livewire) => $livewire instanceof
                    \App\Filament\Resources\SubscriptionResource\Pages\EditSubscription
                ),
            Forms\Components\TextInput::make('amount')->numeric()->required(),
            Forms\Components\DateTimePicker::make('starts_at')->required(),
            Forms\Components\DateTimePicker::make('ends_at')->required(),
        ]);
    }
}
