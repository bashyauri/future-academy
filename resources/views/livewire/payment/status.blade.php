<div class="max-w-lg mx-auto p-6 bg-white dark:bg-green-950 rounded-2xl shadow-2xl flex flex-col items-center gap-6 mt-10">
    <div class="flex flex-col items-center gap-2 text-center">
        <flux:heading size="xl" class="font-bold text-green-900 dark:text-green-200">
            {{ __('Account Status') }}
        </flux:heading>

        @if($onTrial)
            <flux:badge color="yellow" class="mb-2">{{ __('Trial Active') }}</flux:badge>
            @php
                $daysLeft = max(0, (int) $trialDaysLeft);
                $hoursLeft = max(0, (int) ($daysLeft * 24));
            @endphp
            <flux:text class="text-green-900 dark:text-green-100">
                @if($daysLeft >= 1)
                    {{ __('You have') }} <strong>{{ $daysLeft }}</strong> {{ __('day(s) left on your free trial.') }}
                @elseif($hoursLeft > 0)
                    {{ __('Your trial ends in about') }} <strong>{{ $hoursLeft }}</strong> {{ __('hour(s).') }}
                @else
                    {{ __('Your trial has ended.') }}
                @endif
            </flux:text>

        @elseif($isSubscribed)
            @if($subscription->status === 'cancelled' || $subscription->status === 'non_renewing')
                <flux:badge color="orange" class="mb-2">{{ __('Cancelling') }}</flux:badge>
                <flux:text class="text-green-900 dark:text-green-100">
                    {{ __('Active until') }} <strong>{{ $subscriptionEndsAt }}</strong>.
                    {{ __('You can reactivate anytime.') }}
                </flux:text>
            @else
                <flux:badge color="green" class="mb-2">{{ __('Active Subscription') }}</flux:badge>
                <flux:text class="text-green-900 dark:text-green-100">
                    {{ __('Your plan is active until') }} <strong>{{ $subscriptionEndsAt }}</strong>.
                </flux:text>
            @endif

        @else
            <flux:badge color="red" class="mb-2">{{ __('No Active Plan') }}</flux:badge>
            <flux:text class="text-green-900 dark:text-green-100">
                {{ __('Your trial has ended and you don’t have an active subscription.') }}
            </flux:text>
        @endif
    </div>

    <div class="w-full flex flex-col items-center gap-2">
        <flux:button
            href="{{ route('subscription.manage') }}"
            variant="primary"
            class="w-full py-3 text-base font-bold"
            icon="credit-card"
        >
            {{ $isSubscribed ? __('Manage Subscription') : __('Upgrade Now') }}
        </flux:button>

        @if($onTrial)
            <flux:text size="sm" color="muted" class="text-center mt-2">
                {{ __('Upgrade today to continue after your trial ends.') }}
            </flux:text>
        @endif
    </div>
</div>
