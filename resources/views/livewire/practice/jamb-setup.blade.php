<flux:container>
    <div class="space-y-8 py-8">
        <!-- Header -->
        <div>
            <flux:heading size="xl" level="1">JAMB Practice Test Setup</flux:heading>
            <flux:text class="text-gray-600 dark:text-gray-400 mt-2">Configure your JAMB practice test. Select a year and choose exactly 4 subjects to begin.</flux:text>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column: Year & Subjects -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Year Selection -->
                <div>
                    <flux:heading size="lg" level="2" class="mb-2">Select Exam Year <span class="text-sm text-gray-500 dark:text-gray-400 font-normal">(Optional)</span></flux:heading>
                    <flux:text class="text-sm text-gray-600 dark:text-gray-400 mb-4">Choose a specific year or leave blank to practice from all available years</flux:text>

                    <div class="grid grid-cols-3 sm:grid-cols-4 gap-3">
                        <!-- All Years Button -->
                        <button
                            wire:click="$set('selectedYear', null)"
                            class="px-4 py-2 rounded-lg font-semibold transition-all {{ $selectedYear === null ? 'bg-blue-500 text-white shadow-md' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                            All Years
                        </button>

                        @if($years->count() > 0)
                            @foreach($years as $year)
                                <button
                                    wire:click="$set('selectedYear', {{ $year }})"
                                    class="px-4 py-2 rounded-lg font-semibold transition-all {{ $selectedYear == $year ? 'bg-green-500 text-white shadow-md' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                                    {{ $year }}
                                </button>
                            @endforeach
                        @endif
                    </div>

                    @if($years->count() === 0)
                        <div class="mt-4 p-3 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800">
                            <flux:text class="text-blue-800 dark:text-blue-200 text-sm">No specific years configured yet. Start with "All Years" to practice from all available questions.</flux:text>
                        </div>
                    @endif

                    @error('selectedYear')
                        <div class="mt-2 p-3 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
                            <flux:text class="text-red-800 dark:text-red-200 text-sm">{{ $message }}</flux:text>
                        </div>
                    @enderror
                </div>

                <!-- Subject Selection -->
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <flux:heading size="lg" level="2" class="mb-1">Select 4 Subjects</flux:heading>
                            <flux:text class="text-sm text-gray-600 dark:text-gray-400">Choose exactly 4 subjects for your JAMB practice</flux:text>
                        </div>
                        <div class="px-4 py-2 rounded-lg {{ count($selectedSubjects) == $maxSubjects ? 'bg-green-50 dark:bg-green-900/20' : 'bg-gray-100 dark:bg-gray-800' }}">
                            <flux:text class="font-semibold {{ count($selectedSubjects) == $maxSubjects ? 'text-green-700 dark:text-green-300' : 'text-gray-700 dark:text-gray-300' }}">
                                {{ count($selectedSubjects) }}/{{ $maxSubjects }}
                            </flux:text>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach($subjects as $subject)
                            @php
                                $isSelected = in_array($subject->id, $selectedSubjects);
                                $canSelect = count($selectedSubjects) < $maxSubjects || $isSelected;
                            @endphp
                            <button
                                wire:click="toggleSubject({{ $subject->id }})"
                                wire:loading.attr="disabled"
                                wire:target="toggleSubject"
                                class="w-full text-left h-auto py-3 px-4 rounded-lg border-2 font-medium transition-all {{ $isSelected ? 'border-green-500 bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300' : ($canSelect ? 'border-gray-200 dark:border-gray-700 hover:border-green-400 dark:hover:border-green-600 text-gray-700 dark:text-gray-300' : 'border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800 text-gray-400 dark:text-gray-600 cursor-not-allowed opacity-50') }}"
                                {{ !$canSelect ? 'disabled' : '' }}>
                                <div class="flex items-center justify-between w-full">
                                    <span>{{ $subject->name }}</span>
                                    @if($isSelected)
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                    @endif
                                </div>
                            </button>
                        @endforeach
                    </div>

                    @error('selectedSubjects')
                        <div class="mt-2 p-3 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
                            <flux:text class="text-red-800 dark:text-red-200 text-sm">{{ $message }}</flux:text>
                        </div>
                    @enderror
                </div>
            </div>

            <!-- Right Column: Configuration -->
            <div class="space-y-6">
                <!-- Quiz Settings -->
                <div class="space-y-6">
                    <flux:heading size="lg" level="2" class="mb-4">Quiz Settings</flux:heading>

                    <!-- Questions Per Subject -->
                    <div class="space-y-2">
                        <flux:label for="questionsPerSubject" class="text-sm">Questions per Subject</flux:label>
                        <div class="flex flex-col sm:flex-row gap-2">
                            <flux:input
                                id="questionsPerSubject"
                                wire:model="questionsPerSubject"
                                type="number"
                                min="1"
                                placeholder="All questions (default: 40)"
                                class="flex-1 text-sm"
                            />
                            <flux:button
                                wire:click="$set('questionsPerSubject', null)"
                                variant="ghost"
                                size="sm"
                                class="text-xs sm:text-xs w-full sm:w-auto py-2.5 sm:py-2"
                            >
                                Reset
                            </flux:button>
                        </div>
                        <flux:text class="text-xs text-gray-500 dark:text-gray-400">
                            Leave blank for all available questions (default: 40 per subject)
                        </flux:text>
                    </div>

                    <!-- Time Limit -->
                    <div class="space-y-2">
                        <flux:label for="timeLimit" class="text-sm">Time Limit (minutes)</flux:label>
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
                        <flux:text class="text-xs text-gray-500 dark:text-gray-400">
                            Leave blank for untimed practice (standard: 180 mins/3 hours)
                        </flux:text>
                    </div>

                    <!-- Shuffle Option -->
                    <div class="space-y-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <flux:checkbox
                            wire:model="shuffleQuestions"
                            label="Shuffle questions" />
                        <flux:text class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                            Answers and explanations will be shown automatically after selecting an option
                        </flux:text>
                    </div>
                </div>

                <!-- Summary Card -->
                <div class="p-4 rounded-lg bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border border-blue-200 dark:border-blue-800">
                    <flux:heading size="sm" level="3" class="mb-4">Test Summary</flux:heading>
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between items-center">
                            <flux:text class="text-gray-600 dark:text-gray-400">Questions/Subject:</flux:text>
                            <flux:text class="font-bold text-blue-600 dark:text-blue-400">{{ $questionsPerSubject ?? 'All' }}</flux:text>
                        </div>
                        <div class="flex justify-between items-center">
                            <flux:text class="text-gray-600 dark:text-gray-400">Total Questions:</flux:text>
                            <flux:text class="font-bold text-blue-600 dark:text-blue-400">{{ $questionsPerSubject ? $questionsPerSubject * 4 : 'All available' }}</flux:text>
                        </div>
                        <div class="flex justify-between items-center">
                            <flux:text class="text-gray-600 dark:text-gray-400">Time Limit:</flux:text>
                            <flux:text class="font-bold text-blue-600 dark:text-blue-400">{{ $timeLimit ? $timeLimit . ' mins' : 'Unlimited' }}</flux:text>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex flex-col sm:flex-row gap-3 sm:gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            <button
                wire:click="startJambTest"
                wire:loading.attr="disabled"
                wire:target="startJambTest"
                class="w-full sm:w-auto px-6 sm:px-8 py-3 bg-blue-600 dark:bg-blue-700 hover:bg-blue-700 dark:hover:bg-blue-600 text-white font-semibold rounded-lg transition-all flex items-center justify-center gap-2 {{ count($selectedSubjects) != $maxSubjects ? 'opacity-50 cursor-not-allowed' : '' }}"
                {{ count($selectedSubjects) != $maxSubjects ? 'disabled' : '' }}>
                <span wire:loading.remove wire:target="startJambTest">Start JAMB Practice Test</span>
                <span wire:loading wire:target="startJambTest" class="flex items-center gap-2">
                    <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Starting...
                </span>
            </button>
            <flux:button
                wire:navigate href="{{ route('practice.home') }}"
                variant="ghost"
                class="w-full sm:w-auto">
                Back to Practice
            </flux:button>
        </div>
    </div>
</flux:container>

