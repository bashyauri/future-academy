<div class="w-full">
    <flux:heading size="sm" class="mb-2 font-bold text-neutral-900 dark:text-white text-center">{{ __('Payment History') }}</flux:heading>
    @if($subscriptions->isEmpty())
        <div class="text-center py-4">
            <flux:text class="text-neutral-500 dark:text-neutral-400 font-medium mb-2">{{ __('No payments yet.') }}</flux:text>
        </div>
    @else
        <div class="space-y-2 max-h-56 overflow-y-auto">
            @foreach($subscriptions->take(5) as $subscription)
                <div class="rounded-lg border border-neutral-200 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-800 p-3 flex flex-col gap-1">
                    <div class="flex items-center justify-between">
                        <span class="font-semibold text-xs text-neutral-900 dark:text-white">{{ $subscription->plan }}</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold
                            {{
                                $subscription->status === 'active' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300' :
                                ($subscription->status === 'pending' ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300' :
                                ($subscription->status === 'cancelled' ? 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300' :
                                'bg-neutral-100 dark:bg-neutral-700 text-neutral-700 dark:text-neutral-300'))
                            }}">
                            {{ ucfirst($subscription->status) }}
                        </span>
                    </div>
                    <div class="flex flex-wrap gap-2 text-xs text-neutral-600 dark:text-neutral-300">
                        <div><span class="font-semibold">₦</span>{{ number_format($subscription->amount, 2) }}</div>
                        <div><span class="font-semibold">{{ __('Ref:') }}</span> <span class="text-neutral-500">{{ $subscription->reference }}</span></div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-2 text-center">
            <a href="{{ route('dashboard') }}#payments" class="text-xs text-blue-600 hover:underline">{{ __('View all payments') }}</a>
        </div>
    @endif
</div>
