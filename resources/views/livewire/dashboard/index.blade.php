<div class="space-y-6">
    {{-- Welcome Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <flux:heading size="2xl">{{ __('Welcome back, :name!', ['name' => auth()->user()->name]) }}</flux:heading>
            <flux:text class="mt-1">{{ __('Continue your learning journey') }}</flux:text>
        </div>
        <div class="flex gap-3">
            <flux:button href="{{ route('analytics') }}" variant="ghost" icon="chart-bar" wire:navigate>
                {{ __('Analytics') }}
            </flux:button>
            <flux:button href="{{ route('lessons.subjects') }}" variant="primary" icon="academic-cap" wire:navigate>
                {{ __('Browse Lessons') }}
            </flux:button>
            <flux:button href="{{ route('quizzes.index') }}" icon="clipboard-document-list" wire:navigate>
                {{ __('Take Quiz') }}
            </flux:button>
        </div>
    </div>

    {{-- Stats Overview --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Lessons Completed --}}
        <div
            class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-6 bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-950/30 dark:to-blue-900/20">
            <div class="flex items-center justify-between">
                <div>
                    <flux:text class="text-sm text-blue-600 dark:text-blue-400 font-medium">
                        {{ __('Lessons Completed') }}
                    </flux:text>
                    <flux:heading size="2xl" class="text-blue-900 dark:text-blue-100 mt-2">{{ $lessonsCompleted }}
                    </flux:heading>
                </div>
                <div
                    class="w-12 h-12 rounded-full bg-blue-600 dark:bg-blue-500 flex items-center justify-center text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                </div>
            </div>
        </div>

        {{-- Quizzes Completed --}}
        <div
            class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-6 bg-gradient-to-br from-green-50 to-green-100 dark:from-green-950/30 dark:to-green-900/20">
            <div class="flex items-center justify-between">
                <div>
                    <flux:text class="text-sm text-green-600 dark:text-green-400 font-medium">
                        {{ __('Quizzes Completed') }}
                    </flux:text>
                    <flux:heading size="2xl" class="text-green-900 dark:text-green-100 mt-2">{{ $quizzesCompleted }}
                    </flux:heading>
                </div>
                <div
                    class="w-12 h-12 rounded-full bg-green-600 dark:bg-green-500 flex items-center justify-center text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        {{-- Average Score --}}
        <div
            class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-6 bg-gradient-to-br from-amber-50 to-amber-100 dark:from-amber-950/30 dark:to-amber-900/20">
            <div class="flex items-center justify-between">
                <div>
                    <flux:text class="text-sm text-amber-600 dark:text-amber-400 font-medium">{{ __('Average Score') }}
                    </flux:text>
                    <flux:heading size="2xl" class="text-amber-900 dark:text-amber-100 mt-2">{{ $averageScore }}%
                    </flux:heading>
                </div>
                <div
                    class="w-12 h-12 rounded-full bg-amber-600 dark:bg-amber-500 flex items-center justify-center text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                    </svg>
                </div>
            </div>
        </div>

        {{-- Total Time Spent --}}
        <div
            class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-6 bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-950/30 dark:to-purple-900/20">
            <div class="flex items-center justify-between">
                <div>
                    <flux:text class="text-sm text-purple-600 dark:text-purple-400 font-medium">{{ __('Time Spent') }}
                    </flux:text>
                    <flux:heading size="2xl" class="text-purple-900 dark:text-purple-100 mt-2">
                        {{ floor($totalTimeSpent / 3600) }}h {{ floor(($totalTimeSpent % 3600) / 60) }}m
                    </flux:heading>
                </div>
                <div
                    class="w-12 h-12 rounded-full bg-purple-600 dark:bg-purple-500 flex items-center justify-center text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Continue Learning --}}
    @if($continueLesson)
        <div
            class="rounded-xl border-2 border-amber-200 dark:border-amber-800 p-6 bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-950/30 dark:to-orange-950/30">
            <div class="flex items-start justify-between gap-4">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-600" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <flux:heading size="lg" class="text-amber-900 dark:text-amber-100">{{ __('Continue Learning') }}
                        </flux:heading>
                    </div>
                    <flux:text class="text-amber-800 dark:text-amber-200 mb-1">
                        {{ $continueLesson->lesson->title }}
                    </flux:text>
                    <flux:text class="text-sm text-amber-600 dark:text-amber-400">
                        {{ $continueLesson->lesson->subject->name }} • {{ $continueLesson->progress_percentage }}% Complete
                    </flux:text>
                    <div class="mt-3 w-full bg-amber-200 dark:bg-amber-900 rounded-full h-2">
                        <div class="bg-amber-600 dark:bg-amber-500 h-2 rounded-full transition-all"
                            style="width: {{ $continueLesson->progress_percentage }}%"></div>
                    </div>
                </div>
                <flux:button href="{{ route('lessons.view', $continueLesson->lesson) }}" variant="primary" icon="play"
                    wire:navigate>
                    {{ __('Continue') }}
                </flux:button>
            </div>
        </div>
    @endif

    <div class="grid lg:grid-cols-2 gap-6">
        {{-- Recent Quiz Attempts --}}
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
            <flux:heading size="lg" class="mb-4">{{ __('Recent Quiz Attempts') }}</flux:heading>

            @if($recentAttempts->isEmpty())
                <div class="text-center py-8">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-neutral-400 mb-3" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <flux:text class="text-neutral-500">{{ __('No quiz attempts yet') }}</flux:text>
                    <flux:button href="{{ route('quizzes.index') }}" class="mt-4" variant="primary" wire:navigate>
                        {{ __('Take Your First Quiz') }}
                    </flux:button>
                </div>
            @else
                <div class="space-y-3">
                    @foreach($recentAttempts as $attempt)
                        <div
                            class="flex items-center gap-4 p-3 rounded-lg border border-neutral-200 dark:border-neutral-700 hover:border-neutral-300 dark:hover:border-neutral-600 transition">
                            <div
                                class="flex-shrink-0 w-12 h-12 rounded-full flex items-center justify-center {{ $attempt->passed ? 'bg-green-100 dark:bg-green-900 text-green-600 dark:text-green-400' : 'bg-red-100 dark:bg-red-900 text-red-600 dark:text-red-400' }}">
                                <span class="text-lg font-bold">{{ round($attempt->score_percentage) }}%</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <flux:text class="font-medium truncate">{{ $attempt->quiz->title }}</flux:text>
                                <flux:text class="text-sm text-neutral-500">
                                    {{ $attempt->completed_at?->diffForHumans() ?? $attempt->created_at->diffForHumans() }}
                                </flux:text>
                            </div>
                            <flux:badge :color="$attempt->passed ? 'green' : 'red'">
                                {{ $attempt->passed ? __('Passed') : __('Failed') }}
                            </flux:badge>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Subject Performance --}}
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
            <flux:heading size="lg" class="mb-4">{{ __('Performance by Subject') }}</flux:heading>

            @if($subjectPerformance->isEmpty())
                <div class="text-center py-8">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-neutral-400 mb-3" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    <flux:text class="text-neutral-500">{{ __('No performance data yet') }}</flux:text>
                </div>
            @else
                <div class="space-y-4">
                    @foreach($subjectPerformance as $perf)
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <flux:text class="font-medium">{{ $perf['subject'] }}</flux:text>
                                <flux:text
                                    class="text-sm font-semibold {{ $perf['avg_score'] >= 70 ? 'text-green-600 dark:text-green-400' : 'text-amber-600 dark:text-amber-400' }}">
                                    {{ $perf['avg_score'] }}%
                                </flux:text>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="flex-1 bg-neutral-200 dark:bg-neutral-700 rounded-full h-2">
                                    <div class="h-2 rounded-full transition-all {{ $perf['avg_score'] >= 70 ? 'bg-green-600 dark:bg-green-500' : 'bg-amber-600 dark:bg-amber-500' }}"
                                        style="width: {{ $perf['avg_score'] }}%"></div>
                                </div>
                                <flux:text class="text-xs text-neutral-500">
                                    {{ $perf['total_attempts'] }} {{ $perf['total_attempts'] == 1 ? 'quiz' : 'quizzes' }}
                                </flux:text>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>