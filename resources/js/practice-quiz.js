/**
 * Practice Quiz JavaScript Engine
 * High-performance client-side quiz with autosave
 */

export function practiceQuiz() {
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
