<div class="min-h-screen bg-gradient-to-br from-neutral-50 to-neutral-100 dark:from-neutral-900 dark:to-neutral-800 -m-6 p-6">
    <div class="max-w-7xl mx-auto space-y-8">
        {{-- Onboarding Setup Card - Prominent Banner --}}
        @if(!auth()->user()->has_completed_onboarding)
        <div class="rounded-2xl border-0 p-8 bg-gradient-to-r from-blue-600 via-blue-500 to-cyan-500 dark:from-blue-700 dark:via-blue-600 dark:to-cyan-600 shadow-xl hover:shadow-2xl transition-shadow">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="relative">
                            <div class="absolute inset-0 bg-white/30 rounded-full animate-pulse"></div>
                            <svg class="w-8 h-8 text-white relative z-10 animate-bounce" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                            </svg>
                        </div>
                        <flux:heading size="xl" class="text-white mb-0 font-bold">{{ __('Welcome! Complete Your Setup') }}</flux:heading>
                    </div>
                    <flux:text class="text-blue-100 text-sm font-medium mb-2">{{ __('Personalize your learning experience') }}</flux:text>
                    <flux:text class="text-blue-50 text-sm">{{ __('Select your stream, exam type (JAMB, NECO, WAEC), and subjects to get tailored recommendations. Takes just 2 minutes!') }}</flux:text>
                </div>
                <div class="flex-shrink-0">
                    <flux:button
                        href="{{ route('onboarding') }}"
                        wire:navigate
                        class="px-6 py-3 bg-white hover:bg-blue-50 text-blue-600 hover:text-blue-700 font-bold rounded-lg shadow-md hover:shadow-lg transition-all whitespace-nowrap"
                    >
                        {{ __('Complete Setup') }}
                        <svg class="w-4 h-4 ml-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                        </svg>
                    </flux:button>
                </div>
            </div>
        </div>
        @endif

        {{-- Header Section --}}
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
            <div>
                <flux:heading size="3xl" class="font-bold text-neutral-900 dark:text-white mb-2">{{ __('Welcome back, :name!', ['name' => auth()->user()->name]) }}</flux:heading>
                <flux:text class="text-neutral-600 dark:text-neutral-400 text-lg">{{ __('Here\'s your learning progress and quick access') }}</flux:text>
            </div>
            <flux:button href="{{ route('analytics') }}" variant="primary" icon="chart-bar" wire:navigate class="w-full sm:w-auto">
                {{ __('View Full Analytics') }}
            </flux:button>
        </div>

        {{-- Quick Links Section --}}
        <div class="rounded-2xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 p-8">
            <flux:heading size="lg" class="mb-6 font-bold text-neutral-900 dark:text-white">{{ __('Quick Access') }}</flux:heading>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                <!-- Dashboard -->
                <a href="{{ route('dashboard') }}" class="group flex flex-col items-center p-5 rounded-xl border border-neutral-200 dark:border-neutral-700 hover:border-blue-500 dark:hover:border-blue-400 bg-neutral-50 dark:bg-neutral-700/50 hover:bg-blue-50 dark:hover:bg-blue-950/20 transition-all">
                    <div class="p-3 rounded-lg bg-blue-100 dark:bg-blue-900/30 group-hover:bg-blue-200 dark:group-hover:bg-blue-900/50 transition-colors mb-3">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-3m0 0l7-4 7 4M5 9v10a1 1 0 001 1h12a1 1 0 001-1V9m-9 11l4-4m0 0l4 4m-4-4v4"></path>
                        </svg>
                    </div>
                    <flux:text class="text-xs font-bold text-neutral-700 dark:text-neutral-300 text-center">{{ __('Dashboard') }}</flux:text>
                </a>

                <!-- Lessons -->
                <a href="{{ route('lessons.subjects') }}" class="group flex flex-col items-center p-5 rounded-xl border border-neutral-200 dark:border-neutral-700 hover:border-green-500 dark:hover:border-green-400 bg-neutral-50 dark:bg-neutral-700/50 hover:bg-green-50 dark:hover:bg-green-950/20 transition-all">
                    <div class="p-3 rounded-lg bg-green-100 dark:bg-green-900/30 group-hover:bg-green-200 dark:group-hover:bg-green-900/50 transition-colors mb-3">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C6.5 6.253 2 10.998 2 17s4.5 10.747 10 10.747c5.5 0 10-4.998 10-10.747S17.5 6.253 12 6.253z"></path>
                        </svg>
                    </div>
                    <flux:text class="text-xs font-bold text-neutral-700 dark:text-neutral-300 text-center">{{ __('Lessons') }}</flux:text>
                </a>

                <!-- Mock Exams -->
                <a href="{{ route('mock.setup') }}" class="group flex flex-col items-center p-5 rounded-xl border border-neutral-200 dark:border-neutral-700 hover:border-purple-500 dark:hover:border-purple-400 bg-neutral-50 dark:bg-neutral-700/50 hover:bg-purple-50 dark:hover:bg-purple-950/20 transition-all">
                    <div class="p-3 rounded-lg bg-purple-100 dark:bg-purple-900/30 group-hover:bg-purple-200 dark:group-hover:bg-purple-900/50 transition-colors mb-3">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <flux:text class="text-xs font-bold text-neutral-700 dark:text-neutral-300 text-center">{{ __('Mock Exams') }}</flux:text>
                </a>

                <!-- Practice -->
                <a href="{{ route('practice.home') }}" class="group flex flex-col items-center p-5 rounded-xl border border-neutral-200 dark:border-neutral-700 hover:border-orange-500 dark:hover:border-orange-400 bg-neutral-50 dark:bg-neutral-700/50 hover:bg-orange-50 dark:hover:bg-orange-950/20 transition-all">
                    <div class="p-3 rounded-lg bg-orange-100 dark:bg-orange-900/30 group-hover:bg-orange-200 dark:group-hover:bg-orange-900/50 transition-colors mb-3">
                        <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <flux:text class="text-xs font-bold text-neutral-700 dark:text-neutral-300 text-center">{{ __('Practice') }}</flux:text>
                </a>

                <!-- Analytics -->
                <a href="{{ route('analytics') }}" class="group flex flex-col items-center p-5 rounded-xl border border-neutral-200 dark:border-neutral-700 hover:border-red-500 dark:hover:border-red-400 bg-neutral-50 dark:bg-neutral-700/50 hover:bg-red-50 dark:hover:bg-red-950/20 transition-all">
                    <div class="p-3 rounded-lg bg-red-100 dark:bg-red-900/30 group-hover:bg-red-200 dark:group-hover:bg-red-900/50 transition-colors mb-3">
                        <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <flux:text class="text-xs font-bold text-neutral-700 dark:text-neutral-300 text-center">{{ __('Analytics') }}</flux:text>
                </a>

                <!-- Onboarding -->
                <a href="{{ route('onboarding') }}" class="group flex flex-col items-center p-5 rounded-xl border-2 {{ auth()->user()->has_completed_onboarding ? 'border-neutral-200 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-700/50' : 'border-blue-400 dark:border-blue-500 bg-blue-50 dark:bg-blue-950/30' }} hover:border-blue-500 dark:hover:border-blue-400 hover:shadow-md transition-all">
                    <div class="p-3 rounded-lg {{ auth()->user()->has_completed_onboarding ? 'bg-green-100 dark:bg-green-900/30' : 'bg-blue-100 dark:bg-blue-900/30' }} group-hover:shadow-md transition-all mb-3">
                        @if(auth()->user()->has_completed_onboarding)
                            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                        @else
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m7 8a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        @endif
                    </div>
                    <flux:text class="text-xs font-bold {{ auth()->user()->has_completed_onboarding ? 'text-neutral-700 dark:text-neutral-300' : 'text-blue-700 dark:text-blue-300' }} text-center">{{ __('Onboarding') }}</flux:text>
                    @if(!auth()->user()->has_completed_onboarding)
                    <span class="mt-2 inline-block px-2 py-0.5 bg-red-500 text-white text-xs font-bold rounded-full text-center">{{ __('Required') }}</span>
                    @endif
                </a>
            </div>
        </div>

        {{-- Stats Overview Grid --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            {{-- Videos Watched --}}
            <div class="rounded-2xl border border-neutral-200 dark:border-neutral-700 p-6 bg-white dark:bg-neutral-800 hover:shadow-lg transition-shadow">
                <div class="flex items-start justify-between mb-4">
                    <div class="p-3 rounded-lg bg-blue-100 dark:bg-blue-900/30">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                        </svg>
                    </div>
                    <span class="text-xs font-bold text-green-600 bg-green-100 dark:bg-green-900/30 dark:text-green-400 px-3 py-1 rounded-full">
                        {{ $stats['total_videos'] > 0 ? round(($stats['videos_watched'] / $stats['total_videos']) * 100) : 0 }}%
                    </span>
                </div>
                <flux:text class="text-sm text-neutral-600 dark:text-neutral-400 font-medium mb-1">{{ __('Videos Watched') }}</flux:text>
                <div class="flex items-baseline gap-2 mb-3">
                    <flux:heading size="xl" class="text-neutral-900 dark:text-white font-bold">{{ $stats['videos_watched'] }}</flux:heading>
                    <flux:text class="text-neutral-500 dark:text-neutral-400">/ {{ $stats['total_videos'] }}</flux:text>
                </div>
                @if($stats['total_videos'] > 0)
                <div class="w-full bg-neutral-200 dark:bg-neutral-700 rounded-full h-2 overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-full transition-all" style="width: {{ ($stats['videos_watched'] / $stats['total_videos']) * 100 }}%"></div>
                </div>
                @endif
            </div>

            {{-- Mock Exams Taken --}}
            <div class="rounded-2xl border border-neutral-200 dark:border-neutral-700 p-6 bg-white dark:bg-neutral-800 hover:shadow-lg transition-shadow">
                <div class="flex items-start justify-between mb-4">
                    <div class="p-3 rounded-lg bg-green-100 dark:bg-green-900/30">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <span class="text-xs font-bold text-green-600 bg-green-100 dark:bg-green-900/30 dark:text-green-400 px-3 py-1 rounded-full">
                        {{ $stats['total_quizzes'] > 0 ? round(($stats['quizzes_taken'] / $stats['total_quizzes']) * 100) : 0 }}%
                    </span>
                </div>
                <flux:text class="text-sm text-neutral-600 dark:text-neutral-400 font-medium mb-1">{{ __('Mock Exams Taken') }}</flux:text>
                <div class="flex items-baseline gap-2 mb-3">
                    <flux:heading size="xl" class="text-neutral-900 dark:text-white font-bold">{{ $stats['quizzes_taken'] }}</flux:heading>
                    <flux:text class="text-neutral-500 dark:text-neutral-400">/ {{ $stats['total_quizzes'] }}</flux:text>
                </div>
                @if($stats['total_quizzes'] > 0)
                <div class="w-full bg-neutral-200 dark:bg-neutral-700 rounded-full h-2 overflow-hidden">
                    <div class="bg-gradient-to-r from-green-500 to-green-600 h-full transition-all" style="width: {{ ($stats['quizzes_taken'] / $stats['total_quizzes']) * 100 }}%"></div>
                </div>
                @endif
            </div>

            {{-- Average Score --}}
            <div class="rounded-2xl border border-neutral-200 dark:border-neutral-700 p-6 bg-white dark:bg-neutral-800 hover:shadow-lg transition-shadow">
                <div class="flex items-start justify-between mb-4">
                    <div class="p-3 rounded-lg bg-amber-100 dark:bg-amber-900/30">
                        <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <span class="text-xs font-bold {{ $stats['average_score'] >= 70 ? 'text-green-600 bg-green-100 dark:bg-green-900/30 dark:text-green-400' : ($stats['average_score'] >= 50 ? 'text-amber-600 bg-amber-100 dark:bg-amber-900/30 dark:text-amber-400' : 'text-red-600 bg-red-100 dark:bg-red-900/30 dark:text-red-400') }} px-3 py-1 rounded-full">
                        {{ $stats['average_score'] >= 70 ? 'Excellent' : ($stats['average_score'] >= 50 ? 'Good' : 'Fair') }}
                    </span>
                </div>
                <flux:text class="text-sm text-neutral-600 dark:text-neutral-400 font-medium mb-1">{{ __('Average Score') }}</flux:text>
                <flux:heading size="xl" class="text-neutral-900 dark:text-white font-bold mb-3">{{ number_format($stats['average_score'], 1) }}%</flux:heading>
                <div class="w-full bg-neutral-200 dark:bg-neutral-700 rounded-full h-2 overflow-hidden">
                    <div class="bg-gradient-to-r from-amber-500 to-amber-600 h-full transition-all" style="width: {{ $stats['average_score'] }}%"></div>
                </div>
            </div>

            {{-- Enrolled Subjects --}}
            <div class="rounded-2xl border border-neutral-200 dark:border-neutral-700 p-6 bg-white dark:bg-neutral-800 hover:shadow-lg transition-shadow">
                <div class="flex items-start justify-between mb-4">
                    <div class="p-3 rounded-lg bg-purple-100 dark:bg-purple-900/30">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C6.5 6.253 2 10.998 2 17s4.5 10.747 10 10.747c5.5 0 10-4.998 10-10.747S17.5 6.253 12 6.253z"></path>
                        </svg>
                    </div>
                    <span class="text-xs font-bold text-purple-600 bg-purple-100 dark:bg-purple-900/30 dark:text-purple-400 px-3 py-1 rounded-full">Active</span>
                </div>
                <flux:text class="text-sm text-neutral-600 dark:text-neutral-400 font-medium mb-1">{{ __('My Subjects') }}</flux:text>
                <flux:heading size="xl" class="text-neutral-900 dark:text-white font-bold mb-3">{{ $stats['subjects_enrolled'] }}</flux:heading>
                <flux:button href="{{ route('lessons.subjects') }}" variant="ghost" size="sm" class="w-full" wire:navigate>
                    {{ __('View All') }} →
                </flux:button>
            </div>
        </div>

        {{-- Enrolled Subjects Section --}}
        @if($enrolledSubjects->isNotEmpty())
        <div class="rounded-2xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 p-8">
            <flux:heading size="lg" class="mb-6 font-bold text-neutral-900 dark:text-white">{{ __('My Subjects') }}</flux:heading>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                @foreach($enrolledSubjects as $subject)
                <a href="{{ route('lessons.list', $subject->slug) }}" class="group flex flex-col items-center p-5 rounded-xl border border-neutral-200 dark:border-neutral-700 hover:border-blue-500 dark:hover:border-blue-400 bg-neutral-50 dark:bg-neutral-700/50 hover:bg-blue-50 dark:hover:bg-blue-950/20 transition-all">
                    @if($subject->icon)
                    <span class="text-4xl mb-2 group-hover:scale-110 transition-transform">{{ $subject->icon }}</span>
                    @endif
                    <flux:text class="text-sm font-semibold text-neutral-900 dark:text-white text-center group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">{{ $subject->name }}</flux:text>
                </a>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Practice Section --}}
        <div class="rounded-2xl border-0 p-8 bg-gradient-to-br from-orange-500 via-orange-400 to-amber-500 dark:from-orange-700 dark:via-orange-600 dark:to-amber-600 shadow-lg hover:shadow-xl transition-shadow">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6">
                <div>
                    <flux:heading size="lg" class="text-white mb-2 font-bold">{{ __('Practice Past Papers') }}</flux:heading>
                    <flux:text class="text-orange-50">{{ __('Master exam questions by practicing JAMB, NECO, and other past papers by subject and year. Start with JAMB full-length tests!') }}</flux:text>
                </div>
                <flux:button
                    href="{{ route('practice.home') }}"
                    wire:navigate
                    class="flex-shrink-0 px-6 py-3 bg-white hover:bg-orange-50 text-orange-600 hover:text-orange-700 font-bold rounded-lg shadow-md hover:shadow-lg whitespace-nowrap"
                >
                    {{ __('Start Practice') }}
                    <svg class="w-4 h-4 ml-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                    </svg>
                </flux:button>
            </div>
        </div>

        {{-- Content Grid --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Recent Videos --}}
            <div class="rounded-2xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 overflow-hidden shadow-sm hover:shadow-md transition-shadow">
                <div class="px-8 py-5 border-b border-neutral-200 dark:border-neutral-700 flex items-center justify-between bg-neutral-50 dark:bg-neutral-700/50">
                    <div>
                        <flux:heading size="lg" class="font-bold text-neutral-900 dark:text-white">{{ __('Recent Videos') }}</flux:heading>
                        <flux:text class="text-sm text-neutral-500 dark:text-neutral-400 mt-1">{{ __('Continue where you left off') }}</flux:text>
                    </div>
                    <a href="{{ route('lessons.subjects') }}" class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 font-semibold whitespace-nowrap ml-4">
                        {{ __('View All') }} →
                    </a>
                </div>
                <div class="p-6">
                    @if($recentVideos->isEmpty())
                        <div class="text-center py-12">
                            <svg class="w-16 h-16 text-neutral-300 dark:text-neutral-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                            </svg>
                            <flux:text class="text-neutral-500 dark:text-neutral-400 font-medium">{{ __('No videos available yet') }}</flux:text>
                        </div>
                    @else
                        <div class="space-y-3">
                            @foreach($recentVideos as $video)
                            <a href="{{ route('lessons.view', $video->id) }}" class="group flex items-start space-x-4 p-4 rounded-xl hover:bg-neutral-50 dark:hover:bg-neutral-700/50 transition-colors border border-neutral-200 dark:border-neutral-700/50">
                                @if($video->thumbnail)
                                <img src="{{ $video->thumbnail }}" alt="{{ $video->title }}" class="w-20 h-14 object-cover rounded-lg flex-shrink-0">
                                @else
                                <div class="w-20 h-14 bg-neutral-200 dark:bg-neutral-700 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <svg class="w-6 h-6 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                                    </svg>
                                </div>
                                @endif
                                <div class="flex-1 min-w-0">
                                    <flux:heading size="sm" class="truncate font-semibold text-neutral-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                        {{ $video->title }}
                                    </flux:heading>
                                    <flux:text class="text-xs text-neutral-600 dark:text-neutral-400 mt-1">{{ $video->subject->name }}</flux:text>
                                    @if($video->duration)
                                    <div class="flex items-center mt-2 space-x-2">
                                        <svg class="w-4 h-4 text-neutral-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00-.293.707l-2.828 2.829a1 1 0 101.415 1.415L9 9.414V6z" clip-rule="evenodd"></path>
                                        </svg>
                                        <flux:text class="text-xs text-neutral-500">{{ gmdate('i:s', $video->duration) }}</flux:text>
                                    </div>
                                    @endif
                                </div>
                            </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- Available Mock Exams --}}
            <div class="rounded-2xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 overflow-hidden shadow-sm hover:shadow-md transition-shadow">
                <div class="px-8 py-5 border-b border-neutral-200 dark:border-neutral-700 flex items-center justify-between bg-neutral-50 dark:bg-neutral-700/50">
                    <div>
                        <flux:heading size="lg" class="font-bold text-neutral-900 dark:text-white">{{ __('Available Mock Exams') }}</flux:heading>
                        <flux:text class="text-sm text-neutral-500 dark:text-neutral-400 mt-1">{{ __('Test your knowledge') }}</flux:text>
                    </div>
                    <a href="{{ route('quizzes.index') }}" class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 font-semibold whitespace-nowrap ml-4">
                        {{ __('View All') }} →
                    </a>
                </div>
                <div class="p-6">
                    @if($recentQuizzes->isEmpty())
                        <div class="text-center py-12">
                            <svg class="w-16 h-16 text-neutral-300 dark:text-neutral-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <flux:text class="text-neutral-500 dark:text-neutral-400 font-medium">{{ __('No mock exams available yet') }}</flux:text>
                        </div>
                    @else
                        <div class="space-y-3">
                            @foreach($recentQuizzes as $quiz)
                            <a href="{{ route('quiz.take', $quiz->id) }}" class="group flex items-start justify-between p-4 rounded-xl hover:bg-neutral-50 dark:hover:bg-neutral-700/50 transition-colors border border-neutral-200 dark:border-neutral-700/50">
                                <div class="flex-1 min-w-0">
                                    <flux:heading size="sm" class="font-semibold text-neutral-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                        {{ $quiz->title }}
                                    </flux:heading>
                                    <flux:text class="text-xs text-neutral-600 dark:text-neutral-400 mt-1">
                                        {{ $quiz->subject->name ?? 'Mixed Subjects' }}
                                    </flux:text>
                                    <div class="flex items-center space-x-3 mt-3">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300">
                                            {{ $quiz->total_questions }} Q's
                                        </span>
                                        @if($quiz->time_limit)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300">
                                            {{ $quiz->time_limit }}m
                                        </span>
                                        @endif
                                    </div>
                                </div>
                                <svg class="h-5 w-5 text-neutral-400 group-hover:text-blue-600 dark:group-hover:text-blue-400 flex-shrink-0 mt-1 ml-4 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                                </svg>
                            </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
