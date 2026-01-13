<div class="space-y-6">
    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm">
        <a href="{{ route('lessons.subjects') }}" wire:navigate
            class="text-blue-600 dark:text-blue-400 hover:underline">
            {{ __('Lessons') }}
        </a>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-neutral-400" fill="none" viewBox="0 0 24 24"
            stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
        <a href="{{ route('lessons.list', $lesson->subject_id) }}" wire:navigate
            class="text-blue-600 dark:text-blue-400 hover:underline">
            {{ $lesson->subject->name }}
        </a>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-neutral-400" fill="none" viewBox="0 0 24 24"
            stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
        <flux:text>{{ $lesson->title }}</flux:text>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Video Player --}}
            @if($lesson->video_url)
                <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 overflow-hidden bg-black">
                    <div class="aspect-video relative" x-data="videoPlayer()" x-init="init()">
                        @if($lesson->video_type === 'local')
                            {{-- Local video with optimized streaming --}}
                            @php
                                $videoUrl = app(\App\Services\VideoSigningService::class)->getOptimizedUrl($lesson->video_url);
                            @endphp
                            <video id="lesson-video" class="w-full h-full" controls preload="metadata">
                                <source src="{{ $videoUrl }}" type="video/mp4">
                                <p>Your browser doesn't support HTML5 video. Please update your browser.</p>
                            </video>
                        @else
                            {{-- YouTube/Vimeo embedded iframe --}}
                            <iframe src="{{ $lesson->getVideoEmbedUrl() }}" class="w-full h-full" frameborder="0"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                allowfullscreen>
                            </iframe>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Lesson Header --}}
            <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
                <div class="flex items-start justify-between gap-4 mb-4">
                    <div class="flex-1">
                        <flux:heading size="2xl">{{ $lesson->title }}</flux:heading>
                        <div class="flex flex-wrap items-center gap-3 mt-2">
                            @if($lesson->topic)
                                <flux:badge color="blue">{{ $lesson->topic->name }}</flux:badge>
                            @endif
                            @if($lesson->duration_minutes)
                                <div class="flex items-center gap-1 text-sm text-neutral-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>{{ $lesson->duration_minutes }} minutes</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    @if(!$progress->is_completed)
                        <div class="flex flex-col gap-2 items-end">
                            @if($lessonQuiz && !$lessonQuizCompleted)
                                <flux:badge color="amber" size="sm">
                                    {{ __('Complete the lesson quiz to unlock completion') }}
                                </flux:badge>
                            @endif

                            <flux:button wire:click="markComplete" wire:loading.attr="disabled" wire:target="markComplete"
                                variant="primary" icon="check"
                                :disabled="$lessonQuiz && !$lessonQuizCompleted">
                                <span wire:loading.remove wire:target="markComplete">{{ __('Mark Complete') }}</span>
                                <span wire:loading wire:target="markComplete">{{ __('Saving...') }}</span>
                            </flux:button>
                        </div>
                    @else
                        <flux:badge color="green" size="lg" class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            {{ __('Completed') }}
                        </flux:badge>
                    @endif
                </div>

                {{-- Progress Bar --}}
                @if(!$progress->is_completed && $progress->progress_percentage > 0)
                    <div class="mb-4">
                        <div class="flex items-center justify-between text-sm mb-2">
                            <flux:text>{{ __('Your Progress') }}</flux:text>
                            <flux:text class="font-semibold text-blue-600 dark:text-blue-400">
                                {{ $progress->progress_percentage }}%
                            </flux:text>
                        </div>
                        <div class="w-full bg-neutral-200 dark:bg-neutral-700 rounded-full h-2">
                            <div class="bg-blue-600 dark:bg-blue-500 h-2 rounded-full transition-all"
                                style="width: {{ $progress->progress_percentage }}%"></div>
                        </div>
                    </div>
                @endif

                @if($lesson->description)
                    <flux:text class="leading-relaxed">{{ $lesson->description }}</flux:text>
                @endif
            </div>

            {{-- Practice Questions - Positioned before notes for immediate reinforcement --}}
            @if($lesson->questions->isNotEmpty())
                <livewire:lessons.practice-questions :questions="$lesson->questions" :lesson-id="$lesson->id"
                    :key="'practice-' . $lesson->id" />
            @endif

            {{-- Lesson Content/Notes - Detailed reference material --}}
            @if($lesson->content)
                <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
                    <flux:heading size="lg" class="mb-4">{{ __('Lesson Notes') }}</flux:heading>
                    <flux:text class="text-sm text-neutral-600 dark:text-neutral-400 mb-4">
                        {{ __('Detailed notes and additional reading material for this lesson.') }}
                    </flux:text>
                    <div class="prose dark:prose-invert max-w-none">
                        {!! $lesson->content !!}
                    </div>
                </div>
            @endif

            {{-- Navigation Buttons --}}
            <div class="flex items-center justify-between pt-4">
                @if($previousLesson)
                    <flux:button href="{{ route('lessons.view', $previousLesson) }}" wire:navigate variant="ghost"
                        icon="arrow-left">
                        {{ __('Previous Lesson') }}
                    </flux:button>
                @else
                    <div></div>
                @endif

                @if($nextLesson)
                    <flux:button href="{{ route('lessons.view', $nextLesson) }}" wire:navigate variant="primary"
                        icon-trailing="arrow-right"
                        :disabled="$lessonQuiz && !$lessonQuizCompleted">
                        {{ $lessonQuiz && !$lessonQuizCompleted ? __('Complete Quiz to Unlock') : __('Next Lesson') }}
                    </flux:button>
                @else
                    <flux:button href="{{ route('lessons.list', $lesson->subject_id) }}" wire:navigate variant="primary">
                        {{ __('Back to Lessons') }}
                    </flux:button>
                @endif
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="lg:col-span-1 space-y-6">
            {{-- Quick Stats --}}
            <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-6 space-y-4">
                <flux:heading size="lg">{{ __('Your Progress') }}</flux:heading>

                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 rounded-lg bg-blue-50 dark:bg-blue-950/30">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-10 h-10 rounded-full bg-blue-600 dark:bg-blue-500 flex items-center justify-center text-white">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <div>
                                <flux:text class="text-xs text-blue-600 dark:text-blue-400">{{ __('Progress') }}
                                </flux:text>
                                <flux:text class="font-semibold">{{ $progress->progress_percentage }}%</flux:text>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between p-3 rounded-lg bg-purple-50 dark:bg-purple-950/30">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-10 h-10 rounded-full bg-purple-600 dark:bg-purple-500 flex items-center justify-center text-white">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <flux:text class="text-xs text-purple-600 dark:text-purple-400">{{ __('Time Spent') }}
                                </flux:text>
                                <flux:text class="font-semibold">
                                    {{ floor($progress->time_spent_seconds / 60) }} min
                                </flux:text>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Lesson Quiz --}}
            <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
                <flux:heading size="lg" class="mb-4">{{ __('Lesson Quiz') }}</flux:heading>

                @php
                    // Defensive: check for multiple quizzes linked to this lesson
                    $lessonQuizzes = $lesson->quizzes ?? null;
                    $hasMultipleQuizzes = $lessonQuizzes && $lessonQuizzes->count() > 1;
                @endphp

                @if($hasMultipleQuizzes)
                    <div class="mb-4 p-3 rounded bg-red-100 text-red-800 border border-red-300">
                        <strong>{{ __('Data Error:') }}</strong> {{ __('Multiple quizzes are linked to this lesson. Please contact the administrator to resolve this data integrity issue. Only the first quiz will be shown below.') }}
                    </div>
                @endif

                @php
                    // Use only the first quiz if multiple exist
                    $displayQuiz = $hasMultipleQuizzes ? $lessonQuizzes->first() : ($lessonQuiz ?? null);
                @endphp

                @if($displayQuiz)
                    @php
                        $typeEnum = $displayQuiz->type instanceof \App\Enums\QuizType
                            ? $displayQuiz->type
                            : \App\Enums\QuizType::tryFrom((string) $displayQuiz->type);

                        $typeColor = match ($typeEnum?->value) {
                            'practice' => 'blue',
                            'timed' => 'orange',
                            'mock' => 'purple',
                            default => 'zinc',
                        };

                        $typeLabel = $typeEnum?->label()
                            ?? (is_scalar($displayQuiz->type) ? ucfirst((string) $displayQuiz->type) : '-');
                    @endphp

                    <div class="space-y-3">
                        <div class="flex items-start justify-between">
                            <div>
                                <flux:text class="text-sm text-neutral-500">{{ __('Linked to this lesson') }}</flux:text>
                                <flux:heading size="md">{{ $displayQuiz->title }}</flux:heading>
                            </div>
                            <flux:badge :color="$typeColor">{{ $typeLabel }}</flux:badge>
                        </div>

                        <div class="grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <flux:text class="text-xs text-neutral-500">{{ __('Questions') }}</flux:text>
                                <flux:text class="font-semibold">{{ $displayQuiz->question_count }}</flux:text>
                            </div>

                            <div>
                                <flux:text class="text-xs text-neutral-500">{{ __('Attempts') }}</flux:text>
                                <flux:text class="font-semibold">
                                    {{ $displayQuiz->user_stats['total_attempts'] ?? 0 }}
                                    @if($displayQuiz->max_attempts)
                                        / {{ $displayQuiz->max_attempts }}
                                    @endif
                                </flux:text>
                            </div>

                            @if(($displayQuiz->user_stats['best_score'] ?? null) !== null)
                                <div>
                                    <flux:text class="text-xs text-neutral-500">{{ __('Best Score') }}</flux:text>
                                    <flux:text class="font-semibold">
                                        {{ round($displayQuiz->user_stats['best_score'], 1) }}%
                                    </flux:text>
                                </div>
                            @endif

                            @if($displayQuiz->isTimed())
                                <div>
                                    <flux:text class="text-xs text-neutral-500">{{ __('Duration') }}</flux:text>
                                    <flux:text class="font-semibold">{{ $displayQuiz->duration_minutes }} min</flux:text>
                                </div>
                            @endif
                        </div>

                        @if($displayQuiz->can_attempt ?? false)
                            <flux:button href="{{ route('quiz.take', $displayQuiz) }}" variant="primary" class="w-full"
                                icon="play">
                                {{ ($displayQuiz->user_stats['total_attempts'] ?? 0) > 0 ? __('Retake Quiz') : __('Start Quiz') }}
                            </flux:button>
                        @else
                            <flux:button variant="ghost" disabled class="w-full">
                                {{ __('Max Attempts Reached') }}
                            </flux:button>
                        @endif
                    </div>
                @else
                    <flux:text class="text-sm mb-4">
                        {{ __('No quiz is linked to this lesson yet. You can still practice from the quiz library.') }}
                    </flux:text>
                    <flux:button href="{{ route('quizzes.index') }}" wire:navigate variant="primary" class="w-full"
                        icon="clipboard-document-list">
                        {{ __('Browse Quizzes') }}
                    </flux:button>
                @endif
            </div>

            {{-- Help Card --}}
            <div
                class="rounded-xl border-2 border-amber-200 dark:border-amber-800 p-6 bg-gradient-to-br from-amber-50 to-orange-50 dark:from-amber-950/30 dark:to-orange-950/30">
                <div class="flex items-start gap-3 mb-3">
                    <div
                        class="w-10 h-10 rounded-full bg-amber-600 dark:bg-amber-500 flex items-center justify-center flex-shrink-0 text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <flux:heading size="sm" class="text-amber-900 dark:text-amber-100 mb-2">
                            {{ __('Need Help?') }}
                        </flux:heading>
                        <flux:text class="text-sm text-amber-800 dark:text-amber-200">
                            {{ __('If you have questions about this lesson, feel free to reach out to your instructor.') }}
                        </flux:text>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
