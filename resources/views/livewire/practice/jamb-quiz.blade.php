<div x-data="{
    timeRemaining: @entangle('timeRemaining'),
    currentSubjectIndex: @entangle('currentSubjectIndex'),
    currentQuestionIndex: @entangle('currentQuestionIndex'),
    questionsBySubject: @js($questionsBySubject),
    userAnswers: @js($userAnswers),
    subjectsData: @js($subjectsData),
    questionsPerSubject: @js($questionsPerSubject),
    csrfToken: @js(csrf_token()),
    quizAttemptId: @js($quizAttemptId),
    hasResults: {{ $showResults ? 'true' : 'false' }},
    showProgressGrid: false,
    timer: null,
    autosaveTimer: null,
    autosaveDebounce: false,

    init() {
        if (!this.hasResults) {
            if (this.timeRemaining !== null) {
                this.startTimer();
            }
            // Start autosave every 10 seconds (cache-only, no database writes)
            this.autosaveTimer = setInterval(() => this.autosave(), 10000);
            // Save on page unload
            window.addEventListener('beforeunload', () => this.saveSync());
        }
    },

    startTimer() {
        this.timer = setInterval(() => {
            if (this.timeRemaining > 0) {
                this.timeRemaining--;
            } else {
                clearInterval(this.timer);
                $wire.call('handleTimerEnd');
            }
        }, 1000);
    },

    formatTime(seconds) {
        const h = Math.floor(seconds / 3600);
        const m = Math.floor((seconds % 3600) / 60);
        const s = seconds % 60;
        return `${h.toString().padStart(2,'0')}:${m.toString().padStart(2,'0')}:${s.toString().padStart(2,'0')}`;
    },

    getCurrentSubjectId() {
        return this.subjectsData[this.currentSubjectIndex]?.id;
    },

    getCurrentQuestions() {
        return this.questionsBySubject[this.getCurrentSubjectId()] || [];
    },

    getCurrentQuestion() {
        return this.getCurrentQuestions()[this.currentQuestionIndex] || null;
    },

    selectAnswer(optionId) {
        // Instant client-side feedback - NO server call
        const subjectId = this.getCurrentSubjectId();
        this.userAnswers[subjectId][this.currentQuestionIndex] = optionId;
        this.autosaveDebounce = true;
    },

    async autosave() {
        if (!this.autosaveDebounce || !this.quizAttemptId) return;

        try {
            // Send to server for CACHING (not database save)
            // Database is only written on submit
            const response = await fetch('/jamb/autosave', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                },
                body: JSON.stringify({
                    attempt_id: this.quizAttemptId,
                    questions: this.questionsBySubject,
                    answers: this.userAnswers,
                    position: {
                        subjectIndex: this.currentSubjectIndex,
                        questionIndex: this.currentQuestionIndex,
                    },
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
        if (this.quizAttemptId) {
            fetch('/jamb/autosave', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                },
                body: JSON.stringify({
                    attempt_id: this.quizAttemptId,
                    questions: this.questionsBySubject,
                    answers: this.userAnswers,
                    position: {
                        subjectIndex: this.currentSubjectIndex,
                        questionIndex: this.currentQuestionIndex,
                    },
                }),
                keepalive: true,
            });
        }
    },

    switchSubject(index) {
        this.currentSubjectIndex = index;
        this.currentQuestionIndex = 0;
    },

    nextQuestion() {
        const maxIndex = this.getCurrentQuestions().length - 1;

        if (this.currentQuestionIndex < maxIndex) {
            this.currentQuestionIndex++;
        } else if (this.currentSubjectIndex < this.subjectsData.length - 1) {
            this.currentSubjectIndex++;
            this.currentQuestionIndex = 0;
        }
    },

    previousQuestion() {
        if (this.currentQuestionIndex > 0) {
            this.currentQuestionIndex--;
        } else if (this.currentSubjectIndex > 0) {
            this.currentSubjectIndex--;
            const prevSubjectId = this.subjectsData[this.currentSubjectIndex].id;
            this.currentQuestionIndex = Math.max(this.questionsBySubject[prevSubjectId].length - 1, 0);
        }
    },

    jumpToQuestion(subjectIndex, questionIndex) {
        this.currentSubjectIndex = subjectIndex;
        this.currentQuestionIndex = questionIndex;
    },

    getAnsweredCount(subjectId) {
        return this.userAnswers[subjectId]?.filter(a => a !== null).length || 0;
    },

    getTotalInSubject() {
        return this.getCurrentQuestions().length;
    },

    confirmSubmit() {
        const answered = Object.values(this.userAnswers).reduce((sum, answers) =>
            sum + (Array.isArray(answers) ? answers.filter(a => a !== null).length : 0), 0
        );
        const total = Object.values(this.questionsBySubject).reduce((sum, questions) =>
            sum + (Array.isArray(questions) ? questions.length : 0), 0
        );

        if (confirm(`Are you sure you want to submit your test?\\n\\nYou have answered ${answered} out of ${total} questions.\\n\\nOnce submitted, you won't be able to change your answers.`)) {
            $wire.call('submitQuiz');
        }
    },

    confirmExit() {
        const answered = Object.values(this.userAnswers).reduce((sum, answers) =>
            sum + (Array.isArray(answers) ? answers.filter(a => a !== null).length : 0), 0
        );
        const total = Object.values(this.questionsBySubject).reduce((sum, questions) =>
            sum + (Array.isArray(questions) ? questions.length : 0), 0
        );

        if (confirm(`Your progress will be saved and you can continue this practice test later.\\n\\nCurrent progress: ${answered} of ${total} questions answered.\\n\\nDo you want to exit?`)) {
            this.autosave().then(() => $wire.call('exitQuiz'));
        }
    }
}" class="bg-neutral-50 dark:bg-neutral-900 min-h-screen">

@if(!$showResults)
    <div wire:key="jamb-quiz-mode" class="space-y-4 md:space-y-6 p-4 md:p-6">
        <!-- Header -->
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-4 md:p-6 bg-white dark:bg-neutral-800">
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                <div class="flex-1">
                    <flux:heading size="lg" level="1" class="mb-1">JAMB Practice Test</flux:heading>
                    <flux:text class="text-neutral-600 dark:text-neutral-400 text-sm" x-text="subjectsData[currentSubjectIndex]?.name || ''"></flux:text>
                </div>
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 sm:gap-6 w-full md:w-auto">
                    @if($timeLimit)
                        <div class="text-left" wire:ignore>
                            <flux:text class="text-neutral-600 dark:text-neutral-400 text-xs md:text-sm">Time Remaining</flux:text>
                            <div class="text-2xl md:text-3xl font-bold font-mono" :class="timeRemaining < 600 ? 'text-red-600 dark:text-red-400' : 'text-blue-600 dark:text-blue-400'" x-text="formatTime(timeRemaining)"></div>
                        </div>
                    @endif
                    <div class="flex gap-2 w-full sm:w-auto">
                        <button
                            @click="confirmExit()"
                            class="flex-1 sm:flex-none px-3 sm:px-4 py-2 bg-gray-600 dark:bg-gray-700 hover:bg-gray-700 dark:hover:bg-gray-600 text-white font-semibold rounded-lg transition-all shadow-sm text-sm sm:text-base">
                            Exit & Continue Later
                        </button>
                        <button
                            @click="confirmSubmit()"
                            class="flex-1 sm:flex-none px-3 sm:px-4 py-2 bg-green-600 dark:bg-green-700 hover:bg-green-700 dark:hover:bg-green-600 text-white font-semibold rounded-lg transition-all shadow-sm text-sm sm:text-base">
                            Submit Test
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Subject Tabs Navigation -->
        <div class="overflow-x-auto">
            <div class="flex gap-2">
                <template x-for="(subject, index) in subjectsData" :key="subject.id">
                    <button
                        @click="switchSubject(index)"
                        :class="currentSubjectIndex === index ? 'bg-green-500 text-white shadow-md' : 'bg-white dark:bg-neutral-800 text-neutral-700 dark:text-neutral-300 hover:bg-neutral-50 dark:hover:bg-neutral-700 border border-neutral-200 dark:border-neutral-700'"
                        class="flex-shrink-0 px-4 py-3 rounded-lg whitespace-nowrap transition-all">
                        <div class="font-semibold text-sm" x-text="subject.name"></div>
                        <div class="text-xs mt-1" :class="currentSubjectIndex === index ? 'text-green-100' : 'text-neutral-500 dark:text-neutral-400'">
                            <span class="font-medium" x-text="getAnsweredCount(subject.id)"></span>/<span x-text="questionsPerSubject"></span>
                        </div>
                    </button>
                </template>
            </div>
        </div>

        <!-- Mobile Progress Toggle Button -->
        <div class="lg:hidden flex justify-center mb-4">
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
        <div x-show="showProgressGrid" x-transition x-cloak class="lg:hidden mb-6 rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 p-4">
            <div class="space-y-4">
                <template x-for="(subject, subjectIndex) in subjectsData" :key="subject.id">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <button
                                @click="switchSubject(subjectIndex); showProgressGrid = false"
                                class="text-sm font-medium text-neutral-700 dark:text-neutral-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors text-left"
                                x-text="subject.name">
                            </button>
                            <flux:text class="text-xs text-neutral-500 dark:text-neutral-400">
                                <span x-text="getAnsweredCount(subject.id)"></span>/<span x-text="questionsPerSubject"></span>
                            </flux:text>
                        </div>
                        <div class="grid grid-cols-10 gap-1">
                            <template x-for="i in questionsPerSubject" :key="i">
                                <button
                                    @click="jumpToQuestion(subjectIndex, i - 1); showProgressGrid = false"
                                    :title="'Q' + i"
                                    :class="{
                                        'bg-blue-500 text-white ring-2 ring-blue-300 dark:ring-blue-700': currentSubjectIndex === subjectIndex && currentQuestionIndex === (i - 1),
                                        'bg-green-500 text-white': (userAnswers[subject.id] && userAnswers[subject.id][i - 1] !== null) && !(currentSubjectIndex === subjectIndex && currentQuestionIndex === (i - 1)),
                                        'bg-neutral-200 dark:bg-neutral-700 text-neutral-600 dark:text-neutral-400': !userAnswers[subject.id] || userAnswers[subject.id][i - 1] === null
                                    }"
                                    class="aspect-square h-6 w-6 p-0 flex items-center justify-center rounded text-xs font-medium transition-all"
                                    x-text="i">
                                </button>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Main Quiz Container -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-6">
            <!-- Main Question Area -->
            <div class="lg:col-span-2 space-y-4 md:space-y-6" x-show="getCurrentQuestion()">
                <!-- Question Header -->
                <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-4 md:p-6 bg-white dark:bg-neutral-800">
                    <div class="flex items-center justify-between mb-4">
                        <flux:heading size="sm" class="text-base md:text-lg font-semibold mb-0">
                            Question <span x-text="currentQuestionIndex + 1"></span> of <span x-text="questionsPerSubject"></span>
                        </flux:heading>
                        <flux:text class="text-xs md:text-sm text-neutral-600 dark:text-neutral-400 font-medium" x-text="Math.round(((currentQuestionIndex + 1) / questionsPerSubject) * 100) + '%'">
                        </flux:text>
                    </div>
                    <div class="w-full bg-neutral-200 dark:bg-neutral-700 rounded-full h-2.5">
                        <div class="bg-blue-600 dark:bg-blue-500 h-2.5 rounded-full transition-all" :style="`width: ${((currentQuestionIndex + 1) / questionsPerSubject) * 100}%`"></div>
                    </div>
                </div>

                <!-- Question Text -->
                <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-4 md:p-8 bg-white dark:bg-neutral-800">
                    <flux:heading size="lg" class="text-lg md:text-xl leading-relaxed mb-4" x-text="getCurrentQuestion()?.question_text || ''"></flux:heading>
                    <template x-if="getCurrentQuestion()?.question_image">
                        <img :src="getCurrentQuestion().question_image" alt="Question" class="mt-4 max-w-full h-auto rounded-lg" loading="lazy">
                    </template>
                </div>

                <!-- Answer Options with Instant Feedback -->
                <div class="space-y-3">
                    <template x-for="option in getCurrentQuestion()?.options || []" :key="option.id">
                        <button
                            @click="selectAnswer(option.id)"
                            :class="{
                                'border-green-500 bg-green-50 dark:bg-green-950/20': userAnswers[getCurrentSubjectId()][currentQuestionIndex] !== null && option.is_correct,
                                'border-red-500 bg-red-50 dark:bg-red-950/20': userAnswers[getCurrentSubjectId()][currentQuestionIndex] === option.id && !option.is_correct,
                                'border-neutral-200 dark:border-neutral-700 bg-neutral-50/50 dark:bg-neutral-900/50 opacity-60': userAnswers[getCurrentSubjectId()][currentQuestionIndex] !== null && userAnswers[getCurrentSubjectId()][currentQuestionIndex] !== option.id && !option.is_correct,
                                'border-blue-500 bg-blue-50 dark:bg-blue-900/20': userAnswers[getCurrentSubjectId()][currentQuestionIndex] === option.id && userAnswers[getCurrentSubjectId()][currentQuestionIndex] === null,
                                'border-neutral-200 dark:border-neutral-700 hover:border-blue-400 dark:hover:border-blue-600': userAnswers[getCurrentSubjectId()][currentQuestionIndex] === null
                            }"
                            class="w-full p-4 rounded-lg border-2 text-left transition-all">
                            <div class="flex items-start gap-3">
                                <!-- Radio/Status Icon -->
                                <div class="flex-shrink-0 mt-0.5">
                                    <!-- Correct answer (green check) -->
                                    <template x-if="userAnswers[getCurrentSubjectId()][currentQuestionIndex] !== null && option.is_correct">
                                        <div class="w-6 h-6 rounded-full bg-green-500 dark:bg-green-600 flex items-center justify-center">
                                            <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                        </div>
                                    </template>
                                    <!-- Wrong answer (red X) -->
                                    <template x-if="userAnswers[getCurrentSubjectId()][currentQuestionIndex] === option.id && !option.is_correct">
                                        <div class="w-6 h-6 rounded-full bg-red-500 dark:bg-red-600 flex items-center justify-center">
                                            <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                            </svg>
                                        </div>
                                    </template>
                                    <!-- Unanswered (empty circle) -->
                                    <template x-if="userAnswers[getCurrentSubjectId()][currentQuestionIndex] === null || (userAnswers[getCurrentSubjectId()][currentQuestionIndex] !== option.id && userAnswers[getCurrentSubjectId()][currentQuestionIndex] !== null && !option.is_correct)">
                                        <div class="w-6 h-6 rounded-full border-2" :class="userAnswers[getCurrentSubjectId()][currentQuestionIndex] === option.id ? 'border-blue-500 bg-blue-500' : 'border-neutral-300 dark:border-neutral-600'">
                                            <template x-if="userAnswers[getCurrentSubjectId()][currentQuestionIndex] === option.id">
                                                <div class="w-2.5 h-2.5 bg-white rounded-full"></div>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                                <!-- Option Text -->
                                <div class="flex-1">
                                    <span
                                        :class="{
                                            'text-green-800 dark:text-green-300 font-medium': userAnswers[getCurrentSubjectId()][currentQuestionIndex] !== null && option.is_correct,
                                            'text-red-800 dark:text-red-300': userAnswers[getCurrentSubjectId()][currentQuestionIndex] === option.id && !option.is_correct,
                                            'text-blue-700 dark:text-blue-300 font-medium': userAnswers[getCurrentSubjectId()][currentQuestionIndex] === option.id && userAnswers[getCurrentSubjectId()][currentQuestionIndex] === null,
                                            'text-neutral-700 dark:text-neutral-300': userAnswers[getCurrentSubjectId()][currentQuestionIndex] === null || (userAnswers[getCurrentSubjectId()][currentQuestionIndex] !== option.id && !option.is_correct)
                                        }"
                                        x-text="option.option_text">
                                    </span>
                                </div>
                            </div>
                        </button>
                    </template>
                </div>

                <!-- Instant Explanation -->
                <template x-if="userAnswers[getCurrentSubjectId()][currentQuestionIndex] !== null && getCurrentQuestion()?.explanation">
                    <div class="rounded-lg border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-950/20 p-4 animate-fade-in">
                        <div class="flex gap-3">
                            <div class="flex-shrink-0">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <flux:heading size="sm" class="text-blue-900 dark:text-blue-300 mb-1 font-semibold">Explanation</flux:heading>
                                <flux:text class="text-sm text-blue-800 dark:text-blue-400 leading-relaxed" x-text="getCurrentQuestion()?.explanation || ''"></flux:text>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Navigation Buttons -->
                <div class="flex flex-col sm:flex-row justify-between items-center gap-3 pt-4 border-t border-neutral-200 dark:border-neutral-700">
                    <button
                        @click="previousQuestion()"
                        :disabled="currentSubjectIndex === 0 && currentQuestionIndex === 0"
                        :class="(currentSubjectIndex === 0 && currentQuestionIndex === 0) ? 'opacity-50 cursor-not-allowed' : ''"
                        class="w-full sm:w-auto px-4 py-2 border border-neutral-300 dark:border-neutral-600 text-neutral-700 dark:text-neutral-300 font-medium rounded-lg hover:bg-neutral-50 dark:hover:bg-neutral-700 transition-all">
                        ← Previous
                    </button>
                    <flux:text class="text-xs md:text-sm text-neutral-600 dark:text-neutral-400 font-medium">
                        Question <span x-text="currentQuestionIndex + 1"></span>/<span x-text="questionsPerSubject"></span>
                    </flux:text>
                    <button
                        @click="nextQuestion()"
                        class="w-full sm:w-auto px-4 py-2 bg-green-600 dark:bg-green-700 hover:bg-green-700 dark:hover:bg-green-600 text-white font-medium rounded-lg transition-all"
                        x-text="currentQuestionIndex === (questionsPerSubject - 1) ? 'Next Subject' : 'Next'">
                    </button>
                </div>
            </div>

            <!-- Right Sidebar: Question Navigator (Hidden on Mobile) -->
            <div class="hidden lg:block bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 rounded-xl p-4 h-fit sticky top-4 max-h-[calc(100vh-2rem)] overflow-y-auto">
                <!-- Current Subject Questions -->
                <div class="mb-6 pb-6 border-b-2 border-blue-200 dark:border-blue-800">
                    <div class="flex items-center justify-between mb-3">
                        <flux:heading size="sm" level="3" class="mb-0 text-blue-600 dark:text-blue-400" x-text="subjectsData[currentSubjectIndex]?.name || ''"></flux:heading>
                        <flux:badge color="blue" class="text-xs">
                            <span x-text="getAnsweredCount(getCurrentSubjectId())"></span>/<span x-text="questionsPerSubject"></span>
                        </flux:badge>
                    </div>
                    <div class="grid grid-cols-5 gap-2">
                        <template x-for="i in questionsPerSubject" :key="i">
                            <button
                                @click="jumpToQuestion(currentSubjectIndex, i - 1)"
                                :title="'Question ' + i"
                                :class="{
                                    'bg-green-500 text-white ring-2 ring-green-300 dark:ring-green-700': currentQuestionIndex === (i - 1),
                                    'bg-green-500 text-white hover:bg-green-600 dark:bg-green-600 dark:hover:bg-green-500': userAnswers[getCurrentSubjectId()] && userAnswers[getCurrentSubjectId()][i - 1] !== null && currentQuestionIndex !== (i - 1),
                                    'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600': !userAnswers[getCurrentSubjectId()] || userAnswers[getCurrentSubjectId()][i - 1] === null
                                }"
                                class="aspect-square h-8 w-8 p-0 flex items-center justify-center rounded-lg text-xs font-medium transition-all"
                                x-text="i">
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Other Subjects -->
                <template x-if="subjectsData.length > 1">
                    <div>
                        <flux:text class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-3">Other Subjects</flux:text>
                        <template x-for="(subject, subjectIndex) in subjectsData" :key="subject.id">
                            <template x-if="subjectIndex !== currentSubjectIndex">
                                <div class="mb-4 pb-3 border-b border-gray-200 dark:border-gray-700">
                                    <div class="flex items-center justify-between mb-2">
                                        <button
                                            @click="switchSubject(subjectIndex)"
                                            class="text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors text-left"
                                            x-text="subject.name">
                                        </button>
                                        <flux:text class="text-xs text-gray-500 dark:text-gray-400">
                                            <span x-text="getAnsweredCount(subject.id)"></span>/<span x-text="questionsPerSubject"></span>
                                        </flux:text>
                                    </div>
                                    <div class="grid grid-cols-8 gap-1">
                                        <template x-for="i in questionsPerSubject" :key="i">
                                            <button
                                                @click="jumpToQuestion(subjectIndex, i - 1)"
                                                :title="'Q' + i"
                                                :class="(userAnswers[subject.id] && userAnswers[subject.id][i - 1] !== null) ? 'bg-green-500 text-white hover:bg-green-600 dark:bg-green-600 dark:hover:bg-green-500' : 'bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400 hover:bg-gray-300 dark:hover:bg-gray-600'"
                                                class="aspect-square h-6 w-6 p-0 flex items-center justify-center rounded text-xs font-medium transition-all">
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </template>
                    </div>
                </template>

                <!-- Legend -->
                <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <flux:text class="text-sm font-medium mb-3">Legend</flux:text>
                    <div class="space-y-2 text-sm">
                        <div class="flex items-center gap-2">
                            <div class="w-4 h-4 rounded bg-blue-500 dark:bg-blue-400"></div>
                            <flux:text class="text-xs text-gray-600 dark:text-gray-400">Current</flux:text>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-4 h-4 rounded bg-green-500 dark:bg-green-400"></div>
                            <flux:text class="text-xs text-gray-600 dark:text-gray-400">Answered</flux:text>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-4 h-4 rounded bg-gray-200 dark:bg-gray-700"></div>
                            <flux:text class="text-xs text-gray-600 dark:text-gray-400">Not Answered</flux:text>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@else
    <!-- Results Screen -->
    <div wire:key="jamb-results-mode" class="bg-neutral-50 dark:bg-neutral-900 min-h-screen p-4 md:p-6">
        <div class="max-w-4xl mx-auto space-y-6 md:space-y-8">
            <!-- Header -->
            <div class="text-center">
                <flux:heading size="2xl" level="1" class="mb-2">Test Completed!</flux:heading>
                <flux:text class="text-neutral-600 dark:text-neutral-400">Here's your detailed performance breakdown</flux:text>
            </div>

            <!-- Overall Score Card -->
            <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 p-6 md:p-8 text-center">
                @php
                    $scoresBySubject = $this->getScoresBySubject();
                    $totalScore = array_sum($scoresBySubject);
                    $totalQuestions = count($subjectsData) * $questionsPerSubject;
                    $percentage = $totalQuestions > 0 ? ($totalScore / $totalQuestions) * 100 : 0;
                @endphp
                <div class="flex flex-col items-center">
                    <div class="inline-flex items-center justify-center w-32 h-32 md:w-48 md:h-48 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 text-white mb-6">
                        <div class="text-center">
                            <flux:heading size="2xl" level="2" class="text-white mb-0 font-bold md:text-3xl">{{ $totalScore }}</flux:heading>
                            <flux:text class="text-blue-100 text-base md:text-lg">/ {{ $totalQuestions }}</flux:text>
                        </div>
                    </div>
                    <flux:heading size="xl" level="2" class="mb-1 md:text-2xl">{{ number_format($percentage, 1) }}%</flux:heading>
                    <flux:text class="text-neutral-600 dark:text-neutral-400">Overall Score</flux:text>
                </div>
            </div>

            <!-- Subject Scores -->
            <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 p-4 md:p-6">
                <flux:heading size="lg" level="2" class="mb-6">Subject Breakdown</flux:heading>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($subjectsData as $subject)
                        @php
                            $score = $scoresBySubject[$subject->id] ?? 0;
                            $subjectPercentage = $questionsPerSubject > 0 ? ($score / $questionsPerSubject) * 100 : 0;
                        @endphp
                        <div wire:key="score-{{ $subject->id }}" class="p-4 rounded-lg bg-neutral-50 dark:bg-neutral-700">
                            <flux:text class="font-medium mb-2">{{ $subject->name }}</flux:text>
                            <div class="flex items-center justify-between mb-3">
                                <flux:heading size="xl" level="3" class="text-blue-600 dark:text-blue-400 mb-0">{{ $score }}/{{ $questionsPerSubject }}</flux:heading>
                                <flux:text class="text-neutral-600 dark:text-neutral-400">{{ number_format($subjectPercentage, 1) }}%</flux:text>
                            </div>
                            <div class="h-2 bg-neutral-200 dark:bg-neutral-600 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-blue-500 to-indigo-500 rounded-full" style="width: {{ $subjectPercentage }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row flex-wrap gap-3 justify-center">
                <button
                    wire:click="toggleReview"
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-75"
                    wire:target="toggleReview"
                    class="px-6 py-2 bg-green-600 dark:bg-green-700 hover:bg-green-700 dark:hover:bg-green-600 text-white font-semibold rounded-lg transition-all">
                    {{ $showReview ? 'Hide' : 'View' }} Answer Review
                </button>
                <a href="{{ route('practice.jamb.setup') }}" class="px-6 py-2 text-gray-700 dark:text-gray-300 font-semibold rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-all inline-flex items-center justify-center gap-2">
                    Try Another Test
                </a>
                <a href="{{ route('dashboard') }}" class="px-6 py-2 text-gray-700 dark:text-gray-300 font-semibold rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-all inline-flex items-center justify-center gap-2">
                    Back to Dashboard
                </a>
            </div>

            <!-- Answer Review -->
            @if($showReview)
                <div class="space-y-6 mt-8">
                    @foreach($subjectsData as $subjectIndex => $subject)
                        <div wire:key="review-subject-{{ $subject->id }}" class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 p-4 md:p-6">
                            <flux:heading size="lg" level="2" class="mb-6">{{ $subject->name }} - Answer Review</flux:heading>
                            <div class="space-y-6">
                                @foreach($questionsBySubject[$subject->id] as $qIndex => $question)
                                    @php
                                        $userAnswer = $userAnswers[$subject->id][$qIndex] ?? null;
                                        $correctOption = $question->options->firstWhere('is_correct', true);
                                        $isCorrect = $userAnswer && $correctOption && $userAnswer == $correctOption->id;
                                        $userOption = $question->options->firstWhere('id', $userAnswer);
                                    @endphp
                                    <div wire:key="review-q-{{ $subject->id }}-{{ $qIndex }}" class="p-4 rounded-lg {{ $isCorrect ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' : ($userAnswer ? 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800' : 'bg-neutral-50 dark:bg-neutral-700 border border-neutral-200 dark:border-neutral-600') }}">
                                        <div class="flex items-start gap-2 mb-3">
                                            <flux:badge :color="$isCorrect ? 'green' : ($userAnswer ? 'red' : 'gray')">
                                                Q{{ $qIndex + 1 }}
                                            </flux:badge>
                                            <flux:text class="flex-1 font-medium text-sm">{{ $question->question_text }}</flux:text>
                                        </div>

                                        @if($userAnswer)
                                            <div class="ml-12 space-y-2 text-sm">
                                                <div>
                                                    <flux:text class="text-xs font-medium text-neutral-600 dark:text-neutral-400">Your answer:</flux:text>
                                                    <flux:text class="{{ $isCorrect ? 'text-green-700 dark:text-green-300 font-medium' : 'text-red-700 dark:text-red-300 font-medium' }}">
                                                        {{ $userOption->option_text }}
                                                    </flux:text>
                                                </div>
                                                @if(!$isCorrect && $correctOption)
                                                    <div>
                                                        <flux:text class="text-xs font-medium text-neutral-600 dark:text-neutral-400">Correct answer:</flux:text>
                                                        <flux:text class="text-green-700 dark:text-green-300 font-medium">
                                                            {{ $correctOption->option_text }}
                                                        </flux:text>
                                                    </div>
                                                @endif
                                                @if($question->explanation && $showExplanations)
                                                    <div class="mt-2 pt-2 border-t border-neutral-200 dark:border-neutral-600">
                                                        <flux:text class="text-xs font-medium text-neutral-600 dark:text-neutral-400">Explanation:</flux:text>
                                                        <flux:text class="text-neutral-700 dark:text-neutral-300">{{ $question->explanation }}</flux:text>
                                                    </div>
                                                @endif
                                            </div>
                                        @else
                                            <div class="ml-12 space-y-2 text-sm">
                                                <flux:text class="text-neutral-600 dark:text-neutral-400">Not answered</flux:text>
                                                @if($correctOption)
                                                    <div>
                                                        <flux:text class="text-xs font-medium text-neutral-600 dark:text-neutral-400">Correct answer:</flux:text>
                                                        <flux:text class="text-green-700 dark:text-green-300 font-medium">
                                                            {{ $correctOption->option_text }}
                                                        </flux:text>
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endif
</div>

