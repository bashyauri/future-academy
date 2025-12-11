<div class="space-y-4 md:space-y-6">
    @if(!$showResults)
    {{-- Quiz Taking Interface --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-6">
        {{-- Main Question Area --}}
        <div class="lg:col-span-2 space-y-4 md:space-y-6">
            {{-- Progress Header --}}
            <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-4 md:p-6 bg-white dark:bg-neutral-800">
                <div class="flex items-center justify-between mb-3 md:mb-4">
                    <flux:heading size="sm" class="text-sm md:text-base">
                        Question {{ $currentQuestionIndex + 1 }} of {{ $totalQuestions }}
                    </flux:heading>
                    <flux:text class="text-xs md:text-sm text-neutral-600 dark:text-neutral-400">
                        {{ round(($currentQuestionIndex + 1) / $totalQuestions * 100) }}% Complete
                    </flux:text>
                </div>
                <div class="w-full bg-neutral-200 dark:bg-neutral-700 rounded-full h-2">
                    <div class="bg-blue-600 dark:bg-blue-500 h-2 rounded-full transition-all" 
                         style="width: {{ ($currentQuestionIndex + 1) / $totalQuestions * 100 }}%"></div>
                </div>
            </div>

            {{-- Current Question --}}
            @if(isset($questions[$currentQuestionIndex]))
            @php $question = $questions[$currentQuestionIndex]; @endphp
            
            <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-4 md:p-8 bg-white dark:bg-neutral-800 space-y-4 md:space-y-6">
                {{-- Question Text --}}
                <div>
                    <flux:heading size="lg" class="text-lg md:text-xl">{{ $question['question_text'] }}</flux:heading>
                    @if($question['question_image'])
                    <img src="{{ $question['question_image'] }}" alt="Question" class="mt-3 md:mt-4 max-w-full h-auto rounded-lg">
                    @endif
                </div>

                {{-- Options --}}
                <div class="space-y-2 md:space-y-3">
                    @foreach($question['options'] as $option)
                    @php $isSelected = $userAnswers[$currentQuestionIndex] === $option['id']; @endphp
                    <button 
                        wire:click="selectAnswer({{ $option['id'] }})"
                        wire:loading.attr="disabled"
                        wire:target="selectAnswer"
                        class="w-full text-left rounded-lg border-2 p-3 md:p-4 transition-all disabled:opacity-50 disabled:cursor-not-allowed active:scale-95 md:active:scale-100 {{ $isSelected ? 'border-green-500 dark:border-green-500 bg-green-50 dark:bg-green-900/20 ring-2 ring-green-300 dark:ring-green-700' : 'border-neutral-200 dark:border-neutral-700 hover:border-green-400 dark:hover:border-green-600' }}"
                    >
                        <div class="flex items-start gap-3 md:gap-4">
                            <div class="flex-shrink-0 mt-1">
                                <div class="w-5 md:w-6 h-5 md:h-6 rounded-full border-2 flex items-center justify-center transition-all {{ $isSelected ? 'border-green-500 dark:border-green-500 bg-green-500 dark:bg-green-600' : 'border-neutral-300 dark:border-neutral-600' }}">
                                    @if($isSelected)
                                    <div class="w-2 md:w-2.5 h-2 md:h-2.5 bg-white rounded-full animate-pulse"></div>
                                    @endif
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <flux:text class="text-sm md:text-base {{ $isSelected ? 'text-green-700 dark:text-green-300 font-medium' : 'text-neutral-700 dark:text-neutral-300' }}">{{ $option['option_text'] }}</flux:text>
                                @if($option['option_image'])
                                <img src="{{ $option['option_image'] }}" alt="Option" class="mt-2 max-w-full h-auto rounded">
                                @endif
                            </div>
                            {{-- Loading Indicator --}}
                            <div wire:loading wire:target="selectAnswer" class="flex-shrink-0">
                                <svg class="animate-spin h-4 md:h-5 w-4 md:w-5 text-green-500 dark:text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                            {{-- Checkmark (shown when selected and not loading) --}}
                            <div wire:loading.remove wire:target="selectAnswer" class="flex-shrink-0">
                                @if($isSelected)
                                <svg class="h-4 md:h-5 w-4 md:w-5 text-green-500 dark:text-green-400 animate-bounce" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                @endif
                            </div>
                        </div>
                    </button>
                    @endforeach
                </div>
            </div>

            {{-- Navigation Buttons --}}
            <div class="flex flex-col-reverse sm:flex-row gap-2 md:gap-4">
                <flux:button 
                    variant="ghost" 
                    icon="arrow-left"
                    wire:click="previousQuestion"
                    wire:loading.attr="disabled"
                    wire:target="previousQuestion"
                    :disabled="$currentQuestionIndex === 0"
                    class="text-sm md:text-base"
                >
                    <span wire:loading.remove wire:target="previousQuestion">{{ __('Previous') }}</span>
                    <span wire:loading wire:target="previousQuestion" class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </span>
                </flux:button>
                
                @if($currentQuestionIndex === $totalQuestions - 1)
                <flux:button 
                    variant="primary" 
                    icon="check"
                    wire:click="submitQuiz"
                    wire:loading.attr="disabled"
                    wire:target="submitQuiz"
                    class="flex-1 text-sm md:text-base py-3 md:py-2"
                >
                    <span wire:loading.remove wire:target="submitQuiz">{{ __('Submit Quiz') }}</span>
                    <span wire:loading wire:target="submitQuiz" class="flex items-center justify-center gap-2">
                        <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ __('Submitting...') }}
                    </span>
                </flux:button>
                @else
                <flux:button 
                    variant="primary" 
                    icon-trailing="arrow-right"
                    wire:click="nextQuestion"
                    wire:loading.attr="disabled"
                    wire:target="nextQuestion"
                    class="flex-1 text-sm md:text-base py-3 md:py-2"
                >
                    <span wire:loading.remove wire:target="nextQuestion">{{ __('Next') }}</span>
                    <span wire:loading wire:target="nextQuestion" class="flex items-center justify-center gap-2">
                        <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </span>
                </flux:button>
                @endif
            </div>
            @endif
        </div>

        {{-- Sidebar: Question Navigator (Hidden on Mobile, Visible on Large Screens) --}}
        <div class="hidden lg:block lg:col-span-1">
            <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-6 bg-white dark:bg-neutral-800 sticky top-20">
                <flux:heading size="sm" class="mb-4">{{ __('Questions') }}</flux:heading>
                
                <div class="grid grid-cols-5 gap-2 mb-6">
                    @for($i = 0; $i < $totalQuestions; $i++)
                    <button 
                        wire:click="jumpToQuestion({{ $i }})"
                        wire:loading.attr="disabled"
                        wire:target="jumpToQuestion"
                        class="aspect-square rounded-lg border-2 font-semibold text-sm transition-all disabled:opacity-50 disabled:cursor-not-allowed {{ $currentQuestionIndex === $i ? 'border-blue-500 dark:border-blue-500 bg-blue-600 dark:bg-blue-500 text-white' : ($userAnswers[$i] !== null ? 'border-green-500 dark:border-green-500 bg-green-50 dark:bg-green-950/30 text-green-700 dark:text-green-300' : 'border-neutral-300 dark:border-neutral-600 hover:border-neutral-400 dark:hover:border-neutral-500') }}"
                    >
                        {{ $i + 1 }}
                    </button>
                    @endfor
                </div>

                <div class="space-y-3 text-xs">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full bg-blue-600"></div>
                        <flux:text>{{ __('Current') }}</flux:text>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full bg-green-600"></div>
                        <flux:text>{{ __('Answered') }}</flux:text>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full border-2 border-neutral-300"></div>
                        <flux:text>{{ __('Not Answered') }}</flux:text>
                    </div>
                </div>
            </div>
        </div>

        {{-- Mobile Question Navigator (Visible on Mobile Only) --}}
        <div class="lg:hidden">
            <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-4 bg-white dark:bg-neutral-800">
                <flux:heading size="sm" class="mb-3">{{ __('Progress') }}</flux:heading>
                
                {{-- Horizontal Scrollable Question Grid --}}
                <div class="overflow-x-auto -mx-4 px-4 mb-4">
                    <div class="flex gap-2 pb-2">
                        @for($i = 0; $i < $totalQuestions; $i++)
                        <button 
                            wire:click="jumpToQuestion({{ $i }})"
                            wire:loading.attr="disabled"
                            wire:target="jumpToQuestion"
                            class="flex-shrink-0 w-10 h-10 rounded-lg border-2 font-semibold text-xs transition-all disabled:opacity-50 disabled:cursor-not-allowed {{ $currentQuestionIndex === $i ? 'border-blue-500 dark:border-blue-500 bg-blue-600 dark:bg-blue-500 text-white' : ($userAnswers[$i] !== null ? 'border-green-500 dark:border-green-500 bg-green-50 dark:bg-green-950/30 text-green-700 dark:text-green-300' : 'border-neutral-300 dark:border-neutral-600') }}"
                        >
                            {{ $i + 1 }}
                        </button>
                        @endfor
                    </div>
                </div>

                <div class="space-y-2 text-xs">
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full bg-blue-600"></div>
                        <flux:text class="text-xs">{{ __('Current') }}</flux:text>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full bg-green-600"></div>
                        <flux:text class="text-xs">{{ __('Answered') }}</flux:text>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full border-2 border-neutral-300"></div>
                        <flux:text class="text-xs">{{ __('Not Answered') }}</flux:text>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @else
    {{-- Results Screen --}}
    <div class="space-y-4 md:space-y-6">
        {{-- Score Card --}}
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-6 md:p-8 bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-950/30 dark:to-blue-900/20 text-center">
            <flux:heading size="xl" class="mb-4 text-xl md:text-2xl">{{ __('Quiz Completed!') }}</flux:heading>
            
            <div class="my-6 md:my-8">
                <div class="inline-flex items-center justify-center w-24 h-24 md:w-32 md:h-32 rounded-full border-6 md:border-8 border-blue-600 dark:border-blue-500 bg-white dark:bg-neutral-900">
                    <div class="text-center">
                        <flux:heading size="lg" class="text-lg md:text-2xl text-blue-600 dark:text-blue-400">{{ $score }}</flux:heading>
                        <flux:text class="text-xs md:text-sm text-neutral-600 dark:text-neutral-400">/{{ $totalQuestions }}</flux:text>
                    </div>
                </div>
            </div>
            
            <flux:heading size="lg" class="mb-2 text-base md:text-lg">{{ round($score / $totalQuestions * 100) }}%</flux:heading>
            <flux:text class="text-base md:text-lg text-neutral-700 dark:text-neutral-300">
                @if(round($score / $totalQuestions * 100) >= 70)
                    {{ __('Excellent! Great job!') }}
                @elseif(round($score / $totalQuestions * 100) >= 50)
                    {{ __('Good effort! Keep practicing!') }}
                @else
                    {{ __('Keep practicing! You\'ll improve!') }}
                @endif
            </flux:text>
        </div>

        {{-- Statistics --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6">
            <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-4 md:p-6 bg-white dark:bg-neutral-800 text-center">
                <flux:text class="text-xs md:text-sm text-neutral-600 dark:text-neutral-400 mb-2">{{ __('Correct Answers') }}</flux:text>
                <flux:heading size="lg" class="text-base md:text-lg text-green-600 dark:text-green-400">{{ $score }}</flux:heading>
            </div>
            
            <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-4 md:p-6 bg-white dark:bg-neutral-800 text-center">
                <flux:text class="text-xs md:text-sm text-neutral-600 dark:text-neutral-400 mb-2">{{ __('Wrong Answers') }}</flux:text>
                <flux:heading size="lg" class="text-base md:text-lg text-red-600 dark:text-red-400">{{ $totalQuestions - $score }}</flux:heading>
            </div>
            
            <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-4 md:p-6 bg-white dark:bg-neutral-800 text-center">
                <flux:text class="text-xs md:text-sm text-neutral-600 dark:text-neutral-400 mb-2">{{ __('Percentage') }}</flux:text>
                <flux:heading size="lg" class="text-base md:text-lg text-blue-600 dark:text-blue-400">{{ round($score / $totalQuestions * 100) }}%</flux:heading>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="flex flex-col gap-3 md:gap-4">
            <flux:button 
                href="{{ route('practice.home') }}" 
                variant="ghost" 
                icon="arrow-left"
                wire:navigate
                class="w-full text-sm md:text-base py-3 md:py-2"
            >
                {{ __('Try Another Test') }}
            </flux:button>
            
            <flux:button 
                href="{{ route('dashboard') }}" 
                variant="primary" 
                icon="home"
                wire:navigate
                class="w-full text-sm md:text-base py-3 md:py-2"
            >
                {{ __('Back to Dashboard') }}
            </flux:button>
        </div>
    </div>
    @endif
</div>
