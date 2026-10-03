<div

    x-data="mockQuizTimer({

        expiresAt: @js($expiresAt),
        sessionId: @js(request()->query('session')),
        csrfToken: @js(csrf_token()),
        answers: @js($flatAnswers),

    })"

    x-init="init()"

    class="min-h-screen bg-white dark:bg-neutral-950"

>

    @if(!$showResults)

        <div class="flex flex-col lg:flex-row min-h-screen overflow-hidden">

            {{-- Main exam area --}}

            <div class="flex-1 flex flex-col overflow-hidden">

                {{-- Header --}}

                <div class="bg-white dark:bg-neutral-900 border-b border-gray-200 dark:border-neutral-800 p-4">

                    <div class="max-w-7xl mx-auto flex items-center justify-between gap-3">

                        <div>

                            <flux:heading size="lg" level="1" class="mb-0">Mock Exam</flux:heading>

                            <flux:text class="text-gray-600 dark:text-gray-400 text-sm">

                                {{ $subjectsData[$currentSubjectIndex]->name ?? '' }}

                            </flux:text>

                        </div>

                        <div class="flex items-center gap-4 sm:gap-6">

                            <div class="text-right">

                                <flux:text class="text-gray-600 dark:text-gray-400 text-sm">

                                    Time Remaining

                                </flux:text>

                                <div

                                    class="text-2xl sm:text-3xl font-bold font-mono"

                                    :class="timeRemaining < 600

                                        ? 'text-red-600 dark:text-red-400'

                                        : 'text-blue-600 dark:text-blue-300'"

                                    x-text="formatTime(timeRemaining)"

                                ></div>

                            </div>

                            <button

                                type="button"

                                wire:click="submitQuiz"

                                wire:confirm="Are you sure you want to submit your exam?"

                                wire:loading.attr="disabled"

                                wire:target="submitQuiz"

                                class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition-all flex items-center gap-2 shadow-sm disabled:opacity-60"

                            >

                                Submit

                            </button>

                        </div>

                    </div>

                </div>

                {{-- Subject tabs --}}

                <div class="bg-white dark:bg-neutral-900 border-b border-gray-200 dark:border-neutral-800 px-4 shadow-sm">

                    <div class="max-w-7xl mx-auto">

                        <div class="flex gap-2 overflow-x-auto py-2">

                            @foreach($subjectsData as $subjectIndex => $subject)

                                @php

                                    $subjectId = $subject->id;

                                    $answered = collect($userAnswers[$subjectId] ?? [])

                                        ->filter(fn($answer) => $answer !== null)

                                        ->count();

                                    $total = count($questionsBySubject[$subjectId] ?? []);

                                @endphp

                                <button

                                    type="button"

                                    wire:click="switchSubject({{ $subjectIndex }})"

                                    wire:key="subject-tab-{{ $subject->id }}"

                                    class="flex-shrink-0 px-4 py-3 rounded-xl whitespace-nowrap transition-all

                                        {{ $currentSubjectIndex === $subjectIndex

                                            ? 'bg-blue-600 text-white shadow-md'

                                            : 'bg-gray-50 dark:bg-neutral-800 text-gray-800 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-neutral-700' }}"

                                >

                                    <div class="font-semibold text-sm">{{ $subject->name }}</div>

                                    <div class="text-xs mt-1 {{ $currentSubjectIndex === $subjectIndex ? 'text-blue-100' : 'text-gray-500 dark:text-gray-400' }}">

                                        {{ $answered }}/{{ $total }} answered

                                    </div>

                                </button>

                            @endforeach

                        </div>

                    </div>

                </div>

                {{-- Question --}}

                <div class="flex-1 overflow-y-auto p-4 sm:p-6">

                    <div class="max-w-4xl mx-auto">

                        @php

                            $currentSubjectId = $this->getCurrentSubjectId();

                            $currentQuestions = $this->getCurrentQuestions();

                            $currentQuestion = $this->getCurrentQuestion();

                            $currentAnswer = $currentSubjectId !== null

                                ? ($userAnswers[$currentSubjectId][$currentQuestionIndex] ?? null)

                                : null;

                            $answeredCount = $currentSubjectId !== null

                                ? collect($userAnswers[$currentSubjectId] ?? [])->filter(fn($a) => $a !== null)->count()

                                : 0;

                            $totalInSubject = count($currentQuestions);

                        @endphp

                        @if($currentQuestion)

                            <div class="mb-6">

                                <div class="flex items-center justify-between mb-3 gap-3">

                                    <flux:text class="text-sm font-medium text-gray-600 dark:text-gray-400">

                                        {{ $subjectsData[$currentSubjectIndex]->name ?? '' }}

                                    </flux:text>

                                    <flux:badge color="blue">

                                        Question {{ $currentQuestionIndex + 1 }} of {{ $totalInSubject }}

                                    </flux:badge>

                                </div>

                                <div

                                    class="rounded-xl border border-gray-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-5 sm:p-6 shadow-sm js-math-content"

                                >

                                    <div class="text-lg font-medium leading-relaxed text-gray-900 dark:text-gray-100">

                                        {!! $currentQuestion->question_text_html !!}

                                    </div>

                                </div>

                            </div>

                            {{-- Options --}}

                            <div class="space-y-3 mb-8">

                                @foreach($currentQuestion->options as $option)

                                    @php
                                        $selected = (int) $currentAnswer === (int) $option->id;
                                    @endphp

                                    <button

                                        type="button"

                                        x-on:click="selectAnswer({{ $option->id }}, {{ $currentSubjectId }}, {{ $currentQuestionIndex }})"

                                        wire:key="question-{{ $currentQuestion->id }}-option-{{ $option->id }}"

                                        wire:loading.attr="disabled"

                                        class="w-full p-4 rounded-xl border-2 text-left transition-all relative

                                            {{ $selected

                                                ? 'border-green-500 bg-green-50 dark:bg-neutral-900 ring-2 ring-green-300 dark:ring-green-700'

                                                : 'border-gray-200 dark:border-neutral-800 hover:border-green-400 dark:hover:border-green-500' }}"

                                    >

                                        <div class="flex items-start gap-3">

                                            <div

                                                class="flex-shrink-0 w-6 h-6 rounded-full border-2 flex items-center justify-center mt-0.5

                                                    {{ $selected

                                                        ? 'border-green-500 bg-green-500'

                                                        : 'border-gray-300 dark:border-gray-700' }}"

                                            >

                                                @if($selected)

                                                    <div class="w-2.5 h-2.5 bg-white rounded-full"></div>

                                                @endif

                                            </div>

                                            <span class="flex-1 {{ $selected

                                                ? 'text-green-700 dark:text-green-200 font-medium'

                                                : 'text-gray-800 dark:text-gray-200' }}">

                                                {!! $option->option_text !!}

                                            </span>

                                            @if($selected)

                                                <svg class="h-5 w-5 text-green-500 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">

                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414 1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>

                                                </svg>

                                            @endif

                                        </div>

                                    </button>

                                @endforeach

                            </div>

                            {{-- Progress --}}

                            <div class="mb-6">

                                <div class="flex items-center justify-between mb-2">

                                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">

                                        Question Progress

                                    </span>

                                    <span class="text-sm font-semibold text-blue-600 dark:text-blue-400">

                                        {{ $currentQuestionIndex + 1 }} of {{ $totalInSubject }}

                                    </span>

                                </div>

                                <div class="w-full bg-gray-200 dark:bg-neutral-800 rounded-full h-2.5 overflow-hidden">

                                    <div

                                        class="bg-gradient-to-r from-blue-500 to-blue-600 h-2.5 transition-all duration-300"

                                        style="width: {{ $totalInSubject > 0 ? (($currentQuestionIndex + 1) / $totalInSubject) * 100 : 0 }}%"

                                    ></div>

                                </div>

                                <div class="mt-2 text-xs text-gray-500 dark:text-gray-400 flex justify-between">

                                    <span>{{ $answeredCount }} answered</span>

                                    <span>{{ max(0, $totalInSubject - $answeredCount) }} remaining</span>

                                </div>

                            </div>

                            {{-- Navigation --}}

                            <div class="flex items-center gap-3">

                                <button

                                    type="button"

                                    wire:click="previousQuestion"

                                    wire:loading.attr="disabled"

                                    @disabled($currentSubjectIndex === 0 && $currentQuestionIndex === 0)

                                    class="flex-1 flex items-center justify-center gap-2 px-4 py-3 bg-gray-100 dark:bg-neutral-800 text-gray-700 dark:text-gray-200 rounded-lg transition-all duration-200 font-medium disabled:opacity-50 disabled:cursor-not-allowed hover:shadow-md"

                                >

                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>

                                    </svg>

                                    <span class="hidden sm:inline">Previous</span>

                                </button>

                                <div class="flex flex-col items-center px-3 py-2 bg-blue-50 dark:bg-neutral-800 rounded-lg">

                                    <span class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide font-semibold">

                                        Question

                                    </span>

                                    <span class="text-xl font-bold text-blue-600 dark:text-blue-400">

                                        {{ $currentQuestionIndex + 1 }}

                                    </span>

                                </div>

                                <button

                                    type="button"

                                    wire:click="nextQuestion"

                                    wire:loading.attr="disabled"

                                    class="flex-1 flex items-center justify-center gap-2 px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-all duration-200 font-medium shadow-sm hover:shadow-md"

                                >

                                    <span class="hidden sm:inline">

                                        {{ $currentQuestionIndex + 1 >= $totalInSubject && $currentSubjectIndex < count($subjectsData) - 1 ? 'Subject' : 'Next' }}

                                    </span>

                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7-7"/>

                                    </svg>

                                </button>

                            </div>

                        @else

                            <div class="p-8 text-center rounded-xl border border-red-200 bg-red-50">

                                <flux:heading size="md">Question unavailable</flux:heading>

                                <flux:text>Please reload the mock or return to setup.</flux:text>

                            </div>

                        @endif

                    </div>

                </div>

            </div>

            {{-- Desktop question navigator --}}

            <div class="hidden lg:block w-80 bg-white dark:bg-neutral-900 border-l border-gray-200 dark:border-neutral-800 overflow-y-auto p-4">

                <div class="mb-6 pb-6 border-b-2 border-blue-200 dark:border-blue-800">

                    <div class="flex items-center justify-between mb-3">

                        <flux:heading size="sm" level="3" class="mb-0 text-blue-600 dark:text-blue-300">

                            {{ $subjectsData[$currentSubjectIndex]->name ?? '' }}

                        </flux:heading>

                        <flux:badge color="blue" class="text-xs">

                            {{ $answeredCount }}/{{ $totalInSubject }}

                        </flux:badge>

                    </div>

                    <div class="grid grid-cols-5 gap-2">

                        @foreach($currentQuestions as $index => $question)

                            @php
                                $answered = ($userAnswers[$currentSubjectId][$index] ?? null) !== null;
                            @endphp

                            <button

                                type="button"

                                wire:click="jumpToQuestion({{ $currentSubjectIndex }}, {{ $index }})"

                                wire:key="nav-current-{{ $question->id }}"

                                class="aspect-square h-8 w-8 p-0 flex items-center justify-center rounded-lg text-xs font-medium transition-all

                                    {{ $currentQuestionIndex === $index

                                        ? 'bg-blue-600 text-white ring-2 ring-blue-300 dark:ring-blue-700'

                                        : ($answered

                                            ? 'bg-green-500 text-white hover:bg-green-600'

                                            : 'bg-gray-100 dark:bg-neutral-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-neutral-700') }}"

                            >

                                {{ $index + 1 }}

                            </button>

                        @endforeach

                    </div>

                </div>

                @if(count($subjectsData) > 1)

                    <div class="mb-6 pb-6 border-b-2 border-gray-200 dark:border-gray-700">

                        <flux:text class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-3 block">

                            Other Subjects

                        </flux:text>

                        @foreach($subjectsData as $subjectIndex => $subject)

                            @if($subjectIndex === $currentSubjectIndex)
                                @continue
                            @endif

                            @php

                                $sid = $subject->id;

                                $subjectAnswers = $userAnswers[$sid] ?? [];

                                $subjectQuestions = $questionsBySubject[$sid] ?? [];

                            @endphp

                            <div class="mb-4 last:mb-0">

                                <button

                                    type="button"

                                    wire:click="switchSubject({{ $subjectIndex }})"

                                    class="w-full flex items-center justify-between gap-2 mb-2 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-neutral-800 transition-colors"

                                >

                                    <span class="flex-1 text-sm font-semibold text-gray-800 dark:text-gray-200 text-left truncate">

                                        {{ $subject->name }}

                                    </span>

                                    <flux:badge color="zinc" size="sm">

                                        {{ collect($subjectAnswers)->filter(fn($a) => $a !== null)->count() }}/{{ count($subjectQuestions) }}

                                    </flux:badge>

                                </button>

                                <div class="grid grid-cols-8 gap-1 px-2">

                                    @foreach($subjectQuestions as $index => $question)

                                        @php
                                            $answered = ($subjectAnswers[$index] ?? null) !== null;
                                        @endphp

                                        <button

                                            type="button"

                                            wire:click="jumpToQuestion({{ $subjectIndex }}, {{ $index }})"

                                            wire:key="nav-other-{{ $question->id }}"

                                            title="Question {{ $index + 1 }}"

                                            class="aspect-square h-6 w-6 p-0 flex items-center justify-center rounded text-xs font-medium transition-all

                                                {{ $answered

                                                    ? 'bg-green-500 text-white hover:bg-green-600'

                                                    : 'bg-gray-200 dark:bg-neutral-800 text-gray-500 dark:text-gray-400 hover:bg-gray-300 dark:hover:bg-neutral-700' }}"

                                        >

                                            {{ $index + 1 }}

                                        </button>

                                    @endforeach

                                </div>

                            </div>

                        @endforeach

                    </div>

                @endif

                <div class="flex items-center justify-between">

                    <flux:text class="text-sm font-medium text-gray-600 dark:text-gray-400">

                        Total Progress

                    </flux:text>

                    @php

                        $allAnswered = collect($userAnswers)

                            ->flatten()

                            ->filter(fn($answer) => $answer !== null)

                            ->count();

                        $allQuestions = collect($questionsBySubject)

                            ->flatten(1)

                            ->count();

                    @endphp

                    <flux:text class="font-semibold text-gray-900 dark:text-gray-100">

                        {{ $allAnswered }}/{{ $allQuestions }}

                    </flux:text>

                </div>

            </div>

        </div>

    @endif

    @if($showResults)
        {{-- Results --}}
        <flux:container class="py-12">

            <div class="max-w-4xl mx-auto space-y-8">

                <div class="text-center">

                    <flux:heading size="2xl" level="1" class="mb-2">Mock Completed</flux:heading>

                    <flux:text class="text-gray-600 dark:text-gray-400">

                        Here is your performance breakdown.

                    </flux:text>

                </div>

                @php

                    $scores = $this->getScoresBySubject();

                    $totalQuestions = array_sum(array_map('count', $questionsBySubject));

                    $totalScore = array_sum($scores);

                    $percentage = $totalQuestions

                        ? round(($totalScore / $totalQuestions) * 100, 1)

                        : 0;

                @endphp

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                    <div class="p-5 rounded-xl border border-green-200 dark:border-green-800 bg-green-50 dark:bg-neutral-900">

                        <flux:text class="text-sm text-green-700 dark:text-green-200">Score</flux:text>

                        <flux:heading size="xl" class="text-green-800 dark:text-green-100">

                            {{ $totalScore }}/{{ $totalQuestions }}

                        </flux:heading>

                    </div>

                    <div class="p-5 rounded-xl border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-neutral-900">

                        <flux:text class="text-sm text-blue-700 dark:text-blue-200">Percentage</flux:text>

                        <flux:heading size="xl" class="text-blue-800 dark:text-blue-100">

                            {{ $percentage }}%

                        </flux:heading>

                    </div>

                    <div class="p-5 rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-neutral-900">

                        <flux:text class="text-sm text-amber-700 dark:text-amber-200">Subjects</flux:text>

                        <flux:heading size="xl" class="text-amber-800 dark:text-amber-100">

                            {{ count($subjectsData) }}

                        </flux:heading>

                    </div>

                </div>

                <div class="p-5 rounded-xl border border-gray-200 dark:border-neutral-800 bg-white dark:bg-neutral-900">

                    <flux:heading size="md" class="mb-4">Subject Breakdown</flux:heading>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        @foreach($subjectsData as $subject)

                            @php

                                $subjectQuestions = $questionsBySubject[$subject->id] ?? [];

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

                <div class="flex flex-wrap gap-3">

                    <flux:button

                        wire:click="toggleReview"

                        icon="{{ $showReview ? 'x-mark' : 'eye' }}"

                        variant="primary"

                    >

                        {{ $showReview ? 'Hide Review' : 'Review Answers' }}

                    </flux:button>

                    <flux:button wire:navigate href="{{ route('mock.setup') }}" icon="arrow-left">

                        Back to Setup

                    </flux:button>

                    <flux:button wire:navigate href="{{ route('dashboard') }}" variant="ghost">

                        Dashboard

                    </flux:button>

                </div>

                @if($showReview)

                    <div class="mt-8 space-y-8">

                        <flux:heading size="lg" class="text-center">Answer Review</flux:heading>

                        @foreach($this->getReviewData() as $subjectData)

                            <div class="p-6 rounded-xl border border-gray-200 dark:border-neutral-800 bg-white dark:bg-neutral-900">

                                <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-200 dark:border-neutral-800">

                                    <flux:heading size="md" class="mb-0">

                                        {{ $subjectData['subject']->name }}

                                    </flux:heading>

                                    @php

                                        $correctCount = collect($subjectData['questions'])

                                            ->where('isCorrect', true)

                                            ->count();

                                        $totalCount = count($subjectData['questions']);

                                    @endphp

                                    <flux:badge color="blue" size="lg">

                                        {{ $correctCount }}/{{ $totalCount }} Correct

                                    </flux:badge>

                                </div>

                                <div class="space-y-6">

                                    @foreach($subjectData['questions'] as $qData)

                                        <div class="p-5 rounded-lg border

                                            {{ $qData['isCorrect']

                                                ? 'border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-950/20'

                                                : ($qData['wasAnswered']

                                                    ? 'border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-950/20'

                                                    : 'border-gray-200 dark:border-neutral-800 bg-gray-50 dark:bg-neutral-900/50') }}"

                                        >

                                            <div class="flex items-start gap-3 mb-4">

                                                <div class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold

                                                    {{ $qData['isCorrect']

                                                        ? 'bg-green-500 text-white'

                                                        : ($qData['wasAnswered']

                                                            ? 'bg-red-500 text-white'

                                                            : 'bg-gray-400 text-white') }}"

                                                >

                                                    {{ $qData['questionNumber'] }}

                                                </div>

                                                <div class="flex-1 text-gray-900 dark:text-gray-100">

                                                    {!! $qData['question']->question_text_html !!}

                                                </div>

                                            </div>

                                            <div class="ml-11 space-y-2">

                                                @foreach($qData['question']->options as $option)

                                                    @php

                                                        $isUserAnswer = (int) $qData['userAnswerId'] === (int) $option->id;

                                                        $isCorrectAnswer = (bool) $option->is_correct;

                                                    @endphp

                                                    <div class="p-3 rounded-lg border

                                                        {{ $isCorrectAnswer

                                                            ? 'border-green-500 bg-green-100 dark:bg-green-900/30'

                                                            : ($isUserAnswer

                                                                ? 'border-red-500 bg-red-100 dark:bg-red-900/30'

                                                                : 'border-gray-200 dark:border-neutral-700 bg-white dark:bg-neutral-800/50') }}"

                                                    >

                                                        <div class="flex items-center gap-2">

                                                            @if($isCorrectAnswer)

                                                                <span class="text-green-600 dark:text-green-400">✓</span>

                                                            @elseif($isUserAnswer)

                                                                <span class="text-red-600 dark:text-red-400">✕</span>

                                                            @else

                                                                <span class="w-4"></span>

                                                            @endif

                                                            <span class="{{ $isCorrectAnswer

                                                                ? 'font-semibold text-green-900 dark:text-green-100'

                                                                : ($isUserAnswer

                                                                    ? 'font-semibold text-red-900 dark:text-red-100'

                                                                    : 'text-gray-700 dark:text-gray-300') }}"

                                                            >

                                                                {!! $option->option_text !!}

                                                                @if($isCorrectAnswer)

                                                                    <span class="ml-2 text-xs font-bold text-green-700 dark:text-green-300">

                                                                        (Correct Answer)

                                                                    </span>

                                                                @endif

                                                                @if($isUserAnswer && !$isCorrectAnswer)

                                                                    <span class="ml-2 text-xs font-bold text-red-700 dark:text-red-300">

                                                                        (Your Answer)

                                                                    </span>

                                                                @endif

                                                            </span>

                                                        </div>

                                                    </div>

                                                @endforeach

                                                @if(!$qData['wasAnswered'])

                                                    <div class="p-3 rounded-lg border border-amber-300 bg-amber-50 dark:bg-amber-900/20 dark:border-amber-700">

                                                        <flux:text class="text-amber-800 dark:text-amber-200 text-sm font-medium">

                                                            ⚠️ You did not answer this question

                                                        </flux:text>

                                                    </div>

                                                @endif

                                                @if($qData['question']->explanation)

                                                    <div class="p-3 rounded-lg border border-blue-200 bg-blue-50 dark:bg-blue-900/20 dark:border-blue-800 mt-3">

                                                        <flux:text class="text-blue-900 dark:text-blue-100 text-sm">

                                                            <span class="font-semibold">Explanation:</span>

                                                            {!! $qData['question']->explanation !!}

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

    {{-- Timer only: Alpine owns the display, server owns the expiry. --}}

    <script>

        function mockQuizTimer(config) {

            return {

                expiresAt: config.expiresAt,
                sessionId: config.sessionId,
                csrfToken: config.csrfToken,
                answers: config.answers ?? {},
                timeRemaining: 0,
                timer: null,
                autosaveTimer: null,
                autosaveDebounce: false,
                submitting: false,

                init() {

                    this.updateTimer();

                    this.timer = setInterval(() => {

                        this.updateTimer();

                    }, 1000);

                    this.autosaveTimer = setInterval(() => {

                        this.autosave();

                    }, 10000);

                    this.$nextTick(() => this.renderMath());

                    if (window.Livewire) {

                        Livewire.hook('morphed', () => {

                            this.$nextTick(() => this.renderMath());

                        });

                    }

                    window.addEventListener('beforeunload', () => {

                        if (this.timer) clearInterval(this.timer);
                        if (this.autosaveTimer) clearInterval(this.autosaveTimer);
                        this.saveSync();

                    });

                },

                selectAnswer(optionId, subjectId, questionIndex) {
                    const currentQuestion = this.$wire.getCurrentQuestion ? this.$wire.getCurrentQuestion() : null;

                    if (currentQuestion && currentQuestion.id) {
                        this.answers[currentQuestion.id] = optionId;
                    }

                    this.autosaveDebounce = true;
                    this.$wire.selectAnswer(optionId);
                },

                async autosave() {
                    if (!this.sessionId || !this.autosaveDebounce || !this.answers) {
                        return;
                    }

                    try {
                        const response = await fetch('/mock/save-web-progress', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken,
                            },
                            body: JSON.stringify({
                                session_id: this.sessionId,
                                questions: this.$wire.questionsBySubject,
                                answers: this.answers,
                                position: {
                                    subjectIndex: this.$wire.currentSubjectIndex,
                                    questionIndex: this.$wire.currentQuestionIndex,
                                },
                            }),
                        });

                        if (response.ok) {
                            this.autosaveDebounce = false;
                        }
                    } catch (error) {
                        console.error('Mock autosave failed:', error);
                    }
                },

                saveSync() {
                    if (!this.sessionId || !this.answers || !Object.keys(this.answers).length) {
                        return;
                    }

                    const payload = {
                        session_id: this.sessionId,
                        questions: this.$wire.questionsBySubject,
                        answers: this.answers,
                        position: {
                            subjectIndex: this.$wire.currentSubjectIndex,
                            questionIndex: this.$wire.currentQuestionIndex,
                        },
                    };

                    navigator.sendBeacon('/mock/save-web-progress', JSON.stringify(payload));
                },

                updateTimer() {

                    if (!this.expiresAt) return;

                    const expiry = new Date(this.expiresAt).getTime();

                    const now = Date.now();

                    this.timeRemaining = Math.max(

                        0,

                        Math.floor((expiry - now) / 1000)

                    );

                    if (this.timeRemaining <= 0 && !this.submitting) {

                        this.submitting = true;

                        if (this.timer) {

                            clearInterval(this.timer);

                        }

                        this.$wire.handleTimerEnd();

                    }

                },

                formatTime(seconds) {

                    const h = Math.floor(seconds / 3600);

                    const m = Math.floor((seconds % 3600) / 60);

                    const s = seconds % 60;

                    return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;

                },

                renderMath() {

                    if (!window.renderMathInElement) return;

                    document.querySelectorAll('.js-math-content').forEach((el) => {

                        window.renderMathInElement(el, {

                            delimiters: [

                                { left: '$', right: '$', display: false },

                                { left: '$$', right: '$$', display: true },

                            ],

                            throwOnError: false,

                        });

                    });

                }

            }

        }

    </script>

</div>
