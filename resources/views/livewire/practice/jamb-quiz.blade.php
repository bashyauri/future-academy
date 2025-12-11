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

                    <!-- Answer Options -->
                    <div class="space-y-3 mb-8">
                        @foreach($question->options as $option)
                            @php $isSelected = ($userAnswers[$currentSubjectId][$currentQuestionIndex] ?? null) == $option->id; @endphp
                            <button
                                wire:click="selectAnswer({{ $option->id }})"
                                wire:loading.attr="disabled"
                                wire:target="selectAnswer"
                                class="w-full p-4 rounded-lg border-2 text-left transition-all relative {{ $isSelected ? 'border-green-500 bg-green-50 dark:bg-green-900/20 ring-2 ring-green-300 dark:ring-green-700' : 'border-gray-200 dark:border-gray-700 hover:border-green-400 dark:hover:border-green-600' }} disabled:opacity-50 disabled:cursor-not-allowed">
                                <div class="flex items-start gap-3">
                                    <!-- Radio Button -->
                                    <div class="flex-shrink-0 w-6 h-6 rounded-full border-2 {{ $isSelected ? 'border-green-500 bg-green-500' : 'border-gray-300 dark:border-gray-600 group-hover:border-green-400' }} flex items-center justify-center flex-shrink-0 mt-0.5 transition-all">
                                        @if($isSelected)
                                            <div class="w-2.5 h-2.5 bg-white rounded-full animate-pulse"></div>
                                        @endif
                                    </div>
                                    <!-- Option Text -->
                                    <span class="flex-1 {{ $isSelected ? 'text-green-700 dark:text-green-300 font-medium' : 'text-gray-700 dark:text-gray-300' }}">{{ $option->option_text }}</span>
                                    <!-- Loading Indicator -->
                                    <div wire:loading wire:target="selectAnswer" class="flex-shrink-0">
                                        <svg class="animate-spin h-5 w-5 text-green-500 dark:text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </div>
                                    <!-- Checkmark (shown when selected and not loading) -->
                                    <div wire:loading.remove wire:target="selectAnswer" class="flex-shrink-0">
                                        @if($isSelected)
                                            <svg class="h-5 w-5 text-green-500 dark:text-green-400 animate-bounce" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                        @endif
                                    </div>
                                </div>
                            </button>
                        @endforeach
                    </div>

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

