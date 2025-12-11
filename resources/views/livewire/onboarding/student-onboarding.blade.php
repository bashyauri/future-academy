<div class="space-y-6">
    {{-- Progress Bar --}}
    <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-6 bg-white dark:bg-neutral-800">
        <div class="flex items-center justify-between mb-3">
            <flux:heading size="sm">Step {{ $step }} of 3</flux:heading>
            <flux:text class="text-sm">{{ round(($step / 3) * 100) }}% Complete</flux:text>
        </div>
        <div class="w-full bg-neutral-200 dark:bg-neutral-700 rounded-full h-2">
            <div class="bg-blue-600 dark:bg-blue-500 h-2 rounded-full transition-all duration-300" style="width: {{ ($step / 3) * 100 }}%"></div>
        </div>
    </div>

    {{-- Step 1: Select Stream --}}
    @if($step === 1)
    <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-8 bg-white dark:bg-neutral-800">
        <flux:heading size="2xl" class="mb-2">{{ __('Choose Your Stream') }}</flux:heading>
        <flux:text class="mb-8 text-neutral-600 dark:text-neutral-400">{{ __('Select your area of study or choose subjects manually') }}</flux:text>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @foreach($streams as $stream)
            <button 
                wire:click="selectStream('{{ $stream->slug }}')"
                class="group relative text-left rounded-xl border-2 border-neutral-200 dark:border-neutral-700 p-6 hover:border-blue-500 dark:hover:border-blue-500 hover:bg-neutral-50 dark:hover:bg-neutral-700 transition-all duration-200"
            >
                <div class="flex items-start space-x-4">
                    @if($stream->icon)
                    <span class="text-4xl flex-shrink-0">{{ $stream->icon }}</span>
                    @endif
                    <div class="flex-1">
                        <flux:heading size="sm" class="group-hover:text-blue-600 dark:group-hover:text-blue-400">
                            {{ $stream->name }}
                        </flux:heading>
                        @if($stream->description)
                        <flux:text class="mt-2 text-sm text-neutral-600 dark:text-neutral-400">{{ $stream->description }}</flux:text>
                        @endif
                    </div>
                </div>
                <svg class="absolute top-4 right-4 w-6 h-6 text-neutral-400 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
            @endforeach

            {{-- Custom Option --}}
            <button 
                wire:click="selectStream('custom')"
                class="group relative text-left rounded-xl border-2 border-purple-200 dark:border-purple-800 p-6 bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-950/30 dark:to-pink-950/30 hover:border-purple-500 dark:hover:border-purple-500 hover:shadow-md transition-all duration-200"
            >
                <div class="flex items-start space-x-4">
                    <span class="text-4xl flex-shrink-0">🎯</span>
                    <div class="flex-1">
                        <flux:heading size="sm" class="text-purple-900 dark:text-purple-100 group-hover:text-purple-700 dark:group-hover:text-purple-300">
                            {{ __('Choose Subjects Manually') }}
                        </flux:heading>
                        <flux:text class="mt-2 text-sm text-purple-700 dark:text-purple-300">{{ __('Select your own combination of subjects') }}</flux:text>
                    </div>
                </div>
                <svg class="absolute top-4 right-4 w-6 h-6 text-purple-400 dark:text-purple-600 group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
        </div>
    </div>
    @endif

    {{-- Step 2: Select Exam Type --}}
    @if($step === 2)
    <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-8 bg-white dark:bg-neutral-800">
        <flux:heading size="2xl" class="mb-2">{{ __('Select Exam Type') }}</flux:heading>
        <flux:text class="mb-8 text-neutral-600 dark:text-neutral-400">{{ __('Choose the exam(s) you\'re preparing for') }}</flux:text>

        @error('selectedExamTypes')
            <div class="rounded-lg border border-red-200 dark:border-red-900 bg-red-50 dark:bg-red-950/30 p-4 mb-6">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <flux:text class="text-red-800 dark:text-red-200">{{ $message }}</flux:text>
                </div>
            </div>
        @enderror

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            @foreach($examTypes as $examType)
            <button 
                wire:click="toggleExamType({{ $examType->id }})"
                class="group relative text-left rounded-xl border-2 p-6 transition-all duration-200 {{ in_array($examType->id, $selectedExamTypes) ? 'border-blue-500 dark:border-blue-500 bg-blue-50 dark:bg-blue-950/30' : 'border-neutral-200 dark:border-neutral-700 hover:border-blue-300 dark:hover:border-blue-700' }}"
            >
                <div class="flex items-start space-x-4">
                    <div class="flex-shrink-0 mt-1">
                        <div class="w-6 h-6 rounded-full border-2 flex items-center justify-center transition-all {{ in_array($examType->id, $selectedExamTypes) ? 'border-blue-500 dark:border-blue-500 bg-blue-500 dark:bg-blue-600' : 'border-neutral-300 dark:border-neutral-600' }}">
                            @if(in_array($examType->id, $selectedExamTypes))
                            <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            @endif
                        </div>
                    </div>
                    <div class="flex-1">
                        <flux:heading size="sm">{{ $examType->name }}</flux:heading>
                        @if($examType->description)
                        <flux:text class="mt-2 text-sm text-neutral-600 dark:text-neutral-400">{{ $examType->description }}</flux:text>
                        @endif
                    </div>
                </div>
            </button>
            @endforeach
        </div>

        <div class="flex justify-between gap-4">
            <flux:button variant="ghost" wire:click="previousStep" icon="arrow-left">
                {{ __('Previous') }}
            </flux:button>
            <flux:button variant="primary" wire:click="nextToSubjects" icon-trailing="arrow-right">
                {{ __('Next: Choose Subjects') }}
            </flux:button>
        </div>
    </div>
    @endif

    {{-- Step 3: Select Subjects --}}
    @if($step === 3)
    <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-8 bg-white dark:bg-neutral-800">
        <flux:heading size="2xl" class="mb-2">{{ __('Select Your Subjects') }}</flux:heading>
        <flux:text class="mb-6 text-neutral-600 dark:text-neutral-400">{{ __('Choose the subjects you want to study (minimum 1 required)') }}</flux:text>

        @error('selectedSubjects')
            <div class="rounded-lg border border-red-200 dark:border-red-900 bg-red-50 dark:bg-red-950/30 p-4 mb-6">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <flux:text class="text-red-800 dark:text-red-200">{{ $message }}</flux:text>
                </div>
            </div>
        @enderror

        <div class="mb-6 flex items-center justify-between">
            <flux:text class="text-sm font-medium">
                {{ __('Selected:') }} <span class="text-blue-600 dark:text-blue-400 font-semibold">{{ count($selectedSubjects) }}</span> {{ __('subject(s)') }}
            </flux:text>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mb-8">
            @foreach($subjects as $subject)
            <button 
                wire:click="toggleSubject({{ $subject->id }})"
                class="group relative text-center rounded-lg border-2 p-4 transition-all duration-200 {{ in_array($subject->id, $selectedSubjects) ? 'border-blue-500 dark:border-blue-500 bg-blue-50 dark:bg-blue-950/30' : 'border-neutral-200 dark:border-neutral-700 hover:border-blue-300 dark:hover:border-blue-700' }}"
            >
                @if(in_array($subject->id, $selectedSubjects))
                <div class="absolute top-2 right-2">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                </div>
                @endif
                
                @if($subject->icon)
                <div class="text-2xl mb-2">{{ $subject->icon }}</div>
                @endif
                <flux:text class="text-sm font-medium">{{ $subject->name }}</flux:text>
            </button>
            @endforeach
        </div>

        <div class="flex justify-between gap-4">
            @if($selectedStream !== 'custom')
            <flux:button variant="ghost" wire:click="previousStep" icon="arrow-left">
                {{ __('Previous') }}
            </flux:button>
            @else
            <flux:button variant="ghost" wire:click="$set('step', 1)" icon="arrow-left">
                {{ __('Back to Streams') }}
            </flux:button>
            @endif
            
            <flux:button variant="primary" color="green" wire:click="completeOnboarding" icon="check">
                {{ __('Complete Setup') }}
            </flux:button>
        </div>
    </div>
    @endif
</div>
