<div x-data="{
    timeRemaining: @entangle('timeRemaining'),
    currentSubjectIndex: @entangle('currentSubjectIndex'),
    currentQuestionIndex: @entangle('currentQuestionIndex'),
    questionsBySubject: @js($questionsBySubject),
    userAnswers: @js($userAnswers),
    subjectsData: @js($subjectsData),
    csrfToken: @js(csrf_token()),
    sessionId: @js(request()->query('session')),
    timer: null,
    autosaveTimer: null,
    autosaveDebounce: false,

    init() {
        this.startTimer();
        // Start autosave every 10 seconds (cache-only, no database writes)
        this.autosaveTimer = setInterval(() => this.autosave(), 10000);
        // Save on page unload
        window.addEventListener('beforeunload', () => this.saveSync());
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
        if (!this.autosaveDebounce || !this.sessionId) return;

        try {
            // Send to server for CACHING (not database save)
            // Database is only written on submit
            const response = await fetch('/api/practice/save', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                },
                body: JSON.stringify({
                    session_id: this.sessionId,
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
        if (this.sessionId) {
            navigator.sendBeacon('/api/practice/save', JSON.stringify({
                session_id: this.sessionId,
                questions: this.questionsBySubject,
                answers: this.userAnswers,
                position: {
                    subjectIndex: this.currentSubjectIndex,
                    questionIndex: this.currentQuestionIndex,
                },
            }));
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

    getAnsweredCount() {
        const subjectId = this.getCurrentSubjectId();
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

        if (confirm(`Are you sure you want to submit your exam?\n\nYou have answered ${answered} out of ${total} questions.\n\nOnce submitted, you won't be able to change your answers.`)) {
            $wire.call('submitQuiz');
        }
    }
}" class="min-h-screen bg-white dark:bg-neutral-950">

@if(!$showResults)
    <div class="flex flex-col lg:flex-row min-h-screen overflow-hidden">
        <div class="flex-1 flex flex-col overflow-hidden">
            <div class="bg-white dark:bg-neutral-900 border-b border-gray-200 dark:border-neutral-800 p-4">
                <div class="max-w-7xl mx-auto flex items-center justify-between gap-3">
                    <div>
                        <flux:heading size="lg" level="1" class="mb-0">Mock Exam</flux:heading>
                        <flux:text class="text-gray-600 dark:text-gray-400 text-sm">{{ $subjectsData[$currentSubjectIndex]->name ?? '' }}</flux:text>
                    </div>
                    <div class="flex items-center gap-4 sm:gap-6">
                        <div class="text-right">
                            <flux:text class="text-gray-600 dark:text-gray-400 text-sm">Time Remaining</flux:text>
                            <div class="text-2xl sm:text-3xl font-bold font-mono" :class="timeRemaining < 600 ? 'text-red-600 dark:text-red-400' : 'text-blue-600 dark:text-blue-300'" x-text="formatTime(timeRemaining)"></div>
                        </div>
                        <button
                            @click="confirmSubmit()"
                            class="px-4 py-2 bg-green-600 dark:bg-green-600 hover:bg-green-700 dark:hover:bg-green-500 text-white font-semibold rounded-lg transition-all flex items-center gap-2 shadow-sm">
                            Submit
                        </button>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-neutral-900 border-b border-gray-200 dark:border-neutral-800 px-4 shadow-sm">
                <div class="max-w-7xl mx-auto">
                    <div class="flex gap-2 overflow-x-auto py-2">
                        <template x-for="(subject, index) in subjectsData" :key="subject.id">
                            <button
                                @click="switchSubject(index)"
                                :class="currentSubjectIndex === index ? 'bg-blue-600 text-white shadow-md' : 'bg-gray-50 dark:bg-neutral-800 text-gray-800 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-neutral-700'"
                                class="flex-shrink-0 px-4 py-3 rounded-xl whitespace-nowrap transition-all">
                                <div class="font-semibold text-sm" x-text="subject.name"></div>
                                <div
                                    :class="currentSubjectIndex === index ? 'text-blue-100' : 'text-gray-500 dark:text-gray-400'"
                                    class="text-xs mt-1"
                                    x-text="`${(userAnswers[subject.id] || []).filter(a => a !== null).length}/${(questionsBySubject[subject.id] || []).length} answered`">
                                </div>
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto p-4 sm:p-6">
                <div class="max-w-4xl mx-auto">
                    <div class="mb-6" x-show="getCurrentQuestion()">
                        <div class="flex items-center justify-between mb-3 gap-3">
                            <flux:text class="text-sm font-medium text-gray-600 dark:text-gray-400" x-text="subjectsData[currentSubjectIndex]?.name || ''"></flux:text>
                            <flux:badge color="blue" x-text="`Question ${currentQuestionIndex + 1} of ${getTotalInSubject()}`"></flux:badge>
                        </div>
                        <div class="rounded-xl border border-gray-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-5 sm:p-6 shadow-sm">
                            <flux:text class="text-lg font-medium leading-relaxed text-gray-900 dark:text-gray-100" x-text="getCurrentQuestion()?.question_text || ''"></flux:text>
                        </div>
                    </div>

                    <div class="space-y-3 mb-8">
                        <template x-for="option in getCurrentQuestion()?.options || []" :key="option.id">
                            <button
                                @click="selectAnswer(option.id)"
                                :class="userAnswers[getCurrentSubjectId()][currentQuestionIndex] === option.id ? 'border-green-500 bg-green-50 dark:bg-neutral-900 ring-2 ring-green-300 dark:ring-green-700' : 'border-gray-200 dark:border-neutral-800 hover:border-green-400 dark:hover:border-green-500'"
                                class="w-full p-4 rounded-xl border-2 text-left transition-all relative">
                                <div class="flex items-start gap-3">
                                    <div
                                        :class="userAnswers[getCurrentSubjectId()][currentQuestionIndex] === option.id ? 'border-green-500 bg-green-500' : 'border-gray-300 dark:border-gray-700'"
                                        class="flex-shrink-0 w-6 h-6 rounded-full border-2 flex items-center justify-center mt-0.5 transition-all">
                                        <template x-if="userAnswers[getCurrentSubjectId()][currentQuestionIndex] === option.id">
                                            <div class="w-2.5 h-2.5 bg-white rounded-full"></div>
                                        </template>
                                    </div>
                                    <span
                                        :class="userAnswers[getCurrentSubjectId()][currentQuestionIndex] === option.id ? 'text-green-700 dark:text-green-200 font-medium' : 'text-gray-800 dark:text-gray-200'"
                                        class="flex-1"
                                        x-text="option.option_text">
                                    </span>
                                    <template x-if="userAnswers[getCurrentSubjectId()][currentQuestionIndex] === option.id">
                                        <svg class="h-5 w-5 text-green-500 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                    </template>
                                </div>
                            </button>
                        </template>
                    </div>

                    <!-- Progress Bar -->
                    <div class="mb-6">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Question Progress</span>
                            <span class="text-sm font-semibold text-blue-600 dark:text-blue-400" x-text="`${currentQuestionIndex + 1} of ${getTotalInSubject()}`"></span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-neutral-800 rounded-full h-2.5 overflow-hidden">
                            <div
                                class="bg-gradient-to-r from-blue-500 to-blue-600 h-2.5 transition-all duration-300"
                                :style="`width: ${((currentQuestionIndex + 1) / getTotalInSubject()) * 100}%`">
                            </div>
                        </div>
                        <div class="mt-2 text-xs text-gray-500 dark:text-gray-400 flex justify-between">
                            <span x-text="`${getAnsweredCount()} answered`"></span>
                            <span x-text="`${getTotalInSubject() - getAnsweredCount()} remaining`"></span>
                        </div>
                    </div>

                    <!-- Navigation Buttons -->
                    <div class="flex items-center gap-3">
                        <button
                            @click="previousQuestion()"
                            :disabled="currentQuestionIndex === 0 && currentSubjectIndex === 0"
                            :class="currentQuestionIndex === 0 && currentSubjectIndex === 0 ? 'opacity-50 cursor-not-allowed' : 'hover:shadow-md'"
                            class="flex-1 flex items-center justify-center gap-2 px-4 py-3 bg-gray-100 dark:bg-neutral-800 text-gray-700 dark:text-gray-200 rounded-lg transition-all duration-200 font-medium">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                            <span class="hidden sm:inline">Previous</span>
                        </button>

                        <!-- Center Progress Indicator -->
                        <div class="flex flex-col items-center px-3 py-2 bg-blue-50 dark:bg-neutral-800 rounded-lg">
                            <span class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide font-semibold">Question</span>
                            <span class="text-xl font-bold text-blue-600 dark:text-blue-400" x-text="`${currentQuestionIndex + 1}`"></span>
                        </div>

                        <button
                            @click="nextQuestion()"
                            class="flex-1 flex items-center justify-center gap-2 px-4 py-3 bg-blue-600 dark:bg-blue-600 hover:bg-blue-700 dark:hover:bg-blue-500 text-white rounded-lg transition-all duration-200 font-medium shadow-sm hover:shadow-md active:scale-95">
                            <span class="hidden sm:inline" x-show="currentQuestionIndex + 1 >= getTotalInSubject() && currentSubjectIndex < subjectsData.length - 1">Subject</span>
                            <span class="hidden sm:inline" x-show="!(currentQuestionIndex + 1 >= getTotalInSubject() && currentSubjectIndex < subjectsData.length - 1)">Next</span>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="hidden lg:block w-80 bg-white dark:bg-neutral-900 border-l border-gray-200 dark:border-neutral-800 overflow-y-auto p-4" x-cloak>
            <div class="mb-6 pb-6 border-b-2 border-blue-200 dark:border-blue-800">
                <div class="flex items-center justify-between mb-3">
                    <flux:heading size="sm" level="3" class="mb-0 text-blue-600 dark:text-blue-300" x-text="subjectsData[currentSubjectIndex]?.name || ''"></flux:heading>
                    <flux:badge color="blue" class="text-xs" x-text="`${getAnsweredCount()}/${getTotalInSubject()}`"></flux:badge>
                </div>
                <div class="grid grid-cols-5 gap-2">
                    <template x-for="(question, index) in getCurrentQuestions()" :key="index">
                        <button
                            @click="jumpToQuestion(currentSubjectIndex, index)"
                            :class="{
                                'bg-blue-600 text-white ring-2 ring-blue-300 dark:ring-blue-700': currentQuestionIndex === index,
                                'bg-green-500 text-white hover:bg-green-600 dark:bg-green-600 dark:hover:bg-green-500': currentQuestionIndex !== index && userAnswers[getCurrentSubjectId()][index] !== null,
                                'bg-gray-100 dark:bg-neutral-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-neutral-700': currentQuestionIndex !== index && userAnswers[getCurrentSubjectId()][index] === null,
                            }"
                            class="aspect-square h-8 w-8 p-0 flex items-center justify-center rounded-lg text-xs font-medium transition-all"
                            x-text="index + 1">
                        </button>
                    </template>
                </div>
            </div>

            <template x-if="subjectsData.length > 1">
                <div class="mb-6 pb-6 border-b-2 border-gray-200 dark:border-gray-700">
                    <flux:text class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-3 block">Other Subjects</flux:text>
                    <template x-for="(subject, subjectIndex) in subjectsData" :key="subject.id">
                        <template x-if="subjectIndex !== currentSubjectIndex">
                            <div class="mb-4 last:mb-0">
                                <button
                                    @click="switchSubject(subjectIndex)"
                                    class="w-full flex items-center justify-between gap-2 mb-2 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-neutral-800 transition-colors">
                                    <span class="flex-1 text-sm font-semibold text-gray-800 dark:text-gray-200 text-left truncate" :title="subject.name" x-text="subject.name"></span>
                                    <flux:badge color="zinc" size="sm" x-text="`${(userAnswers[subject.id] || []).filter(a => a !== null).length}/${(questionsBySubject[subject.id] || []).length}`"></flux:badge>
                                </button>
                                <div class="grid grid-cols-8 gap-1 px-2">
                                    <template x-for="(question, index) in (questionsBySubject[subject.id] || [])" :key="index">
                                        <button
                                            @click="jumpToQuestion(subjectIndex, index)"
                                            :class="{
                                                'bg-green-500 text-white hover:bg-green-600 dark:bg-green-600 dark:hover:bg-green-500': userAnswers[subject.id][index] !== null,
                                                'bg-gray-200 dark:bg-neutral-800 text-gray-500 dark:text-gray-400 hover:bg-gray-300 dark:hover:bg-neutral-700': userAnswers[subject.id][index] === null
                                            }"
                                            class="aspect-square h-6 w-6 p-0 flex items-center justify-center rounded text-xs font-medium transition-all"
                                            :title="`Question ${index + 1}`">
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </template>
                </div>
            </template>

            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <flux:text class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Progress</flux:text>
                    <flux:text class="font-semibold text-gray-900 dark:text-gray-100" x-text="`${Object.values(userAnswers).reduce((sum, answers) => sum + (Array.isArray(answers) ? answers.filter(a => a !== null).length : 0), 0)}/${Object.values(questionsBySubject).reduce((sum, questions) => sum + (Array.isArray(questions) ? questions.length : 0), 0)}`"></flux:text>
                </div>
            </div>
        </div>
    </div>
@else
    <flux:container class="py-12">
        <div class="max-w-4xl mx-auto space-y-8">
            <div class="text-center">
                <flux:heading size="2xl" level="1" class="mb-2">Mock Completed</flux:heading>
                <flux:text class="text-gray-600 dark:text-gray-400">Here is your performance breakdown.</flux:text>
            </div>

            @php
                $scores = $this->getScoresBySubject();
                $totalQuestions = array_sum(array_map('count', $questionsBySubject));
                $totalScore = array_sum($scores);
                $percentage = $totalQuestions ? round(($totalScore / $totalQuestions) * 100, 1) : 0;
            @endphp

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="p-5 rounded-xl border border-green-200 dark:border-green-800 bg-green-50 dark:bg-neutral-900">
                    <flux:text class="text-sm text-green-700 dark:text-green-200">Score</flux:text>
                    <flux:heading size="xl" class="text-green-800 dark:text-green-100">{{ $totalScore }}/{{ $totalQuestions }}</flux:heading>
                </div>
                <div class="p-5 rounded-xl border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-neutral-900">
                    <flux:text class="text-sm text-blue-700 dark:text-blue-200">Percentage</flux:text>
                    <flux:heading size="xl" class="text-blue-800 dark:text-blue-100">{{ $percentage }}%</flux:heading>
                </div>
                <div class="p-5 rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-neutral-900">
                    <flux:text class="text-sm text-amber-700 dark:text-amber-200">Subjects</flux:text>
                    <flux:heading size="xl" class="text-amber-800 dark:text-amber-100">{{ count($subjectsData) }}</flux:heading>
                </div>
            </div>

            <div class="p-5 rounded-xl border border-gray-200 dark:border-neutral-800 bg-white dark:bg-neutral-900">
                <flux:heading size="md" class="mb-4">Subject Breakdown</flux:heading>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($subjectsData as $subject)
                        @php
                            $subjectQuestions = $questionsBySubject[$subject->id];
                            $score = $scores[$subject->id] ?? 0;
                            $subjectTotal = count($subjectQuestions);
                            $subjectPct = $subjectTotal ? round(($score / $subjectTotal) * 100) : 0;
                        @endphp
                        <div class="p-4 rounded-lg border border-gray-200 dark:border-neutral-800 bg-gray-50 dark:bg-neutral-900/80">
                            <div class="flex items-center justify-between mb-2">
                                <flux:text class="font-semibold">{{ $subject->name }}</flux:text>
                                <flux:badge color="blue">{{ $score }}/{{ $subjectTotal }}</flux:badge>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-neutral-800 rounded-full h-2 overflow-hidden">
                                <div class="bg-blue-500 h-full" style="width: {{ $subjectPct }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex gap-3">
                <flux:button wire:click="toggleReview" icon="{{ $showReview ? 'x-mark' : 'eye' }}" variant="primary">
                    {{ $showReview ? 'Hide Review' : 'Review Answers' }}
                </flux:button>
                <flux:button wire:navigate href="{{ route('mock.setup') }}" icon="arrow-left">Back to Setup</flux:button>
                <flux:button wire:navigate href="{{ route('dashboard') }}" variant="ghost">Dashboard</flux:button>
            </div>

            @if($showReview)
                <div class="mt-8 space-y-8">
                    <flux:heading size="lg" class="text-center">Answer Review</flux:heading>

                    @foreach($this->getReviewData() as $subjectData)
                        <div class="p-6 rounded-xl border border-gray-200 dark:border-neutral-800 bg-white dark:bg-neutral-900">
                            <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-200 dark:border-neutral-800">
                                <flux:heading size="md" class="mb-0">{{ $subjectData['subject']->name }}</flux:heading>
                                @php
                                    $correctCount = collect($subjectData['questions'])->where('isCorrect', true)->count();
                                    $totalCount = count($subjectData['questions']);
                                @endphp
                                <flux:badge color="blue" size="lg">{{ $correctCount }}/{{ $totalCount }} Correct</flux:badge>
                            </div>

                            <div class="space-y-6">
                                @foreach($subjectData['questions'] as $qData)
                                    <div class="p-5 rounded-lg border {{ $qData['isCorrect'] ? 'border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-950/20' : ($qData['wasAnswered'] ? 'border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-950/20' : 'border-gray-200 dark:border-neutral-800 bg-gray-50 dark:bg-neutral-900/50') }}">
                                        <div class="flex items-start gap-3 mb-4">
                                            <div class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold {{ $qData['isCorrect'] ? 'bg-green-500 text-white' : ($qData['wasAnswered'] ? 'bg-red-500 text-white' : 'bg-gray-400 text-white') }}">
                                                {{ $qData['questionNumber'] }}
                                            </div>
                                            <div class="flex-1">
                                                <flux:text class="font-medium text-gray-900 dark:text-gray-100">{!! $qData['question']->question_text !!}</flux:text>
                                            </div>
                                            @if($qData['isCorrect'])
                                                <svg class="w-6 h-6 text-green-600 dark:text-green-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            @elseif($qData['wasAnswered'])
                                                <svg class="w-6 h-6 text-red-600 dark:text-red-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            @else
                                                <svg class="w-6 h-6 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                                </svg>
                                            @endif
                                        </div>

                                        <div class="ml-11 space-y-2">
                                            @foreach($qData['question']->options as $option)
                                                @php
                                                    $isUserAnswer = $qData['userAnswerId'] == $option->id;
                                                    $isCorrectAnswer = $option->is_correct;
                                                @endphp
                                                <div class="p-3 rounded-lg border {{ $isCorrectAnswer ? 'border-green-500 bg-green-100 dark:bg-green-900/30' : ($isUserAnswer ? 'border-red-500 bg-red-100 dark:bg-red-900/30' : 'border-gray-200 dark:border-neutral-700 bg-white dark:bg-neutral-800/50') }}">
                                                    <div class="flex items-center gap-2">
                                                        @if($isCorrectAnswer)
                                                            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                            </svg>
                                                        @elseif($isUserAnswer)
                                                            <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                            </svg>
                                                        @else
                                                            <div class="w-5 h-5"></div>
                                                        @endif
                                                        <flux:text class="{{ $isCorrectAnswer ? 'font-semibold text-green-900 dark:text-green-100' : ($isUserAnswer ? 'font-semibold text-red-900 dark:text-red-100' : 'text-gray-700 dark:text-gray-300') }}">
                                                            {!! $option->option_text !!}
                                                            @if($isCorrectAnswer)
                                                                <span class="ml-2 text-xs font-bold text-green-700 dark:text-green-300">(Correct Answer)</span>
                                                            @endif
                                                            @if($isUserAnswer && !$isCorrectAnswer)
                                                                <span class="ml-2 text-xs font-bold text-red-700 dark:text-red-300">(Your Answer)</span>
                                                            @endif
                                                        </flux:text>
                                                    </div>
                                                </div>
                                            @endforeach

                                            @if(!$qData['wasAnswered'])
                                                <div class="p-3 rounded-lg border border-amber-300 bg-amber-50 dark:bg-amber-900/20 dark:border-amber-700">
                                                    <flux:text class="text-amber-800 dark:text-amber-200 text-sm font-medium">⚠️ You did not answer this question</flux:text>
                                                </div>
                                            @endif

                                            @if($qData['question']->explanation)
                                                <div class="p-3 rounded-lg border border-blue-200 bg-blue-50 dark:bg-blue-900/20 dark:border-blue-800 mt-3">
                                                    <flux:text class="text-blue-900 dark:text-blue-100 text-sm">
                                                        <span class="font-semibold">Explanation:</span> {!! $qData['question']->explanation !!}
                                                    </flux:text>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </flux:container>
@endif
</div>
