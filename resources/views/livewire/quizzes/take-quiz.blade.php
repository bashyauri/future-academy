<div class="w-full space-y-6">
    @if(!$attempt)
        {{-- Quiz Start Screen --}}
        <div class="max-w-3xl mx-auto">
            <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-8 space-y-6">
                <div class="text-center space-y-4">
                    <flux:heading size="2xl">{{ $quiz->title }}</flux:heading>

                    @if($quiz->description)
                        <flux:text class="text-lg">{{ $quiz->description }}</flux:text>
                    @endif
                </div>

                <div class="grid grid-cols-2 gap-4 py-6">
                    <div class="text-center p-4 rounded-lg bg-neutral-50 dark:bg-neutral-800">
                        <flux:text class="text-sm text-neutral-500">{{ __('Questions') }}</flux:text>
                        <flux:heading size="lg">{{ $quiz->question_count }}</flux:heading>
                    </div>

                    @if($quiz->isTimed())
                        <div class="text-center p-4 rounded-lg bg-neutral-50 dark:bg-neutral-800">
                            <flux:text class="text-sm text-neutral-500">{{ __('Duration') }}</flux:text>
                            <flux:heading size="lg">{{ $quiz->duration_minutes }} min</flux:heading>
                        </div>
                    @endif

                    <div class="text-center p-4 rounded-lg bg-neutral-50 dark:bg-neutral-800">
                        <flux:text class="text-sm text-neutral-500">{{ __('Passing Score') }}</flux:text>
                        <flux:heading size="lg">{{ $quiz->passing_score }}%</flux:heading>
                    </div>

                    <div class="text-center p-4 rounded-lg bg-neutral-50 dark:bg-neutral-800">
                        @php
                            $typeEnum = $quiz->type instanceof \App\Enums\QuizType ? $quiz->type : \App\Enums\QuizType::tryFrom((string) $quiz->type);
                            $typeLabel = $typeEnum?->label() ?? (is_scalar($quiz->type) ? ucfirst((string) $quiz->type) : '-');
                        @endphp
                        <flux:text class="text-sm text-neutral-500">{{ __('Quiz Type') }}</flux:text>
                        <flux:heading size="lg">{{ $typeLabel }}</flux:heading>
                    </div>
                </div>

                <div
                    class="space-y-3 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 rounded-lg p-4">
                    <flux:text class="font-semibold text-amber-900 dark:text-amber-200">{{ __('Instructions:') }}
                    </flux:text>
                    <ul class="space-y-2 text-sm text-amber-800 dark:text-amber-300">
                        <li>• {{ __('Read each question carefully before selecting your answer') }}</li>
                        <li>• {{ __('You can navigate between questions using the navigation panel') }}</li>
                        @if($quiz->isTimed())
                            <li>• {{ __('The quiz will auto-submit when time expires') }}</li>
                        @endif
                        @if($quiz->allow_review)
                            <li>• {{ __('You can review your answers after submission') }}</li>
                        @endif
                        <li>• {{ __('Click Submit when you\'re ready to finish') }}</li>
                    </ul>
                </div>

                <div class="flex gap-4 justify-center">
                    <flux:button wire:click="startQuiz" wire:target="startQuiz" wire:loading.attr="disabled"
                        wire:loading.class="opacity-75 cursor-wait" variant="primary" size="base" class="h-12 text-base">
                        <span wire:loading.remove wire:target="startQuiz">{{ __('Start Quiz') }}</span>
                        <span wire:loading wire:target="startQuiz" class="flex items-center gap-2">
                            <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                                </circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            {{ __('Loading...') }}
                        </span>
                    </flux:button>
                    <flux:button href="{{ route('quizzes.index') }}" variant="ghost">
                        {{ __('Back to Quizzes') }}
                    </flux:button>
                </div>
            </div>
        </div>

    @elseif($showResults)
        {{-- Results Screen --}}
        <div class="max-w-4xl mx-auto">
            <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-8 space-y-6">
                <div class="text-center space-y-4">
                    <flux:heading size="2xl">
                        {{ $attempt->passed ? __('Congratulations!') : __('Quiz Completed') }}
                    </flux:heading>

                    <div class="text-6xl font-bold"
                        style="color: {{ $attempt->passed ? 'rgb(34 197 94)' : 'rgb(239 68 68)' }}">
                        {{ round($attempt->score_percentage, 1) }}%
                    </div>

                    <flux:badge :color="$attempt->passed ? 'green' : 'red'" size="lg">
                        {{ $attempt->passed ? __('PASSED') : __('FAILED') }}
                    </flux:badge>
                </div>

                <div class="grid grid-cols-3 gap-4 py-6">
                    <div class="text-center p-4 rounded-lg bg-neutral-50 dark:bg-neutral-800">
                        <flux:text class="text-sm text-neutral-500">{{ __('Correct') }}</flux:text>
                        <flux:heading size="lg" class="text-green-600 dark:text-green-400">
                            {{ $attempt->correct_answers }}/{{ $attempt->total_questions }}
                        </flux:heading>
                    </div>

                    <div class="text-center p-4 rounded-lg bg-neutral-50 dark:bg-neutral-800">
                        <flux:text class="text-sm text-neutral-500">{{ __('Time Spent') }}</flux:text>
                        <flux:heading size="lg">
                            {{ gmdate('i:s', $attempt->time_spent_seconds) }}
                        </flux:heading>
                    </div>

                    <div class="text-center p-4 rounded-lg bg-neutral-50 dark:bg-neutral-800">
                        <flux:text class="text-sm text-neutral-500">{{ __('Passing Score') }}</flux:text>
                        <flux:heading size="lg">{{ $quiz->passing_score }}%</flux:heading>
                    </div>
                </div>

                @if($quiz->allow_review && $quiz->show_answers_after_submit)
                    <div class="space-y-4 border-t border-neutral-200 dark:border-neutral-700 pt-6">
                        <flux:heading size="lg" class="mb-6">{{ __('Review Your Answers') }}</flux:heading>

                        @foreach($attempt->answers()->with(['question.options', 'question.subject', 'question.topic'])->get() as $answer)
                            <div
                                class="rounded-lg border overflow-hidden {{ $answer->is_correct ? 'border-green-200 dark:border-green-800' : 'border-red-200 dark:border-red-800' }}">
                                {{-- Question Header --}}
                                <div
                                    class="p-4 {{ $answer->is_correct ? 'bg-green-50 dark:bg-green-950/30' : 'bg-red-50 dark:bg-red-950/30' }}">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-3 mb-2">
                                                <flux:badge variant="outline" size="sm">Q{{ $loop->iteration }}</flux:badge>
                                                @if($answer->question->subject)
                                                    <flux:badge color="blue" size="sm">{{ $answer->question->subject->name }}
                                                    </flux:badge>
                                                @endif
                                                @if($answer->question->topic)
                                                    <flux:badge color="neutral" size="sm">{{ $answer->question->topic->name }}
                                                    </flux:badge>
                                                @endif
                                                <flux:badge
                                                    :color="match($answer->question->difficulty) { 'easy' => 'green', 'medium' => 'yellow', 'hard' => 'red', default => 'neutral' }"
                                                    size="sm">
                                                    {{ ucfirst($answer->question->difficulty) }}
                                                </flux:badge>
                                            </div>
                                            <flux:text class="text-base font-medium">
                                                {{ $answer->question->question_text }}
                                            </flux:text>
                                        </div>
                                        <div class="flex-shrink-0">
                                            <flux:badge :color="$answer->is_correct ? 'green' : 'red'" size="lg">
                                                {{ $answer->is_correct ? '✓ Correct' : '✗ Incorrect' }}
                                            </flux:badge>
                                        </div>
                                    </div>

                                    @if($answer->question->question_image)
                                        <div class="mt-3">
                                            <img src="{{ Storage::url($answer->question->question_image) }}" alt="Question image"
                                                class="rounded-lg max-w-md border border-neutral-200 dark:border-neutral-700">
                                        </div>
                                    @endif
                                </div>

                                {{-- Answer Options --}}
                                <div class="p-4 bg-white dark:bg-neutral-900 space-y-2">
                                    @foreach($answer->question->options as $option)
                                        @php
                                            $isUserAnswer = $option->id === $answer->option_id;
                                            $isCorrectAnswer = $option->is_correct;

                                            // Determine styling
                                            if ($isCorrectAnswer && $isUserAnswer) {
                                                // User selected correct answer
                                                $bgColor = 'bg-green-100 dark:bg-green-900/30 border-green-500';
                                                $icon = '✓';
                                                $iconColor = 'text-green-600 dark:text-green-400';
                                                $label = 'Your answer (Correct)';
                                                $labelColor = 'text-green-700 dark:text-green-300';
                                            } elseif ($isCorrectAnswer && !$isUserAnswer) {
                                                // Correct answer but user didn't select it
                                                $bgColor = 'bg-green-50 dark:bg-green-950/20 border-green-400';
                                                $icon = '✓';
                                                $iconColor = 'text-green-600 dark:text-green-400';
                                                $label = 'Correct answer';
                                                $labelColor = 'text-green-600 dark:text-green-400';
                                            } elseif (!$isCorrectAnswer && $isUserAnswer) {
                                                // User selected wrong answer
                                                $bgColor = 'bg-red-100 dark:bg-red-900/30 border-red-500';
                                                $icon = '✗';
                                                $iconColor = 'text-red-600 dark:text-red-400';
                                                $label = 'Your answer (Incorrect)';
                                                $labelColor = 'text-red-700 dark:text-red-300';
                                            } else {
                                                // Not selected, not correct
                                                $bgColor = 'bg-neutral-50 dark:bg-neutral-800 border-neutral-200 dark:border-neutral-700';
                                                $icon = '';
                                                $iconColor = '';
                                                $label = '';
                                                $labelColor = '';
                                            }
                                        @endphp

                                        <div class="flex items-start gap-3 p-3 rounded-lg border-2 {{ $bgColor }}">
                                            @if($icon)
                                                <span class="flex-shrink-0 text-xl font-bold {{ $iconColor }}">{{ $icon }}</span>
                                            @else
                                                <span class="flex-shrink-0 w-6"></span>
                                            @endif

                                            <div class="flex-1">
                                                <flux:text class="font-medium">{{ $option->option_text }}</flux:text>
                                                @if($option->option_image)
                                                    <img src="{{ Storage::url($option->option_image) }}" alt="Option image"
                                                        class="mt-2 rounded max-w-xs border border-neutral-200 dark:border-neutral-700">
                                                @endif
                                                @if($label)
                                                    <flux:text class="text-sm mt-1 {{ $labelColor }} font-semibold">
                                                        {{ __($label) }}
                                                    </flux:text>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                {{-- Explanation Section --}}
                                @if($quiz->show_explanations && $answer->question->explanation)
                                    <div class="p-4 bg-blue-50 dark:bg-blue-950/20 border-t-2 border-blue-200 dark:border-blue-800">
                                        <div class="flex items-start gap-3">
                                            <div
                                                class="flex-shrink-0 w-10 h-10 rounded-full bg-blue-600 dark:bg-blue-500 flex items-center justify-center text-white">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20"
                                                    fill="currentColor">
                                                    <path fill-rule="evenodd"
                                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                            <div class="flex-1">
                                                <flux:heading size="sm" class="text-blue-900 dark:text-blue-200 mb-2">
                                                    {{ __('Explanation') }}
                                                </flux:heading>
                                                <flux:text class="text-blue-800 dark:text-blue-300 leading-relaxed">
                                                    {{ $answer->question->explanation }}
                                                </flux:text>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="flex gap-4 justify-center pt-4">
                    @if($quiz->canUserAttempt(auth()->user()))
                        <flux:button wire:click="$refresh" wire:target="$refresh" wire:loading.attr="disabled"
                            wire:loading.class="opacity-75 cursor-wait" variant="primary"
                            href="{{ route('quiz.take', $quiz->id) }}">
                            <span wire:loading.remove wire:target="$refresh">{{ __('Retake Quiz') }}</span>
                            <span wire:loading wire:target="$refresh" class="flex items-center gap-2">
                                <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                                    </circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                {{ __('Loading...') }}
                            </span>
                        </flux:button>
                    @endif
                    <flux:button href="{{ route('quizzes.index') }}" variant="ghost">
                        {{ __('Back to Quizzes') }}
                    </flux:button>
                </div>
            </div>
        </div>

    @else
        {{-- Quiz Taking Screen --}}
        <div class="grid lg:grid-cols-4 gap-6">
            {{-- Question Navigation Sidebar --}}
            <div class="lg:col-span-1">
                <div class="sticky top-4 space-y-4">
                    {{-- Timer --}}
                    @if($quiz->isTimed() && $timeRemaining)
                        <div
                            class="p-4 rounded-xl border border-neutral-200 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-800">
                            <flux:text class="text-sm text-center text-neutral-500 mb-2">{{ __('Time Remaining') }}</flux:text>
                            <div id="timer" class="text-3xl font-bold text-center" x-data="{
                                                                                     timeLeft: {{ $timeRemaining }},
                                                                                     timer: null,
                                                                                     init() {
                                                                                         this.timer = setInterval(() => {
                                                                                             this.timeLeft--;
                                                                                             if (this.timeLeft <= 0) {
                                                                                                 clearInterval(this.timer);
                                                                                                 $wire.dispatch('timer-expired');
                                                                                             }
                                                                                         }, 1000);
                                                                                     },
                                                                                     formatTime() {
                                                                                         let minutes = Math.floor(this.timeLeft / 60);
                                                                                         let seconds = this.timeLeft % 60;
                                                                                         return minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
                                                                                     }
                                                                                 }" x-text="formatTime()"
                                :class="timeLeft < 60 ? 'text-red-600 dark:text-red-400' : 'text-neutral-900 dark:text-neutral-100'">
                            </div>
                        </div>
                    @endif

                    {{-- Question Grid --}}
                    <div class="p-4 rounded-xl border border-neutral-200 dark:border-neutral-700">
                        <flux:text class="text-sm mb-3">{{ __('Questions') }} ({{ $answeredCount }}/{{ $totalQuestions }})
                        </flux:text>
                        <div class="grid grid-cols-5 gap-2">
                            @foreach($questions as $index => $question)
                                            <button wire:click="goToQuestion({{ $index }})"
                                                class="aspect-square rounded-lg text-sm font-medium transition
                                                                                                                                                                                    {{ $currentQuestionIndex === $index
                                ? 'bg-amber-600 text-white'
                                : (isset($answers[$question->id])
                                    ? 'bg-green-100 dark:bg-green-900 text-green-900 dark:text-green-100'
                                    : 'bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-400 hover:bg-neutral-200 dark:hover:bg-neutral-700')
                                                                                                                                                                                    }}">
                                                {{ $index + 1 }}
                                            </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- Question Content --}}
            <div class="lg:col-span-3 space-y-6">
                @if($currentQuestion)
                    <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-6 space-y-6">
                        {{-- Question Header --}}
                        <div class="flex items-start justify-between">
                            <flux:heading size="lg">{{ __('Question') }} {{ $currentQuestionIndex + 1 }} {{ __('of') }}
                                {{ $totalQuestions }}
                            </flux:heading>
                            <flux:badge color="blue">{{ ucfirst($currentQuestion->difficulty) }}</flux:badge>
                        </div>

                        {{-- Question Text --}}
                        <div class="prose dark:prose-invert max-w-none">
                            <flux:text class="text-lg">{{ $currentQuestion->question_text }}</flux:text>

                            @if($currentQuestion->question_image)
                                <img src="{{ Storage::url($currentQuestion->question_image) }}" alt="{{ __('Question image') }}"
                                    class="mt-4 rounded-lg max-w-lg">
                            @endif
                        </div>

                        {{-- Answer Options --}}
                        <div class="space-y-3">
                            @php
                                $answered = isset($answers[$currentQuestion->id]);
                                $userAnswerId = $answers[$currentQuestion->id] ?? null;
                                $correctOption = collect($shuffledOptions[$currentQuestion->id] ?? $currentQuestion->options)->firstWhere('is_correct', true);
                            @endphp

                            @foreach($shuffledOptions[$currentQuestion->id] ?? $currentQuestion->options as $option)
                                @php
                                    $isUserAnswer = $answered && $userAnswerId == $option->id;
                                    $isCorrect = $option->is_correct;

                                    // Determine styling based on answer state
                                    if ($answered) {
                                        if ($isUserAnswer && $isCorrect) {
                                            $borderColor = 'border-green-500 bg-green-50 dark:bg-green-950/30';
                                            $icon = '✓';
                                            $iconColor = 'text-green-600 dark:text-green-400';
                                        } elseif ($isUserAnswer && !$isCorrect) {
                                            $borderColor = 'border-red-500 bg-red-50 dark:bg-red-950/30';
                                            $icon = '✗';
                                            $iconColor = 'text-red-600 dark:text-red-400';
                                        } elseif ($isCorrect) {
                                            $borderColor = 'border-green-400 bg-green-50 dark:bg-green-950/20';
                                            $icon = '✓';
                                            $iconColor = 'text-green-600 dark:text-green-400';
                                        } else {
                                            $borderColor = 'border-neutral-200 dark:border-neutral-700 opacity-60';
                                            $icon = '';
                                            $iconColor = '';
                                        }
                                    } else {
                                        $borderColor = 'border-neutral-200 dark:border-neutral-700 hover:border-neutral-300 dark:hover:border-neutral-600';
                                        $icon = '';
                                        $iconColor = '';
                                    }
                                @endphp

                                <button wire:click="answerQuestion({{ $currentQuestion->id }}, {{ $option->id }})"
                                    wire:loading.attr="disabled"
                                    wire:target="answerQuestion"
                                    @if($answered) disabled @endif
                                    class="w-full flex items-start gap-4 p-4 rounded-lg border-2 transition {{ $borderColor }} {{ $answered ? 'cursor-default' : 'cursor-pointer' }} text-left">

                                    @if($icon)
                                        <span class="flex-shrink-0 text-2xl font-bold {{ $iconColor }} mt-0.5">{{ $icon }}</span>
                                    @else
                                        <span class="flex-shrink-0 w-6 h-6 rounded-full border-2 border-neutral-300 dark:border-neutral-600 mt-0.5">
                                            <!-- Loading spinner when clicking -->
                                            <svg wire:loading wire:target="answerQuestion" class="animate-spin h-5 w-5 text-neutral-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                        </span>
                                    @endif

                                    <div class="flex-1">
                                        <flux:text class="font-medium">{{ $option->option_text }}</flux:text>
                                        @if($option->option_image)
                                            <img src="{{ Storage::url($option->option_image) }}" alt="{{ __('Option image') }}"
                                                class="mt-2 rounded max-w-xs border border-neutral-200 dark:border-neutral-700">
                                        @endif
                                    </div>
                                </button>
                            @endforeach
                        </div>

                        {{-- Explanation (shown after answering) --}}
                        @if($answered && $currentQuestion->explanation)
                            <div
                                class="mt-6 p-4 bg-blue-50 dark:bg-blue-950/20 border-2 border-blue-200 dark:border-blue-800 rounded-lg">
                                <div class="flex items-start gap-3">
                                    <div
                                        class="flex-shrink-0 w-10 h-10 rounded-full bg-blue-600 dark:bg-blue-500 flex items-center justify-center text-white">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20"
                                            fill="currentColor">
                                            <path fill-rule="evenodd"
                                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <flux:heading size="sm" class="text-blue-900 dark:text-blue-200 mb-2">
                                            {{ __('Explanation') }}
                                        </flux:heading>
                                        <flux:text class="text-blue-800 dark:text-blue-300 leading-relaxed">
                                            {{ $currentQuestion->explanation }}
                                        </flux:text>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Navigation Buttons --}}
                        <div class="flex items-center justify-between pt-4 border-t border-neutral-200 dark:border-neutral-700">
                            <div class="flex gap-3">
                                <flux:button wire:click="exitQuiz" wire:target="exitQuiz"
                                    wire:loading.attr="disabled" wire:loading.class="opacity-75 cursor-wait" variant="ghost"
                                    color="danger"
                                    wire:confirm="{{ __('Exit the quiz? Your in-progress answers will be saved, but the attempt will be marked as cancelled.') }}">
                                    <span wire:loading.remove wire:target="exitQuiz">{{ __('Exit Quiz') }}</span>
                                    <span wire:loading wire:target="exitQuiz" class="flex items-center gap-2">
                                        <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                                            viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                            </path>
                                        </svg>
                                        {{ __('Exiting...') }}
                                    </span>
                                </flux:button>

                                <flux:button wire:click="previousQuestion" wire:target="previousQuestion"
                                    wire:loading.attr="disabled" wire:loading.class="opacity-75 cursor-wait" variant="ghost"
                                    :disabled="$currentQuestionIndex === 0">
                                    <span wire:loading.remove wire:target="previousQuestion">{{ __('Previous') }}</span>
                                    <span wire:loading wire:target="previousQuestion" class="flex items-center gap-2">
                                        <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                                            viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                            </path>
                                        </svg>
                                        {{ __('Loading...') }}
                                    </span>
                                </flux:button>
                            </div>

                            <div class="flex gap-3">
                                @if($currentQuestionIndex === $totalQuestions - 1)
                                    <flux:button wire:click="submitQuiz" wire:target="submitQuiz" wire:loading.attr="disabled"
                                        wire:loading.class="opacity-75 cursor-wait" variant="primary"
                                        wire:confirm="{{ __('Are you sure you want to submit? You cannot change your answers after submission.') }}">
                                        <span wire:loading.remove wire:target="submitQuiz">{{ __('Submit Quiz') }}</span>
                                        <span wire:loading wire:target="submitQuiz" class="flex items-center gap-2">
                                            <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                                                viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                    stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor"
                                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                </path>
                                            </svg>
                                            {{ __('Submitting...') }}
                                        </span>
                                    </flux:button>
                                @else
                                    <flux:button wire:click="nextQuestion" wire:target="nextQuestion" wire:loading.attr="disabled"
                                        wire:loading.class="opacity-75 cursor-wait" variant="primary">
                                        <span wire:loading.remove wire:target="nextQuestion">{{ __('Next') }}</span>
                                        <span wire:loading wire:target="nextQuestion" class="flex items-center gap-2">
                                            <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                                                viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                    stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor"
                                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                </path>
                                            </svg>
                                            {{ __('Loading...') }}
                                        </span>
                                    </flux:button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
