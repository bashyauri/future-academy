<div class="flex flex-col gap-3">
    <div class="flex items-center gap-3">
        @if(session('success'))
            <div class="px-3 py-1.5 rounded bg-green-100 text-green-800 text-xs font-semibold">
                {{ session('success') }}
            </div>
        @endif

        <flux:modal.trigger name="confirm-subscription-cancel">
            <flux:button variant="danger" x-data="" x-on:click.prevent="$dispatch('open-modal', 'confirm-subscription-cancel')">
                {{ __('Cancel Subscription') }}
            </flux:button>
        </flux:modal.trigger>
    </div>

    @if($errors->has('subscription'))
        <div class="rounded-lg border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 text-amber-800 dark:text-amber-200 px-4 py-2 text-sm font-medium">
            {{ $errors->first('subscription') }}
        </div>
    @endif

    <flux:modal name="confirm-subscription-cancel" :show="$errors->has('subscription')" focusable class="max-w-lg">
        <form method="POST" action="{{ route('subscription.cancel') }}" class="space-y-6">
            @csrf
            <div>
                <flux:heading size="lg">{{ __('Cancel Subscription') }}</flux:heading>
                <flux:subheading>
                    {{ __('Are you sure you want to cancel your subscription? This action cannot be undone and you will lose access to premium features at the end of your billing cycle.') }}
                </flux:subheading>
            </div>

            @if($errors->has('subscription'))
                <div class="px-4 py-3 rounded bg-red-100 text-red-800 text-sm text-center">
                    {{ $errors->first('subscription') }}
                </div>
            @endif

            <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Close') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" type="submit">
                    {{ __('Confirm Cancel') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
