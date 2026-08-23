<div class="flex flex-col items-center p-8 rounded-2xl border-0 shadow-2xl bg-gradient-to-br from-green-400/90 via-green-200/80 to-white dark:from-green-900 dark:via-green-950 dark:to-green-900 max-w-lg mx-auto w-full">
    @if(session('trial_upgrade_prompt'))
        <div class="w-full mb-4 p-4 rounded-xl border border-amber-300 bg-amber-50 text-amber-900 dark:border-amber-700 dark:bg-amber-900/30 dark:text-amber-100">
            <div class="font-semibold">{{ session('trial_upgrade_prompt') }}</div>
            <div class="text-sm mt-1">
                {{ __('Blocked feature: :feature', ['feature' => session('blocked_feature', __('Premium Features'))]) }}
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="w-full mb-4 p-4 rounded-xl border border-red-300 bg-red-50 text-red-800 dark:border-red-700 dark:bg-red-900/30 dark:text-red-100">
            {{ session('error') }}
        </div>
    @endif

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
   @if ($errors->any())
    <div class="w-full mb-4">
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    </div>
@endif
    <form action="{{ route('payment.initialize') }}" method="POST" id="payment-form" class="w-full flex flex-col gap-4 mt-1">
        @csrf

        @if($isGuardian)
            <div class="flex flex-col gap-2">
                <label for="student_id" class="block text-xs font-semibold mb-1 text-green-900 dark:text-green-200">{{ __('Choose Linked Student') }}</label>
                @if(empty($linkedStudents))
                    <div class="rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-900/30 dark:text-amber-100">
                        <div class="font-semibold">{{ __('No linked student available for payment yet.') }}</div>
                        <div class="mt-1">{{ __('Link a student from your guardian dashboard before purchasing premium access.') }}</div>
                        <a href="{{ route('parent.dashboard') }}" wire:navigate class="mt-2 inline-flex text-sm font-medium text-blue-700 hover:underline dark:text-blue-300">
                            {{ __('Go to Guardian Dashboard') }}
                        </a>
                    </div>
                @else
                    <select wire:model.live="student_id" name="student_id" id="student_id" class="form-select w-full rounded border-green-300 dark:border-green-700 bg-white dark:bg-green-950 text-sm text-green-900 dark:text-green-100 focus:ring-green-400 focus:border-green-400 dark:focus:ring-green-600 dark:focus:border-green-600 transition-colors">
                        <option value="">{{ __('Select a linked student') }}</option>
                        @foreach($linkedStudents as $student)
                            <option value="{{ $student['id'] }}">{{ $student['name'] }}</option>
                        @endforeach
                    </select>
                    <flux:text class="text-xs text-green-800 dark:text-green-200">
                        {{ __('Guardians can only purchase premium access for students linked to their account.') }}
                    </flux:text>
                @endif
            </div>
        @elseif($student_id)
            <input type="hidden" name="student_id" value="{{ $student_id }}">
        @endif

        @if(! $isGuardian || ! empty($student_id))
            <div class="flex flex-col gap-2">
                <label for="plan" class="block text-xs font-semibold mb-1 text-green-900 dark:text-green-200">{{ __('Choose Plan') }}</label>
                @if($loadingPlans)
                    <div class="flex items-center gap-2 text-sm text-green-700 dark:text-green-300">
                        <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>{{ __('Loading plans...') }}</span>
                    </div>
                @else
                    <select name="plan" id="plan" class="form-select w-full rounded border-green-300 dark:border-green-700 bg-white dark:bg-green-950 text-sm text-green-900 dark:text-green-100 focus:ring-green-400 focus:border-green-400 dark:focus:ring-green-600 dark:focus:border-green-600 transition-colors">
                        @foreach($plans as $key => $plan)
                            <option value="{{ $key }}">{{ __(':plan - ₦:amount', ['plan' => ucfirst($key), 'amount' => number_format($plan['amount'])]) }}</option>
                        @endforeach
                    </select>
                @endif
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
        @elseif(! empty($linkedStudents))
            <div class="rounded-xl border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-900 dark:border-green-700 dark:bg-green-900/30 dark:text-green-100">
                {{ __('Select a linked student to continue with payment.') }}
            </div>
        @endif
    </form>

    <script>
        let paymentWindow = null;
        let paymentReference = null;
        let verificationInterval = null;

        document.getElementById('payment-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const submitButton = this.querySelector('button[type="submit"]');

            // Disable button and show loading
            submitButton.disabled = true;
            submitButton.textContent = 'Processing...';

            fetch('{{ route("payment.initialize") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.data && data.data.authorization_url) {
                    paymentReference = data.data.reference;

                    // Open payment in popup instead of new tab for better control
                    const width = 500;
                    const height = 600;
                    const left = (screen.width - width) / 2;
                    const top = (screen.height - height) / 2;
                    paymentWindow = window.open(
                        data.data.authorization_url,
                        'paystack-payment',
                        `width=${width},height=${height},left=${left},top=${top},resizable=yes,scrollbars=yes`
                    );

                    // Start polling for payment completion
                    if (paymentWindow) {
                        verificationInterval = setInterval(checkPaymentStatus, 3000);

                        // Handle window close
                        paymentWindow.onbeforeunload = function() {
                            clearInterval(verificationInterval);
                            // Attempt final verification when window closes
                            setTimeout(() => verifyPayment(paymentReference), 1000);
                        };
                    }
                } else {
                    throw new Error(data.message || 'Payment initialization failed');
                }
            })
            .catch(error => {
                console.error('Payment error:', error);
                alert(error.message || 'Could not start payment process. Please try again.');
            })
            .finally(() => {
                submitButton.disabled = false;
                submitButton.textContent = 'Pay Now';
            });
        });

        function checkPaymentStatus() {
            if (!paymentReference || !paymentWindow || paymentWindow.closed) {
                clearInterval(verificationInterval);
                return;
            }

            // Try to verify payment silently
            verifyPayment(paymentReference, true);
        }

        function verifyPayment(reference, silent = false) {
            fetch('{{ route("payment.verify") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ reference: reference })
            })
            .then(response => response.json())
            .then(data => {
                if (data.message === 'Payment successful' || data.data) {
                    clearInterval(verificationInterval);
                    if (paymentWindow && !paymentWindow.closed) {
                        paymentWindow.close();
                    }
                    // Reload page to show subscription status
                    window.location.reload();
                }
            })
            .catch(error => {
                if (!silent) {
                    console.error('Verification error:', error);
                }
            });
        }
    </script>
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
