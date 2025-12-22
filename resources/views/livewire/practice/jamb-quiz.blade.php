<div x-data="{
    timeRemaining: @entangle('timeRemaining'),
    timer: null,
    init() { this.startTimer(); },
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
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = seconds % 60;
        return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }
}" class="min-h-screen bg-gray-50 dark:bg-gray-900">

@if(!$showResults)
    <div class="flex h-screen overflow-hidden">
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header with Timer and Submit -->
            <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 p-4">
                <div class="max-w-7xl mx-auto flex items-center justify-between">
                    <div>
                        <flux:heading size="lg" level="1" class="mb-0">JAMB {{ $year }} Practice Test</flux:heading>
                        <flux:text class="text-gray-600 dark:text-gray-400 text-sm">{{ $subjectsData[$currentSubjectIndex]->name }}</flux:text>
                    </div>
                    <div class="flex items-center gap-6">
                        <div class="text-right">
                            <flux:text class="text-gray-600 dark:text-gray-400 text-sm">Time Remaining</flux:text>
                            <div class="text-3xl font-bold font-mono" :class="timeRemaining < 600 ? 'text-red-600 dark:text-red-400' : 'text-blue-600 dark:text-blue-400'" x-text="formatTime(timeRemaining)"></div>
                        </div>
                        <button
                            wire:click="submitQuiz"
                            wire:loading.attr="disabled"
                            wire:target="submitQuiz"
                            class="px-4 py-2 bg-green-600 dark:bg-green-700 hover:bg-green-700 dark:hover:bg-green-600 text-white font-semibold rounded-lg transition-all flex items-center gap-2">
                            <span wire:loading.remove wire:target="submitQuiz">Submit Test</span>
                            <span wire:loading wire:target="submitQuiz" class="flex items-center gap-2">
                                <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Submitting...
                            </span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Subject Tabs Navigation -->
            <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-4 shadow-sm">
                <div class="max-w-7xl mx-auto">
                    <div class="flex gap-1 overflow-x-auto py-2">
                        @foreach($subjectsData as $index => $subject)
                            @php $answered = count(array_filter($userAnswers[$subject->id])); @endphp
                            <button
                                wire:click="switchSubject({{ $index }})"
                                wire:loading.attr="disabled"
                                wire:target="switchSubject"
                                class="flex-shrink-0 px-4 py-3 rounded-lg whitespace-nowrap transition-all {{ $currentSubjectIndex == $index ? 'bg-green-500 text-white shadow-md' : 'bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600' }}">
                                <div class="font-semibold text-sm">{{ $subject->name }}</div>
                                <div class="text-xs mt-1 {{ $currentSubjectIndex == $index ? 'text-green-100' : 'text-gray-500 dark:text-gray-400' }}">
                                    <span class="font-medium">{{ $answered }}</span>/40 answered
                                </div>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto p-6">
                <div class="max-w-4xl mx-auto">
                    @php
                        $question = $this->getCurrentQuestion();
                        $currentSubjectId = $this->getCurrentSubjectId();
                    @endphp

                    <!-- Question Header -->
                    <div class="mb-6">
                        <div class="flex items-center justify-between mb-3">
                            <flux:text class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ $subjectsData[$currentSubjectIndex]->name }}</flux:text>
                            <flux:badge color="blue">Question {{ $currentQuestionIndex + 1 }} of 40</flux:badge>
                        </div>
                        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm">
                            <flux:text class="text-lg font-medium leading-relaxed">{{ $question->question_text }}</flux:text>
                        </div>
                    </div>

                    <!-- Answer Options with Instant Feedback -->
                    <div class="space-y-3 mb-8">
                        @php
                            $hasAnswered = ($userAnswers[$currentSubjectId][$currentQuestionIndex] ?? null) !== null;
                            $correctOption = $question->options->firstWhere('is_correct', true);
                        @endphp
                        @foreach($question->options as $option)
                            @php
                                $isSelected = ($userAnswers[$currentSubjectId][$currentQuestionIndex] ?? null) == $option->id;
                                $isCorrect = $option->is_correct;

                                // Determine styling based on answer state
                                if ($hasAnswered) {
                                    if ($isCorrect) {
                                        $borderClass = 'border-green-500 bg-green-50 dark:bg-green-950/20';
                                        $textClass = 'text-green-800 dark:text-green-300 font-medium';
                                    } elseif ($isSelected && !$isCorrect) {
                                        $borderClass = 'border-red-500 bg-red-50 dark:bg-red-950/20';
                                        $textClass = 'text-red-800 dark:text-red-300';
                                    } else {
                                        $borderClass = 'border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50 opacity-60';
                                        $textClass = 'text-gray-700 dark:text-gray-300';
                                    }
                                } else {
                                    $borderClass = $isSelected ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 dark:border-gray-700 hover:border-blue-400 dark:hover:border-blue-600';
                                    $textClass = $isSelected ? 'text-blue-700 dark:text-blue-300 font-medium' : 'text-gray-700 dark:text-gray-300';
                                }
                            @endphp
                            <button
                                wire:click="selectAnswer({{ $option->id }})"
                                wire:loading.attr="disabled"
                                wire:target="selectAnswer"
                                @if($hasAnswered) disabled @endif
                                class="w-full p-4 rounded-lg border-2 text-left transition-all disabled:cursor-not-allowed {{ $borderClass }}">
                                <div class="flex items-start gap-3">
                                    <!-- Radio/Status Icon -->
                                    <div class="flex-shrink-0 mt-0.5">
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
                                            <div class="w-6 h-6 rounded-full border-2 border-gray-300 dark:border-gray-600"></div>
                                            @endif
                                        @else
                                            <div class="w-6 h-6 rounded-full border-2 flex items-center justify-center {{ $isSelected ? 'border-blue-500 bg-blue-500' : 'border-gray-300 dark:border-gray-600' }}">
                                                @if($isSelected)
                                                <div class="w-2.5 h-2.5 bg-white rounded-full"></div>
                                                @endif
                                                <!-- Loading spinner when clicking -->
                                                <svg wire:loading wire:target="selectAnswer" class="animate-spin h-5 w-5 text-neutral-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                            </div>
                                        @endif
                                    </div>
                                    <!-- Option Text -->
                                    <div class="flex-1">
                                        <div class="flex items-start gap-2">
                                            <span class="{{ $textClass }}">{{ $option->option_text }}</span>
                                            @if($hasAnswered && $isCorrect)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-green-600 text-white whitespace-nowrap">
                                                Correct
                                            </span>
                                            @elseif($hasAnswered && $isSelected && !$isCorrect)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-red-600 text-white whitespace-nowrap">
                                                Wrong
                                            </span>
                                            @elseif($isSelected && !$hasAnswered)
                                            <span wire:loading wire:target="selectAnswer" class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-xs font-medium bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300">
                                                <svg class="animate-spin h-3 w-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                Processing...
                                            </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </button>
                        @endforeach
                    </div>

                    <!-- Instant Explanation (shown after answering) -->
                    @if($hasAnswered && $question->explanation)
                    <div class="mb-8 rounded-lg border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-950/20 p-4 animate-fade-in">
                        <div class="flex gap-3">
                            <div class="flex-shrink-0">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <flux:heading size="sm" class="text-blue-900 dark:text-blue-300 mb-1 font-semibold">Explanation</flux:heading>
                                <flux:text class="text-sm text-blue-800 dark:text-blue-400 leading-relaxed">{{ $question->explanation }}</flux:text>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Navigation Buttons -->
                    <div class="mt-8 flex justify-between items-center pt-6 border-t border-gray-200 dark:border-gray-700">
                        <button
                            wire:click="previousQuestion"
                            wire:loading.attr="disabled"
                            wire:target="previousQuestion"
                            class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-all flex items-center gap-2 {{ ($currentSubjectIndex == 0 && $currentQuestionIndex == 0) ? 'opacity-50 cursor-not-allowed' : '' }}"
                            {{ ($currentSubjectIndex == 0 && $currentQuestionIndex == 0) ? 'disabled' : '' }}>
                            <span wire:loading.remove wire:target="previousQuestion">← Previous</span>
                            <span wire:loading wire:target="previousQuestion" class="flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </span>
                        </button>
                        <flux:text class="text-sm text-gray-600 dark:text-gray-400 font-medium">
                            {{ $currentQuestionIndex + 1 }}/40
                        </flux:text>
                        <button
                            wire:click="nextQuestion"
                            wire:loading.attr="disabled"
                            wire:target="nextQuestion"
                            class="px-4 py-2 bg-green-600 dark:bg-green-700 hover:bg-green-700 dark:hover:bg-green-600 text-white font-medium rounded-lg transition-all flex items-center gap-2">
                            <span wire:loading.remove wire:target="nextQuestion">{{ $currentQuestionIndex == 39 ? 'Next Subject' : 'Next' }}</span>
                            <span wire:loading wire:target="nextQuestion" class="flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Sidebar: Question Navigator -->
        <div class="w-80 bg-white dark:bg-gray-800 border-l border-gray-200 dark:border-gray-700 overflow-y-auto p-4">
            <!-- Current Subject Questions -->
            <div class="mb-6 pb-6 border-b-2 border-blue-200 dark:border-blue-800">
                <div class="flex items-center justify-between mb-3">
                    <flux:heading size="sm" level="3" class="mb-0 text-blue-600 dark:text-blue-400">{{ $subjectsData[$currentSubjectIndex]->name }}</flux:heading>
                    @php $currentAnswered = count(array_filter($userAnswers[$subjectsData[$currentSubjectIndex]->id])); @endphp
                    <flux:badge color="blue" class="text-xs">{{ $currentAnswered }}/40</flux:badge>
                </div>
                <div class="grid grid-cols-5 gap-2">
                    @for($i = 0; $i < 40; $i++)
                        @php
                            $isAnswered = ($userAnswers[$subjectsData[$currentSubjectIndex]->id][$i] ?? null) !== null;
                            $isCurrent = $currentQuestionIndex == $i;
                        @endphp
                        <button
                            wire:click="jumpToQuestion({{ $currentSubjectIndex }}, {{ $i }})"
                            wire:loading.attr="disabled"
                            wire:target="jumpToQuestion"
                            title="Question {{ $i + 1 }}"
                            class="aspect-square h-8 w-8 p-0 flex items-center justify-center rounded-lg text-xs font-medium transition-all {{ $isCurrent ? 'bg-green-500 text-white ring-2 ring-green-300 dark:ring-green-700' : ($isAnswered ? 'bg-green-500 text-white hover:bg-green-600 dark:bg-green-600 dark:hover:bg-green-500' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600') }}">
                            {{ $i + 1 }}
                        </button>
                    @endfor
                </div>
            </div>

            <!-- Other Subjects -->
            @if(count($subjectsData) > 1)
                <div>
                    <flux:text class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-3">Other Subjects</flux:text>
                    @foreach($subjectsData as $subjectIndex => $subject)
                        @if($subjectIndex != $currentSubjectIndex)
                            <div class="mb-4 pb-3 border-b border-gray-200 dark:border-gray-700">
                                <div class="flex items-center justify-between mb-2">
                                    <button
                                        wire:click="switchSubject({{ $subjectIndex }})"
                                        class="text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors text-left">
                                        {{ $subject->name }}
                                    </button>
                                    @php $answered = count(array_filter($userAnswers[$subject->id])); @endphp
                                    <flux:text class="text-xs text-gray-500 dark:text-gray-400">{{ $answered }}/40</flux:text>
                                </div>
                                <div class="grid grid-cols-8 gap-1">
                                    @for($i = 0; $i < 40; $i++)
                                        @php
                                            $isAnswered = ($userAnswers[$subject->id][$i] ?? null) !== null;
                                        @endphp
                                        <button
                                            wire:click="jumpToQuestion({{ $subjectIndex }}, {{ $i }})"
                                            wire:loading.attr="disabled"
                                            wire:target="jumpToQuestion"
                                            title="Q{{ $i + 1 }}"
                                            class="aspect-square h-6 w-6 p-0 flex items-center justify-center rounded text-xs font-medium transition-all {{ $isAnswered ? 'bg-green-500 text-white hover:bg-green-600 dark:bg-green-600 dark:hover:bg-green-500' : 'bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400 hover:bg-gray-300 dark:hover:bg-gray-600' }}">
                                        </button>
                                    @endfor
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif

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
@else
    <!-- Results Screen -->
    <flux:container class="py-12">
        <div class="max-w-4xl mx-auto space-y-8">
            <!-- Header -->
            <div class="text-center">
                <flux:heading size="2xl" level="1" class="mb-2">Test Completed!</flux:heading>
                <flux:text class="text-gray-600 dark:text-gray-400">Here's your detailed performance breakdown</flux:text>
            </div>

            <!-- Overall Score Card -->
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 p-8 text-center">
                @php
                    $scoresBySubject = $this->getScoresBySubject();
                    $totalScore = array_sum($scoresBySubject);
                    $totalQuestions = count($subjectsData) * 40;
                    $percentage = ($totalScore / $totalQuestions) * 100;
                @endphp
                <div class="flex flex-col items-center">
                    <div class="inline-flex items-center justify-center w-48 h-48 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 text-white mb-6">
                        <div class="text-center">
                            <flux:heading size="3xl" level="2" class="text-white mb-0 font-bold">{{ $totalScore }}</flux:heading>
                            <flux:text class="text-blue-100 text-lg">/ {{ $totalQuestions }}</flux:text>
                        </div>
                    </div>
                    <flux:heading size="2xl" level="2" class="mb-1">{{ number_format($percentage, 1) }}%</flux:heading>
                    <flux:text class="text-gray-600 dark:text-gray-400">Overall Score</flux:text>
                </div>
            </div>

            <!-- Subject Scores -->
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6">
                <flux:heading size="lg" level="2" class="mb-6">Subject Breakdown</flux:heading>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($subjectsData as $subject)
                        @php
                            $score = $scoresBySubject[$subject->id];
                            $subjectPercentage = ($score / 40) * 100;
                        @endphp
                        <div class="p-4 rounded-lg bg-gray-50 dark:bg-gray-700">
                            <flux:text class="font-medium mb-2">{{ $subject->name }}</flux:text>
                            <div class="flex items-center justify-between mb-3">
                                <flux:heading size="xl" level="3" class="text-blue-600 dark:text-blue-400 mb-0">{{ $score }}/40</flux:heading>
                                <flux:text class="text-gray-600 dark:text-gray-400">{{ number_format($subjectPercentage, 1) }}%</flux:text>
                            </div>
                            <div class="h-2 bg-gray-200 dark:bg-gray-600 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-blue-500 to-indigo-500 rounded-full" style="width: {{ $subjectPercentage }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-wrap gap-3 justify-center">
                <button
                    wire:click="toggleReview"
                    wire:loading.attr="disabled"
                    wire:target="toggleReview"
                    class="px-6 py-2 bg-green-600 dark:bg-green-700 hover:bg-green-700 dark:hover:bg-green-600 text-white font-semibold rounded-lg transition-all flex items-center gap-2">
                    <span wire:loading.remove wire:target="toggleReview">{{ $showReview ? 'Hide' : 'View' }} Answer Review</span>
                    <span wire:loading wire:target="toggleReview" class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </span>
                </button>
                <flux:button
                    wire:navigate
                    href="{{ route('practice.jamb.setup') }}"
                    variant="ghost">
                    Try Another Test
                </flux:button>
                <flux:button
                    wire:navigate
                    href="{{ route('dashboard') }}"
                    variant="outline">
                    Back to Dashboard
                </flux:button>
            </div>

            <!-- Answer Review -->
            @if($showReview)
                <div class="space-y-6 mt-8">
                    @foreach($subjectsData as $subjectIndex => $subject)
                        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6">
                            <flux:heading size="lg" level="2" class="mb-6">{{ $subject->name }} - Answer Review</flux:heading>
                            <div class="space-y-6">
                                @foreach($questionsBySubject[$subject->id] as $qIndex => $question)
                                    @php
                                        $userAnswer = $userAnswers[$subject->id][$qIndex] ?? null;
                                        $correctOption = $question->options->firstWhere('is_correct', true);
                                        $isCorrect = $userAnswer && $correctOption && $userAnswer == $correctOption->id;
                                        $userOption = $question->options->firstWhere('id', $userAnswer);
                                    @endphp
                                    <div class="p-4 rounded-lg {{ $isCorrect ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' : ($userAnswer ? 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800' : 'bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600') }}">
                                        <div class="flex items-start gap-2 mb-3">
                                            <flux:badge :color="$isCorrect ? 'green' : ($userAnswer ? 'red' : 'gray')">
                                                Q{{ $qIndex + 1 }}
                                            </flux:badge>
                                            <flux:text class="flex-1 font-medium">{{ $question->question_text }}</flux:text>
                                        </div>

                                        @if($userAnswer)
                                            <div class="ml-12 space-y-2">
                                                <div>
                                                    <flux:text class="text-sm font-medium text-gray-600 dark:text-gray-400">Your answer:</flux:text>
                                                    <flux:text class="text-sm {{ $isCorrect ? 'text-green-700 dark:text-green-300 font-medium' : 'text-red-700 dark:text-red-300 font-medium' }}">
                                                        {{ $userOption->option_text }}
                                                    </flux:text>
                                                </div>
                                                @if(!$isCorrect && $correctOption)
                                                    <div>
                                                        <flux:text class="text-sm font-medium text-gray-600 dark:text-gray-400">Correct answer:</flux:text>
                                                        <flux:text class="text-sm text-green-700 dark:text-green-300 font-medium">
                                                            {{ $correctOption->option_text }}
                                                        </flux:text>
                                                    </div>
                                                @endif
                                                @if($question->explanation && $showExplanations)
                                                    <div class="mt-2 pt-2 border-t border-gray-200 dark:border-gray-600">
                                                        <flux:text class="text-sm font-medium text-gray-600 dark:text-gray-400">Explanation:</flux:text>
                                                        <flux:text class="text-sm text-gray-700 dark:text-gray-300">{{ $question->explanation }}</flux:text>
                                                    </div>
                                                @endif
                                            </div>
                                        @else
                                            <div class="ml-12 space-y-2">
                                                <flux:text class="text-sm text-gray-600 dark:text-gray-400">Not answered</flux:text>
                                                @if($correctOption)
                                                    <div>
                                                        <flux:text class="text-sm font-medium text-gray-600 dark:text-gray-400">Correct answer:</flux:text>
                                                        <flux:text class="text-sm text-green-700 dark:text-green-300 font-medium">
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
    </flux:container>
@endif
</div>

