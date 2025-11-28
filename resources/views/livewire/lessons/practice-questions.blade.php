<div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
    <div class="flex items-center justify-between mb-4">
        <flux:heading size="lg">{{ __('Practice Questions') }}</flux:heading>
        <div class="flex items-center gap-3">
            <flux:badge color="blue">{{ count($questions) }} {{ __('Questions') }}</flux:badge>
            @if($totalAnswered > 0)
                <flux:badge :color="$score / $totalAnswered >= 0.7 ? 'green' : 'amber'">
                    {{ __('Score:') }} {{ $score }}/{{ $totalAnswered }}
                </flux:badge>
            @endif
        </div>
    </div>

    <flux:text class="text-sm text-neutral-500 mb-6">
        {{ __('Test your understanding with these practice questions. Select an answer and submit to check if you\'re correct.') }}
    </flux:text>

    {{-- Complete Summary --}}
    @if($isComplete)
        <div class="mb-6 p-6 rounded-xl border-2 {{ $score / $totalAnswered >= 0.7 ? 'border-green-500 bg-green-50 dark:bg-green-950/30' : 'border-amber-500 bg-amber-50 dark:bg-amber-950/30' }}">
            <div class="text-center">
                <div class="w-16 h-16 rounded-full mx-auto mb-4 flex items-center justify-center {{ $score / $totalAnswered >= 0.7 ? 'bg-green-600' : 'bg-amber-600' }} text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        @if($score / $totalAnswered >= 0.7)
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        @else
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        @endif
                    </svg>
                </div>
                <flux:heading size="xl" class="mb-2">
                    {{ $score / $totalAnswered >= 0.7 ? __('Great job!') : __('Keep practicing!') }}
                </flux:heading>
                <flux:text class="text-lg mb-4">
                    {{ __('You scored') }} {{ $score }} {{ __('out of') }} {{ $totalAnswered }} ({{ round(($score / $totalAnswered) * 100) }}%)
                </flux:text>
                <flux:button wire:click="resetAll" variant="primary">
                    {{ __('Try Again') }}
                </flux:button>
            </div>
        </div>
    @endif

    <div class="space-y-6">
        @foreach($questions as $index => $question)
            <div class="p-4 rounded-lg border border-neutral-200 dark:border-neutral-700 {{ $showResults[$index] ? ($selectedAnswers[$index] && $question->options->find($selectedAnswers[$index])->is_correct ? 'bg-green-50 dark:bg-green-950/20' : 'bg-red-50 dark:bg-red-950/20') : 'bg-white dark:bg-neutral-900' }}">
                <div class="flex items-start gap-3 mb-4">
                    <div class="flex-shrink-0 w-8 h-8 rounded-full {{ $showResults[$index] ? ($selectedAnswers[$index] && $question->options->find($selectedAnswers[$index])->is_correct ? 'bg-green-600' : 'bg-red-600') : 'bg-blue-600' }} flex items-center justify-center text-white font-semibold text-sm">
                        @if($showResults[$index])
                            @if($selectedAnswers[$index] && $question->options->find($selectedAnswers[$index])->is_correct)
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            @else
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            @endif
                        @else
                            {{ $index + 1 }}
                        @endif
                    </div>
                    <div class="flex-1">
                        <flux:text class="font-medium">{{ $question->question_text }}</flux:text>
                        @if($question->subject || $question->topic)
                            <div class="flex gap-2 mt-2">
                                @if($question->subject)
                                    <flux:badge color="gray" size="sm">{{ $question->subject->name }}</flux:badge>
                                @endif
                                @if($question->topic)
                                    <flux:badge color="gray" size="sm">{{ $question->topic->name }}</flux:badge>
                                @endif
                                <flux:badge :color="match($question->difficulty) {
                                    'easy' => 'green',
                                    'medium' => 'amber',
                                    'hard' => 'red',
                                    default => 'gray'
                                }" size="sm">
                                    {{ ucfirst($question->difficulty) }}
                                </flux:badge>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="space-y-2 ml-11">
                    @foreach($question->options as $option)
                        <button
                            wire:click="selectAnswer({{ $index }}, {{ $option->id }})"
                            @if($showResults[$index]) disabled @endif
                            class="w-full text-left flex items-start gap-2 p-3 rounded transition-all {{ 
                                $showResults[$index] 
                                    ? ($option->is_correct 
                                        ? 'bg-green-100 dark:bg-green-950/50 border-2 border-green-500 cursor-not-allowed' 
                                        : ($selectedAnswers[$index] == $option->id 
                                            ? 'bg-red-100 dark:bg-red-950/50 border-2 border-red-500 cursor-not-allowed' 
                                            : 'bg-neutral-100 dark:bg-neutral-800 border border-neutral-300 dark:border-neutral-600 cursor-not-allowed opacity-50'))
                                    : ($selectedAnswers[$index] == $option->id 
                                        ? 'bg-blue-100 dark:bg-blue-950/50 border-2 border-blue-500' 
                                        : 'bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 hover:border-blue-300 dark:hover:border-blue-700')
                            }}">
                            <div class="flex-shrink-0 mt-0.5">
                                @if($showResults[$index] && $option->is_correct)
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                @elseif($showResults[$index] && $selectedAnswers[$index] == $option->id && !$option->is_correct)
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                @elseif($selectedAnswers[$index] == $option->id)
                                    <div class="w-4 h-4 rounded-full bg-blue-600"></div>
                                @else
                                    <div class="w-4 h-4 rounded-full border-2 border-neutral-300 dark:border-neutral-600"></div>
                                @endif
                            </div>
                            <flux:text class="{{ $showResults[$index] && $option->is_correct ? 'font-medium' : '' }}">
                                {{ $option->option_text }}
                            </flux:text>
                        </button>
                    @endforeach
                </div>

                @if($showExplanations[$index] && $question->explanation)
                    <div class="ml-11 mt-4 p-3 rounded bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800">
                        <div class="flex items-start gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div>
                                <flux:text class="text-sm font-medium text-blue-900 dark:text-blue-100">{{ __('Explanation:') }}</flux:text>
                                <flux:text class="text-sm text-blue-800 dark:text-blue-200 mt-1">{{ $question->explanation }}</flux:text>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="ml-11 mt-4 flex gap-2">
                    @if(!$showResults[$index])
                        <flux:button 
                            wire:click="submitAnswer({{ $index }})" 
                            variant="primary" 
                            size="sm"
                            :disabled="!isset($selectedAnswers[$index]) || $selectedAnswers[$index] === null">
                            {{ __('Submit Answer') }}
                        </flux:button>
                    @else
                        <flux:button wire:click="resetQuestion({{ $index }})" variant="ghost" size="sm">
                            {{ __('Try Again') }}
                        </flux:button>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
