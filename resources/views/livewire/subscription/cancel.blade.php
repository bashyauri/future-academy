
<div class="rounded-2xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 p-8 mt-8 max-w-lg mx-auto shadow-xl">
    <div class="flex flex-col items-center mb-6">
        <div class="p-3 rounded-full bg-red-100 dark:bg-red-900/30 mb-3">
            <svg class="w-10 h-10 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </div>
        <flux:heading size="lg" class="font-bold text-neutral-900 dark:text-white mb-1">{{ __('Cancel Subscription') }}</flux:heading>
        <flux:text class="text-neutral-600 dark:text-neutral-400 text-center mb-2">
            {{ __('Are you sure you want to cancel your subscription? This action cannot be undone and you will lose access to premium features at the end of your billing cycle.') }}
        </flux:text>
    </div>

    @if(session('success'))
        <div class="mb-4 px-4 py-3 rounded bg-green-100 text-green-800 text-sm text-center">
            {{ session('success') }}
        </div>
    @endif
    @if($errors->has('subscription'))
        <div class="mb-4 px-4 py-3 rounded bg-red-100 text-red-800 text-sm text-center">
            {{ $errors->first('subscription') }}
        </div>
    @endif

    <form method="POST" action="{{ route('subscription.cancel') }}" class="flex flex-col gap-4">
        @csrf
        <button type="submit" class="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-red-600 to-pink-600 hover:from-red-700 hover:to-pink-700 text-white font-bold text-base shadow-md transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-red-400 focus:ring-opacity-50">
            {{ __('Cancel Subscription') }}
        </button>
    </form>
</div>
