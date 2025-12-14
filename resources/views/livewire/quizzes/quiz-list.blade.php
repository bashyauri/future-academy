<div class="w-full space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Available Quizzes') }}</flux:heading>
            <flux:subheading>{{ __('Choose a quiz to test your knowledge') }}</flux:subheading>
        </div>
    </div>

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <flux:input wire:model.live.debounce.300ms="search" type="search" :placeholder="__('Search quizzes...')"
            class="sm:w-96" />

        <flux:select wire:model.live="typeFilter" class="sm:w-48">
            <option value="all">{{ __('All Types') }}</option>
            <option value="practice">{{ __('Practice') }}</option>
            <option value="timed">{{ __('Timed') }}</option>
            <option value="mock">{{ __('Mock Exam') }}</option>
        </flux:select>
    </div>

    @if($quizzes->isEmpty())
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-12 text-center">
            <flux:text>{{ __('No quizzes available at the moment.') }}</flux:text>
        </div>
    @else
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach($quizzes as $quiz)
                <div
                    class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-6 space-y-4 hover:border-neutral-300 dark:hover:border-neutral-600 transition">
                    <div class="space-y-2">
                        <div class="flex items-start justify-between gap-2">
                            @php
                                $typeEnum = $quiz->type instanceof \App\Enums\QuizType
                                    ? $quiz->type
                                    : \App\Enums\QuizType::tryFrom((string) $quiz->type);

                                $typeColor = match ($typeEnum?->value) {
                                    'practice' => 'blue',
                                    'timed' => 'orange',
                                    'mock' => 'purple',
                                    default => 'zinc',
                                };

                                $typeLabel = $typeEnum?->label()
                                    ?? (is_scalar($quiz->type) ? ucfirst((string) $quiz->type) : '-');
                            @endphp

                            <flux:heading size="lg">{{ $quiz->title }}</flux:heading>
                            <flux:badge :color="$typeColor">
                                {{ $typeLabel }}
                            </flux:badge>
                        </div>

                        @if($quiz->description)
                            <flux:text class="text-sm line-clamp-2">{{ $quiz->description }}</flux:text>
                        @endif
                    </div>

                    <div class="space-y-2 text-sm">
                        <div class="flex items-center justify-between">
                            <flux:text class="text-xs">{{ __('Questions') }}</flux:text>
                            <flux:text class="font-medium">{{ $quiz->question_count }}</flux:text>
                        </div>

                        @if($quiz->isTimed())
                            <div class="flex items-center justify-between">
                                <flux:text class="text-xs">{{ __('Duration') }}</flux:text>
                                <flux:text class="font-medium">{{ $quiz->duration_minutes }} min</flux:text>
                            </div>
                        @endif

                        <div class="flex items-center justify-between">
                            <flux:text class="text-xs">{{ __('Passing Score') }}</flux:text>
                            <flux:text class="font-medium">{{ $quiz->passing_score }}%</flux:text>
                        </div>

                        @if($quiz->max_attempts)
                            <div class="flex items-center justify-between">
                                <flux:text class="text-xs">{{ __('Attempts') }}</flux:text>
                                <flux:text class="font-medium">{{ $quiz->user_stats['total_attempts'] }}/{{ $quiz->max_attempts }}
                                </flux:text>
                            </div>
                        @endif
                    </div>

                    @if($quiz->user_stats['total_attempts'] > 0)
                        <div class="pt-3 border-t border-neutral-200 dark:border-neutral-700">
                            <div class="flex items-center justify-between text-sm">
                                <flux:text class="text-xs">{{ __('Best Score') }}</flux:text>
                                <flux:badge :color="$quiz->user_stats['best_score'] >= $quiz->passing_score ? 'green' : 'red'">
                                    {{ round($quiz->user_stats['best_score'], 1) }}%
                                </flux:badge>
                            </div>
                        </div>
                    @endif

                    <div class="pt-2">
                        @if($quiz->can_attempt)
                            <flux:button wire:click="startQuiz({{ $quiz->id }})" wire:target="startQuiz({{ $quiz->id }})"
                                wire:loading.attr="disabled" wire:loading.class="opacity-75 cursor-wait" variant="primary"
                                class="w-full">
                                {{ $quiz->user_stats['total_attempts'] > 0 ? __('Retake Quiz') : __('Start Quiz') }}
                            </flux:button>
                        @else
                            <flux:button variant="ghost" disabled class="w-full">
                                {{ __('Max Attempts Reached') }}
                            </flux:button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
