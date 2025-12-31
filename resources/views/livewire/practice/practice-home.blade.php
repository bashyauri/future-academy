<div class="space-y-4 md:space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 md:gap-4">
        <div>
            <flux:heading size="xl" class="text-lg md:text-2xl">{{ __('Practice Questions') }}</flux:heading>
            <flux:text class="mt-1 text-xs md:text-sm">{{ __('Master exam questions by practicing with past papers') }}</flux:text>
        </div>
        <flux:button href="{{ route('dashboard') }}" variant="ghost" icon="arrow-left" wire:navigate class="w-full sm:w-auto text-sm md:text-base">
            {{ __('Back to Dashboard') }}
        </flux:button>
    </div>

    {{-- Quick JAMB Practice --}}
    <div class="rounded-xl border border-blue-200 bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 p-4 md:p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 md:gap-4">
            <div>
                <flux:heading size="lg" class="mb-1 md:mb-2 text-base md:text-lg">JAMB Practice Test</flux:heading>
                <flux:text class="text-xs md:text-sm">Take a comprehensive JAMB practice test with 4 subjects, timer, and detailed explanations</flux:text>
            </div>
            <flux:button href="{{ route('practice.jamb.setup') }}" variant="primary" wire:navigate class="w-full sm:w-auto text-sm md:text-base">
                Start JAMB Test
            </flux:button>
        </div>
    </div>

    {{-- Resume In-Progress Quizzes --}}
    @if(!empty($allResumeAttempts) && count($allResumeAttempts) > 0)
        <div class="rounded-xl border border-green-300 bg-white/90 dark:bg-neutral-900/90 shadow-lg p-4 md:p-6 mb-6">
            <flux:heading size="md" class="mb-3 text-green-900 dark:text-green-100 flex items-center gap-2">
                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                {{ __('Resume In-Progress Quizzes') }}
            </flux:heading>
            <div class="flex flex-col gap-3">
                @foreach($allResumeAttempts as $attempt)
                    @if($attempt->status === 'in_progress')
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 p-3 rounded-lg bg-white dark:bg-neutral-800 border border-green-100 dark:border-green-800 shadow-sm transition hover:shadow-md">
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap items-center gap-2 mb-1">
                                <span class="font-semibold text-green-700 dark:text-green-300 truncate">{{ $attempt->exam_type_id ? ($examTypes->firstWhere('id', $attempt->exam_type_id)?->name ?? 'Exam') : 'Exam' }}</span>
                                @if($attempt->exam_year)
                                    <span class="ml-2 text-xs text-neutral-500">{{ $attempt->exam_year }}</span>
                                @endif
                                <span class="ml-2 text-xs text-neutral-500">{{ __('Started') }} {{ $attempt->started_at->diffForHumans() }}</span>
                            </div>
                            <div class="flex flex-wrap items-center gap-2 text-xs text-neutral-500 dark:text-neutral-400">
                                <span>{{ __('Questions') }}: {{ $attempt->total_questions }}</span>
                                @if($attempt->time_taken_seconds)
                                    <span class="px-2 py-0.5 rounded bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-300">{{ __('Timed') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="flex gap-2 mt-2 sm:mt-0">
                            <flux:button
                                variant="primary"
                                wire:key="resume-{{ $attempt->id }}"
                                onclick="window.location='{{ route('practice.quiz', ['exam_type' => $attempt->exam_type_id, 'subject' => $attempt->subject_id, 'year' => $attempt->exam_year, 'attempt' => $attempt->id]) }}'"
                                icon="arrow-path"
                                class="flex-1 sm:flex-none text-sm md:text-base py-2 min-w-[100px]"
                            >
                                {{ __('Resume') }}
                            </flux:button>
                            <flux:button
                                variant="ghost"
                                wire:click="dismissResumeAttempt({{ $attempt->id }})"
                                wire:key="dismiss-{{ $attempt->id }}"
                                icon="x-mark"
                                class="flex-1 sm:flex-none text-xs md:text-sm py-2 text-red-600 hover:text-red-800 border border-red-100 dark:border-red-800 min-w-[80px]"
                            >
                                {{ __('Dismiss') }}
                            </flux:button>
                        </div>
                    </div>
                    @endif
                @endforeach
            </div>
        </div>
    @endif

    {{-- Main Selection Panel --}}
    <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-4 md:p-8 bg-white dark:bg-neutral-800">
        <flux:heading size="lg" class="mb-4 md:mb-6 text-base md:text-lg">{{ __('Select Your Practice Test') }}</flux:heading>

        {{-- Selection Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6">

            {{-- Subject Selection --}}
            <div class="space-y-2 md:space-y-3">
                <flux:heading size="sm" class="text-sm md:text-base">{{ __('Subject') }}</flux:heading>
                <div class="relative">
                    <flux:select
                        wire:model.live="selectedSubject"
                        placeholder="{{ __('Choose subject...') }}"
                        class="text-sm md:text-base"
                    >
                        <option value="">{{ __('Choose subject...') }}</option>
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                        @endforeach
                    </flux:select>
                    <div wire:loading wire:target="selectedSubject" class="absolute right-3 top-1/2 -translate-y-1/2">
                        <svg class="animate-spin h-4 md:h-5 w-4 md:w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                </div>
                <flux:text class="text-xs text-neutral-500 dark:text-neutral-400">
                    {{ __('Select your subject') }}
                </flux:text>
            </div>

            {{-- Exam Type Selection (Optional) --}}
            <div class="space-y-2 md:space-y-3">
                <flux:heading size="sm" class="text-sm md:text-base">{{ __('Exam Type (Optional)') }}</flux:heading>
                <div class="relative">
                    <flux:select
                        wire:model.live="selectedExamType"
                        placeholder="{{ __('(Optional) Choose exam...') }}"
                        class="text-sm md:text-base"
                    >
                        <option value="">{{ __('All Exam Types') }}</option>
                        @foreach($examTypes as $exam)
                            <option value="{{ $exam->id }}">{{ $exam->name }}</option>
                        @endforeach
                    </flux:select>
                    <div wire:loading wire:target="selectedExamType" class="absolute right-3 top-1/2 -translate-y-1/2">
                        <svg class="animate-spin h-4 md:h-5 w-4 md:w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                </div>
                <flux:text class="text-xs text-neutral-500 dark:text-neutral-400">
                    {{ __('e.g., JAMB, NECO, WAEC') }}
                </flux:text>
            </div>

            {{-- Year Selection --}}
            <div class="space-y-2 md:space-y-3">
                <flux:heading size="sm" class="text-sm md:text-base">{{ __('Exam Year') }}</flux:heading>
                <div class="relative">
                    <flux:select
                        wire:model="selectedYear"
                        placeholder="{{ __('(Optional) Choose year...') }}"
                        :disabled="!$selectedExamType || !$selectedSubject"
                        class="text-sm md:text-base"
                    >
                        <option value="">{{ __('(All Years)') }}</option>
                        @foreach($filteredYears as $year)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endforeach
                    </flux:select>
                    <div wire:loading wire:target="selectedSubject,updatedSelectedSubject" class="absolute right-3 top-1/2 -translate-y-1/2">
                        <svg class="animate-spin h-4 md:h-5 w-4 md:w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                </div>
                <flux:text class="text-xs text-neutral-500 dark:text-neutral-400">
                    {{ __('Past exam papers') }}
                </flux:text>
            </div>
        </div>
        {{-- Configuration Options --}}
        <div class="mt-6 p-4 rounded-lg border border-neutral-200 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-700/50 space-y-4">
            <flux:heading size="sm" class="text-sm font-semibold mb-3">{{ __('Practice Settings') }}</flux:heading>

            {{-- Number of Questions --}}
            <div class="space-y-2">
                <flux:label for="questionLimit" class="text-sm">{{ __('Number of Questions') }}</flux:label>
                <div class="flex flex-col sm:flex-row gap-2">
                    <flux:input
                        id="questionLimit"
                        wire:model="questionLimit"
                        type="number"
                        min="1"
                        :max="$availableQuestionCount"
                        placeholder="All questions"
                        class="flex-1 text-sm"
                    />
                    @if($availableQuestionCount > 0)
                        <flux:button
                            wire:click="$set('questionLimit', null)"
                            variant="ghost"
                            size="sm"
                            class="text-xs sm:text-xs w-full sm:w-auto py-2.5 sm:py-2"
                        >
                            Use All ({{ $availableQuestionCount }})
                        </flux:button>
                    @endif
                </div>
                @error('questionLimit')
                    <flux:text class="text-xs text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                @enderror
                <flux:text class="text-xs text-neutral-600 dark:text-neutral-400">
                    {{ __('Leave blank to practice all available questions') }}
                    @if($availableQuestionCount > 0)
                        <span class="font-semibold">({{ $availableQuestionCount }} available)</span>
                    @endif
                </flux:text>
            </div>

            {{-- Time Limit --}}
            <div class="space-y-2">
                <flux:label for="timeLimit" class="text-sm">{{ __('Time Limit (minutes)') }}</flux:label>
                <div class="flex flex-col sm:flex-row gap-2">
                    <flux:input
                        id="timeLimit"
                        wire:model="timeLimit"
                        type="number"
                        min="1"
                        placeholder="No time limit"
                        class="flex-1 text-sm"
                    />
                    <flux:button
                        wire:click="$set('timeLimit', null)"
                        variant="ghost"
                        size="sm"
                        class="text-xs sm:text-xs w-full sm:w-auto py-2.5 sm:py-2"
                    >
                        No Limit
                    </flux:button>
                </div>
                <flux:text class="text-xs text-neutral-600 dark:text-neutral-400">
                    {{ __('Leave blank for untimed practice') }}
                </flux:text>
            </div>

            {{-- Shuffle Questions --}}
            <label class="flex items-start gap-3 cursor-pointer pt-2 border-t border-neutral-200 dark:border-neutral-600">
                <input
                    type="checkbox"
                    wire:model="shuffleQuestions"
                    class="mt-1 h-5 w-5 rounded border-neutral-300 dark:border-neutral-600 text-blue-600 focus:ring-blue-500 dark:focus:ring-blue-600 dark:bg-neutral-700 cursor-pointer"
                >
                <div class="flex-1">
                    <flux:heading size="sm" class="text-sm font-semibold">{{ __('Shuffle Questions') }}</flux:heading>
                    <flux:text class="text-xs text-neutral-600 dark:text-neutral-400 mt-1">
                        {{ __('Randomize the order of questions. Answers and explanations show automatically.') }}
                    </flux:text>
                </div>
            </label>
        </div>

        {{-- Start Button --}}
        <div class="mt-6 md:mt-8 flex flex-col sm:flex-row gap-3 md:gap-4">
            {{-- Resume Button --}}
            @if($resumeAttempt)
                <flux:button
                    variant="success"
                    wire:click="resumePractice"
                    icon="arrow-path"
                    class="w-full sm:w-auto text-sm md:text-base py-3 md:py-2 mb-2"
                    wire:loading.attr="disabled"
                >
                    {{ __('Resume In-Progress Test') }}
                </flux:button>
            @endif
            <flux:button
                variant="primary"
                wire:click="startPractice"
                icon="play"
                class="w-full sm:w-auto text-sm md:text-base py-3 md:py-2"
                wire:loading.attr="disabled"
            >
                <span wire:loading.remove>{{ __('Start Practice Test') }}</span>
                <span wire:loading class="flex items-center justify-center gap-2">
                    <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ __('Loading...') }}
                </span>
            </flux:button>
            <flux:text class="flex-1 text-xs md:text-sm text-neutral-600 dark:text-neutral-400 flex items-center">
                {{ __('Complete all questions to see your score') }}
            </flux:text>
        </div>
    </div>

    {{-- Info Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6">
        {{-- How It Works --}}
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-4 md:p-6 bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-950/30 dark:to-blue-900/20">
            <div class="flex items-start gap-3 md:gap-4">
                <div class="flex-shrink-0">
                    <div class="flex items-center justify-center h-8 md:h-10 w-8 md:w-10 rounded-lg bg-blue-600 dark:bg-blue-500">
                        <svg class="h-5 md:h-6 w-5 md:w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                </div>
                <div>
                    <flux:heading size="sm" class="text-blue-900 dark:text-blue-100 text-sm md:text-base">{{ __('How It Works') }}</flux:heading>
                    <flux:text class="mt-1 md:mt-2 text-xs md:text-sm text-blue-800 dark:text-blue-200">
                        {{ __('Select an exam type, subject, and year. Answer all questions and get instant feedback on your performance.') }}
                    </flux:text>
                </div>
            </div>
        </div>

        {{-- Timed Tests --}}
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-4 md:p-6 bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-950/30 dark:to-purple-900/20">
            <div class="flex items-start gap-3 md:gap-4">
                <div class="flex-shrink-0">
                    <div class="flex items-center justify-center h-8 md:h-10 w-8 md:w-10 rounded-lg bg-purple-600 dark:bg-purple-500">
                        <svg class="h-5 md:h-6 w-5 md:w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <div>
                    <flux:heading size="sm" class="text-purple-900 dark:text-purple-100 text-sm md:text-base">{{ __('Realistic Experience') }}</flux:heading>
                    <flux:text class="mt-1 md:mt-2 text-xs md:text-sm text-purple-800 dark:text-purple-200">
                        {{ __('Practice under exam conditions with accurate timing and scoring just like the real exam.') }}
                    </flux:text>
                </div>
            </div>
        </div>

        {{-- Track Progress --}}
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-4 md:p-6 bg-gradient-to-br from-green-50 to-green-100 dark:from-green-950/30 dark:to-green-900/20">
            <div class="flex items-start gap-3 md:gap-4">
                <div class="flex-shrink-0">
                    <div class="flex items-center justify-center h-8 md:h-10 w-8 md:w-10 rounded-lg bg-green-600 dark:bg-green-500">
                        <svg class="h-5 md:h-6 w-5 md:w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                </div>
                <div>
                    <flux:heading size="sm" class="text-green-900 dark:text-green-100 text-sm md:text-base">{{ __('Track Progress') }}</flux:heading>
                    <flux:text class="mt-1 md:mt-2 text-xs md:text-sm text-green-800 dark:text-green-200">
                        {{ __('Monitor your improvement over time with detailed performance analytics.') }}
                    </flux:text>
                </div>
            </div>
        </div>
    </div>
</div>
