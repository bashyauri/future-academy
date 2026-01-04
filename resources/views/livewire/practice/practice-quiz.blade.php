<div x-data="{
    timeRemaining: @entangle('timeRemaining'),
    currentQuestionIndex: @entangle('currentQuestionIndex'),
    userAnswers: @js($userAnswers),
    questions: @js($questions),
    allQuestionIds: @js($allQuestionIds),
    totalQuestions: @js($totalQuestions),
    loadedUpToIndex: @js($loadedUpToIndex),
    quizAttemptId: @js($attempt),
    csrfToken: @js($csrfToken),
    timer: null,
    autosaveTimer: null,
    autosaveDebounce: false,

    init() {
        if (@js($time) > 0) this.startTimer();
        // Start autosave every 10 seconds
        this.autosaveTimer = setInterval(() => this.autosave(), 10000);
    },

    startTimer() {
        this.timer = setInterval(() => {
            if (this.timeRemaining > 0) {
                this.timeRemaining--;
            } else {
                clearInterval(this.timer);
                $wire.call('handleTimerExpired');
            }
        }, 1000);
    },

    formatTime(seconds) {
        const h = Math.floor(seconds / 3600);
        const m = Math.floor((seconds % 3600) / 60);
        const s = seconds % 60;
        return `${h.toString().padStart(2,'0')}:${m.toString().padStart(2,'0')}:${s.toString().padStart(2,'0')}`;
    },

    selectAnswer(optionId) {
        // Instant client-side feedback - NO server call
        this.userAnswers[this.currentQuestionIndex] = optionId;

        // Trigger autosave (debounced, runs every 10s via timer)
        this.autosaveDebounce = true;
    },

    async autosave() {
        if (!this.autosaveDebounce || !this.quizAttemptId) return;

        try {
            // Send answers to server for CACHING (not database save)
            // Database is only written on explicit exit/submit
            const response = await fetch('/quiz/autosave', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                },
                body: JSON.stringify({
                    attempt_id: this.quizAttemptId,
                    answers: this.userAnswers,
                    current_question_index: this.currentQuestionIndex,
                }),
            });

            if (response.ok) {
                this.autosaveDebounce = false;
            }
        } catch (error) {
            console.error('Autosave failed:', error);
        }
    },

    saveSync() {
        // Use sendBeacon on page unload for critical saves
        if (this.quizAttemptId && Object.keys(this.userAnswers).length > 0) {
            navigator.sendBeacon('/quiz/autosave', JSON.stringify({
                attempt_id: this.quizAttemptId,
                answers: this.userAnswers,
                current_question_index: this.currentQuestionIndex,
            }));
        }
    },

    nextQuestion() {
        if (this.currentQuestionIndex < this.totalQuestions - 1) {
            this.currentQuestionIndex++;
        }
    },

    previousQuestion() {
        if (this.currentQuestionIndex > 0) {
            this.currentQuestionIndex--;
        }
    },

    jumpToQuestion(index) {
        if (index >= 0 && index < this.totalQuestions) {
            this.currentQuestionIndex = index;
        }
    },

    confirmSubmit() {
        const answered = Object.values(this.userAnswers).filter(a => a !== null).length;
        if (confirm(`Are you sure you want to submit your exam?\n\nYou have answered ${answered} out of ${this.totalQuestions} questions.\n\nOnce submitted, you won't be able to change your answers.`)) {
            $wire.call('submitQuiz');
        }
    },

    confirmExit() {
        const answered = Object.values(this.userAnswers).filter(a => a !== null).length;
        if (confirm(`Your progress will be saved and you can continue this practice exam later.\n\nCurrent progress: ${answered} of ${this.totalQuestions} questions answered.\n\nDo you want to exit?`)) {
            this.autosave().then(() => $wire.call('exitQuiz'));
        }
    },

    getCurrentQuestion() {
        return this.questions[this.currentQuestionIndex] || null;
    },

    getAnsweredCount() {
        return Object.values(this.userAnswers).filter(a => a !== null).length;
    }
}" @unload="saveSync()" class="min-h-screen bg-white dark:bg-neutral-950">
@if(!$showResults)
    <div class="flex flex-col lg:flex-row min-h-screen overflow-hidden">
        <div class="flex-1 flex flex-col overflow-hidden">
            {{-- Header --}}
            <div class="bg-white dark:bg-neutral-900 border-b border-gray-200 dark:border-neutral-800 p-4">
                <div class="max-w-7xl mx-auto flex items-center justify-between gap-3">
                    <div>
                        <flux:heading size="lg" level="1" class="mb-0">Practice Exam</flux:heading>
                    </div>
                    <div class="flex items-center gap-3 sm:gap-4">
                        @if($time)
                        <div class="text-right">
                            <flux:text class="text-gray-600 dark:text-gray-400 text-sm">Time Remaining</flux:text>
                            <div class="text-2xl sm:text-3xl font-bold font-mono" :class="timeRemaining < 600 ? 'text-red-600 dark:text-red-400' : 'text-blue-600 dark:text-blue-300'" x-text="formatTime(timeRemaining)"></div>
                        </div>
                        @endif
                        <div class="flex gap-2">
                            <button
                                @click="confirmExit()"
                                class="px-3 sm:px-4 py-2 bg-gray-600 dark:bg-gray-700 hover:bg-gray-700 dark:hover:bg-gray-600 text-white font-semibold rounded-lg transition-all shadow-sm text-sm sm:text-base">
                                Exit & Continue Later
                            </button>
                            <button
                                @click="confirmSubmit()"
                                class="px-3 sm:px-4 py-2 bg-green-600 dark:bg-green-600 hover:bg-green-700 dark:hover:bg-green-500 text-white font-semibold rounded-lg transition-all shadow-sm text-sm sm:text-base">
                                Submit
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Main Content --}}
            <div class="flex-1 overflow-y-auto p-4 sm:p-6">
                <div class="max-w-4xl mx-auto">
                    @if($totalQuestions === 0)
                        <div class="rounded-xl border border-red-200 bg-red-50 dark:bg-red-900/20 p-8 text-center">
                            <flux:heading size="lg" class="text-red-700 dark:text-red-200 mb-2">No Questions Available</flux:heading>
                            <flux:text class="text-base text-red-800 dark:text-red-300">There are no questions available for your selection.</flux:text>
                            <div class="mt-6">
                                <a href="{{ route('practice.home') }}" class="inline-block px-6 py-2 rounded-lg bg-blue-600 text-white font-semibold hover:bg-blue-700 transition">Back to Practice</a>
                            </div>
                        </div>
                    @else
                        {{-- Question Header --}}
                        <div class="mb-6">
                            <div class="flex items-center justify-between mb-3 gap-3">
                                <flux:text class="text-sm font-medium text-gray-600 dark:text-gray-400">Practice Exam</flux:text>
                                <flux:badge color="blue" x-text="`Question ${currentQuestionIndex + 1} of ${totalQuestions}`"></flux:badge>
                            </div>
                        </div>

                        {{-- Question Card --}}
                        <template x-if="getCurrentQuestion()">
                            <div class="rounded-xl border border-gray-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-5 sm:p-6 shadow-sm mb-8">
                                <flux:text class="text-lg font-medium leading-relaxed text-gray-900 dark:text-gray-100" x-text="getCurrentQuestion().question_text"></flux:text>
                                <template x-if="getCurrentQuestion().question_image">
                                    <img :src="getCurrentQuestion().question_image" alt="Question" class="mt-4 max-w-full h-auto rounded-lg" loading="lazy">
                                </template>
                            </div>
                        </template>

                            {{-- Options --}}
                            <div class="space-y-3 mb-8">
                                <template x-for="(option, index) in getCurrentQuestion().options" :key="option.id">
                                    <button
                                        @click="selectAnswer(option.id)"
                                        :class="{
                                            'border-green-500 bg-green-50 dark:bg-green-900/20 ring-2 ring-green-300 dark:ring-green-700': userAnswers[currentQuestionIndex] === option.id && option.is_correct,
                                            'border-red-500 bg-red-50 dark:bg-red-900/20 ring-2 ring-red-300 dark:ring-red-700': userAnswers[currentQuestionIndex] === option.id && !option.is_correct,
                                            'border-green-400 bg-green-50/50 dark:bg-green-900/10': userAnswers[currentQuestionIndex] !== null && userAnswers[currentQuestionIndex] !== option.id && option.is_correct,
                                            'border-gray-200 dark:border-neutral-800 hover:border-green-400 dark:hover:border-green-500': userAnswers[currentQuestionIndex] === null,
                                            'border-gray-200 dark:border-neutral-800': userAnswers[currentQuestionIndex] !== null && userAnswers[currentQuestionIndex] !== option.id && !option.is_correct,
                                        }"
                                        class="w-full p-4 rounded-xl border-2 text-left transition-all">
                                        <div class="flex items-start gap-3">
                                            <div :class="{
                                                'border-green-500 bg-green-500': userAnswers[currentQuestionIndex] === option.id && option.is_correct,
                                                'border-red-500 bg-red-500': userAnswers[currentQuestionIndex] === option.id && !option.is_correct,
                                                'border-green-400 bg-green-400': userAnswers[currentQuestionIndex] !== null && userAnswers[currentQuestionIndex] !== option.id && option.is_correct,
                                                'border-gray-300 dark:border-gray-700': userAnswers[currentQuestionIndex] === null || (userAnswers[currentQuestionIndex] !== option.id && !option.is_correct),
                                            }" class="flex-shrink-0 w-6 h-6 rounded-full border-2 flex items-center justify-center mt-0.5 transition-all">
                                                <template x-if="userAnswers[currentQuestionIndex] === option.id">
                                                    <div class="w-2.5 h-2.5 bg-white rounded-full"></div>
                                                </template>
                                            </div>
                                            <span :class="{
                                                'text-green-700 dark:text-green-200 font-medium': userAnswers[currentQuestionIndex] === option.id && option.is_correct,
                                                'text-red-700 dark:text-red-200 font-medium': userAnswers[currentQuestionIndex] === option.id && !option.is_correct,
                                                'text-green-600 dark:text-green-300': userAnswers[currentQuestionIndex] !== null && userAnswers[currentQuestionIndex] !== option.id && option.is_correct,
                                                'text-gray-800 dark:text-gray-200': userAnswers[currentQuestionIndex] === null,
                                                'text-gray-600 dark:text-gray-400': userAnswers[currentQuestionIndex] !== null && userAnswers[currentQuestionIndex] !== option.id && !option.is_correct,
                                            }" class="flex-1" x-text="option.option_text"></span>
                                            <template x-if="userAnswers[currentQuestionIndex] === option.id && option.is_correct">
                                                <svg class="h-5 w-5 text-green-500 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                            </template>
                                            <template x-if="userAnswers[currentQuestionIndex] === option.id && !option.is_correct">
                                                <svg class="h-5 w-5 text-red-500 dark:text-red-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                                            </template>
                                            <template x-if="userAnswers[currentQuestionIndex] !== null && userAnswers[currentQuestionIndex] !== option.id && option.is_correct">
                                                <svg class="h-5 w-5 text-green-400 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            </template>
                                        </div>
                                    </button>
                                </template>
                            </div>

                            {{-- Explanation --}}
                            <template x-if="userAnswers[currentQuestionIndex] !== null && getCurrentQuestion().explanation">
                                <div class="mb-8 p-5 rounded-lg border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-950/20">
                                    <div class="flex gap-3">
                                        <div class="flex-shrink-0">
                                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                            </svg>
                                        </div>
                                        <div class="flex-1">
                                            <flux:heading size="sm" class="text-blue-900 dark:text-blue-300 mb-1 font-semibold">Explanation</flux:heading>
                                            <flux:text class="text-sm text-blue-800 dark:text-blue-400 leading-relaxed" x-text="getCurrentQuestion().explanation"></flux:text>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            {{-- Navigation --}}
                            <div class="flex items-center justify-between gap-3">
                                <button
                                    @click="previousQuestion()"
                                    :disabled="currentQuestionIndex === 0"
                                    class="flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-neutral-800 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-200 dark:hover:bg-neutral-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                    ← Previous
                                </button>
                                <flux:text class="text-sm text-gray-600 dark:text-gray-400 font-medium" x-text="`${currentQuestionIndex + 1}/${totalQuestions}`"></flux:text>
                                <button
                                    @click="nextQuestion()"
                                    class="px-4 py-2 bg-blue-600 dark:bg-blue-600 hover:bg-blue-700 dark:hover:bg-blue-500 text-white font-medium rounded-lg transition-all shadow-sm">
                                    Next →
                                </button>
                            </div>
                        </template>
                    @endif
                </div>
            </div>
        </div>

        {{-- Sidebar - Hidden on Mobile --}}
        <div class="hidden lg:block w-80 bg-white dark:bg-neutral-900 border-l border-gray-200 dark:border-neutral-800 overflow-y-auto p-4">
            <div class="mb-6 pb-6 border-b-2 border-blue-200 dark:border-blue-800">
                <div class="flex items-center justify-between mb-3">
                    <flux:heading size="sm" level="3" class="mb-0 text-blue-600 dark:text-blue-300">Questions</flux:heading>
                    <flux:badge color="blue" class="text-xs" x-text="`${getAnsweredCount()}/${totalQuestions}`"></flux:badge>
                </div>
                <div class="grid grid-cols-5 gap-2">
                    <template x-for="(q, i) in totalQuestions" :key="i">
                        <button
                            @click="jumpToQuestion(i)"
                            :class="{
                                'bg-blue-600 text-white ring-2 ring-blue-300 dark:ring-blue-700': currentQuestionIndex === i,
                                'bg-green-500 text-white hover:bg-green-600 dark:bg-green-600 dark:hover:bg-green-500': currentQuestionIndex !== i && userAnswers[i] !== null,
                                'bg-gray-100 dark:bg-neutral-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-neutral-700': currentQuestionIndex !== i && userAnswers[i] === null,
                            }"
                            class="aspect-square h-8 w-8 p-0 flex items-center justify-center rounded-lg text-xs font-medium transition-all"
                            x-text="i + 1">
                        </button>
                    </template>
                </div>
            </div>

            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <flux:text class="text-sm font-medium text-gray-600 dark:text-gray-400">Answered</flux:text>
                    <flux:text class="font-semibold text-gray-900 dark:text-gray-100" x-text="`${getAnsweredCount()}/${totalQuestions}`"></flux:text>
                </div>
                <button
                    @click="confirmExit()"
                    class="w-full px-4 py-2 bg-gray-100 dark:bg-neutral-800 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-200 dark:hover:bg-neutral-700 transition-colors text-sm font-medium">
                    Exit & Save Progress
                </button>
            </div>
        </div>
    </div>
@endif

@if($showResults)
    {{-- Results Screen --}}
    <flux:container class="py-12">
        <div class="max-w-4xl mx-auto space-y-8">
            <div class="text-center">
                <flux:heading size="2xl" level="1" class="mb-2">Practice Completed</flux:heading>
                <flux:text class="text-gray-600 dark:text-gray-400">Here is your performance breakdown.</flux:text>
            </div>

            @php
                $percentage = $totalQuestions ? round(($score / $totalQuestions) * 100, 1) : 0;
            @endphp

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="p-5 rounded-xl border border-green-200 dark:border-green-800 bg-green-50 dark:bg-neutral-900">
                    <flux:text class="text-sm text-green-700 dark:text-green-200">Score</flux:text>
                    <flux:heading size="xl" class="text-green-800 dark:text-green-100">{{ $score }}/{{ $totalQuestions }}</flux:heading>
                </div>
                <div class="p-5 rounded-xl border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-neutral-900">
                    <flux:text class="text-sm text-blue-700 dark:text-blue-200">Percentage</flux:text>
                    <flux:heading size="xl" class="text-blue-800 dark:text-blue-100">{{ $percentage }}%</flux:heading>
                </div>
                <div class="p-5 rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-neutral-900">
                    <flux:text class="text-sm text-amber-700 dark:text-amber-200">Status</flux:text>
                    <flux:heading size="xl" class="text-amber-800 dark:text-amber-100">
                        @if($percentage >= 70)
                            Excellent
                        @elseif($percentage >= 50)
                            Good
                        @else
                            Keep Trying
                        @endif
                    </flux:heading>
                </div>
            </div>

            <div class="flex gap-3">
                <flux:button wire:navigate href="{{ route('practice.home') }}" icon="arrow-left" variant="primary">
                    Try Another Practice
                </flux:button>
                <flux:button wire:navigate href="{{ route('dashboard') }}" variant="ghost">Dashboard</flux:button>
            </div>
        </div>
    </flux:container>
@endif
</div>
