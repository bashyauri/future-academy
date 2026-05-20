<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-white dark:bg-zinc-800">
    <flux:sidebar sticky stashable class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

        <a href="{{ route('dashboard') }}" class="me-5 flex items-center space-x-2 rtl:space-x-reverse" wire:navigate>
            <x-app-logo />
        </a>

        <flux:navlist variant="outline">
            <flux:navlist.group :heading="__('Platform')" class="grid">
                <flux:navlist.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')"
                    wire:navigate>{{ __('Dashboard') }}</flux:navlist.item>
                <flux:navlist.item icon="book-open" :href="route('lessons.subjects')"
                    :current="request()->routeIs('lessons.*') || request()->routeIs('lesson.*')" wire:navigate>
                    {{ __('Lessons') }}
                </flux:navlist.item>
                <flux:navlist.item icon="chart-bar" :href="route('analytics')"
                    :current="request()->routeIs('analytics')" wire:navigate>
                    {{ __('Analytics') }}
                </flux:navlist.item>
                <flux:navlist.item icon="credit-card" :href="route('dashboard') . '#subscription'" wire:navigate>
                    {{ __('Subscription') }}
                </flux:navlist.item>
            </flux:navlist.group>
        </flux:navlist>

        @if(auth()->user()->isStudent() || auth()->user()->isTeacher())
            <div class="my-4 w-full flex flex-col items-center gap-4">
                <livewire:payment.status />
                {{-- <livewire:payment.history /> --}}
            </div>
        @endif
        <flux:spacer />

        <!-- Removed GitHub/Documentation navlist card as requested -->

        <!-- Desktop User Menu -->
        <flux:dropdown class="hidden lg:block" position="bottom" align="start">
            <flux:profile :name="auth()->user()->name" :initials="auth()->user()->initials()"
                icon:trailing="chevrons-up-down" />

            <flux:menu class="w-[220px]">
                <flux:menu.radio.group>
                    <div class="p-0 text-sm font-normal">
                        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                            <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                <span
                                    class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                    {{ auth()->user()->initials() }}
                                </span>
                            </span>

                            <div class="grid flex-1 text-start text-sm leading-tight">
                                <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                            </div>
                        </div>
                    </div>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <flux:menu.radio.group>
                    <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Settings') }}
                    </flux:menu.item>
                </flux:menu.radio.group>

                <flux:menu.separator />

                @php
                    $canGuardianContext = auth()->user()->canUseGuardianContext();
                    $canStudentContext = auth()->user()->canUseStudentContext();
                    $activeRoleContext = auth()->user()->resolveActiveRoleContext();
                @endphp

                @if($canGuardianContext && $canStudentContext)
                    <div class="px-2 py-1 space-y-2">
                        <p class="text-xs font-semibold text-neutral-500 dark:text-neutral-400">{{ __('Active Context') }}</p>

                        <div class="grid grid-cols-2 gap-2">
                            <form method="POST" action="{{ route('role-context.switch') }}" class="w-full">
                                @csrf
                                <input type="hidden" name="context" value="guardian">
                                <flux:button type="submit" size="sm" variant="{{ $activeRoleContext === 'guardian' ? 'primary' : 'outline' }}" class="w-full">
                                    {{ __('Guardian') }}
                                </flux:button>
                            </form>

                            <form method="POST" action="{{ route('role-context.switch') }}" class="w-full">
                                @csrf
                                <input type="hidden" name="context" value="student">
                                <flux:button type="submit" size="sm" variant="{{ $activeRoleContext === 'student' ? 'primary' : 'outline' }}" class="w-full">
                                    {{ __('Student') }}
                                </flux:button>
                            </form>
                        </div>
                    </div>

                    <flux:menu.separator />
                @endif

                @if(session('impersonator_id'))
                    <div class="px-2 py-1 space-y-2">
                        <p class="text-xs font-semibold text-neutral-500 dark:text-neutral-400">{{ __('Support Impersonation') }}</p>

                        @if(session('impersonated_user_id'))
                            <p class="text-xs text-amber-700 dark:text-amber-300">
                                {{ __('Impersonating: :email', ['email' => session('impersonated_user_email', __('Unknown user'))]) }}
                            </p>
                        @endif

                        <form method="POST" action="{{ route('impersonate.stop') }}">
                            @csrf
                            <flux:button type="submit" size="sm" variant="outline" class="w-full">
                                {{ __('Stop Impersonation') }}
                            </flux:button>
                        </form>
                    </div>

                    <flux:menu.separator />
                @endif

                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                        {{ __('Log Out') }}
                    </flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </flux:sidebar>

    <!-- Mobile User Menu -->
    <flux:header class="lg:hidden">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

        <flux:spacer />

        <flux:dropdown position="top" align="end">
            <flux:profile :initials="auth()->user()->initials()" icon-trailing="chevron-down" />

            <flux:menu>
                <flux:menu.radio.group>
                    <div class="p-0 text-sm font-normal">
                        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                            <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                <span
                                    class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                    {{ auth()->user()->initials() }}
                                </span>
                            </span>

                            <div class="grid flex-1 text-start text-sm leading-tight">
                                <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                            </div>
                        </div>
                    </div>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <flux:menu.radio.group>
                    <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Settings') }}
                    </flux:menu.item>
                </flux:menu.radio.group>

                <flux:menu.separator />

                @php
                    $canGuardianContext = auth()->user()->canUseGuardianContext();
                    $canStudentContext = auth()->user()->canUseStudentContext();
                    $activeRoleContext = auth()->user()->resolveActiveRoleContext();
                @endphp

                @if($canGuardianContext && $canStudentContext)
                    <div class="px-2 py-1 space-y-2">
                        <p class="text-xs font-semibold text-neutral-500 dark:text-neutral-400">{{ __('Active Context') }}</p>

                        <div class="grid grid-cols-2 gap-2">
                            <form method="POST" action="{{ route('role-context.switch') }}" class="w-full">
                                @csrf
                                <input type="hidden" name="context" value="guardian">
                                <flux:button type="submit" size="sm" variant="{{ $activeRoleContext === 'guardian' ? 'primary' : 'outline' }}" class="w-full">
                                    {{ __('Guardian') }}
                                </flux:button>
                            </form>

                            <form method="POST" action="{{ route('role-context.switch') }}" class="w-full">
                                @csrf
                                <input type="hidden" name="context" value="student">
                                <flux:button type="submit" size="sm" variant="{{ $activeRoleContext === 'student' ? 'primary' : 'outline' }}" class="w-full">
                                    {{ __('Student') }}
                                </flux:button>
                            </form>
                        </div>
                    </div>

                    <flux:menu.separator />
                @endif

                @if(session('impersonator_id'))
                    <div class="px-2 py-1 space-y-2">
                        <p class="text-xs font-semibold text-neutral-500 dark:text-neutral-400">{{ __('Support Impersonation') }}</p>

                        @if(session('impersonated_user_id'))
                            <p class="text-xs text-amber-700 dark:text-amber-300">
                                {{ __('Impersonating: :email', ['email' => session('impersonated_user_email', __('Unknown user'))]) }}
                            </p>
                        @endif

                        <form method="POST" action="{{ route('impersonate.stop') }}">
                            @csrf
                            <flux:button type="submit" size="sm" variant="outline" class="w-full">
                                {{ __('Stop Impersonation') }}
                            </flux:button>
                        </form>
                    </div>

                    <flux:menu.separator />
                @endif

                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                        {{ __('Log Out') }}
                    </flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </flux:header>

    @if(session('impersonated_user_id'))
        <div class="sticky top-0 z-40 border-b border-amber-200 bg-amber-50 px-4 py-2 dark:border-amber-800/50 dark:bg-amber-900/30">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-3">
                <p class="text-sm font-medium text-amber-900 dark:text-amber-100">
                    {{ __('Support mode: You are impersonating :name (:email).', [
                        'name' => session('impersonated_user_name', __('Unknown user')),
                        'email' => session('impersonated_user_email', __('Unknown email')),
                    ]) }}
                </p>

                <form method="POST" action="{{ route('impersonate.stop') }}">
                    @csrf
                    <flux:button type="submit" size="xs" variant="outline">
                        {{ __('Stop Impersonation') }}
                    </flux:button>
                </form>
            </div>
        </div>
    @endif

    {{ $slot }}

    @fluxScripts
</body>

</html>
