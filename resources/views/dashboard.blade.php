<x-layouts.app :title="__('Dashboard')">
    <div class="space-y-6">
        {{-- Header --}}
        <div>
            <flux:heading size="2xl">{{ __('Welcome to Future Academy') }}</flux:heading>
            <flux:text class="mt-2 text-neutral-600 dark:text-neutral-400">{{ __('Choose what you want to do today') }}</flux:text>
        </div>

        {{-- Main Action Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            {{-- Lessons/Class Card --}}
            <a href="{{ route('lessons.subjects') }}" wire:navigate class="group">
                <div class="relative overflow-hidden rounded-xl border-2 border-neutral-200 dark:border-neutral-700 p-8 bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-950/30 dark:to-blue-900/20 hover:border-blue-500 dark:hover:border-blue-500 transition-all">
                    <div class="absolute top-0 right-0 opacity-10 group-hover:opacity-20 transition-opacity">
                        <svg class="w-32 h-32 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2 6a2 2 0 012-2h12a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6zM4 9a1 1 0 011-1h10a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1V9z"/>
                        </svg>
                    </div>
                    <div class="relative">
                        <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-blue-600 dark:bg-blue-500 mb-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C6.5 6.253 2 10.998 2 17.5s4.5 11.247 10 11.247m0-13c5.5 0 10 4.745 10 11.247s-4.5 11.247-10 11.247m0-13V5m0 13H5m10 0h5"/>
                            </svg>
                        </div>
                        <flux:heading size="lg" class="text-blue-900 dark:text-blue-100">{{ __('Lessons & Classes') }}</flux:heading>
                        <flux:text class="mt-2 text-sm text-blue-800 dark:text-blue-200">{{ __('Learn from our comprehensive lessons and study materials') }}</flux:text>
                        <div class="mt-4 inline-flex items-center gap-2 text-blue-600 dark:text-blue-400 font-medium">
                            {{ __('Start Learning') }}
                            <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </a>

            {{-- Exams Card --}}
            <a href="{{ route('quizzes.index') }}" wire:navigate class="group">
                <div class="relative overflow-hidden rounded-xl border-2 border-neutral-200 dark:border-neutral-700 p-8 bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-950/30 dark:to-purple-900/20 hover:border-purple-500 dark:hover:border-purple-500 transition-all">
                    <div class="absolute top-0 right-0 opacity-10 group-hover:opacity-20 transition-opacity">
                        <svg class="w-32 h-32 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                            <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 1 1 0 000 2H3a1 1 0 00-1 1v10a1 1 0 001 1h14a1 1 0 001-1V6a1 1 0 00-1-1h3a1 1 0 000-2 2 2 0 01-2-2V3a1 1 0 00-1-1H4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="relative">
                        <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-purple-600 dark:bg-purple-500 mb-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                            </svg>
                        </div>
                        <flux:heading size="lg" class="text-purple-900 dark:text-purple-100">{{ __('Exams') }}</flux:heading>
                        <flux:text class="mt-2 text-sm text-purple-800 dark:text-purple-200">{{ __('Take official exams and structured quizzes to test your knowledge') }}</flux:text>
                        <div class="mt-4 inline-flex items-center gap-2 text-purple-600 dark:text-purple-400 font-medium">
                            {{ __('Take Exam') }}
                            <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </a>

            {{-- Mock/Practice Card --}}
            <a href="{{ route('practice.home') }}" wire:navigate class="group">
                <div class="relative overflow-hidden rounded-xl border-2 border-neutral-200 dark:border-neutral-700 p-8 bg-gradient-to-br from-green-50 to-green-100 dark:from-green-950/30 dark:to-green-900/20 hover:border-green-500 dark:hover:border-green-500 transition-all">
                    <div class="absolute top-0 right-0 opacity-10 group-hover:opacity-20 transition-opacity">
                        <svg class="w-32 h-32 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v2a1 1 0 001 1h14a1 1 0 001-1V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="relative">
                        <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-green-600 dark:bg-green-500 mb-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <flux:heading size="lg" class="text-green-900 dark:text-green-100">{{ __('Mock Tests') }}</flux:heading>
                        <flux:text class="mt-2 text-sm text-green-800 dark:text-green-200">{{ __('Practice with past papers and mock exams to prepare effectively') }}</flux:text>
                        <div class="mt-4 inline-flex items-center gap-2 text-green-600 dark:text-green-400 font-medium">
                            {{ __('Start Practice') }}
                            <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        {{-- Quick Stats Section (Optional) --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-8">
            <div class="rounded-lg border border-neutral-200 dark:border-neutral-700 p-6 bg-white dark:bg-neutral-800">
                <flux:text class="text-sm text-neutral-600 dark:text-neutral-400">{{ __('Lessons Completed') }}</flux:text>
                <flux:heading size="lg" class="mt-2 text-blue-600 dark:text-blue-400">0</flux:heading>
            </div>
            <div class="rounded-lg border border-neutral-200 dark:border-neutral-700 p-6 bg-white dark:bg-neutral-800">
                <flux:text class="text-sm text-neutral-600 dark:text-neutral-400">{{ __('Exams Attempted') }}</flux:text>
                <flux:heading size="lg" class="mt-2 text-purple-600 dark:text-purple-400">0</flux:heading>
            </div>
            <div class="rounded-lg border border-neutral-200 dark:border-neutral-700 p-6 bg-white dark:bg-neutral-800">
                <flux:text class="text-sm text-neutral-600 dark:text-neutral-400">{{ __('Practice Sessions') }}</flux:text>
                <flux:heading size="lg" class="mt-2 text-green-600 dark:text-green-400">0</flux:heading>
            </div>
        </div>
    </div>
</x-layouts.app>
