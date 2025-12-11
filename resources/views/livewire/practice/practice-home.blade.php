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

    {{-- Main Selection Panel --}}
    <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-4 md:p-8 bg-white dark:bg-neutral-800">
        <flux:heading size="lg" class="mb-4 md:mb-6 text-base md:text-lg">{{ __('Select Your Practice Test') }}</flux:heading>

        {{-- Selection Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6">
            {{-- Exam Type Selection --}}
            <div class="space-y-2 md:space-y-3">
                <flux:heading size="sm" class="text-sm md:text-base">{{ __('Exam Type') }}</flux:heading>
                <div class="relative">
                    <flux:select 
                        wire:model.live="selectedExamType"
                        placeholder="{{ __('Choose exam...') }}"
                        class="text-sm md:text-base"
                    >
                        <option value="">{{ __('Choose exam...') }}</option>
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

            {{-- Subject Selection --}}
            <div class="space-y-2 md:space-y-3">
                <flux:heading size="sm" class="text-sm md:text-base">{{ __('Subject') }}</flux:heading>
                <div class="relative">
                    <flux:select 
                        wire:model.live="selectedSubject"
                        placeholder="{{ __('Choose subject...') }}"
                        :disabled="!$selectedExamType"
                        class="text-sm md:text-base"
                    >
                        <option value="">{{ __('Choose subject...') }}</option>
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                        @endforeach
                    </flux:select>
                    <div wire:loading wire:target="selectedSubject,updatedSelectedExamType" class="absolute right-3 top-1/2 -translate-y-1/2">
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

            {{-- Year Selection --}}
            <div class="space-y-2 md:space-y-3">
                <flux:heading size="sm" class="text-sm md:text-base">{{ __('Exam Year') }}</flux:heading>
                <div class="relative">
                    <flux:select 
                        wire:model="selectedYear"
                        placeholder="{{ __('Choose year...') }}"
                        :disabled="!$selectedExamType || !$selectedSubject"
                        class="text-sm md:text-base"
                    >
                        <option value="">{{ __('Choose year...') }}</option>
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

        {{-- Start Button --}}
        <div class="mt-6 md:mt-8 flex flex-col sm:flex-row gap-3 md:gap-4">
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
