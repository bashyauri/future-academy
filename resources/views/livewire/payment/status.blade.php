<div class="max-w-lg mx-auto p-6 bg-white dark:bg-green-950 rounded-2xl shadow-2xl flex flex-col items-center gap-6 mt-10">
    <div class="flex flex-col items-center gap-2">
        <flux:heading size="xl" class="font-bold text-green-900 dark:text-green-200">
            {{ __('Account Status') }}
        </flux:heading>
        @if($onTrial)
            <flux:badge color="yellow" class="mb-2">{{ __('Trial Active') }}</flux:badge>
            <flux:text class="text-green-900 dark:text-green-100 text-center">
                @if($trialDaysLeft >= 1)
                    {{ __('You are currently on a free trial. You have') }}
                    <span class="font-bold">{{ $trialDaysLeft }}</span>
                    {{ __('day(s) left.') }}
                @else
                    {{ __('You are currently on a free trial. You have less than 1 day left (about') }}
                    <span class="font-bold">{{ $trialDaysLeft * 24 }}</span>
                    {{ __('hour(s) left).') }}
                @endif
            </flux:text>
        @elseif($isSubscribed)
            <flux:badge color="green" class="mb-2">{{ __('Subscribed') }}</flux:badge>
            <flux:text class="text-green-900 dark:text-green-100 text-center">
                {{ __('Your subscription is active until') }}
                <span class="font-bold">{{ $subscriptionEndsAt }}</span>
            </flux:text>
        @else
            <flux:badge color="red" class="mb-2">{{ __('No Access') }}</flux:badge>
            <flux:text class="text-green-900 dark:text-green-100 text-center">
                {{ __('Your trial has ended and you do not have an active subscription.') }}
            </flux:text>
        @endif
    </div>
    <div class="w-full flex flex-col items-center gap-2">
        <flux:button href="{{ route('payment.pricing') }}" variant="primary" class="w-full py-3 text-base font-bold" icon="credit-card">
            {{ $isSubscribed ? __('Manage Subscription') : __('Upgrade Now') }}
        </flux:button>
        @if($onTrial)
            <flux:text class="text-xs text-green-700 dark:text-green-300 text-center mt-2">
                {{ __('Upgrade now to keep your access after your trial ends!') }}
            </flux:text>
        @endif
    </div>
</div>
