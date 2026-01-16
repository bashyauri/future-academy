<div class="min-h-screen bg-white dark:bg-neutral-950 transition-colors duration-200">
    <flux:container class="pb-24 sm:pb-12">
        <div class="space-y-6 sm:space-y-8 py-6 sm:py-8">
            <!-- Header Section -->
            <div class="space-y-3 sm:space-y-4">
                <div class="flex items-center justify-between gap-3 flex-wrap mb-2">
                    <flux:heading size="xl" level="1" class="leading-tight">
                        Select Your Mock Exam
                    </flux:heading>
                </div>

                <!-- Subject and Exam Type Info -->
                <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3">
                    @if($examTypeName)
                        <flux:badge color="purple" size="sm" class="w-fit">
                            {{ $examTypeName }}
                        </flux:badge>
                    @endif
                    @if($subjectName)
                        <flux:badge color="blue" size="sm" class="w-fit">
                            {{ $subjectName }}
                        </flux:badge>
                    @endif
                </div>

                <flux:text class="text-gray-600 dark:text-gray-400 text-sm sm:text-base">
                    Choose a mock batch to test your knowledge. You can retake completed mocks to improve your score.
                </flux:text>
            </div>

            <!-- Mock Groups Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                @forelse($mockGroups as $group)
                    <div
                        class="group relative text-left transition-all duration-200 rounded-xl border-2 p-5 sm:p-6 {{ $group['isCompleted'] ? 'border-green-300 bg-green-50 dark:border-green-900/50 dark:bg-green-950/20 hover:border-green-400 dark:hover:border-green-800 hover:shadow-md dark:hover:shadow-green-900/20' : 'border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 hover:border-blue-400 dark:hover:border-blue-600 hover:shadow-lg dark:hover:shadow-blue-900/30' }}"
                    >
                        <!-- Completed Badge -->
                        @if($group['isCompleted'])
                            <div class="absolute top-3 right-3 sm:top-4 sm:right-4 flex items-center gap-1.5 bg-green-500 dark:bg-green-600 text-white px-2.5 sm:px-3 py-1 sm:py-1.5 rounded-full text-xs sm:text-sm font-semibold shadow-md">
                                <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 00-1.414-1.414L8 11.172 4.707 7.879a1 1 0 00-1.414 1.414l4 4a1 1 0 001.414 0l7-7z" clip-rule="evenodd"/>
                                </svg>
                                <span>Completed</span>
                            </div>
                        @endif

                        <!-- Mock Title and Question Count -->
                        <div class="flex items-start justify-between gap-3 mb-3 sm:mb-4">
                            <div class="flex-1">
                                <flux:heading size="lg" level="3" class="{{ $group['isCompleted'] ? 'text-green-700 dark:text-green-300' : 'text-blue-600 dark:text-blue-400' }} group-hover:{{ $group['isCompleted'] ? 'text-green-800 dark:text-green-200' : 'text-blue-700 dark:text-blue-300' }} transition-colors">
                                    {{ $group['label'] }}
                                </flux:heading>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <div class="text-2xl sm:text-3xl font-bold {{ $group['isCompleted'] ? 'text-green-700 dark:text-green-300' : 'text-gray-900 dark:text-gray-100' }}">
                                    {{ $group['total_questions'] }}
                                </div>
                                <flux:text class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                    Questions
                                </flux:text>
                            </div>
                        </div>

                        <!-- Description -->
                        <flux:text class="text-gray-600 dark:text-gray-400 text-sm mb-4">
                            Comprehensive mock exam with {{ $group['total_questions'] }} questions
                        </flux:text>

                        <!-- Best Score Display -->
                        @if($group['isCompleted'] && $group['bestScore'])
                            <div class="mb-4 p-2.5 sm:p-3 bg-green-100 dark:bg-green-900/30 rounded-lg border border-green-200 dark:border-green-900/50">
                                <div class="flex items-center justify-between">
                                    <flux:text class="text-xs sm:text-sm font-semibold text-green-800 dark:text-green-300">
                                        Best Score
                                    </flux:text>
                                    <flux:badge color="green" size="sm" class="text-sm sm:text-base font-bold">
                                        {{ number_format($group['bestScore'], 1) }}%
                                    </flux:badge>
                                </div>
                            </div>
                        @elseif($group['isCompleted'])
                            <div class="mb-4 p-2.5 sm:p-3 bg-gray-100 dark:bg-neutral-800 rounded-lg">
                                <flux:text class="text-xs sm:text-sm text-gray-600 dark:text-gray-400">
                                    Completed • Ready to retake
                                </flux:text>
                            </div>
                        @else
                            <div class="mb-4 p-2.5 sm:p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-900/50">
                                <flux:text class="text-xs sm:text-sm text-blue-700 dark:text-blue-300">
                                    ⏱️ Approx. 60 minutes
                                </flux:text>
                            </div>
                        @endif

                        <!-- Action Button -->
                        <div class="pt-2">
                            <flux:button
                                wire:click="selectBatch({{ $group['batch_number'] }})"
                                type="button"
                                variant="{{ $group['isCompleted'] ? 'primary' : 'primary' }}"
                                class="w-full {{ $group['isCompleted'] ? 'bg-green-600 dark:bg-green-700 hover:bg-green-700 dark:hover:bg-green-600' : 'bg-blue-600 dark:bg-blue-700 hover:bg-blue-700 dark:hover:bg-blue-600' }} text-white font-semibold"
                            >
                                @if($group['isCompleted'])
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                    Retake Mock {{ $group['batch_number'] }}
                                @else
                                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/>
                                    </svg>
                                    Start Mock {{ $group['batch_number'] }}
                                @endif
                            </flux:button>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full">
                        <div class="border border-yellow-200 dark:border-yellow-900/50 bg-yellow-50 dark:bg-yellow-950/30 rounded-xl p-5 sm:p-6">
                            <div class="flex gap-3 sm:gap-4">
                                <svg class="w-5 h-5 sm:w-6 sm:h-6 text-yellow-600 dark:text-yellow-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                <div>
                                    <flux:heading size="sm" level="3" class="text-yellow-800 dark:text-yellow-300 mb-1">
                                        No Mock Groups Available
                                    </flux:heading>
                                    <flux:text class="text-yellow-700 dark:text-yellow-400 text-sm">
                                        Mock questions are not yet available for the selected subject and exam type.
                                    </flux:text>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforelse
            </div>

            <!-- Summary Section -->
            @if(count($mockGroups) > 0)
                <div class="pt-4 sm:pt-6 border-t border-gray-200 dark:border-neutral-800">
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                        <div class="p-4 sm:p-5 rounded-lg border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900">
                            <div class="text-center">
                                <div class="text-2xl sm:text-3xl font-bold text-blue-600 dark:text-blue-400">
                                    {{ count($mockGroups) }}
                                </div>
                                <flux:text class="text-xs sm:text-sm text-neutral-600 dark:text-neutral-400 mt-1">
                                    Total Mocks
                                </flux:text>
                            </div>
                        </div>

                        <div class="p-4 sm:p-5 rounded-lg border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900">
                            <div class="text-center">
                                <div class="text-2xl sm:text-3xl font-bold text-green-600 dark:text-green-400">
                                    {{ count($completedMockGroupIds) }}
                                </div>
                                <flux:text class="text-xs sm:text-sm text-neutral-600 dark:text-neutral-400 mt-1">
                                    Completed
                                </flux:text>
                            </div>
                        </div>

                        <div class="p-4 sm:p-5 rounded-lg border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900">
                            <div class="text-center">
                                <div class="text-2xl sm:text-3xl font-bold text-amber-600 dark:text-amber-400">
                                    {{ count($mockGroups) - count($completedMockGroupIds) }}
                                </div>
                                <flux:text class="text-xs sm:text-sm text-neutral-600 dark:text-neutral-400 mt-1">
                                    Remaining
                                </flux:text>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Footer -->
        <div class="fixed left-0 right-0 bottom-0 z-20 bg-white/95 dark:bg-neutral-950/95 border-t border-gray-200 dark:border-neutral-800 px-4 py-3 shadow-2xl sm:static sm:bg-transparent sm:dark:bg-transparent sm:border-0 sm:shadow-none sm:px-0 sm:py-0 sm:mt-8">
            <div class="max-w-7xl mx-auto">
                <flux:button
                    wire:navigate
                    href="{{ route('mock.setup') }}"
                    variant="ghost"
                    class="w-full sm:w-auto text-neutral-700 dark:text-neutral-300 hover:text-neutral-900 dark:hover:text-neutral-100"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Back to Setup
                </flux:button>
            </div>
        </div>
    </flux:container>
</div>
