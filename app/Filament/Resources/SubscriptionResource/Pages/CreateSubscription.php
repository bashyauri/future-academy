<?php

namespace App\Filament\Resources\SubscriptionResource\Pages;

use App\Filament\Resources\SubscriptionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSubscription extends CreateRecord
{
    protected static string $resource = SubscriptionResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        // Deactivate all other active subscriptions for this user
        if (isset($data['user_id'])) {
            \App\Models\Subscription::where('user_id', $data['user_id'])
                ->where('status', 'active')
                ->update(['status' => 'inactive']);
        }
        return parent::handleRecordCreation($data);
    }
}
