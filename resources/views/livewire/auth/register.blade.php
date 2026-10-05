<x-layouts.auth>
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Create an account')" :description="__('Enter your details below to create your account')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Name -->
            <flux:input
                name="name"
                :label="__('Name')"
                type="text"
                required
                autofocus
                autocomplete="name"
                :placeholder="__('Full name')"
            />

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="__('Email address')"
                type="email"
                required
                autocomplete="email"
                placeholder="email@example.com"
            />

            <!-- Password -->
            <flux:input
                name="password"
                :label="__('Password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Password')"
                viewable
            />

            <!-- Confirm Password -->
            <flux:input
                name="password_confirmation"
                :label="__('Confirm password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Confirm password')"
                viewable
            />

            <!-- Account Type -->
            <flux:select
                name="account_type"
                :label="__('I am a...')"
                required
            >
                <option value="student">{{ __('Student - Learning and taking exams') }}</option>
                <option value="guardian">{{ __('Parent/Guardian - Managing student(s)') }}</option>
                <option value="school">{{ __('School - Registering as an institution') }}</option>
                <option value="community">{{ __('Community - Registering as a group/association') }}</option>
                {{-- <option value="teacher">{{ __('Teacher - Creating and managing content') }}</option> --}}
            </flux:select>

            <!-- Data Protection & Privacy Notice -->
            <flux:callout variant="neutral" class="text-sm">
                <div class="space-y-2">
                    <flux:text weight="semibold">{{ __('Data Protection & Privacy Notice') }}</flux:text>
                    <flux:text>
                        {{ __('By creating an account, you agree to the collection and processing of your personal data in accordance with our Privacy Policy. Your information will be used to provide and improve our educational services.') }}
                    </flux:text>
                    <flux:text>
                        {{ __('For students: We collect personal information necessary for educational purposes and to track learning progress. Your data is protected and will not be shared with third parties without consent.') }}
                    </flux:text>
                    <flux:text>
                        {{ __('For guardians: You may be asked to provide information about students under your care. This includes student names, age, and educational information required for account management and progress tracking.') }}
                    </flux:text>
                    <flux:link :href="route('privacy-policy')" target="_blank" variant="subtle">
                        {{ __('Read our full Privacy Policy') }}
                    </flux:link>
                </div>
            </flux:callout>

            <div class="flex items-center justify-end">
                <flux:button type="submit" variant="primary" class="w-full">
                    {{ __('Create account') }}
                </flux:button>
            </div>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
            <span>{{ __('Already have an account?') }}</span>
            <flux:link :href="route('login')" wire:navigate>{{ __('Log in') }}</flux:link>
        </div>
    </div>
</x-layouts.auth>
