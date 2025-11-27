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
                        <flux:text class="text-sm text-neutral-500">{{ __('Quiz Type') }}</flux:text>
                        <flux:heading size="lg">{{ ucfirst($quiz->type) }}</flux:heading>
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
                    <flux:button wire:click="startQuiz" variant="primary" size="lg">
                        {{ __('Start Quiz') }}
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
                        <flux:heading size="lg">{{ __('Review Your Answers') }}</flux:heading>

                        @foreach($attempt->answers()->with(['question.options'])->get() as $answer)
                            <div
                                class="p-4 rounded-lg border {{ $answer->is_correct ? 'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-950/30' : 'border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-950/30' }}">
                                <div class="space-y-3">
                                    <div class="flex items-start justify-between">
                                        <flux:text class="font-medium">{{ $loop->iteration }}.
                                            {{ $answer->question->question_text }}
                                        </flux:text>
                                        <flux:badge :color="$answer->is_correct ? 'green' : 'red'">
                                            {{ $answer->is_correct ? __('Correct') : __('Wrong') }}
                                        </flux:badge>
                                    </div>

                                    <div class="space-y-2">
                                        @foreach($answer->question->options as $option)
                                            <div class="flex items-center gap-2 text-sm">
                                                @if($option->id === $answer->option_id)
                                                    <span class="font-semibold">→ {{ __('Your answer:') }}</span>
                                                @endif
                                                @if($option->is_correct)
                                                    <span class="text-green-600 dark:text-green-400">✓</span>
                                                @endif
                                                <flux:text>{{ $option->option_text }}</flux:text>
                                            </div>
                                        @endforeach
                                    </div>

                                    @if($quiz->show_explanations && $answer->question->explanation)
                                        <div class="pt-2 border-t border-current/10">
                                            <flux:text class="text-sm"><strong>{{ __('Explanation:') }}</strong>
                                                {{ $answer->question->explanation }}</flux:text>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="flex gap-4 justify-center pt-4">
                    @if($quiz->canUserAttempt(auth()->user()))
                        <flux:button wire:click="$refresh" variant="primary" href="{{ route('quiz.take', $quiz->id) }}">
                            {{ __('Retake Quiz') }}
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
                                            <button wire:click="goToQuestion({{ $index }})" class="aspect-square rounded-lg text-sm font-medium transition
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
                            @foreach($shuffledOptions[$currentQuestion->id] ?? $currentQuestion->options as $option)
                                    <label
                                        class="flex items-start gap-4 p-4 rounded-lg border-2 cursor-pointer transition
                                                                    {{ isset($answers[$currentQuestion->id]) && $answers[$currentQuestion->id] == $option->id
                                ? 'border-amber-600 bg-amber-50 dark:bg-amber-950/30'
                                : 'border-neutral-200 dark:border-neutral-700 hover:border-neutral-300 dark:hover:border-neutral-600' }}">
                                        <input type="radio" name="question_{{ $currentQuestion->id }}" value="{{ $option->id }}"
                                            wire:click="answerQuestion({{ $currentQuestion->id }}, {{ $option->id }})" {{ isset($answers[$currentQuestion->id]) && $answers[$currentQuestion->id] == $option->id ? 'checked' : '' }} class="mt-1">
                                        <div class="flex-1">
                                            <flux:text>{{ $option->option_text }}</flux:text>
                                            @if($option->option_image)
                                                <img src="{{ Storage::url($option->option_image) }}" alt="{{ __('Option image') }}"
                                                    class="mt-2 rounded max-w-xs">
                                            @endif
                                        </div>
                                    </label>
                            @endforeach
                        </div>

                        {{-- Navigation Buttons --}}
                        <div class="flex items-center justify-between pt-4 border-t border-neutral-200 dark:border-neutral-700">
                            <flux:button wire:click="previousQuestion" variant="ghost" :disabled="$currentQuestionIndex === 0">
                                {{ __('Previous') }}
                            </flux:button>

                            <div class="flex gap-3">
                                @if($currentQuestionIndex === $totalQuestions - 1)
                                    <flux:button wire:click="submitQuiz" variant="primary"
                                        wire:confirm="{{ __('Are you sure you want to submit? You cannot change your answers after submission.') }}">
                                        {{ __('Submit Quiz') }}
                                    </flux:button>
                                @else
                                    <flux:button wire:click="nextQuestion" variant="primary">
                                        {{ __('Next') }}
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