<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubscriptionResource\Pages;
use App\Models\Subscription;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::CreditCard;

    protected static ?string $navigationLabel = 'Subscriptions';

    protected static ?string $pluralModelLabel = 'Subscriptions';

    protected static ?string $modelLabel = 'Subscription';

    protected static string|\UnitEnum|null $navigationGroup = '💳 Payments';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'reference';

    public static function form(Schema $schema): Schema
    {
        return \App\Filament\Resources\SubscriptionResource\Schemas\SubscriptionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return \App\Filament\Resources\SubscriptionResource\Tables\SubscriptionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['admin', 'super-admin']);
    }

    public static function canCreate(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['admin', 'super-admin']);
    }

    public static function canEdit($record): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['admin', 'super-admin']);
    }

    public static function canDelete($record): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['admin', 'super-admin']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscriptions::route('/'),
            'create' => Pages\CreateSubscription::route('/create'),
            'edit' => Pages\EditSubscription::route('/{record}/edit'),
        ];
    }
}
