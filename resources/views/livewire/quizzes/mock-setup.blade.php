<div class="bg-white dark:bg-neutral-950 min-h-screen">
<flux:container class="pb-24 sm:pb-12">
    <div class="space-y-6 sm:space-y-8 py-6 sm:py-8">
        <div class="space-y-2">
            <flux:heading size="xl" level="1" class="leading-tight">Start a Mock Exam</flux:heading>
            <flux:text class="text-gray-600 dark:text-gray-400 text-sm sm:text-base">Choose an exam type, pick your subjects (up to 4), and we will generate a mock that mirrors the real test format.</flux:text>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-5 sm:gap-8">
            <div class="xl:col-span-2 space-y-6 sm:space-y-8">
                <div class="space-y-3 sm:space-y-4">
                    <flux:heading size="lg" level="2">Exam Type</flux:heading>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach($examTypes as $type)
                            <button
                                wire:click="$set('examTypeId', {{ $type->id }})"
                                class="w-full text-left p-4 rounded-xl border-2 transition-all shadow-sm {{ $examTypeId === $type->id ? 'border-blue-500 bg-blue-50 dark:bg-neutral-900 text-blue-700 dark:text-blue-200 ring-2 ring-blue-200/70 dark:ring-blue-800/60' : 'border-gray-200 dark:border-neutral-800 hover:border-blue-400 dark:hover:border-blue-500 text-gray-800 dark:text-gray-100' }}">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="space-y-1">
                                        <span class="font-semibold text-base sm:text-lg">{{ $type->name }}</span>
                                        @if($type->description)
                                            <flux:text class="text-sm text-gray-500 dark:text-gray-400">{{ $type->description }}</flux:text>
                                        @endif
                                    </div>
                                    @if($examTypeId === $type->id)
                                        <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 00-1.414-1.414L8 11.172 4.707 7.879a1 1 0 00-1.414 1.414l4 4a1 1 0 001.414 0l7-7z" clip-rule="evenodd"/></svg>
                                    @endif
                                </div>
                            </button>
                        @endforeach
                    </div>
                    @error('examTypeId')
                        <flux:badge color="red" class="mt-1">{{ $message }}</flux:badge>
                    @enderror
                </div>

                <div class="space-y-3 sm:space-y-4">
                    <flux:heading size="lg" level="2">Exam Year</flux:heading>
                    @if($years && $years->count())
                        <div class="flex flex-wrap gap-2 sm:gap-3">
                            @foreach($years as $year)
                                <button
                                    wire:click="$set('selectedYear', {{ $year }})"
                                    class="px-4 py-2 rounded-lg font-semibold transition-all text-sm sm:text-base {{ $selectedYear == $year ? 'bg-green-500 text-white shadow-md' : 'bg-gray-100 dark:bg-neutral-900 text-gray-700 dark:text-gray-100 hover:bg-gray-200 dark:hover:bg-neutral-800' }}">
                                    {{ $year }}
                                </button>
                            @endforeach
                        </div>
                    @else
                        <flux:text class="text-sm text-amber-600 dark:text-amber-300">No years available for this exam type yet.</flux:text>
                    @endif
                    @error('selectedYear')
                        <flux:badge color="red" class="mt-1">{{ $message }}</flux:badge>
                    @enderror
                </div>

                <div class="space-y-3 sm:space-y-4">
                    <div class="flex items-center justify-between gap-3 flex-wrap">
                        <flux:heading size="lg" level="2">Choose Subjects (max {{ $maxSubjects }})</flux:heading>
                        <flux:badge color="blue">{{ count($selectedSubjects) }}/{{ $maxSubjects }}</flux:badge>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach($subjects as $subject)
                            @php
                                $isSelected = in_array($subject->id, $selectedSubjects);
                                $canSelect = count($selectedSubjects) < $maxSubjects || $isSelected;
                            @endphp
                            <button
                                wire:click="toggleSubject({{ $subject->id }})"
                                class="w-full text-left p-4 rounded-xl border-2 transition-all shadow-sm {{ $isSelected ? 'border-green-500 bg-green-50 dark:bg-neutral-900 text-green-700 dark:text-green-200' : ($canSelect ? 'border-gray-200 dark:border-neutral-800 hover:border-green-400 dark:hover:border-green-500 text-gray-800 dark:text-gray-100' : 'border-gray-100 bg-gray-50 text-gray-400 cursor-not-allowed') }}"
                                {{ $canSelect ? '' : 'disabled' }}>
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold text-base sm:text-lg">{{ $subject->name }}</span>
                                    @if($isSelected)
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 00-1.414-1.414L8 11.172 4.707 7.879a1 1 0 00-1.414 1.414l4 4a1 1 0 001.414 0l7-7z" clip-rule="evenodd"/></svg>
                                    @endif
                                </div>
                            </button>
                        @endforeach
                    </div>
                    @error('selectedSubjects')
                        <flux:badge color="red" class="mt-1">{{ $message }}</flux:badge>
                    @enderror
                </div>
            </div>

            <div class="space-y-5 sm:space-y-6">
                <div class="p-5 rounded-2xl border border-gray-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-sm space-y-4">
                    <flux:heading size="md" level="3">Mock Settings</flux:heading>

                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <flux:label for="questionsPerSubject">Questions per Subject</flux:label>
                            <flux:text class="font-semibold text-blue-600 dark:text-blue-300">{{ $questionsPerSubject }}</flux:text>
                        </div>
                        <input type="range" id="questionsPerSubject" min="5" max="100" step="5" wire:model.live="questionsPerSubject" class="w-full h-2 rounded-lg bg-gray-200 dark:bg-neutral-800 accent-blue-500" />
                        <flux:text class="text-xs text-gray-500">5 - 100</flux:text>
                    </div>

                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <flux:label for="timeLimit">Time Limit (minutes)</flux:label>
                            <flux:text class="font-semibold text-blue-600 dark:text-blue-300">{{ $timeLimit }}</flux:text>
                        </div>
                        <input type="range" id="timeLimit" min="10" max="600" step="10" wire:model.live="timeLimit" class="w-full h-2 rounded-lg bg-gray-200 dark:bg-neutral-800 accent-blue-500" />
                        <flux:text class="text-xs text-gray-500">10 - 600 minutes</flux:text>
                    </div>

                    <div class="space-y-2 pt-3 border-t border-gray-200 dark:border-gray-700">
                        <flux:checkbox wire:model="shuffleQuestions" label="Shuffle questions" />
                        <flux:checkbox wire:model="showAnswersImmediately" label="Show answers immediately" />
                        <flux:checkbox wire:model="showExplanations" label="Show explanations" />
                    </div>
                </div>

                <div class="p-5 rounded-2xl border border-blue-200 dark:border-blue-900 bg-blue-50 dark:bg-neutral-900 shadow-sm space-y-3">
                    <flux:heading size="sm" class="mb-1">Summary</flux:heading>
                    <div class="flex items-center justify-between text-sm">
                        <span>Total Questions</span>
                        <span class="font-semibold text-blue-700 dark:text-blue-200">{{ count($selectedSubjects) * $questionsPerSubject }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span>Time Allowed</span>
                        <span class="font-semibold text-blue-700 dark:text-blue-200">{{ $timeLimit }} mins</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span>Per Question</span>
                        <span class="font-semibold text-blue-700 dark:text-blue-200">
                            {{ count($selectedSubjects) ? round(($timeLimit * 60) / max(count($selectedSubjects) * $questionsPerSubject, 1)) : 0 }}s
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="fixed left-0 right-0 bottom-0 z-20 bg-white/95 dark:bg-neutral-950/95 border-t border-gray-200 dark:border-neutral-800 px-4 py-3 shadow-2xl sm:static sm:bg-transparent sm:dark:bg-transparent sm:border-0 sm:shadow-none sm:px-0 sm:py-0">
            <div class="max-w-7xl mx-auto flex flex-col sm:flex-row gap-3 sm:gap-4 sm:items-center sm:justify-between">
                <flux:text class="text-sm text-gray-600 dark:text-gray-400 hidden sm:block">Ready? Start your mock when you are satisfied with the setup.</flux:text>
                <div class="flex gap-3">
                    <flux:button wire:navigate href="{{ route('dashboard') }}" variant="ghost" class="flex-1 sm:flex-none">Back</flux:button>
                    <button
                        wire:click="startMock"
                        wire:loading.attr="disabled"
                        class="flex-1 sm:flex-none px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-all flex items-center justify-center gap-2 {{ count($selectedSubjects) < 1 ? 'opacity-50 cursor-not-allowed' : '' }}"
                        {{ count($selectedSubjects) < 1 ? 'disabled' : '' }}>
                        <span wire:loading.remove>Start Mock Exam</span>
                        <span wire:loading class="flex items-center gap-2">
                            <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Preparing...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</flux:container>
</div>
