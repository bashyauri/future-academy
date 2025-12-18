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
        const h = Math.floor(seconds / 3600);
        const m = Math.floor((seconds % 3600) / 60);
        const s = seconds % 60;
        return `${h.toString().padStart(2,'0')}:${m.toString().padStart(2,'0')}:${s.toString().padStart(2,'0')}`;
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
                            wire:click="submitQuiz"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-70 cursor-wait"
                            wire:target="submitQuiz"
                            class="px-4 py-2 bg-green-600 dark:bg-green-600 hover:bg-green-700 dark:hover:bg-green-500 text-white font-semibold rounded-lg transition-all flex items-center gap-2 shadow-sm">
                            <span wire:loading.remove wire:target="submitQuiz">Submit</span>
                            <span wire:loading wire:target="submitQuiz" class="flex items-center gap-2">
                                <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Saving...
                            </span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-neutral-900 border-b border-gray-200 dark:border-neutral-800 px-4 shadow-sm">
                <div class="max-w-7xl mx-auto">
                    <div class="flex gap-2 overflow-x-auto py-2">
                        @foreach($subjectsData as $index => $subject)
                            @php $answered = isset($userAnswers[$subject->id]) ? count(array_filter($userAnswers[$subject->id])) : 0; @endphp
                            <button
                                wire:click="switchSubject({{ $index }})"
                                class="flex-shrink-0 px-4 py-3 rounded-xl whitespace-nowrap transition-all {{ $currentSubjectIndex == $index ? 'bg-blue-600 text-white shadow-md' : 'bg-gray-50 dark:bg-neutral-800 text-gray-800 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-neutral-700' }}">
                                <div class="font-semibold text-sm">{{ $subject->name }}</div>
                                <div class="text-xs mt-1 {{ $currentSubjectIndex == $index ? 'text-blue-100' : 'text-gray-500 dark:text-gray-400' }}">{{ $answered }}/{{ count($questionsBySubject[$subject->id] ?? []) }} answered</div>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto p-4 sm:p-6">
                <div class="max-w-4xl mx-auto">
                    @php
                        $question = $this->getCurrentQuestion();
                        $currentSubjectId = $this->getCurrentSubjectId();
                        $totalInSubject = count($this->getCurrentQuestions());
                    @endphp

                    <div class="mb-6">
                        <div class="flex items-center justify-between mb-3 gap-3">
                            <flux:text class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ $subjectsData[$currentSubjectIndex]->name ?? '' }}</flux:text>
                            <flux:badge color="blue">Question {{ $currentQuestionIndex + 1 }} of {{ $totalInSubject }}</flux:badge>
                        </div>
                        <div class="rounded-xl border border-gray-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-5 sm:p-6 shadow-sm">
                            <flux:text class="text-lg font-medium leading-relaxed text-gray-900 dark:text-gray-100">{{ $question->question_text }}</flux:text>
                        </div>
                    </div>

                    <div class="space-y-3 mb-8">
                        @foreach($question->options as $option)
                            @php $isSelected = ($userAnswers[$currentSubjectId][$currentQuestionIndex] ?? null) == $option->id; @endphp
                            <button
                                wire:click="selectAnswer({{ $option->id }})"
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-70 cursor-wait"
                                wire:target="selectAnswer"
                                class="w-full p-4 rounded-xl border-2 text-left transition-all relative {{ $isSelected ? 'border-green-500 bg-green-50 dark:bg-neutral-900 ring-2 ring-green-300 dark:ring-green-700' : 'border-gray-200 dark:border-neutral-800 hover:border-green-400 dark:hover:border-green-500' }}">
                                <div class="flex items-start gap-3">
                                    <div class="flex-shrink-0 w-6 h-6 rounded-full border-2 {{ $isSelected ? 'border-green-500 bg-green-500' : 'border-gray-300 dark:border-gray-700' }} flex items-center justify-center mt-0.5 transition-all">
                                        @if($isSelected)
                                            <div class="w-2.5 h-2.5 bg-white rounded-full animate-pulse"></div>
                                        @endif
                                    </div>
                                    <span class="flex-1 {{ $isSelected ? 'text-green-700 dark:text-green-200 font-medium' : 'text-gray-800 dark:text-gray-200' }}">{{ $option->option_text }}</span>
                                    <div class="flex items-center gap-2">
                                        @if($isSelected)
                                            <svg class="h-5 w-5 text-green-500 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                        @endif
                                        <svg wire:loading wire:target="selectAnswer" class="h-4 w-4 text-gray-500 dark:text-gray-400 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </div>
                                </div>
                            </button>
                        @endforeach
                    </div>

                    <div class="mt-8 flex justify-between items-center pt-6 border-t border-gray-200 dark:border-neutral-800">
                        <button
                            wire:click="previousQuestion"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-70 cursor-wait"
                            wire:target="previousQuestion"
                            class="px-4 py-2 border border-gray-300 dark:border-neutral-800 text-gray-800 dark:text-gray-200 font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-neutral-800 transition-all {{ ($currentSubjectIndex == 0 && $currentQuestionIndex == 0) ? 'opacity-50 cursor-not-allowed' : '' }}"
                            {{ ($currentSubjectIndex == 0 && $currentQuestionIndex == 0) ? 'disabled' : '' }}>
                            <span wire:loading.remove wire:target="previousQuestion">← Previous</span>
                            <span wire:loading wire:target="previousQuestion" class="flex items-center gap-2">
                                <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Loading...
                            </span>
                        </button>
                        <flux:text class="text-sm text-gray-600 dark:text-gray-400 font-medium">{{ $currentQuestionIndex + 1 }}/{{ $totalInSubject }}</flux:text>
                        <button
                            wire:click="nextQuestion"
                            wire:loading.attr="disabled"
                            wire:target="nextQuestion"
                            class="px-4 py-2 bg-blue-600 dark:bg-blue-600 hover:bg-blue-700 dark:hover:bg-blue-500 text-white font-medium rounded-lg transition-all shadow-sm flex items-center gap-2">
                            <span wire:loading.remove wire:target="nextQuestion">{{ $currentQuestionIndex + 1 >= $totalInSubject ? 'Next Subject' : 'Next' }}</span>
                            <span wire:loading wire:target="nextQuestion" class="flex items-center gap-2">
                                <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Processing...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="hidden lg:block w-80 bg-white dark:bg-neutral-900 border-l border-gray-200 dark:border-neutral-800 overflow-y-auto p-4">
            <div class="mb-6 pb-6 border-b-2 border-blue-200 dark:border-blue-800">
                @php
                    $currentAnswered = isset($userAnswers[$subjectsData[$currentSubjectIndex]->id]) ? count(array_filter($userAnswers[$subjectsData[$currentSubjectIndex]->id])) : 0;
                    $currentTotal = count($questionsBySubject[$subjectsData[$currentSubjectIndex]->id] ?? []);
                @endphp
                <div class="flex items-center justify-between mb-3">
                    <flux:heading size="sm" level="3" class="mb-0 text-blue-600 dark:text-blue-300">{{ $subjectsData[$currentSubjectIndex]->name ?? '' }}</flux:heading>
                    <flux:badge color="blue" class="text-xs">{{ $currentAnswered }}/{{ $currentTotal }}</flux:badge>
                </div>
                <div class="grid grid-cols-5 gap-2">
                    @for($i = 0; $i < $currentTotal; $i++)
                        @php
                            $isAnswered = ($userAnswers[$subjectsData[$currentSubjectIndex]->id][$i] ?? null) !== null;
                            $isCurrent = $currentQuestionIndex == $i;
                        @endphp
                        <button
                            wire:click="jumpToQuestion({{ $currentSubjectIndex }}, {{ $i }})"
                            class="aspect-square h-8 w-8 p-0 flex items-center justify-center rounded-lg text-xs font-medium transition-all {{ $isCurrent ? 'bg-blue-600 text-white ring-2 ring-blue-300 dark:ring-blue-700' : ($isAnswered ? 'bg-green-500 text-white hover:bg-green-600 dark:bg-green-600 dark:hover:bg-green-500' : 'bg-gray-100 dark:bg-neutral-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-neutral-700') }}">
                            {{ $i + 1 }}
                        </button>
                    @endfor
                </div>
            </div>

            @if(count($subjectsData) > 1)
                <div>
                    <flux:text class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-3">Other Subjects</flux:text>
                    @foreach($subjectsData as $subjectIndex => $subject)
                        @if($subjectIndex != $currentSubjectIndex)
                            @php
                                $answered = isset($userAnswers[$subject->id]) ? count(array_filter($userAnswers[$subject->id])) : 0;
                                $total = count($questionsBySubject[$subject->id] ?? []);
                            @endphp
                            <div class="mb-4 pb-3 border-b border-gray-200 dark:border-neutral-800">
                                <div class="flex items-center justify-between mb-2">
                                    <button
                                        wire:click="switchSubject({{ $subjectIndex }})"
                                        class="text-sm font-medium text-gray-800 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-300 transition-colors text-left">
                                        {{ $subject->name }}
                                    </button>
                                    <flux:text class="text-xs text-gray-500 dark:text-gray-400">{{ $answered }}/{{ $total }}</flux:text>
                                </div>
                                <div class="grid grid-cols-8 gap-1">
                                    @for($i = 0; $i < $total; $i++)
                                        @php $isAnswered = ($userAnswers[$subject->id][$i] ?? null) !== null; @endphp
                                        <button
                                            wire:click="jumpToQuestion({{ $subjectIndex }}, {{ $i }})"
                                            class="aspect-square h-6 w-6 p-0 flex items-center justify-center rounded text-xs font-medium transition-all {{ $isAnswered ? 'bg-green-500 text-white hover:bg-green-600 dark:bg-green-600 dark:hover:bg-green-500' : 'bg-gray-200 dark:bg-neutral-800 text-gray-500 dark:text-gray-400 hover:bg-gray-300 dark:hover:bg-neutral-700' }}">
                                        </button>
                                    @endfor
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif
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
                <flux:button wire:navigate href="{{ route('mock.setup') }}" icon="arrow-left">Back to Setup</flux:button>
                <flux:button wire:navigate href="{{ route('dashboard') }}" variant="ghost">Dashboard</flux:button>
            </div>
        </div>
    </flux:container>
@endif
</div>
