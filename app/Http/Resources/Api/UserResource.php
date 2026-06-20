<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{

    public function toArray(Request $request): array
    {

        return [

            'id' => $this->id,


            'name' => $this->name,


            'email' => $this->email,


            'phone' => $this->phone,


            'avatar' => $this->avatar,


            'account_type' => $this->account_type,


  'role' => $this->resolveActiveRoleContext(),


            'has_completed_onboarding' =>
                $this->has_completed_onboarding,


            'is_active' =>
                $this->is_active,


            'has_active_subscription' =>
                $this->hasActiveSubscription(),
                'on_trial' => $this->onTrial(),



            'created_at' =>
                $this->created_at,

        ];

    }

}
