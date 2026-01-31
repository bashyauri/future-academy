<div class="max-w-2xl mx-auto p-8 bg-white dark:bg-neutral-900 rounded-2xl shadow-2xl mt-10">
    <div class="mb-8 text-center">
        <flux:heading size="xl" class="font-bold text-green-900 dark:text-green-200 mb-2">
            {{ __('Manage Subscription') }}
        </flux:heading>
        <flux:text class="text-neutral-600 dark:text-neutral-300">
            {{ __('View your current plan, payment history, and manage your subscription options below.') }}
        </flux:text>
    </div>

    @if($successMessage)
        <div class="mb-6 p-4 rounded-xl bg-green-100 text-green-800 text-center font-semibold">
            {{ $successMessage }}
        </div>
    @endif
    @if($errorMessage)
        <div class="mb-6 p-4 rounded-xl bg-red-100 text-red-800 text-center font-semibold">
            {{ $errorMessage }}
        </div>
    @endif

    @if($onTrial)
        <div class="mb-6 p-4 rounded-xl bg-yellow-50 dark:bg-yellow-900/20 text-yellow-800 dark:text-yellow-200 text-center">
            <strong>{{ __('Trial Active:') }}</strong>
            {{ __('You have :days day(s) left on your free trial.', ['days' => $trialDaysLeft]) }}
        </div>
    @endif

    @if($activeSubscription)
        <div class="mb-6 p-4 rounded-xl bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-200 text-center">
            <strong>{{ __('Active Plan:') }}</strong>
            {{ ucfirst($activeSubscription->plan) }} ({{ ucfirst($activeSubscription->type) }})<br>
            {{ __('Ends at:') }} <span class="font-bold">{{ $activeSubscription->ends_at?->format('M j, Y') }}</span>
        </div>
        <div class="flex flex-col md:flex-row gap-4 mb-8">
            <div class="flex-1" x-data="{ showConfirm: false }">
                <button @click="showConfirm = true" wire:loading.attr="disabled" wire:target="cancelSubscription" type="button"
                    class="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-red-600 to-pink-600 hover:from-red-700 hover:to-pink-700 text-white font-bold text-base shadow-md transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-red-400 focus:ring-opacity-50 flex items-center justify-center">
                    <span wire:loading.remove wire:target="cancelSubscription">{{ __('Cancel Subscription') }}</span>
                    <svg wire:loading wire:target="cancelSubscription" class="animate-spin h-5 w-5 ml-2 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                    </svg>
                </button>
                <!-- Confirmation Dialog -->
                <div x-show="showConfirm" x-cloak class="fixed inset-0 flex items-center justify-center z-50 bg-black/40">
                    <div class="bg-white dark:bg-neutral-900 rounded-xl shadow-xl p-8 max-w-sm w-full text-center">
                        <div class="mb-4 text-lg font-semibold text-red-700 dark:text-red-300">{{ __('Are you sure?') }}</div>
                        <div class="mb-6 text-neutral-700 dark:text-neutral-200">{{ __('Do you really want to cancel your subscription? This action cannot be undone.') }}</div>
                        <div class="flex justify-center gap-4">
                            <button @click="showConfirm = false" type="button" class="px-4 py-2 rounded-lg bg-neutral-200 dark:bg-neutral-700 text-neutral-800 dark:text-neutral-100 font-semibold hover:bg-neutral-300 dark:hover:bg-neutral-600 transition">{{ __('No, Keep It') }}</button>
                            <button @click="$wire.cancelSubscription(); showConfirm = false" type="button" class="px-4 py-2 rounded-lg bg-red-600 text-white font-bold hover:bg-red-700 transition">{{ __('Yes, Cancel') }}</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex-1" x-data="{ showUpgrade: false }">
                <button @click="showUpgrade = true" type="button"
                    class="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-green-600 to-blue-600 hover:from-green-700 hover:to-blue-700 text-white font-bold text-base shadow-md transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-green-400 focus:ring-opacity-50 flex items-center justify-center">
                    <span>{{ __('Upgrade / Change Plan') }}</span>
                </button>
                <!-- Upgrade/Change Plan Modal -->
                <div x-show="showUpgrade" x-cloak class="fixed inset-0 flex items-center justify-center z-50 bg-black/40">
                    <div class="bg-white dark:bg-neutral-900 rounded-xl shadow-xl p-8 max-w-md w-full text-center">
                        <div class="mb-4 text-lg font-semibold text-green-700 dark:text-green-300">{{ __('Upgrade or Change Plan') }}</div>
                        <div class="mb-6 text-neutral-700 dark:text-neutral-200">
                            {{ __('Select a new plan below to upgrade or change your subscription.') }}
                        </div>
                        <div class="mb-4 flex justify-center gap-2">
                            <button @click="$wire.setPlanType('recurring')" :class="{ 'bg-green-600 text-white': $wire.planType === 'recurring', 'bg-neutral-200 text-neutral-800': $wire.planType !== 'recurring' }" class="px-4 py-2 rounded-lg font-semibold transition">{{ __('Recurring') }}</button>
                            <button @click="$wire.setPlanType('onetime')" :class="{ 'bg-green-600 text-white': $wire.planType === 'onetime', 'bg-neutral-200 text-neutral-800': $wire.planType !== 'onetime' }" class="px-4 py-2 rounded-lg font-semibold transition">{{ __('One-Time') }}</button>
                        </div>
                        <div class="mb-6">
                            <form id="upgradeForm" method="POST" action="{{ route('payment.initialize') }}">
                                @csrf
                                <input type="hidden" name="plan" id="upgradePlan" />
                                <input type="hidden" name="type" id="upgradeType" />
                                <input type="hidden" name="plan_code" id="upgradePlanCode" />
                            </form>
                            @if($planType === 'recurring')
                                @foreach($availablePlans as $plan)
                                    <button type="button"
                                        onclick="document.getElementById('upgradePlan').value='{{ $plan['name'] === 'Yearly (Recurring)' ? 'yearly' : 'monthly' }}';document.getElementById('upgradeType').value='recurring';document.getElementById('upgradePlanCode').value='{{ $plan['code'] }}';document.getElementById('upgradeForm').submit(); showUpgrade = false;"
                                        class="block w-full mb-2 py-2 px-4 rounded-lg border border-green-400 bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-200 font-semibold hover:bg-green-100 dark:hover:bg-green-800 transition">
                                        {{ $plan['name'] }} - ₦{{ number_format($plan['amount']) }}
                                    </button>
                                @endforeach
                            @else
                                @foreach($availablePlans as $plan)
                                    <button type="button"
                                        onclick="document.getElementById('upgradePlan').value='{{ $plan['name'] === 'Yearly (One-Time)' ? 'yearly' : 'monthly' }}';document.getElementById('upgradeType').value='one_time';document.getElementById('upgradePlanCode').value='';document.getElementById('upgradeForm').submit(); showUpgrade = false;"
                                        class="block w-full mb-2 py-2 px-4 rounded-lg border border-blue-400 bg-blue-50 dark:bg-blue-900/20 text-blue-800 dark:text-blue-200 font-semibold hover:bg-blue-100 dark:hover:bg-blue-800 transition">
                                        {{ $plan['name'] }} - ₦{{ number_format($plan['amount']) }}
                                    </button>
                                @endforeach
                            @endif
                        </div>
                        <button @click="showUpgrade = false" type="button" class="mt-2 px-4 py-2 rounded-lg bg-neutral-200 dark:bg-neutral-700 text-neutral-800 dark:text-neutral-100 font-semibold hover:bg-neutral-300 dark:hover:bg-neutral-600 transition">{{ __('Cancel') }}</button>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="mb-6 p-4 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-200 text-center">
            <strong>{{ __('No Active Subscription') }}</strong><br>
            {{ __('Upgrade now to access premium features!') }}
        </div>
        <a href="{{ route('payment.pricing') }}" class="w-full block py-3 px-4 rounded-xl bg-gradient-to-r from-green-600 to-blue-600 hover:from-green-700 hover:to-blue-700 text-white font-bold text-base shadow-md text-center transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-green-400 focus:ring-opacity-50">
            {{ __('Upgrade Now') }}
        </a>
    @endif

    <div class="mt-10">
        <flux:heading size="md" class="font-bold text-neutral-900 dark:text-white mb-4">{{ __('Subscription History') }}</flux:heading>
        @if($subscriptions->isEmpty())
            <div class="text-center text-neutral-500 dark:text-neutral-400">{{ __('No previous subscriptions found.') }}</div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700 text-sm">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 text-left font-bold text-neutral-600 dark:text-neutral-300 uppercase">{{ __('Plan') }}</th>
                            <th class="px-4 py-2 text-left font-bold text-neutral-600 dark:text-neutral-300 uppercase">{{ __('Type') }}</th>
                            <th class="px-4 py-2 text-left font-bold text-neutral-600 dark:text-neutral-300 uppercase">{{ __('Status') }}</th>
                            <th class="px-4 py-2 text-left font-bold text-neutral-600 dark:text-neutral-300 uppercase">{{ __('Amount') }}</th>
                            <th class="px-4 py-2 text-left font-bold text-neutral-600 dark:text-neutral-300 uppercase">{{ __('Start') }}</th>
                            <th class="px-4 py-2 text-left font-bold text-neutral-600 dark:text-neutral-300 uppercase">{{ __('End') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($subscriptions as $sub)
                            <tr class="border-b border-neutral-100 dark:border-neutral-700">
                                <td class="px-4 py-2 font-medium">{{ ucfirst($sub->plan) }}</td>
                                <td class="px-4 py-2">{{ ucfirst($sub->type) }}</td>
                                <td class="px-4 py-2">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold
                                        {{
                                            $sub->status === 'active' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300' :
                                            ($sub->status === 'pending' ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300' :
                                            ($sub->status === 'cancelled' ? 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300' :
                                            'bg-neutral-100 dark:bg-neutral-700 text-neutral-700 dark:text-neutral-300'))
                                        }}">
                                        {{ ucfirst($sub->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-2">₦{{ number_format($sub->amount, 2) }}</td>
                                <td class="px-4 py-2 text-xs">{{ $sub->starts_at?->format('M j, Y') }}</td>
                                <td class="px-4 py-2 text-xs">{{ $sub->ends_at?->format('M j, Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
