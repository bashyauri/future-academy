<div class="space-y-4 md:space-y-6" x-data="{
    startEpoch: {{ $timeStarted?->timestamp ?? 0 }},
    durationSeconds: {{ ($time ?? 0) * 60 }},
    timeLeft: {{ $timeRemaining ?? 0 }},
    tickTimer: null,
    syncTimer: null,
    showProgressGrid: false,
    init() {
        // Fallback if server timestamp is missing
        if (!this.startEpoch || this.startEpoch < 1000000000) {
            this.startEpoch = Math.floor(Date.now() / 1000);
        }
        // If timeLeft is invalid, reset to full duration
        if (!Number.isFinite(this.timeLeft) || this.timeLeft <= 0) {
            this.timeLeft = this.durationSeconds;
        }
        this.resync();
        // Tick every second
        this.tickTimer = setInterval(() => this.resync(), 1000);
        // Sync with server every 5 seconds
        @if($time)
        this.syncTimer = setInterval(() => $wire.dispatch('update-timer'), 5000);
        @endif
    },
    resync() {
        const now = Math.floor(Date.now() / 1000);
        const remaining = this.durationSeconds - (now - this.startEpoch);
        this.timeLeft = remaining > 0 ? remaining : 0;
        if (this.timeLeft === 0 && this.durationSeconds > 0) {
            this.stopTicking();
            $wire.dispatch('timer-expired');
        }
    },
    stopTicking() {
        if (this.tickTimer) clearInterval(this.tickTimer);
        if (this.syncTimer) clearInterval(this.syncTimer);
    },
    formatTime(seconds) {
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        const result = mins + ':' + (secs < 10 ? '0' : '') + secs;
        return result;
    }
}">
    @if(!$showResults)
    {{-- Quiz Taking Interface --}}

    <!-- Mobile Progress Toggle Button -->
    <div class="lg:hidden flex justify-center">
        <button
            @click="showProgressGrid = !showProgressGrid"
            class="px-4 py-2 bg-blue-600 dark:bg-blue-700 hover:bg-blue-700 dark:hover:bg-blue-600 text-white font-medium rounded-lg transition-all flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6z M4 10h16"/>
            </svg>
            <span x-text="showProgressGrid ? 'Hide Progress' : 'Show Progress'"></span>
        </button>
    </div>

    <!-- Mobile Progress Grid -->
    <div x-show="showProgressGrid" x-transition x-cloak class="lg:hidden rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 p-4">
        <div class="mb-3">
            <flux:text class="text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase mb-3">Question Progress</flux:text>
            <div class="grid grid-cols-10 gap-1">
                @for($i = 0; $i < $totalQuestions; $i++)
                    @php
                        $isAnswered = ($userAnswers[$i] ?? null) !== null;
                        $isCurrent = $currentQuestionIndex == $i;
                    @endphp
                    <button
                        wire:click="jumpToQuestion({{ $i }})"
                        @click="showProgressGrid = false"
                        wire:loading.attr="disabled"
                        wire:target="jumpToQuestion"
                        title="Q{{ $i + 1 }}"
                        class="aspect-square h-6 w-6 p-0 flex items-center justify-center rounded text-xs font-medium transition-all {{ $isCurrent ? 'bg-blue-500 text-white ring-2 ring-blue-300 dark:ring-blue-700' : ($isAnswered ? 'bg-green-500 text-white' : 'bg-neutral-200 dark:bg-neutral-700 text-neutral-600 dark:text-neutral-400') }}">
                        {{ $i + 1 }}
                    </button>
                @endfor
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:gap-6">
        {{-- Main Question Area --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Progress Header --}}
            <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-5 md:p-6 bg-white dark:bg-neutral-800">
                <div class="flex items-center justify-between mb-4">
                    <flux:heading size="sm" class="text-base md:text-lg font-semibold">
                        Question {{ $currentQuestionIndex + 1 }} of {{ $totalQuestions }}
                    </flux:heading>
                    <div class="flex items-center gap-4">
                        @if($time)
                        <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800" wire:ignore>
                            <svg class="h-4 w-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <flux:text class="text-sm font-semibold text-blue-700 dark:text-blue-300" x-text="formatTime(timeLeft)"></flux:text>
                        </div>
                        @endif
                        <flux:text class="text-sm md:text-base text-neutral-600 dark:text-neutral-400 font-medium">
                            {{ round(($currentQuestionIndex + 1) / $totalQuestions * 100) }}%
                        </flux:text>
                    </div>
                </div>
                <div class="w-full bg-neutral-200 dark:bg-neutral-700 rounded-full h-3">
                    <div class="bg-blue-600 dark:bg-blue-500 h-3 rounded-full transition-all"
                         style="width: {{ ($currentQuestionIndex + 1) / $totalQuestions * 100 }}%"></div>
                </div>
            </div>

            {{-- Current Question --}}
            @if(isset($questions[$currentQuestionIndex]))
            @php $question = $questions[$currentQuestionIndex]; @endphp

            <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-5 md:p-8 bg-white dark:bg-neutral-800 space-y-5 md:space-y-6">
                {{-- Question Text --}}
                <div>
                    <flux:heading size="lg" class="text-lg md:text-xl leading-relaxed">{{ $question['question_text'] }}</flux:heading>
                    @if($question['question_image'])
                    <img src="{{ $question['question_image'] }}" alt="Question" class="mt-3 md:mt-4 max-w-full h-auto rounded-lg">
                    @endif
                </div>

                {{-- Options with Instant Feedback --}}
                <div class="space-y-3 md:space-y-3">
                    @php
                        $hasAnswered = $userAnswers[$currentQuestionIndex] !== null;
                        $correctOption = collect($question['options'])->firstWhere('is_correct', true);
                    @endphp
                    @foreach($question['options'] as $option)
                    @php
                        $isSelected = $userAnswers[$currentQuestionIndex] === $option['id'];
                        $isCorrect = $option['is_correct'];

                        // Determine styling based on answer state
                        if ($hasAnswered) {
                            if ($isCorrect) {
                                $borderClass = 'border-green-500 bg-green-50 dark:bg-green-950/20';
                                $textClass = 'text-green-800 dark:text-green-300 font-medium';
                            } elseif ($isSelected && !$isCorrect) {
                                $borderClass = 'border-red-500 bg-red-50 dark:bg-red-950/20';
                                $textClass = 'text-red-800 dark:text-red-300';
                            } else {
                                $borderClass = 'border-neutral-200 dark:border-neutral-700 bg-neutral-50/50 dark:bg-neutral-900/50 opacity-60';
                                $textClass = 'text-neutral-700 dark:text-neutral-300';
                            }
                        } else {
                            $borderClass = $isSelected ? 'border-blue-500 dark:border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-neutral-200 dark:border-neutral-700 hover:border-blue-400 dark:hover:border-blue-600';
                            $textClass = $isSelected ? 'text-blue-700 dark:text-blue-300 font-medium' : 'text-neutral-700 dark:text-neutral-300';
                        }
                    @endphp
                    <button
                        wire:click="selectAnswer({{ $option['id'] }})"
                        wire:loading.attr="disabled"
                        wire:target="selectAnswer"
                        @if($hasAnswered) disabled @endif
                        class="w-full text-left rounded-lg border-2 p-4 md:p-4 transition-all disabled:cursor-not-allowed {{ $borderClass }}"
                    >
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 mt-1">
                                @if($hasAnswered)
                                    @if($isCorrect)
                                    <div class="w-6 h-6 rounded-full bg-green-500 dark:bg-green-600 flex items-center justify-center">
                                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                    @elseif($isSelected && !$isCorrect)
                                    <div class="w-6 h-6 rounded-full bg-red-500 dark:bg-red-600 flex items-center justify-center">
                                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                    @else
                                    <div class="w-6 h-6 rounded-full border-2 border-neutral-300 dark:border-neutral-600"></div>
                                    @endif
                                @else
                                    <div class="w-6 h-6 rounded-full border-2 flex items-center justify-center transition-all {{ $isSelected ? 'border-blue-500 dark:border-blue-500 bg-blue-500 dark:bg-blue-600' : 'border-neutral-300 dark:border-neutral-600' }}">
                                        @if($isSelected)
                                        <div class="w-3 h-3 bg-white rounded-full"></div>
                                        @endif
                                        <!-- Loading spinner when clicking -->
                                        <svg wire:loading wire:target="selectAnswer" class="animate-spin h-5 w-5 text-neutral-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </div>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start gap-2">
                                    <flux:text class="text-base md:text-base leading-relaxed {{ $textClass }}">{{ $option['option_text'] }}</flux:text>
                                    @if($hasAnswered && $isCorrect)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-green-600 dark:bg-green-700 text-white whitespace-nowrap">
                                        {{ __('Correct') }}
                                    </span>
                                    @elseif($hasAnswered && $isSelected && !$isCorrect)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-red-600 dark:bg-red-700 text-white whitespace-nowrap">
                                        {{ __('Wrong') }}
                                    </span>
                                    @elseif($isSelected && !$hasAnswered)
                                    <span wire:loading wire:target="selectAnswer" class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-xs font-medium bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300">
                                        <svg class="animate-spin h-3 w-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        {{ __('Processing...') }}
                                    </span>
                                    @endif
                                </div>
                                @if($option['option_image'])
                                <img src="{{ $option['option_image'] }}" alt="Option" class="mt-2 max-w-full h-auto rounded">
                                @endif
                            </div>
                        </div>
                    </button>
                    @endforeach
                </div>

                {{-- Instant Explanation (shown after answering) --}}
                @if($hasAnswered && $question['explanation'])
                <div class="rounded-lg border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-950/20 p-4 animate-fade-in">
                    <div class="flex gap-3">
                        <div class="flex-shrink-0">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <flux:heading size="sm" class="text-blue-900 dark:text-blue-300 mb-1 font-semibold">{{ __('Explanation') }}</flux:heading>
                            <flux:text class="text-sm text-blue-800 dark:text-blue-400 leading-relaxed">{{ $question['explanation'] }}</flux:text>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            {{-- Navigation Buttons --}}
            <div class="space-y-3">
                {{-- Main Navigation Row --}}
                <div class="flex flex-col-reverse sm:flex-row gap-3">
                    <flux:button
                        variant="ghost"
                        icon="arrow-left"
                        wire:click="previousQuestion"
                        wire:loading.attr="disabled"
                        wire:target="previousQuestion"
                        :disabled="$currentQuestionIndex === 0"
                        class="text-base min-h-[48px]"
                    >
                        <span wire:loading.remove wire:target="previousQuestion">{{ __('Previous') }}</span>
                        <span wire:loading wire:target="previousQuestion" class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </span>
                    </flux:button>

                    @if($currentQuestionIndex === $totalQuestions - 1)
                    <flux:button
                        variant="primary"
                        icon="check"
                        wire:click="submitQuiz"
                        wire:loading.attr="disabled"
                        wire:target="submitQuiz"
                        class="flex-1 text-base min-h-[48px]"
                    >
                        <span wire:loading.remove wire:target="submitQuiz">{{ __('Submit Quiz') }}</span>
                        <span wire:loading wire:target="submitQuiz" class="flex items-center justify-center gap-2">
                            <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ __('Submitting...') }}
                        </span>
                    </flux:button>
                    @else
                    <flux:button
                        variant="primary"
                        icon-trailing="arrow-right"
                        wire:click="nextQuestion"
                        wire:loading.attr="disabled"
                        wire:target="nextQuestion"
                        class="flex-1 text-base min-h-[48px]"
                    >
                        <span wire:loading.remove wire:target="nextQuestion">{{ __('Next') }}</span>
                        <span wire:loading wire:target="nextQuestion" class="flex items-center justify-center gap-2">
                            <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </span>
                    </flux:button>
                    @endif
                </div>

                {{-- End Practice Button (Always Visible) --}}
                @if($currentQuestionIndex !== $totalQuestions - 1)
                <flux:button
                    variant="danger"
                    wire:click="submitQuiz"
                    wire:loading.attr="disabled"
                    wire:target="submitQuiz"
                    class="w-full text-base min-h-[52px] font-semibold"
                >
                    <span wire:loading.remove wire:target="submitQuiz" class="flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                        {{ __('End Practice & Submit') }}
                    </span>
                    <span wire:loading wire:target="submitQuiz" class="flex items-center justify-center gap-2">
                        <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ __('Submitting...') }}
                    </span>
                </flux:button>
                @endif
            </div>
            @endif
        </div>

        {{-- Sidebar: Question Navigator (Hidden on Mobile, Visible on Large Screens) --}}
        <div class="hidden lg:block lg:col-span-1">
            <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-6 bg-white dark:bg-neutral-800 sticky top-20">
                <flux:heading size="sm" class="mb-4">{{ __('Questions') }}</flux:heading>

                <div class="grid grid-cols-5 gap-2 mb-6">
                    @for($i = 0; $i < $totalQuestions; $i++)
                    <button
                        wire:click="jumpToQuestion({{ $i }})"
                        wire:loading.attr="disabled"
                        wire:target="jumpToQuestion"
                        class="aspect-square rounded-lg border-2 font-semibold text-sm transition-all disabled:opacity-50 disabled:cursor-not-allowed {{ $currentQuestionIndex === $i ? 'border-blue-500 dark:border-blue-500 bg-blue-600 dark:bg-blue-500 text-white' : ($userAnswers[$i] !== null ? 'border-green-500 dark:border-green-500 bg-green-50 dark:bg-green-950/30 text-green-700 dark:text-green-300' : 'border-neutral-300 dark:border-neutral-600 hover:border-neutral-400 dark:hover:border-neutral-500') }}"
                    >
                        {{ $i + 1 }}
                    </button>
                    @endfor
                </div>

                <div class="space-y-3 text-xs">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full bg-blue-600"></div>
                        <flux:text>{{ __('Current') }}</flux:text>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full bg-green-600"></div>
                        <flux:text>{{ __('Answered') }}</flux:text>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full border-2 border-neutral-300"></div>
                        <flux:text>{{ __('Not Answered') }}</flux:text>
                    </div>
                </div>
            </div>
        </div>

        {{-- Mobile Question Navigator (Visible on Mobile Only) --}}
        <div class="lg:hidden">
            <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-5 bg-white dark:bg-neutral-800">
                <flux:heading size="sm" class="mb-4 text-base font-semibold">{{ __('Questions Progress') }}</flux:heading>

                {{-- Horizontal Scrollable Question Grid --}}
                <div class="overflow-x-auto -mx-5 px-5 mb-4">
                    <div class="flex gap-3 pb-2">
                        @for($i = 0; $i < $totalQuestions; $i++)
                        <button
                            wire:click="jumpToQuestion({{ $i }})"
                            wire:loading.attr="disabled"
                            wire:target="jumpToQuestion"
                            class="flex-shrink-0 w-12 h-12 rounded-lg border-2 font-semibold text-sm transition-all disabled:opacity-50 disabled:cursor-not-allowed active:scale-95 {{ $currentQuestionIndex === $i ? 'border-blue-500 dark:border-blue-500 bg-blue-600 dark:bg-blue-500 text-white' : ($userAnswers[$i] !== null ? 'border-green-500 dark:border-green-500 bg-green-50 dark:bg-green-950/30 text-green-700 dark:text-green-300' : 'border-neutral-300 dark:border-neutral-600') }}"
                        >
                            {{ $i + 1 }}
                        </button>
                        @endfor
                    </div>
                </div>

                <div class="space-y-3 text-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-3 h-3 rounded-full bg-blue-600"></div>
                        <flux:text class="text-sm">{{ __('Current') }}</flux:text>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-3 h-3 rounded-full bg-green-600"></div>
                        <flux:text class="text-sm">{{ __('Answered') }}</flux:text>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-3 h-3 rounded-full border-2 border-neutral-300"></div>
                        <flux:text class="text-sm">{{ __('Not Answered') }}</flux:text>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @else
    {{-- Results Screen --}}
    <div class="space-y-4 md:space-y-6">
        {{-- Score Card --}}
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-6 md:p-8 bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-950/30 dark:to-blue-900/20 text-center">
            <flux:heading size="xl" class="mb-4 text-xl md:text-2xl">{{ __('Quiz Completed!') }}</flux:heading>

            <div class="my-6 md:my-8">
                <div class="inline-flex items-center justify-center w-24 h-24 md:w-32 md:h-32 rounded-full border-6 md:border-8 border-blue-600 dark:border-blue-500 bg-white dark:bg-neutral-900">
                    <div class="text-center">
                        <flux:heading size="lg" class="text-lg md:text-2xl text-blue-600 dark:text-blue-400">{{ $score }}</flux:heading>
                        <flux:text class="text-xs md:text-sm text-neutral-600 dark:text-neutral-400">/{{ $totalQuestions }}</flux:text>
                    </div>
                </div>
            </div>

            <flux:heading size="lg" class="mb-2 text-base md:text-lg">{{ round($score / $totalQuestions * 100) }}%</flux:heading>
            <flux:text class="text-base md:text-lg text-neutral-700 dark:text-neutral-300">
                @if(round($score / $totalQuestions * 100) >= 70)
                    {{ __('Excellent! Great job!') }}
                @elseif(round($score / $totalQuestions * 100) >= 50)
                    {{ __('Good effort! Keep practicing!') }}
                @else
                    {{ __('Keep practicing! You\'ll improve!') }}
                @endif
            </flux:text>
        </div>

        {{-- Statistics --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6">
            <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-4 md:p-6 bg-white dark:bg-neutral-800 text-center">
                <flux:text class="text-xs md:text-sm text-neutral-600 dark:text-neutral-400 mb-2">{{ __('Correct Answers') }}</flux:text>
                <flux:heading size="lg" class="text-base md:text-lg text-green-600 dark:text-green-400">{{ $score }}</flux:heading>
            </div>

            <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-4 md:p-6 bg-white dark:bg-neutral-800 text-center">
                <flux:text class="text-xs md:text-sm text-neutral-600 dark:text-neutral-400 mb-2">{{ __('Wrong Answers') }}</flux:text>
                <flux:heading size="lg" class="text-base md:text-lg text-red-600 dark:text-red-400">{{ $totalQuestions - $score }}</flux:heading>
            </div>

            <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-4 md:p-6 bg-white dark:bg-neutral-800 text-center">
                <flux:text class="text-xs md:text-sm text-neutral-600 dark:text-neutral-400 mb-2">{{ __('Percentage') }}</flux:text>
                <flux:heading size="lg" class="text-base md:text-lg text-blue-600 dark:text-blue-400">{{ round($score / $totalQuestions * 100) }}%</flux:heading>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="flex flex-col gap-4">
            <flux:button
                href="{{ route('practice.home') }}"
                variant="primary"
                icon="arrow-left"
                wire:navigate
                class="w-full text-base min-h-[52px] font-semibold"
            >
                {{ __('Try Another Practice Exam') }}
            </flux:button>

            <flux:button
                href="{{ route('dashboard') }}"
                variant="ghost"
                icon="home"
                wire:navigate
                class="w-full text-base min-h-[52px]"
            >
                {{ __('Back to Dashboard') }}
            </flux:button>
        </div>
    </div>
    @endif
</div>
</div>
