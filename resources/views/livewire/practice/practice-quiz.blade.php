<div x-data="{
    timeRemaining: @entangle('timeRemaining'),
    timer: null,
    init() {
        if (@js($time) > 0) this.startTimer();
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
    confirmSubmit() {
        const answered = @js(collect($userAnswers)->filter(fn($a) => $a !== null)->count());
        const total = @js($totalQuestions);
        if (confirm(`Are you sure you want to submit your exam?\n\nYou have answered ${answered} out of ${total} questions.\n\nOnce submitted, you won't be able to change your answers.`)) {
            $wire.call('submitQuiz');
        }
    },
    confirmExit() {
        const answered = @js(collect($userAnswers)->filter(fn($a) => $a !== null)->count());
        const total = @js($totalQuestions);
        if (confirm(`Your progress will be saved and you can continue this practice exam later.\n\nCurrent progress: ${answered} of ${total} questions answered.\n\nDo you want to exit?`)) {
            $wire.call('exitQuiz');
        }
    }
}" class="min-h-screen bg-white dark:bg-neutral-950">
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
                                <flux:badge color="blue">Question {{ $currentQuestionIndex + 1 }} of {{ $totalQuestions }}</flux:badge>
                            </div>
                        </div>

                        {{-- Question Card --}}
                        @php $question = $questions[$currentQuestionIndex] ?? null; @endphp
                        @if($question)
                            <div class="rounded-xl border border-gray-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-5 sm:p-6 shadow-sm mb-8">
                                <flux:text class="text-lg font-medium leading-relaxed text-gray-900 dark:text-gray-100">{{ $question['question_text'] }}</flux:text>
                                @if($question['question_image'])
                                    <img src="{{ $question['question_image'] }}" alt="Question" class="mt-4 max-w-full h-auto rounded-lg" loading="lazy">
                                @endif
                            </div>

                            {{-- Options --}}
                            <div class="space-y-3 mb-8">
                                @foreach($question['options'] as $option)
                                    @php $isSelected = ($userAnswers[$currentQuestionIndex] ?? null) == $option['id']; @endphp
                                    <button
                                        wire:click="selectAnswer({{ $option['id'] }})"
                                        class="w-full p-4 rounded-xl border-2 text-left transition-all {{ $isSelected ? 'border-green-500 bg-green-50 dark:bg-neutral-900 ring-2 ring-green-300 dark:ring-green-700' : 'border-gray-200 dark:border-neutral-800 hover:border-green-400 dark:hover:border-green-500' }}">
                                        <div class="flex items-start gap-3">
                                            <div class="flex-shrink-0 w-6 h-6 rounded-full border-2 {{ $isSelected ? 'border-green-500 bg-green-500' : 'border-gray-300 dark:border-gray-700' }} flex items-center justify-center mt-0.5 transition-all">
                                                @if($isSelected)
                                                    <div class="w-2.5 h-2.5 bg-white rounded-full"></div>
                                                @endif
                                            </div>
                                            <span class="flex-1 {{ $isSelected ? 'text-green-700 dark:text-green-200 font-medium' : 'text-gray-800 dark:text-gray-200' }}">{{ $option['option_text'] }}</span>
                                            @if($isSelected)
                                                <svg class="h-5 w-5 text-green-500 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                            @endif
                                        </div>
                                    </button>
                                @endforeach
                            </div>

                            {{-- Explanation --}}
                            @if($userAnswers[$currentQuestionIndex] && $question['explanation'])
                                <div class="mb-8 p-5 rounded-lg border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-950/20">
                                    <div class="flex gap-3">
                                        <div class="flex-shrink-0">
                                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                            </svg>
                                        </div>
                                        <div class="flex-1">
                                            <flux:heading size="sm" class="text-blue-900 dark:text-blue-300 mb-1 font-semibold">Explanation</flux:heading>
                                            <flux:text class="text-sm text-blue-800 dark:text-blue-400 leading-relaxed">{{ $question['explanation'] }}</flux:text>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            {{-- Navigation --}}
                            <div class="flex items-center justify-between gap-3">
                                <button
                                    wire:click="previousQuestion"
                                    @if($currentQuestionIndex === 0) disabled @endif
                                    class="flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-neutral-800 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-200 dark:hover:bg-neutral-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                    ← Previous
                                </button>
                                <flux:text class="text-sm text-gray-600 dark:text-gray-400 font-medium">{{ $currentQuestionIndex + 1 }}/{{ $totalQuestions }}</flux:text>
                                <button
                                    wire:click="nextQuestion"
                                    class="px-4 py-2 bg-blue-600 dark:bg-blue-600 hover:bg-blue-700 dark:hover:bg-blue-500 text-white font-medium rounded-lg transition-all shadow-sm">
                                    Next →
                                </button>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>

        {{-- Sidebar - Hidden on Mobile --}}
        <div class="hidden lg:block w-80 bg-white dark:bg-neutral-900 border-l border-gray-200 dark:border-neutral-800 overflow-y-auto p-4">
            <div class="mb-6 pb-6 border-b-2 border-blue-200 dark:border-blue-800">
                <div class="flex items-center justify-between mb-3">
                    <flux:heading size="sm" level="3" class="mb-0 text-blue-600 dark:text-blue-300">Questions</flux:heading>
                    @php $answered = collect($userAnswers)->filter(fn($a) => $a !== null)->count(); @endphp
                    <flux:badge color="blue" class="text-xs">{{ $answered }}/{{ $totalQuestions }}</flux:badge>
                </div>
                <div class="grid grid-cols-5 gap-2">
                    @for($i = 0; $i < $totalQuestions; $i++)
                        @php
                            $isAnswered = ($userAnswers[$i] ?? null) !== null;
                            $isCurrent = $currentQuestionIndex == $i;
                        @endphp
                        <button
                            wire:click="jumpToQuestion({{ $i }})"
                            class="aspect-square h-8 w-8 p-0 flex items-center justify-center rounded-lg text-xs font-medium transition-all {{ $isCurrent ? 'bg-blue-600 text-white ring-2 ring-blue-300 dark:ring-blue-700' : ($isAnswered ? 'bg-green-500 text-white hover:bg-green-600 dark:bg-green-600 dark:hover:bg-green-500' : 'bg-gray-100 dark:bg-neutral-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-neutral-700') }}">
                            {{ $i + 1 }}
                        </button>
                    @endfor
                </div>
            </div>

            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <flux:text class="text-sm font-medium text-gray-600 dark:text-gray-400">Answered</flux:text>
                    <flux:text class="font-semibold text-gray-900 dark:text-gray-100">{{ $answered }}/{{ $totalQuestions }}</flux:text>
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
