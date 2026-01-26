@php use Illuminate\Support\Arr; @endphp
<div id="payments" class="rounded-2xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 p-8 mt-8">
    <flux:heading size="lg" class="mb-6 font-bold text-neutral-900 dark:text-white">{{ __('Payment History') }}</flux:heading>
    @if($subscriptions->isEmpty())
        <div class="text-center py-12">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-blue-100 dark:bg-blue-900/30 mx-auto mb-4">
                <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <flux:text class="text-neutral-500 dark:text-neutral-400 font-medium mb-3">{{ __('No payments or subscriptions yet.') }}</flux:text>
        </div>
    @else
        <div class="hidden sm:block overflow-x-auto">
            <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                <thead>
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-bold text-neutral-600 dark:text-neutral-300 uppercase">{{ __('Plan') }}</th>
                        <th class="px-4 py-2 text-left text-xs font-bold text-neutral-600 dark:text-neutral-300 uppercase">{{ __('Type') }}</th>
                        <th class="px-4 py-2 text-left text-xs font-bold text-neutral-600 dark:text-neutral-300 uppercase">{{ __('Status') }}</th>
                        <th class="px-4 py-2 text-left text-xs font-bold text-neutral-600 dark:text-neutral-300 uppercase">{{ __('Amount') }}</th>
                        <th class="px-4 py-2 text-left text-xs font-bold text-neutral-600 dark:text-neutral-300 uppercase">{{ __('Reference') }}</th>
                        <th class="px-4 py-2 text-left text-xs font-bold text-neutral-600 dark:text-neutral-300 uppercase">{{ __('Start') }}</th>
                        <th class="px-4 py-2 text-left text-xs font-bold text-neutral-600 dark:text-neutral-300 uppercase">{{ __('End') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($subscriptions as $subscription)
                        <tr class="border-b border-neutral-100 dark:border-neutral-700">
                            <td class="px-4 py-2 font-medium">{{ $subscription->plan }}</td>
                            <td class="px-4 py-2">{{ ucfirst(str_replace('_', ' ', $subscription->type)) }}</td>
                            <td class="px-4 py-2">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold
                                    {{
                                        $subscription->status === 'active' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300' :
                                        ($subscription->status === 'pending' ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300' :
                                        ($subscription->status === 'cancelled' ? 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300' :
                                        'bg-neutral-100 dark:bg-neutral-700 text-neutral-700 dark:text-neutral-300'))
                                    }}">
                                    {{ ucfirst($subscription->status) }}
                                </span>
                                @if($subscription->status === 'pending')
                                    <div class="mt-1 text-xs text-amber-600 dark:text-amber-300 font-semibold">
                                        {{ __('Payment is pending. Please complete your payment or contact support if you have issues.') }}
                                    </div>
                                @elseif($subscription->status === 'cancelled' || $subscription->status === 'failed')
                                    <div class="mt-1 text-xs text-red-600 dark:text-red-300 font-semibold">
                                        {{ __('Payment failed or was cancelled. Please try again or contact support.') }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-2">₦{{ number_format($subscription->amount, 2) }}</td>
                            <td class="px-4 py-2 text-xs text-neutral-500">{{ $subscription->reference }}</td>
                            <td class="px-4 py-2 text-xs">{{ $subscription->starts_at?->format('M j, Y') }}</td>
                            <td class="px-4 py-2 text-xs">{{ $subscription->ends_at?->format('M j, Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <!-- Mobile Cards -->
        <div class="sm:hidden space-y-4">
            @foreach($subscriptions as $subscription)
                <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-800 p-4 flex flex-col gap-2 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-neutral-900 dark:text-white">{{ $subscription->plan }}</span>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold
                            {{
                                $subscription->status === 'active' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300' :
                                ($subscription->status === 'pending' ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300' :
                                ($subscription->status === 'cancelled' ? 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300' :
                                'bg-neutral-100 dark:bg-neutral-700 text-neutral-700 dark:text-neutral-300'))
                            }}">
                            {{ ucfirst($subscription->status) }}
                        </span>
                        @if($subscription->status === 'pending')
                            <div class="mt-1 text-xs text-amber-600 dark:text-amber-300 font-semibold">
                                {{ __('Payment is pending. Please complete your payment or contact support if you have issues.') }}
                            </div>
                        @elseif($subscription->status === 'cancelled' || $subscription->status === 'failed')
                            <div class="mt-1 text-xs text-red-600 dark:text-red-300 font-semibold">
                                {{ __('Payment failed or was cancelled. Please try again or contact support.') }}
                            </div>
                        @endif
                    </div>
                    <div class="flex flex-wrap gap-2 text-xs text-neutral-600 dark:text-neutral-300">
                        <div><span class="font-semibold">{{ __('Type:') }}</span> {{ ucfirst(str_replace('_', ' ', $subscription->type)) }}</div>
                        <div><span class="font-semibold">{{ __('Amount:') }}</span> ₦{{ number_format($subscription->amount, 2) }}</div>
                        <div><span class="font-semibold">{{ __('Ref:') }}</span> <span class="text-neutral-500">{{ $subscription->reference }}</span></div>
                        <div><span class="font-semibold">{{ __('Start:') }}</span> {{ $subscription->starts_at?->format('M j, Y') }}</div>
                        <div><span class="font-semibold">{{ __('End:') }}</span> {{ $subscription->ends_at?->format('M j, Y') }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
