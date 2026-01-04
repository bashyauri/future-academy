<script>
// Practice Quiz JavaScript Engine - Define BEFORE Alpine processes the DOM
function practiceQuiz() {
    return {
        // State
        attemptId: null,
        questions: [],
        allQuestionIds: [],
        userAnswers: [],
        currentQuestionIndex: 0,
        totalQuestions: 0,
        loadedUpToIndex: -1,
        timeRemaining: 0,
        timeLimit: null,
        startedAt: null,
        timer: null,
        autosaveTimer: null,
        isLoading: false,
        isSaving: false,
        showResults: false,
        score: 0,
        percentage: 0,

        // Initialize
        async init() {
            console.log('Initializing quiz...');
            const urlParams = new URLSearchParams(window.location.search);
            const attemptId = urlParams.get('attempt');

            if (attemptId) {
                await this.loadAttempt(attemptId);
            } else {
                await this.startNewQuiz();
            }

            if (this.timeLimit && this.timeLimit > 0) {
                this.startTimer();
            }

            // Setup autosave every 10 seconds
            this.autosaveTimer = setInterval(() => this.autosave(), 10000);

            // Save on page unload
            window.addEventListener('beforeunload', (e) => {
                if (!this.showResults) {
                    this.saveSync();
                }
            });
        },

        // Start new quiz
        async startNewQuiz() {
            this.isLoading = true;
            const urlParams = new URLSearchParams(window.location.search);

            try {
                const response = await fetch('/api/practice/start', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        exam_type: urlParams.get('exam_type'),
                        subject: urlParams.get('subject'),
                        year: urlParams.get('year'),
                        shuffle: urlParams.get('shuffle') === '1',
                        limit: urlParams.get('limit'),
                        time: urlParams.get('time'),
                    }),
                });

                const data = await response.json();
                console.log('Start quiz response:', data);

                if (data.success) {
                    this.attemptId = data.attempt_id;
                    this.questions = data.questions;
                    this.allQuestionIds = data.all_question_ids;
                    this.userAnswers = data.user_answers;
                    this.totalQuestions = data.total_questions;
                    this.loadedUpToIndex = data.loaded_up_to_index;
                    this.currentQuestionIndex = 0;
                    this.timeLimit = data.time_limit;
                    this.startedAt = new Date(data.started_at);

                    if (this.timeLimit) {
                        this.timeRemaining = this.timeLimit * 60;
                    }
                    console.log('Quiz started successfully');
                } else {
                    alert('Failed to start quiz: ' + (data.error || 'Unknown error'));
                }
            } catch (error) {
                console.error('Failed to start quiz:', error);
                alert('Failed to start quiz. Please try again.');
            } finally {
                this.isLoading = false;
            }
        },

        // Load existing attempt
        async loadAttempt(attemptId) {
            this.isLoading = true;

            try {
                const response = await fetch(`/api/practice/load/${attemptId}`, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                });

                const data = await response.json();

                if (data.success) {
                    this.attemptId = data.attempt_id;
                    this.questions = data.questions;
                    this.allQuestionIds = data.all_question_ids;
                    this.userAnswers = data.user_answers;
                    this.totalQuestions = data.total_questions;
                    this.loadedUpToIndex = data.loaded_up_to_index;
                    this.currentQuestionIndex = data.current_question_index;
                    this.timeLimit = data.time_limit;
                    this.startedAt = new Date(data.started_at);

                    if (this.timeLimit) {
                        const elapsed = Math.floor((Date.now() - this.startedAt.getTime()) / 1000);
                        this.timeRemaining = Math.max(0, this.timeLimit * 60 - elapsed);
                    }
                }
            } catch (error) {
                console.error('Failed to load attempt:', error);
            } finally {
                this.isLoading = false;
            }
        },

        // Timer management
        startTimer() {
            this.timer = setInterval(() => {
                if (this.timeRemaining > 0) {
                    this.timeRemaining--;
                } else {
                    clearInterval(this.timer);
                    this.handleTimerExpired();
                }
            }, 1000);
        },

        handleTimerExpired() {
            if (!this.showResults) {
                alert('Time is up! Submitting your quiz...');
                this.submitQuiz();
            }
        },

        formatTime(seconds) {
            const h = Math.floor(seconds / 3600);
            const m = Math.floor((seconds % 3600) / 60);
            const s = seconds % 60;
            return `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
        },

        // Question navigation
        async nextQuestion() {
            if (this.currentQuestionIndex < this.totalQuestions - 1) {
                this.currentQuestionIndex++;

                // Lazy load if approaching end of loaded batch
                if (this.currentQuestionIndex > this.loadedUpToIndex - 2 &&
                    this.loadedUpToIndex < this.totalQuestions - 1) {
                    await this.loadMoreQuestions();
                }
            }
        },

        previousQuestion() {
            if (this.currentQuestionIndex > 0) {
                this.currentQuestionIndex--;
            }
        },

        async jumpToQuestion(index) {
            this.currentQuestionIndex = index;

            // Load questions if jumping ahead
            while (index > this.loadedUpToIndex && this.loadedUpToIndex < this.totalQuestions - 1) {
                await this.loadMoreQuestions();
            }
        },

        // Lazy loading
        async loadMoreQuestions() {
            if (this.loadedUpToIndex >= this.totalQuestions - 1) {
                return;
            }

            const startIndex = this.loadedUpToIndex + 1;
            const endIndex = Math.min(startIndex + 4, this.totalQuestions - 1);
            const batchIds = this.allQuestionIds.slice(startIndex, endIndex + 1);

            try {
                const response = await fetch('/api/practice/load-batch', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        question_ids: batchIds,
                        shuffle: false,
                    }),
                });

                const data = await response.json();

                if (data.success) {
                    this.questions = [...this.questions, ...data.questions];
                    this.loadedUpToIndex = endIndex;
                }
            } catch (error) {
                console.error('Failed to load more questions:', error);
            }
        },

        // Answer selection
        selectAnswer(optionId) {
            this.userAnswers[this.currentQuestionIndex] = optionId;
        },

        // Computed properties
        get currentQuestion() {
            return this.questions[this.currentQuestionIndex] || null;
        },

        get answeredCount() {
            return this.userAnswers.filter(a => a !== null).length;
        },

        // Autosave (debounced)
        async autosave() {
            if (this.isSaving || !this.attemptId || this.showResults) {
                return;
            }

            this.isSaving = true;

            try {
                await fetch('/api/practice/save', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        attempt_id: this.attemptId,
                        answers: this.userAnswers,
                        current_question_index: this.currentQuestionIndex,
                        all_question_ids: this.allQuestionIds,
                        questions: this.questions,
                        loaded_up_to_index: this.loadedUpToIndex,
                    }),
                });
            } catch (error) {
                console.error('Autosave failed:', error);
            } finally {
                this.isSaving = false;
            }
        },

        // Synchronous save for page unload
        saveSync() {
            if (!this.attemptId || this.showResults) {
                return;
            }

            navigator.sendBeacon('/api/practice/save', new Blob([JSON.stringify({
                attempt_id: this.attemptId,
                answers: this.userAnswers,
                current_question_index: this.currentQuestionIndex,
                all_question_ids: this.allQuestionIds,
                questions: this.questions,
                loaded_up_to_index: this.loadedUpToIndex,
            })], { type: 'application/json' }));
        },

        // Submit quiz
        async submitQuiz() {
            const answered = this.answeredCount;
            const total = this.totalQuestions;

            if (!confirm(`Are you sure you want to submit your exam?\n\nYou have answered ${answered} out of ${total} questions.\n\nOnce submitted, you won't be able to change your answers.`)) {
                return;
            }

            this.isLoading = true;

            try {
                const response = await fetch('/api/practice/submit', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        attempt_id: this.attemptId,
                        answers: this.userAnswers,
                        all_question_ids: this.allQuestionIds,
                    }),
                });

                const data = await response.json();

                if (data.success) {
                    this.score = data.score;
                    this.percentage = data.percentage;
                    this.showResults = true;
                    clearInterval(this.timer);
                    clearInterval(this.autosaveTimer);
                }
            } catch (error) {
                console.error('Failed to submit quiz:', error);
                alert('Failed to submit quiz. Please try again.');
            } finally {
                this.isLoading = false;
            }
        },

        // Exit and save
        async exitQuiz() {
            const answered = this.answeredCount;
            const total = this.totalQuestions;

            if (!confirm(`Your progress will be saved and you can continue this practice exam later.\n\nCurrent progress: ${answered} of ${total} questions answered.\n\nDo you want to exit?`)) {
                return;
            }

            this.isLoading = true;

            try {
                await fetch('/api/practice/exit', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        attempt_id: this.attemptId,
                        answers: this.userAnswers,
                        all_question_ids: this.allQuestionIds,
                        current_question_index: this.currentQuestionIndex,
                    }),
                });

                window.location.href = '/practice';
            } catch (error) {
                console.error('Failed to exit quiz:', error);
                alert('Failed to save progress. Please try again.');
            } finally {
                this.isLoading = false;
            }
        },
    };
}
</script>

<div x-data="practiceQuiz()" x-init="init()" class="min-h-screen bg-white dark:bg-neutral-950">
    {{-- Loading State --}}
    <div x-show="isLoading" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-neutral-900 rounded-lg p-8 text-center">
            <svg class="animate-spin h-12 w-12 text-blue-600 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="text-gray-700 dark:text-gray-300">Loading...</p>
        </div>
    </div>

    {{-- Quiz Interface --}}
    <template x-if="!showResults && !isLoading">
        <div class="flex flex-col lg:flex-row min-h-screen overflow-hidden">
            <div class="flex-1 flex flex-col overflow-hidden">
                {{-- Header --}}
                <div class="bg-white dark:bg-neutral-900 border-b border-gray-200 dark:border-neutral-800 p-4">
                    <div class="max-w-7xl mx-auto flex items-center justify-between gap-3">
                        <div>
                            <flux:heading size="lg" level="1" class="mb-0">Practice Exam (JS Mode)</flux:heading>
                        </div>
                        <div class="flex items-center gap-3 sm:gap-4">
                            <template x-if="timeLimit && timeLimit > 0">
                                <div class="text-right">
                                    <flux:text class="text-gray-600 dark:text-gray-400 text-sm">Time Remaining</flux:text>
                                    <div class="text-2xl sm:text-3xl font-bold font-mono"
                                         :class="timeRemaining < 600 ? 'text-red-600 dark:text-red-400' : 'text-blue-600 dark:text-blue-300'"
                                         x-text="formatTime(timeRemaining)"></div>
                                </div>
                            </template>
                            <div class="flex gap-2">
                                <button
                                    @click="exitQuiz()"
                                    class="px-3 sm:px-4 py-2 bg-gray-600 dark:bg-gray-700 hover:bg-gray-700 dark:hover:bg-gray-600 text-white font-semibold rounded-lg transition-all shadow-sm text-sm sm:text-base">
                                    Exit & Continue Later
                                </button>
                                <button
                                    @click="submitQuiz()"
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
                        <template x-if="totalQuestions === 0">
                            <div class="rounded-xl border border-red-200 bg-red-50 dark:bg-red-900/20 p-8 text-center">
                                <flux:heading size="lg" class="text-red-700 dark:text-red-200 mb-2">No Questions Available</flux:heading>
                                <flux:text class="text-base text-red-800 dark:text-red-300">There are no questions available for your selection.</flux:text>
                                <div class="mt-6">
                                    <a href="/practice" class="inline-block px-6 py-2 rounded-lg bg-blue-600 text-white font-semibold hover:bg-blue-700 transition">Back to Practice</a>
                                </div>
                            </div>
                        </template>

                        <template x-if="currentQuestion">
                            <div>
                                {{-- Question Header --}}
                                <div class="mb-6">
                                    <div class="flex items-center justify-between mb-3 gap-3">
                                        <flux:text class="text-sm font-medium text-gray-600 dark:text-gray-400">Practice Exam</flux:text>
                                        <flux:badge color="blue" x-text="`Question ${currentQuestionIndex + 1} of ${totalQuestions}`"></flux:badge>
                                    </div>
                                </div>

                                {{-- Question Card --}}
                                <div class="rounded-xl border border-gray-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-5 sm:p-6 shadow-sm mb-8">
                                    <flux:text class="text-lg font-medium leading-relaxed text-gray-900 dark:text-gray-100" x-text="currentQuestion.question_text"></flux:text>
                                    <template x-if="currentQuestion.question_image">
                                        <img :src="currentQuestion.question_image" alt="Question" class="mt-4 max-w-full h-auto rounded-lg" loading="lazy">
                                    </template>
                                </div>

                                {{-- Options --}}
                                <div class="space-y-3 mb-8">
                                    <template x-for="(option, idx) in currentQuestion.options" :key="option.id">
                                        <button
                                            @click="selectAnswer(option.id)"
                                            :class="{
                                                'border-green-500 bg-green-50 dark:bg-green-900/20 ring-2 ring-green-300 dark:ring-green-700': userAnswers[currentQuestionIndex] && userAnswers[currentQuestionIndex] === option.id && option.is_correct,
                                                'border-red-500 bg-red-50 dark:bg-red-900/20 ring-2 ring-red-300 dark:ring-red-700': userAnswers[currentQuestionIndex] && userAnswers[currentQuestionIndex] === option.id && !option.is_correct,
                                                'border-green-400 bg-green-50/50 dark:bg-green-900/10': userAnswers[currentQuestionIndex] && userAnswers[currentQuestionIndex] !== option.id && option.is_correct,
                                                'border-gray-200 dark:border-neutral-800 hover:border-green-400': !userAnswers[currentQuestionIndex]
                                            }"
                                            class="w-full p-4 rounded-xl border-2 text-left transition-all">
                                            <div class="flex items-start gap-3">
                                                <div :class="{
                                                    'border-green-500 bg-green-500': userAnswers[currentQuestionIndex] === option.id && option.is_correct,
                                                    'border-red-500 bg-red-500': userAnswers[currentQuestionIndex] === option.id && !option.is_correct,
                                                    'border-green-400 bg-green-400': userAnswers[currentQuestionIndex] && userAnswers[currentQuestionIndex] !== option.id && option.is_correct,
                                                    'border-gray-300 dark:border-gray-700': !userAnswers[currentQuestionIndex]
                                                }"
                                                class="flex-shrink-0 w-6 h-6 rounded-full border-2 flex items-center justify-center mt-0.5 transition-all">
                                                    <template x-if="userAnswers[currentQuestionIndex] === option.id">
                                                        <div class="w-2.5 h-2.5 bg-white rounded-full"></div>
                                                    </template>
                                                </div>
                                                <span :class="{
                                                    'text-green-700 dark:text-green-200 font-medium': userAnswers[currentQuestionIndex] === option.id && option.is_correct,
                                                    'text-red-700 dark:text-red-200 font-medium': userAnswers[currentQuestionIndex] === option.id && !option.is_correct,
                                                    'text-green-600 dark:text-green-300': userAnswers[currentQuestionIndex] && userAnswers[currentQuestionIndex] !== option.id && option.is_correct,
                                                    'text-gray-800 dark:text-gray-200': !userAnswers[currentQuestionIndex]
                                                }"
                                                class="flex-1" x-text="option.option_text"></span>
                                                <template x-if="userAnswers[currentQuestionIndex] === option.id && option.is_correct">
                                                    <svg class="h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                                </template>
                                                <template x-if="userAnswers[currentQuestionIndex] === option.id && !option.is_correct">
                                                    <svg class="h-5 w-5 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                                                </template>
                                                <template x-if="userAnswers[currentQuestionIndex] && userAnswers[currentQuestionIndex] !== option.id && option.is_correct">
                                                    <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                </template>
                                            </div>
                                        </button>
                                    </template>
                                </div>

                                {{-- Explanation --}}
                                <template x-if="userAnswers[currentQuestionIndex] && currentQuestion.explanation">
                                    <div class="mb-8 p-5 rounded-lg border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-950/20">
                                        <div class="flex gap-3">
                                            <div class="flex-shrink-0">
                                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                                </svg>
                                            </div>
                                            <div class="flex-1">
                                                <flux:heading size="sm" class="text-blue-900 dark:text-blue-300 mb-1 font-semibold">Explanation</flux:heading>
                                                <flux:text class="text-sm text-blue-800 dark:text-blue-400 leading-relaxed" x-text="currentQuestion.explanation"></flux:text>
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
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="hidden lg:block w-80 bg-white dark:bg-neutral-900 border-l border-gray-200 dark:border-neutral-800 overflow-y-auto p-4">
                <div class="mb-6 pb-6 border-b-2 border-blue-200 dark:border-blue-800">
                    <div class="flex items-center justify-between mb-3">
                        <flux:heading size="sm" level="3" class="mb-0 text-blue-600 dark:text-blue-300">Questions</flux:heading>
                        <flux:badge color="blue" class="text-xs" x-text="`${answeredCount}/${totalQuestions}`"></flux:badge>
                    </div>
                    <div class="grid grid-cols-5 gap-2">
                        <template x-for="i in totalQuestions" :key="i">
                            <button
                                @click="jumpToQuestion(i - 1)"
                                :class="{
                                    'bg-blue-600 text-white ring-2 ring-blue-300': currentQuestionIndex == i - 1,
                                    'bg-green-500 text-white hover:bg-green-600': userAnswers[i - 1] !== null && currentQuestionIndex != i - 1,
                                    'bg-gray-100 dark:bg-neutral-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200': userAnswers[i - 1] === null && currentQuestionIndex != i - 1
                                }"
                                class="aspect-square h-8 w-8 p-0 flex items-center justify-center rounded-lg text-xs font-medium transition-all"
                                x-text="i">
                            </button>
                        </template>
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <flux:text class="text-sm font-medium text-gray-600 dark:text-gray-400">Answered</flux:text>
                        <flux:text class="font-semibold text-gray-900 dark:text-gray-100" x-text="`${answeredCount}/${totalQuestions}`"></flux:text>
                    </div>
                    <button
                        @click="exitQuiz()"
                        class="w-full px-4 py-2 bg-gray-100 dark:bg-neutral-800 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-200 dark:hover:bg-neutral-700 transition-colors text-sm font-medium">
                        Exit & Save Progress
                    </button>
                </div>
            </div>
        </div>
    </template>

    {{-- Results Screen --}}
    <template x-if="showResults">
        <flux:container class="py-12">
            <div class="max-w-4xl mx-auto">
                <div class="text-center mb-8">
                    <flux:heading size="xl" class="mb-2">Quiz Complete!</flux:heading>
                    <flux:text class="text-gray-600 dark:text-gray-400">Here are your results</flux:text>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-white dark:bg-neutral-900 rounded-xl border border-gray-200 dark:border-neutral-800 p-6 text-center">
                        <flux:text class="text-sm text-gray-600 dark:text-gray-400 mb-2">Score</flux:text>
                        <div class="text-4xl font-bold text-blue-600 dark:text-blue-400" x-text="`${score}/${totalQuestions}`"></div>
                    </div>
                    <div class="bg-white dark:bg-neutral-900 rounded-xl border border-gray-200 dark:border-neutral-800 p-6 text-center">
                        <flux:text class="text-sm text-gray-600 dark:text-gray-400 mb-2">Percentage</flux:text>
                        <div class="text-4xl font-bold text-green-600 dark:text-green-400" x-text="`${percentage.toFixed(1)}%`"></div>
                    </div>
                    <div class="bg-white dark:bg-neutral-900 rounded-xl border border-gray-200 dark:border-neutral-800 p-6 text-center">
                        <flux:text class="text-sm text-gray-600 dark:text-gray-400 mb-2">Status</flux:text>
                        <div class="text-2xl font-bold" :class="percentage >= 50 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'" x-text="percentage >= 50 ? 'PASSED' : 'FAILED'"></div>
                    </div>
                </div>

                <div class="flex gap-4 justify-center">
                    <a href="/practice" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">Try Another Quiz</a>
                    <a href="/dashboard" class="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition">Back to Dashboard</a>
                </div>
            </div>
        </flux:container>
    </template>
</div>

@push('scripts')
<script>
// Practice Quiz JavaScript Engine
function practiceQuiz() {
    return {
        // State
        attemptId: null,
        questions: [],
        allQuestionIds: [],
        userAnswers: [],
        currentQuestionIndex: 0,
        totalQuestions: 0,
        loadedUpToIndex: -1,
        timeRemaining: 0,
        timeLimit: null,
        startedAt: null,
        timer: null,
        autosaveTimer: null,
        isLoading: false,
        isSaving: false,
        showResults: false,
        score: 0,
        percentage: 0,

        // Initialize
        async init() {
            const urlParams = new URLSearchParams(window.location.search);
            const attemptId = urlParams.get('attempt');

            if (attemptId) {
                await this.loadAttempt(attemptId);
            } else {
                await this.startNewQuiz();
            }

            if (this.timeLimit && this.timeLimit > 0) {
                this.startTimer();
            }

            // Setup autosave every 10 seconds
            this.autosaveTimer = setInterval(() => this.autosave(), 10000);

            // Save on page unload
            window.addEventListener('beforeunload', (e) => {
                if (!this.showResults) {
                    this.saveSync();
                }
            });
        },

        // Start new quiz
        async startNewQuiz() {
            this.isLoading = true;
            const urlParams = new URLSearchParams(window.location.search);

            try {
                const response = await fetch('/api/practice/start', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        exam_type: urlParams.get('exam_type'),
                        subject: urlParams.get('subject'),
                        year: urlParams.get('year'),
                        shuffle: urlParams.get('shuffle') === '1',
                        limit: urlParams.get('limit'),
                        time: urlParams.get('time'),
                    }),
                });

                const data = await response.json();

                if (data.success) {
                    this.attemptId = data.attempt_id;
                    this.questions = data.questions;
                    this.allQuestionIds = data.all_question_ids;
                    this.userAnswers = data.user_answers;
                    this.totalQuestions = data.total_questions;
                    this.loadedUpToIndex = data.loaded_up_to_index;
                    this.currentQuestionIndex = 0;
                    this.timeLimit = data.time_limit;
                    this.startedAt = new Date(data.started_at);

                    if (this.timeLimit) {
                        this.timeRemaining = this.timeLimit * 60;
                    }
                } else {
                    alert('Failed to start quiz: ' + (data.error || 'Unknown error'));
                }
            } catch (error) {
                console.error('Failed to start quiz:', error);
                alert('Failed to start quiz. Please try again.');
            } finally {
                this.isLoading = false;
            }
        },

        // Load existing attempt
        async loadAttempt(attemptId) {
            this.isLoading = true;

            try {
                const response = await fetch(`/api/practice/load/${attemptId}`, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                });

                const data = await response.json();

                if (data.success) {
                    this.attemptId = data.attempt_id;
                    this.questions = data.questions;
                    this.allQuestionIds = data.all_question_ids;
                    this.userAnswers = data.user_answers;
                    this.totalQuestions = data.total_questions;
                    this.loadedUpToIndex = data.loaded_up_to_index;
                    this.currentQuestionIndex = data.current_question_index;
                    this.timeLimit = data.time_limit;
                    this.startedAt = new Date(data.started_at);

                    if (this.timeLimit) {
                        const elapsed = Math.floor((Date.now() - this.startedAt.getTime()) / 1000);
                        this.timeRemaining = Math.max(0, this.timeLimit * 60 - elapsed);
                    }
                }
            } catch (error) {
                console.error('Failed to load attempt:', error);
            } finally {
                this.isLoading = false;
            }
        },

        // Timer management
        startTimer() {
            this.timer = setInterval(() => {
                if (this.timeRemaining > 0) {
                    this.timeRemaining--;
                } else {
                    clearInterval(this.timer);
                    this.handleTimerExpired();
                }
            }, 1000);
        },

        handleTimerExpired() {
            if (!this.showResults) {
                alert('Time is up! Submitting your quiz...');
                this.submitQuiz();
            }
        },

        formatTime(seconds) {
            const h = Math.floor(seconds / 3600);
            const m = Math.floor((seconds % 3600) / 60);
            const s = seconds % 60;
            return `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
        },

        // Question navigation
        async nextQuestion() {
            if (this.currentQuestionIndex < this.totalQuestions - 1) {
                this.currentQuestionIndex++;

                // Lazy load if approaching end of loaded batch
                if (this.currentQuestionIndex > this.loadedUpToIndex - 2 &&
                    this.loadedUpToIndex < this.totalQuestions - 1) {
                    await this.loadMoreQuestions();
                }
            }
        },

        previousQuestion() {
            if (this.currentQuestionIndex > 0) {
                this.currentQuestionIndex--;
            }
        },

        async jumpToQuestion(index) {
            this.currentQuestionIndex = index;

            // Load questions if jumping ahead
            while (index > this.loadedUpToIndex && this.loadedUpToIndex < this.totalQuestions - 1) {
                await this.loadMoreQuestions();
            }
        },

        // Lazy loading
        async loadMoreQuestions() {
            if (this.loadedUpToIndex >= this.totalQuestions - 1) {
                return;
            }

            const startIndex = this.loadedUpToIndex + 1;
            const endIndex = Math.min(startIndex + 4, this.totalQuestions - 1);
            const batchIds = this.allQuestionIds.slice(startIndex, endIndex + 1);

            try {
                const response = await fetch('/api/practice/load-batch', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        question_ids: batchIds,
                        shuffle: false,
                    }),
                });

                const data = await response.json();

                if (data.success) {
                    this.questions = [...this.questions, ...data.questions];
                    this.loadedUpToIndex = endIndex;
                }
            } catch (error) {
                console.error('Failed to load more questions:', error);
            }
        },

        // Answer selection
        selectAnswer(optionId) {
            this.userAnswers[this.currentQuestionIndex] = optionId;
        },

        // Computed properties
        get currentQuestion() {
            return this.questions[this.currentQuestionIndex] || null;
        },

        get answeredCount() {
            return this.userAnswers.filter(a => a !== null).length;
        },

        // Autosave (debounced)
        async autosave() {
            if (this.isSaving || !this.attemptId || this.showResults) {
                return;
            }

            this.isSaving = true;

            try {
                await fetch('/api/practice/save', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        attempt_id: this.attemptId,
                        answers: this.userAnswers,
                        current_question_index: this.currentQuestionIndex,
                        all_question_ids: this.allQuestionIds,
                        questions: this.questions,
                        loaded_up_to_index: this.loadedUpToIndex,
                    }),
                });
            } catch (error) {
                console.error('Autosave failed:', error);
            } finally {
                this.isSaving = false;
            }
        },

        // Synchronous save for page unload
        saveSync() {
            if (!this.attemptId || this.showResults) {
                return;
            }

            navigator.sendBeacon('/api/practice/save', new Blob([JSON.stringify({
                attempt_id: this.attemptId,
                answers: this.userAnswers,
                current_question_index: this.currentQuestionIndex,
                all_question_ids: this.allQuestionIds,
                questions: this.questions,
                loaded_up_to_index: this.loadedUpToIndex,
            })], { type: 'application/json' }));
        },

        // Submit quiz
        async submitQuiz() {
            const answered = this.answeredCount;
            const total = this.totalQuestions;

            if (!confirm(`Are you sure you want to submit your exam?\n\nYou have answered ${answered} out of ${total} questions.\n\nOnce submitted, you won't be able to change your answers.`)) {
                return;
            }

            this.isLoading = true;

            try {
                const response = await fetch('/api/practice/submit', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        attempt_id: this.attemptId,
                        answers: this.userAnswers,
                        all_question_ids: this.allQuestionIds,
                    }),
                });

                const data = await response.json();

                if (data.success) {
                    this.score = data.score;
                    this.percentage = data.percentage;
                    this.showResults = true;
                    clearInterval(this.timer);
                    clearInterval(this.autosaveTimer);
                }
            } catch (error) {
                console.error('Failed to submit quiz:', error);
                alert('Failed to submit quiz. Please try again.');
            } finally {
                this.isLoading = false;
            }
        },

        // Exit and save
        async exitQuiz() {
            const answered = this.answeredCount;
            const total = this.totalQuestions;

            if (!confirm(`Your progress will be saved and you can continue this practice exam later.\n\nCurrent progress: ${answered} of ${total} questions answered.\n\nDo you want to exit?`)) {
                return;
            }

            this.isLoading = true;

            try {
                await fetch('/api/practice/exit', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        attempt_id: this.attemptId,
                        answers: this.userAnswers,
                        all_question_ids: this.allQuestionIds,
                        current_question_index: this.currentQuestionIndex,
                    }),
                });

                window.location.href = '/practice';
            } catch (error) {
                console.error('Failed to exit quiz:', error);
                alert('Failed to save progress. Please try again.');
            } finally {
                this.isLoading = false;
            }
        },
    };
}

@endpush
