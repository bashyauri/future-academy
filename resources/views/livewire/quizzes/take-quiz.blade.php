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
                    @if(!$this->hasAvailableQuestions)
                        <div class="col-span-2 text-center p-4 rounded-lg bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700">
                            <flux:heading size="lg" class="text-red-700 dark:text-red-300">
                                {{ __('This quiz has no questions available. Please contact your instructor or try another quiz.') }}
                            </flux:heading>
                        </div>
                    @else
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
                    @endif
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
                    @if($this->hasAvailableQuestions)
                        <flux:button wire:click="startQuiz" variant="primary" size="base" class="h-12 text-base">
                            {{ __('Start Quiz') }}
                        </flux:button>
                    @endif
                    <flux:button href="{{ route('quizzes.index') }}" variant="ghost">
                        {{ __('Back to Quizzes') }}
                    </flux:button>
                </div>
            </div>
        </div>


    @elseif($quiz->questions->isEmpty())
        <div class="max-w-3xl mx-auto">
            <div class="rounded-xl border border-red-200 dark:border-red-700 p-8 space-y-6 bg-red-50 dark:bg-red-900/30">
                <div class="text-center space-y-4">
                    <flux:heading size="2xl" class="text-red-700 dark:text-red-300">
                        {{ __('This quiz has no questions available. Please contact your instructor or try another quiz.') }}
                    </flux:heading>
                </div>
                <div class="flex gap-4 justify-center mt-6">
                    <flux:button href="{{ route('quizzes.index') }}" variant="ghost">
                        {{ __('Back to Quizzes') }}
                    </flux:button>
                </div>
            </div>
        </div>
    @elseif($showResults && !($noQuestions ?? false))
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

                <div class="border-t border-neutral-200 dark:border-neutral-700 pt-6 mt-6 space-y-4">
                    <flux:heading size="base">{{ __('What would you like to do next?') }}</flux:heading>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        {{-- Back to Lesson --}}
                        @if($quiz->lesson_id)
                            <flux:button href="{{ route('lessons.view', $quiz->lesson_id) }}" variant="outline" class="flex flex-col items-center justify-center h-32 gap-2">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C6.5 6.253 2 10.998 2 17.25S6.5 28 12 28s10-4.745 10-10.75S17.5 6.253 12 6.253z"></path>
                                </svg>
                                <div class="text-center">
                                    <div class="font-medium">{{ __('Back to Lesson') }}</div>
                                    <div class="text-xs text-neutral-500">{{ __('Review lesson content') }}</div>
                                </div>
                            </flux:button>
                        @endif

                        {{-- Retake Quiz Option --}}
                        @if($quiz->canUserAttempt(auth()->user()))
                            <flux:button href="{{ route('quiz.take', $quiz->id) }}" variant="outline" class="flex flex-col items-center justify-center h-32 gap-2">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                <div class="text-center">
                                    <div class="font-medium">{{ __('Retake Quiz') }}</div>
                                    <div class="text-xs text-neutral-500">{{ __('Practice again') }}</div>
                                </div>
                            </flux:button>
                        @endif

                        {{-- Review Answers Option --}}
                        @if($quiz->allow_review)
                            <button @click="document.querySelector('[wire\\\\:target=scrollToReview]')?.scrollIntoView({behavior: 'smooth'})" class="flex flex-col items-center justify-center h-32 gap-2 p-4 rounded-lg border border-neutral-200 dark:border-neutral-700 hover:bg-neutral-50 dark:hover:bg-neutral-800 transition">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                                </svg>
                                <div class="text-center">
                                    <div class="font-medium">{{ __('Review Answers') }}</div>
                                    <div class="text-xs text-neutral-500">{{ __('See detailed review') }}</div>
                                </div>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

    @else
        {{-- Quiz Taking Screen --}}
        <div class="grid lg:grid-cols-4 gap-6" x-data="{
            // Pre-loaded data
            questions: @js($questions),
            shuffledOptions: @js($shuffledOptions),
            answers: @js($answers),

            // Reactive state
            currentQuestionIndex: @entangle('currentQuestionIndex'),
            timeRemaining: {{ $timeRemaining ?? 'null' }},
            autoSaveDebounce: false,

            // Select answer (instant client-side)
            selectAnswer(questionId, optionId) {
                this.answers[questionId] = optionId;
                this.autoSaveDebounce = true;

                // Update server state
                $wire.set('answers.' + questionId, optionId);
                $wire.set('showFeedback.' + questionId, true);
            },

            // Navigation methods
            goToQuestion(index) {
                this.currentQuestionIndex = index;
            },

            nextQuestion() {
                if (this.currentQuestionIndex < this.questions.length - 1) {
                    this.currentQuestionIndex++;
                }
            },

            previousQuestion() {
                if (this.currentQuestionIndex > 0) {
                    this.currentQuestionIndex--;
                }
            },

            // Computed properties
            getCurrentQuestion() {
                return this.questions[this.currentQuestionIndex];
            },

            isAnswered(questionId) {
                return this.answers[questionId] !== undefined;
            },

            getAnsweredCount() {
                return Object.keys(this.answers).length;
            },

            // Autosave every 10 seconds (cache-only)
            init() {
                setInterval(() => {
                    if (this.autoSaveDebounce) {
                        $wire.call('autoSaveAnswers');
                        this.autoSaveDebounce = false;
                    }
                }, 10000);
            }
        }">
            {{-- Question Navigation Sidebar --}}
            <div class="lg:col-span-1">
                <div class="sticky top-4 space-y-4">
                    {{-- Timer --}}
                    @if($quiz->isTimed() && $timeRemaining !== null)
                        <div
                            class="p-4 rounded-xl border border-neutral-200 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-800">
                            <flux:text class="text-sm text-center text-neutral-500 mb-2">{{ __('Time Remaining') }}</flux:text>
                            <div id="timer" class="text-3xl font-bold text-center" wire:ignore x-data="{
                                timeLeft: {{ $timeRemaining }},
                                timer: null,
                                serverSyncTimer: null,
                                init() {
                                    this.startTimer();
                                    // Sync with server every 5 seconds to handle browser close/refresh
                                    this.serverSyncTimer = setInterval(() => {
                                        $wire.dispatch('update-timer');
                                    }, 5000);
                                },
                                startTimer() {
                                    this.stopTimer();
                                    this.timer = setInterval(() => {
                                        if (this.timeLeft > 0) {
                                            this.timeLeft--;
                                        }
                                    }, 1000);
                                },
                                stopTimer() {
                                    if (this.timer) {
                                        clearInterval(this.timer);
                                        this.timer = null;
                                    }
                                    if (this.serverSyncTimer) {
                                        clearInterval(this.serverSyncTimer);
                                        this.serverSyncTimer = null;
                                    }
                                },
                                formatTime() {
                                    let minutes = Math.floor(this.timeLeft / 60);
                                    let seconds = this.timeLeft % 60;
                                    return minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
                                }
                            }"
                            @update-timer-value.window="if ($event.detail && typeof $event.detail.value === 'number') { timeLeft = Math.min(timeLeft, $event.detail.value); }"
                            x-text="formatTime()"
                                :class="timeLeft < 300 ? (timeLeft < 60 ? 'text-red-600 dark:text-red-400 animate-pulse' : 'text-orange-600 dark:text-orange-400') : 'text-neutral-900 dark:text-neutral-100'">
                            </div>
                        </div>
                    @endif

                    {{-- Question Grid --}}
                    <div class="p-4 rounded-xl border border-neutral-200 dark:border-neutral-700">
                        <flux:text class="text-sm mb-3"><span x-text="'{{ __('Questions') }} (' + getAnsweredCount() + '/{{ $totalQuestions }})'"></span>
                        </flux:text>
                        <div class="grid grid-cols-5 gap-2">
                            <template x-for="(question, index) in questions" :key="question.id">
                                <button @click="goToQuestion(index)"
                                    class="aspect-square rounded-lg text-sm font-medium transition"
                                    :class="{
                                        'bg-amber-600 text-white': currentQuestionIndex === index,
                                        'bg-green-100 dark:bg-green-900 text-green-900 dark:text-green-100': currentQuestionIndex !== index && isAnswered(question.id),
                                        'bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-400 hover:bg-neutral-200 dark:hover:bg-neutral-700': currentQuestionIndex !== index && !isAnswered(question.id)
                                    }"
                                    x-text="index + 1">
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Question Content --}}
            <div class="lg:col-span-3 space-y-6">
                <template x-if="getCurrentQuestion()">
                    <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-6 space-y-6">
                        {{-- Question Header --}}
                        <div class="flex items-start justify-between">
                            <flux:heading size="lg">
                                <span x-text="'{{ __('Question') }} ' + (currentQuestionIndex + 1) + ' {{ __('of') }} ' + questions.length"></span>
                            </flux:heading>
                            <flux:badge color="blue" x-text="getCurrentQuestion().difficulty.charAt(0).toUpperCase() + getCurrentQuestion().difficulty.slice(1)"></flux:badge>
                        </div>

                        {{-- Question Text --}}
                        <div class="prose dark:prose-invert max-w-none">
                            <flux:text class="text-lg" x-text="getCurrentQuestion().question_text"></flux:text>

                            <template x-if="getCurrentQuestion().question_image">
                                <img loading="lazy" :src="'{{ url('storage') }}/' + getCurrentQuestion().question_image" alt="{{ __('Question image') }}"
                                    class="mt-4 rounded-lg max-w-lg">
                            </template>
                        </div>

                        {{-- Answer Options --}}
                        <div class="space-y-3">
                            <template x-for="option in shuffledOptions[getCurrentQuestion().id]" :key="option.id">
                                <button @click="!isAnswered(getCurrentQuestion().id) && selectAnswer(getCurrentQuestion().id, option.id)"
                                    :disabled="isAnswered(getCurrentQuestion().id)"
                                    class="w-full flex items-start gap-4 p-4 rounded-lg border-2 transition text-left"
                                    :class="{
                                        'border-green-500 bg-green-50 dark:bg-green-950/30': isAnswered(getCurrentQuestion().id) && answers[getCurrentQuestion().id] === option.id && option.is_correct,
                                        'border-red-500 bg-red-50 dark:bg-red-950/30': isAnswered(getCurrentQuestion().id) && answers[getCurrentQuestion().id] === option.id && !option.is_correct,
                                        'border-green-400 bg-green-50 dark:bg-green-950/20': isAnswered(getCurrentQuestion().id) && answers[getCurrentQuestion().id] !== option.id && option.is_correct,
                                        'border-neutral-200 dark:border-neutral-700 opacity-60': isAnswered(getCurrentQuestion().id) && answers[getCurrentQuestion().id] !== option.id && !option.is_correct,
                                        'border-neutral-200 dark:border-neutral-700 hover:border-neutral-300 dark:hover:border-neutral-600 cursor-pointer': !isAnswered(getCurrentQuestion().id),
                                        'cursor-default': isAnswered(getCurrentQuestion().id)
                                    }">

                                    {{-- Icon or checkbox --}}
                                    <template x-if="isAnswered(getCurrentQuestion().id) && answers[getCurrentQuestion().id] === option.id && option.is_correct">
                                        <span class="flex-shrink-0 text-2xl font-bold text-green-600 dark:text-green-400 mt-0.5">✓</span>
                                    </template>
                                    <template x-if="isAnswered(getCurrentQuestion().id) && answers[getCurrentQuestion().id] === option.id && !option.is_correct">
                                        <span class="flex-shrink-0 text-2xl font-bold text-red-600 dark:text-red-400 mt-0.5">✗</span>
                                    </template>
                                    <template x-if="isAnswered(getCurrentQuestion().id) && answers[getCurrentQuestion().id] !== option.id && option.is_correct">
                                        <span class="flex-shrink-0 text-2xl font-bold text-green-600 dark:text-green-400 mt-0.5">✓</span>
                                    </template>
                                    <template x-if="isAnswered(getCurrentQuestion().id) && answers[getCurrentQuestion().id] !== option.id && !option.is_correct">
                                        <span class="flex-shrink-0 w-6 h-6 rounded-full border-2 border-neutral-300 dark:border-neutral-600 mt-0.5 opacity-60"></span>
                                    </template>
                                    <template x-if="!isAnswered(getCurrentQuestion().id)">
                                        <span class="flex-shrink-0 w-6 h-6 rounded-full border-2 border-neutral-300 dark:border-neutral-600 mt-0.5"></span>
                                    </template>

                                    <div class="flex-1">
                                        <flux:text class="font-medium" x-text="option.option_text"></flux:text>
                                        <template x-if="option.option_image">
                                            <img loading="lazy" :src="'{{ url('storage') }}/' + option.option_image" alt="{{ __('Option image') }}"
                                                class="mt-2 rounded max-w-xs border border-neutral-200 dark:border-neutral-700">
                                        </template>
                                    </div>
                                </button>
                            </template>
                        </div>

                        {{-- Explanation (shown after answering) --}}
                        <template x-if="isAnswered(getCurrentQuestion().id) && getCurrentQuestion().explanation">
                            <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-950/20 border-2 border-blue-200 dark:border-blue-800 rounded-lg">
                                <div class="flex items-start gap-3">
                                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-blue-600 dark:bg-blue-500 flex items-center justify-center text-white">
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
                                        <flux:text class="text-blue-800 dark:text-blue-300 leading-relaxed" x-text="getCurrentQuestion().explanation">
                                        </flux:text>
                                    </div>
                                </div>
                            </div>
                        </template>

                        {{-- Navigation Buttons --}}
                        <div class="flex items-center justify-between pt-4 border-t border-neutral-200 dark:border-neutral-700">
                            <div class="flex gap-3">
                                <flux:button wire:click="exitQuiz" variant="ghost"
                                    color="danger"
                                    wire:confirm="{{ __('Exit the quiz? Your in-progress answers will be saved, but the attempt will be marked as cancelled.') }}">
                                    {{ __('Exit Quiz') }}
                                </flux:button>

                                <button @click.debounce.200ms="previousQuestion()"
                                    :disabled="currentQuestionIndex === 0"
                                    class="px-4 py-2 rounded-lg border border-neutral-300 dark:border-neutral-600 hover:bg-neutral-100 dark:hover:bg-neutral-800 disabled:opacity-50 disabled:cursor-not-allowed">
                                    {{ __('Previous') }}
                                </button>
                            </div>

                            <div class="flex gap-3">
                                <flux:button x-show="currentQuestionIndex === questions.length - 1" wire:click="submitQuiz" variant="primary"
                                    wire:confirm="{{ __('Are you sure you want to submit? You cannot change your answers after submission.') }}">
                                    {{ __('Submit Quiz') }}
                                </flux:button>
                                <button x-show="currentQuestionIndex < questions.length - 1"
                                    @click.debounce.200ms="nextQuestion()"
                                    class="px-4 py-2 rounded-lg bg-amber-600 hover:bg-amber-700 text-white">
                                    {{ __('Next') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    @endif
</div>
