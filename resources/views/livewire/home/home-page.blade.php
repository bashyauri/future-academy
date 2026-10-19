<div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-purple-50 dark:from-neutral-900 dark:via-neutral-900 dark:to-neutral-800 font-sans">
    {{-- Top Navigation --}}
    <div class="sticky top-0 z-40 border-b border-blue-100/80 bg-white/90 backdrop-blur-md dark:border-neutral-700/80 dark:bg-neutral-900/90">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5" wire:navigate>
                <img
                    src="{{ asset('images/icons/logo-64.png') }}"
                    alt="{{ config('app.name', 'Future Academy') }} logo"
                    class="size-9 rounded-md object-contain"
                />
                <span class="text-sm font-bold tracking-wide text-neutral-900 dark:text-white sm:text-base">
                    {{ config('app.name', 'Future Academy') }}
                </span>
            </a>

            <div class="flex items-center gap-2 sm:gap-3">
                <a
                    href="#pricing"
                    class="rounded-lg px-3 py-2 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-100 hover:text-neutral-900 dark:text-neutral-200 dark:hover:bg-neutral-800 sm:px-4"
                >
                    Pricing
                </a>
                <a
                    href="{{ route('login') }}"
                    class="rounded-lg px-3 py-2 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-100 hover:text-neutral-900 dark:text-neutral-200 dark:hover:bg-neutral-800 sm:px-4"
                    wire:navigate
                >
                    Log in
                </a>
                <a
                    href="{{ route('register') }}"
                    class="rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 sm:px-4"
                    wire:navigate
                >
                    Get Started
                </a>
            </div>
        </div>
    </div>

    {{-- Hero Section --}}
    <div class="relative overflow-hidden">
        {{-- Background Pattern --}}
        <div class="absolute inset-0 opacity-5">
            <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse">
                        <circle cx="20" cy="20" r="1" fill="currentColor"/>
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#grid)"/>
            </svg>
        </div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 md:py-24">
            {{-- Header Text --}}
            <div class="text-center mb-16 md:mb-20">
                <div class="inline-flex items-center gap-3 rounded-full border border-blue-200/70 bg-white/90 px-4 py-2 shadow-sm dark:border-blue-800/60 dark:bg-neutral-900/80">
                    <img
                        src="{{ asset('images/icons/logo-64.png') }}"
                        alt="{{ config('app.name', 'Future Academy') }} logo"
                        class="size-8 rounded-md object-contain"
                    />
                    <span class="text-sm font-semibold tracking-wide text-neutral-800 dark:text-neutral-100">
                        {{ config('app.name', 'Future Academy') }}
                    </span>
                </div>

                <flux:heading size="2xl" class="mt-6 text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-bold mb-4 md:mb-6">
                    Pass JAMB, WAEC & NECO
                    <span class="block text-blue-600 dark:text-blue-400">with confidence</span>
                </flux:heading>

                <flux:text class="text-lg sm:text-xl md:text-2xl text-neutral-700 dark:text-neutral-300 max-w-3xl mx-auto leading-relaxed px-4">
                    Video lectures for learning, real practice questions, smart explanations, and exam-ready mock practice.
                </flux:text>

                <div class="mt-6 flex flex-wrap items-center justify-center gap-2.5 px-4">
                    <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">Real CBT-style practice</span>
                    <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700 dark:bg-green-900/40 dark:text-green-300">Detailed explanations</span>
                    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">Parent/School/Community progress tracking</span>
                </div>
            </div>

            {{-- Access Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-4xl mx-auto">
                {{-- Student Access --}}
                <div class="group flex flex-col h-full">
                    <div class="relative flex flex-col flex-1 rounded-2xl border-2 border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 p-8 transition-all hover:border-blue-500 dark:hover:border-blue-500 hover:shadow-2xl hover:-translate-y-2 duration-200 ease-in-out min-h-[480px]">
                        {{-- Decorative Element --}}
                        <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-br from-blue-100 to-blue-200 dark:from-blue-900/30 dark:to-blue-800/20 rounded-bl-full opacity-40"></div>

                        <div class="relative flex flex-col h-full">
                            {{-- Icon --}}
                            <div class="w-16 h-16 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform shadow-md">
                                <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                </svg>
                            </div>

                            <flux:heading size="lg" class="text-2xl md:text-3xl mb-4 font-bold">Students</flux:heading>
                            <flux:text class="text-base md:text-lg text-neutral-600 dark:text-neutral-400 mb-6 leading-relaxed">
                                Access video lessons, practice questions, and take mock exams to prepare for your exams.
                            </flux:text>

                            {{-- Features List --}}
                            <div class="space-y-3 mb-8">
                                <div class="flex items-center gap-2.5">
                                    <svg class="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <flux:text class="text-base">Video Lessons</flux:text>
                                </div>
                                <div class="flex items-center gap-2.5">
                                    <svg class="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <flux:text class="text-base">Practice Questions</flux:text>
                                </div>
                                <div class="flex items-center gap-2.5">
                                    <svg class="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <flux:text class="text-base">Mock Exams</flux:text>
                                </div>
                                <div class="flex items-center gap-2.5">
                                    <svg class="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <flux:text class="text-base">Progress Tracking</flux:text>
                                </div>
                            </div>

                            {{-- CTA Button --}}
                            <div class="mt-auto">
                                <flux:button
                                    href="{{ route('login', ['type' => 'student']) }}"
                                    variant="primary"
                                    class="w-full text-lg min-h-[52px] font-semibold bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white shadow-lg hover:shadow-xl transition-all"
                            >
                                    Student Login
                                </flux:button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Teacher Access removed --}}

                {{-- Parent / School / Community Access --}}
                <div class="group flex flex-col h-full">
                    <div class="relative flex flex-col flex-1 rounded-2xl border-2 border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 p-8 transition-all hover:border-green-500 dark:hover:border-green-500 hover:shadow-2xl hover:-translate-y-2 duration-200 ease-in-out min-h-[480px]">
                        {{-- Decorative Element --}}
                        <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-br from-green-100 to-green-200 dark:from-green-900/30 dark:to-green-800/20 rounded-bl-full opacity-50"></div>

                        <div class="relative">
                            {{-- Icon --}}
                            <div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
                                <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>

                            <flux:heading size="lg" class="text-xl md:text-2xl mb-3 font-bold">Parent / School / Community</flux:heading>
                            <flux:text class="text-sm md:text-base text-neutral-600 dark:text-neutral-400 mb-5 leading-relaxed">
                                Register as a parent, school, or community to manage and track multiple students' progress, enroll children, and oversee educational outcomes.
                            </flux:text>

                            {{-- Features List --}}
                            <div class="space-y-2.5 mb-6">
                                <div class="flex items-center gap-2.5">
                                    <svg class="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <flux:text class="text-sm md:text-base">Enroll Students</flux:text>
                                </div>
                                <div class="flex items-center gap-2.5">
                                    <svg class="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <flux:text class="text-sm md:text-base">Track Group Progress</flux:text>
                                </div>
                                <div class="flex items-center gap-2.5">
                                    <svg class="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <flux:text class="text-sm md:text-base">View Performance</flux:text>
                                </div>
                                <div class="flex items-center gap-2.5">
                                    <svg class="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <flux:text class="text-sm md:text-base">Manage Enrollment</flux:text>
                                </div>
                            </div>

                            {{-- CTA Button --}}
                            <flux:button
                                href="{{ route('login', ['type' => 'parent']) }}"
                                variant="primary"
                                class="w-full text-base min-h-[52px] font-semibold bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 text-white"
                            >
                                Parent / School / Community Login
                            </flux:button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Features Section --}}
            <div class="mt-24 md:mt-32">
                <div class="text-center mb-14 md:mb-20">
                    <flux:heading size="xl" class="text-2xl md:text-3xl font-bold mb-3">Why Choose Future Academy?</flux:heading>
                    <flux:text class="text-base md:text-lg text-neutral-600 dark:text-neutral-400">Everything you need to excel in your exams</flux:text>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 md:gap-12">
                    {{-- Feature 1 --}}
                    <div class="text-center p-6 md:p-8 rounded-2xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 transition-all hover:border-blue-500 dark:hover:border-blue-500 hover:shadow-2xl">
                        <div class="w-14 h-14 md:w-16 md:h-16 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-7 h-7 md:w-8 md:h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <flux:heading size="sm" class="font-semibold mb-2">Comprehensive Coverage</flux:heading>
                        <flux:text class="text-sm text-neutral-600 dark:text-neutral-400">JAMB, WAEC & NECO practice questions (5 years)</flux:text>
                    </div>

                    {{-- Feature 2 --}}
                    <div class="text-center p-6 md:p-8 rounded-2xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 transition-all hover:border-purple-500 dark:hover:border-purple-500 hover:shadow-2xl">
                        <div class="w-14 h-14 md:w-16 md:h-16 bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-7 h-7 md:w-8 md:h-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <flux:heading size="sm" class="font-semibold mb-2">Timed Mock Exams</flux:heading>
                        <flux:text class="text-sm text-neutral-600 dark:text-neutral-400">Practice under exam conditions</flux:text>
                    </div>

                    {{-- Feature 3 --}}
                    <div class="text-center p-6 md:p-8 rounded-2xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 transition-all hover:border-green-500 dark:hover:border-green-500 hover:shadow-2xl">
                        <div class="w-14 h-14 md:w-16 md:h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-7 h-7 md:w-8 md:h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                        <flux:heading size="sm" class="font-semibold mb-2">Progress Tracking</flux:heading>
                        <flux:text class="text-sm text-neutral-600 dark:text-neutral-400">Monitor performance and improvement</flux:text>
                    </div>

                    {{-- Feature 4 --}}
                    <div class="text-center p-6 md:p-8 rounded-2xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 transition-all hover:border-orange-500 dark:hover:border-orange-500 hover:shadow-2xl">
                        <div class="w-14 h-14 md:w-16 md:h-16 bg-orange-100 dark:bg-orange-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-7 h-7 md:w-8 md:h-8 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <flux:heading size="sm" class="font-semibold mb-2">Video Lessons</flux:heading>
                        <flux:text class="text-sm text-neutral-600 dark:text-neutral-400">Learn from expert teachers</flux:text>
                    </div>
                </div>
            </div>

            {{-- Pricing Section --}}
            <div id="pricing" class="mt-24 md:mt-32 scroll-mt-20">
                <div class="text-center mb-14 md:mb-16">
                    <div class="inline-flex items-center gap-2 rounded-full border border-blue-200 bg-blue-50 px-3.5 py-1.5 text-xs font-semibold text-blue-800 dark:border-blue-800/60 dark:bg-blue-950/40 dark:text-blue-300 mb-3">
                        <svg class="size-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Simple, Transparent Pricing
                    </div>
                    <flux:heading size="xl" class="text-2xl md:text-3xl lg:text-4xl font-bold mb-3">
                        Affordable Plans for Every Student
                    </flux:heading>
                    <flux:text class="text-base md:text-lg text-neutral-600 dark:text-neutral-400 max-w-2xl mx-auto">
                        Choose the plan that fits your study needs. Access complete practice questions, video lessons, and mock exams.
                    </flux:text>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-4xl mx-auto items-stretch">
                    {{-- Monthly Plan Card --}}
                    <div class="relative flex flex-col rounded-3xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 p-8 shadow-sm hover:shadow-xl transition-all">
                        <div class="flex items-center justify-between gap-4 mb-4">
                            <div>
                                <h3 class="text-xl font-bold text-neutral-900 dark:text-white">Monthly Plan</h3>
                                <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-0.5">Flexible 30-day exam access</p>
                            </div>
                            <span class="rounded-full bg-blue-50 dark:bg-blue-950/60 px-3 py-1 text-xs font-semibold text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800/60">
                                30 Days
                            </span>
                        </div>

                        <div class="flex items-baseline gap-1 my-4">
                            <span class="text-4xl md:text-5xl font-extrabold tracking-tight text-neutral-900 dark:text-white">₦2,000</span>
                            <span class="text-sm font-medium text-neutral-500 dark:text-neutral-400">/ month</span>
                        </div>

                        <p class="text-sm text-neutral-600 dark:text-neutral-400 mb-6 leading-relaxed">
                            Unlimited access to all lessons, CBT mock exams, and practice questions for one month.
                        </p>

                        <div class="space-y-3 mb-8 flex-1">
                            <div class="flex items-start gap-3">
                                <svg class="size-5 text-emerald-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="text-sm text-neutral-700 dark:text-neutral-300">All subject lessons &amp; video tutorials</span>
                            </div>
                            <div class="flex items-start gap-3">
                                <svg class="size-5 text-emerald-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="text-sm text-neutral-700 dark:text-neutral-300">Unlimited JAMB, WAEC &amp; NECO practice questions</span>
                            </div>
                            <div class="flex items-start gap-3">
                                <svg class="size-5 text-emerald-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="text-sm text-neutral-700 dark:text-neutral-300">Timed CBT mock exams with smart explanations</span>
                            </div>
                            <div class="flex items-start gap-3">
                                <svg class="size-5 text-emerald-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="text-sm text-neutral-700 dark:text-neutral-300">Progress tracking &amp; performance breakdown</span>
                            </div>
                            <div class="flex items-start gap-3">
                                <svg class="size-5 text-emerald-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="text-sm text-neutral-700 dark:text-neutral-300">Monthly renewal</span>
                            </div>
                        </div>

                        <a
                            href="{{ route('register') }}"
                            class="inline-flex w-full items-center justify-center rounded-xl border border-neutral-300 dark:border-neutral-600 bg-white dark:bg-neutral-900 px-6 py-3.5 text-base font-semibold text-neutral-900 dark:text-white shadow-sm hover:bg-neutral-50 dark:hover:bg-neutral-800 transition"
                            wire:navigate
                        >
                            Get Started with Monthly
                        </a>
                    </div>

                    {{-- Yearly Plan Card (Featured) --}}
                    <div class="relative flex flex-col rounded-3xl border-2 border-blue-600 dark:border-blue-500 bg-gradient-to-b from-white via-white to-blue-50/40 dark:from-neutral-800 dark:via-neutral-800 dark:to-blue-950/20 p-8 shadow-xl hover:shadow-2xl transition-all">
                        {{-- Popular Ribbon --}}
                        <div class="absolute -top-3.5 left-1/2 -translate-x-1/2 rounded-full bg-blue-600 px-4 py-1 text-xs font-bold uppercase tracking-wider text-white shadow-md">
                            Best Value &bull; Save ₦6,000 (25% Off)
                        </div>

                        <div class="flex items-center justify-between gap-4 mb-4 mt-2">
                            <div>
                                <h3 class="text-xl font-bold text-neutral-900 dark:text-white">Yearly Plan</h3>
                                <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-0.5">Comprehensive 365-day exam preparation</p>
                            </div>
                            <span class="rounded-full bg-emerald-100 dark:bg-emerald-950/60 px-3 py-1 text-xs font-semibold text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60">
                                365 Days
                            </span>
                        </div>

                        <div class="flex items-baseline gap-1 my-4">
                            <span class="text-4xl md:text-5xl font-extrabold tracking-tight text-blue-600 dark:text-blue-400">₦18,000</span>
                            <span class="text-sm font-medium text-neutral-500 dark:text-neutral-400">/ year</span>
                            <span class="ml-2 text-xs font-semibold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 px-2 py-0.5 rounded">₦1,500/mo</span>
                        </div>

                        <p class="text-sm text-neutral-600 dark:text-neutral-400 mb-6 leading-relaxed">
                            Full year access to all content, mock exams, and analytics at the best value.
                        </p>

                        <div class="space-y-3 mb-8 flex-1">
                            <div class="flex items-start gap-3">
                                <svg class="size-5 text-emerald-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="text-sm font-medium text-neutral-800 dark:text-neutral-200">Everything in Monthly, plus:</span>
                            </div>
                            <div class="flex items-start gap-3">
                                <svg class="size-5 text-emerald-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="text-sm text-neutral-700 dark:text-neutral-300">Full 365 days of uninterrupted study</span>
                            </div>
                            <div class="flex items-start gap-3">
                                <svg class="size-5 text-emerald-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="text-sm text-neutral-700 dark:text-neutral-300">Detailed analytics &amp; diagnostics</span>
                            </div>
                            <div class="flex items-start gap-3">
                                <svg class="size-5 text-emerald-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="text-sm text-neutral-700 dark:text-neutral-300">Complete multi-exam prep for JAMB, WAEC &amp; NECO</span>
                            </div>
                            <div class="flex items-start gap-3">
                                <svg class="size-5 text-emerald-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="text-sm text-neutral-700 dark:text-neutral-300">Annual renewal (save ₦6,000/year)</span>
                            </div>
                        </div>

                        <a
                            href="{{ route('register') }}"
                            class="inline-flex w-full items-center justify-center rounded-xl bg-blue-600 px-6 py-3.5 text-base font-semibold text-white shadow-lg hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 transition"
                            wire:navigate
                        >
                            Get Started with Yearly
                        </a>
                    </div>
                </div>

                {{-- Guardian & Payment Info Note --}}
                <div class="mt-8 text-center">
                    <p class="text-xs sm:text-sm text-neutral-500 dark:text-neutral-400">
                        Secure payments processed via Paystack. Parents and schools can manage subscriptions for linked students from their dashboard.
                        <a href="{{ route('pricing') }}" class="font-medium text-blue-600 dark:text-blue-400 hover:underline ml-1" wire:navigate>
                            View Subscription Page &rarr;
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Footer --}}
    <footer class="border-t border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-8 md:grid-cols-4">
                {{-- Column 1: Brand & CAC / Corporate Details --}}
                <div class="md:col-span-2">
                    <div class="flex items-center gap-2.5 mb-3">
                        <img
                            src="{{ asset('images/icons/logo-64.png') }}"
                            alt="{{ config('app.name', 'Future Academy') }} logo"
                            class="size-8 rounded-md object-contain"
                        />
                        <span class="text-base font-bold text-neutral-900 dark:text-white">
                            {{ config('app.name', 'Future Academy') }}
                        </span>
                    </div>
                    <p class="text-sm text-neutral-600 dark:text-neutral-400 max-w-md leading-relaxed mb-4">
                        Empowering secondary school students across Nigeria to achieve academic excellence in JAMB, WAEC, and NECO exams through video lectures for learning and comprehensive CBT practice questions.
                    </p>
                    <div class="space-y-2">
                        <div class="inline-flex items-center gap-2 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 px-3 py-1.5 text-xs text-emerald-800 dark:text-emerald-300 font-medium">
                            <svg class="size-4 text-emerald-600 dark:text-emerald-400 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M16.403 12.652a3 3 0 000-5.304 3 3 0 00-3.75-3.751 3 3 0 00-5.305 0 3 3 0 00-3.751 3.75 3 3 0 000 5.305 3 3 0 003.75 3.751 3 3 0 005.305 0 3 3 0 003.751-3.75zm-2.546-4.46a.75.75 0 00-1.214-.883l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                            </svg>
                            <span>Company Registered &bull; Corporate Affairs Commission</span>
                        </div>
                        <p class="text-xs text-neutral-600 dark:text-neutral-400 leading-relaxed">
                            Future Academy is a project of <strong class="text-neutral-900 dark:text-white font-semibold">RMS GL (Ramenshuka Global Resources)</strong> &bull; RC: <strong class="text-neutral-900 dark:text-white font-semibold">1919844</strong>
                        </p>
                    </div>
                </div>

                {{-- Column 2: Contact Information --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-neutral-900 dark:text-white mb-3">
                        Contact Us
                    </h3>
                    <ul class="space-y-2.5 text-sm text-neutral-600 dark:text-neutral-400">
                        <li class="flex items-start gap-2">
                            <span class="font-medium text-neutral-900 dark:text-white">Company WhatsApp:</span>
                            <a href="https://wa.me/2347071100037?text=Hello%20Future%20Academy%2C%20I%20would%20like%20to%20make%20an%20enquiry" target="_blank" rel="noopener noreferrer" class="text-emerald-600 dark:text-emerald-400 hover:underline font-semibold">
                                07071100037
                            </a>
                        </li>
                        <li class="flex flex-col">
                            <span class="font-medium text-neutral-900 dark:text-white">Direct Email:</span>
                            <a href="mailto:futureacademy-rm@gmail.com" class="text-blue-600 dark:text-blue-400 hover:underline break-all">
                                futureacademy-rm@gmail.com
                            </a>
                        </li>
                        <li class="flex flex-col">
                            <span class="font-medium text-neutral-900 dark:text-white">Official / System Email:</span>
                            <a href="mailto:noreply@futureacademy-rm.com" class="text-neutral-700 dark:text-neutral-300 hover:underline break-all">
                                noreply@futureacademy-rm.com
                            </a>
                        </li>
                    </ul>
                </div>

                {{-- Column 3: Quick Links --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-neutral-900 dark:text-white mb-3">
                        Quick Links
                    </h3>
                    <ul class="space-y-2 text-sm">
                        <li>
                            <a href="{{ route('login', ['type' => 'student']) }}" class="text-neutral-600 dark:text-neutral-400 hover:text-blue-600 dark:hover:text-blue-400" wire:navigate>
                                Student Login
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('login', ['type' => 'parent']) }}" class="text-neutral-600 dark:text-neutral-400 hover:text-blue-600 dark:hover:text-blue-400" wire:navigate>
                                Parent / School Login
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('register') }}" class="text-neutral-600 dark:text-neutral-400 hover:text-blue-600 dark:hover:text-blue-400" wire:navigate>
                                Get Started
                            </a>
                        </li>
                        <li>
                            <a href="#pricing" class="text-neutral-600 dark:text-neutral-400 hover:text-blue-600 dark:hover:text-blue-400">
                                Pricing Plans
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('privacy-policy') }}" class="text-neutral-600 dark:text-neutral-400 hover:text-blue-600 dark:hover:text-blue-400" wire:navigate>
                                Privacy Policy
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('account-deletion') }}" class="text-neutral-600 dark:text-neutral-400 hover:text-blue-600 dark:hover:text-blue-400" wire:navigate>
                                Account Deletion
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="mt-8 border-t border-neutral-200 dark:border-neutral-800 pt-6 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-neutral-500 dark:text-neutral-400">
                <p>
                    &copy; {{ now()->year }} {{ config('app.name', 'Future Academy') }}. All rights reserved. &bull; A project of Ramenshuka Global Resources (RMS GL) &bull; RC: 1919844
                </p>
                <div class="flex items-center gap-4">
                    <a href="{{ route('privacy-policy') }}" class="hover:text-neutral-700 dark:hover:text-neutral-200" wire:navigate>Privacy Policy</a>
                    <span>&bull;</span>
                    <a href="{{ route('account-deletion') }}" class="hover:text-neutral-700 dark:hover:text-neutral-200" wire:navigate>Account Deletion</a>
                </div>
            </div>
        </div>
    </footer>

    {{-- Floating WhatsApp Action Button --}}
    <a
        href="https://wa.me/2347071100037?text=Hello%20Future%20Academy%2C%20I%20would%20like%20to%20make%20an%20enquiry"
        target="_blank"
        rel="noopener noreferrer"
        aria-label="Chat on WhatsApp with Future Academy"
        class="fixed bottom-6 right-6 z-50 flex items-center gap-2 rounded-full bg-emerald-600 px-4 py-3 text-sm font-semibold text-white shadow-xl hover:bg-emerald-500 hover:shadow-2xl transition-all hover:scale-105 focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:ring-offset-2"
    >
        <svg class="size-5 fill-current" viewBox="0 0 24 24">
            <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
        </svg>
        <span class="hidden sm:inline">WhatsApp Us</span>
    </a>
</div>
