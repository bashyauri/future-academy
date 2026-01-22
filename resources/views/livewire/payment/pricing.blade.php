<div class="flex flex-col items-center p-8 rounded-2xl border-0 shadow-2xl bg-gradient-to-br from-green-400/90 via-green-200/80 to-white dark:from-green-900 dark:via-green-950 dark:to-green-900 max-w-lg mx-auto w-full">
    <div class="flex items-center gap-3 mb-4">
        <span class="inline-flex items-center justify-center p-3 rounded-full bg-green-600 dark:bg-green-700 shadow-lg">
            <svg class="w-7 h-7 text-white dark:text-green-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c1.104 0 2-.896 2-2V7a2 2 0 10-4 0v2c0 1.104.896 2 2 2zm6 2v5a2 2 0 01-2 2H8a2 2 0 01-2-2v-5a2 2 0 012-2h8a2 2 0 012 2z"/>
            </svg>
        </span>
        <flux:heading size="2xl" class="font-bold text-green-900 dark:text-green-200">{{ __('Upgrade to Premium') }}</flux:heading>
    </div>
    <flux:text class="text-base text-green-800 dark:text-green-200 text-center mb-2 font-semibold">{{ __('Unlock all features, exams, and resources instantly!') }}</flux:text>
    <div class="w-full flex flex-col gap-3 mb-4">
        <div class="flex items-center gap-2">
            <flux:icon.star class="text-yellow-400 w-5 h-5" />
            <flux:text class="text-green-900 dark:text-green-100 text-sm">{{ __('Unlimited access to all quizzes and mock exams') }}</flux:text>
        </div>
        <div class="flex items-center gap-2">
            <flux:icon.check-circle class="text-green-500 w-5 h-5" />
            <flux:text class="text-green-900 dark:text-green-100 text-sm">{{ __('Detailed analytics and progress tracking') }}</flux:text>
        </div>
        <div class="flex items-center gap-2">
            <flux:icon.bolt class="text-blue-400 w-5 h-5" />
            <flux:text class="text-green-900 dark:text-green-100 text-sm">{{ __('Access to premium video lessons and resources') }}</flux:text>
        </div>
        <div class="flex items-center gap-2">
            <flux:icon.lock-open class="text-purple-400 w-5 h-5" />
            <flux:text class="text-green-900 dark:text-green-100 text-sm">{{ __('No ads, no distractions') }}</flux:text>
        </div>
    </div>
    <form action="{{ route('payment.initialize') }}" method="POST" target="_blank" rel="noopener noreferrer" class="w-full flex flex-col gap-4 mt-1">
        @csrf
        <div class="flex flex-col gap-2">
            <label for="plan" class="block text-xs font-semibold mb-1 text-green-900 dark:text-green-200">{{ __('Choose Plan') }}</label>
            <select name="plan" id="plan" class="form-select w-full rounded border-green-300 dark:border-green-700 bg-white dark:bg-green-950 text-sm text-green-900 dark:text-green-100 focus:ring-green-400 focus:border-green-400 dark:focus:ring-green-600 dark:focus:border-green-600 transition-colors">
                <option value="monthly">{{ __('Monthly - ₦2,000') }}</option>
                <option value="yearly">{{ __('Yearly - ₦12,000') }}</option>
            </select>
        </div>
        <div class="flex flex-col gap-2">
            <label for="type" class="block text-xs font-semibold mb-1 text-green-900 dark:text-green-200">{{ __('Payment Type') }}</label>
            <select name="type" id="type" class="form-select w-full rounded border-green-300 dark:border-green-700 bg-white dark:bg-green-950 text-sm text-green-900 dark:text-green-100 focus:ring-green-400 focus:border-green-400 dark:focus:ring-green-600 dark:focus:border-green-600 transition-colors">
                <option value="one_time">{{ __('One-Time') }}</option>
                <option value="recurring">{{ __('Recurring') }}</option>
            </select>
        </div>
        <flux:button type="submit" class="w-full mt-2 py-3 text-base font-bold" variant="primary" icon="credit-card">
            {{ __('Pay Now') }}
        </flux:button>
    </form>
    <div class="mt-6 w-full flex flex-col items-center gap-2">
        <flux:heading size="md" class="text-green-900 dark:text-green-200 font-bold mb-1">{{ __('Why Go Premium?') }}</flux:heading>
        <ul class="w-full flex flex-col gap-1">
            <li class="flex items-center gap-2">
                <flux:icon.check class="text-green-500 w-4 h-4" />
                <span class="text-xs text-green-900 dark:text-green-100">{{ __('Pass your exams with confidence') }}</span>
            </li>
            <li class="flex items-center gap-2">
                <flux:icon.check class="text-green-500 w-4 h-4" />
                <span class="text-xs text-green-900 dark:text-green-100">{{ __('Get instant access to new features and updates') }}</span>
            </li>
            <li class="flex items-center gap-2">
                <flux:icon.check class="text-green-500 w-4 h-4" />
                <span class="text-xs text-green-900 dark:text-green-100">{{ __('Priority support for premium users') }}</span>
            </li>
        </ul>
        <flux:text class="text-xs text-green-700 dark:text-green-300 text-center mt-2">{{ __('Pay securely with Paystack. Cancel anytime.') }}</flux:text>
    </div>
</div>
