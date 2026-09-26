<?php

namespace App\Filament\Resources\SubscriptionResource\Pages;

use App\Filament\Resources\SubscriptionResource;
use App\Models\Subscription;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateSubscription extends CreateRecord
{
    protected static string $resource = SubscriptionResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        // Deactivate all other active subscriptions for this user
        if (isset($data['user_id'])) {
            Subscription::where('user_id', $data['user_id'])
                ->where('status', 'active')
                ->update(['status' => 'inactive']);
        }

        return parent::handleRecordCreation($data);
    }
}
