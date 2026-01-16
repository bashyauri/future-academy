<div class="bg-white dark:bg-neutral-950 min-h-screen">
<flux:container class="pb-24 sm:pb-12">
    <div class="space-y-6 sm:space-y-8 py-6 sm:py-8">
        @if(session('error'))
            <div class="rounded-xl border border-red-200 bg-red-50 text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-100 px-4 py-3">
                {{ session('error') }}
            </div>
        @endif
        <div class="space-y-2">
            <flux:heading size="xl" level="1" class="leading-tight">Start a Mock Exam</flux:heading>
            <flux:text class="text-gray-600 dark:text-gray-400 text-sm sm:text-base">
                Choose your exam type and select up to 4 subjects.
                @if($isJamb)
                    The exam will automatically follow JAMB specifications: English (70 questions), other subjects (50 questions each), 100 minutes total.
                @elseif($isSsce)
                    The exam will follow SSCE specifications: English (110 questions, 50 mins), Maths/Further Maths (60 questions, 50 mins), others (60 questions, 35 mins).
                @else
                    The exam will automatically configure based on the selected exam type.
                @endif
            </flux:text>
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
                    <div class="flex items-center justify-between gap-3 flex-wrap">
                        <flux:heading size="lg" level="2">Choose Subjects (max {{ $maxSubjects }})</flux:heading>
                        <flux:badge color="blue">{{ count($selectedSubjects) }}/{{ $maxSubjects }}</flux:badge>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach($subjects as $subject)
                            @php
                                $isSelected = in_array($subject->id, $selectedSubjects);
                                $canSelect = count($selectedSubjects) < $maxSubjects || $isSelected;
                                $isEnglish = stripos($subject->name, 'English') !== false;
                                $isMaths = stripos($subject->name, 'Math') !== false || stripos($subject->name, 'Further') !== false;

                                // Determine question count based on exam type
                                if ($isJamb) {
                                    $questionInfo = $isEnglish ? '70 questions' : '50 questions';
                                } elseif ($isSsce) {
                                    if ($isEnglish) {
                                        $questionInfo = '110 questions • 50 mins';
                                    } elseif ($isMaths) {
                                        $questionInfo = '60 questions • 50 mins';
                                    } else {
                                        $questionInfo = '60 questions • 35 mins';
                                    }
                                } else {
                                    $questionInfo = '50 questions';
                                }
                            @endphp
                            <button
                                wire:click="toggleSubject({{ $subject->id }})"
                                class="w-full text-left p-4 rounded-xl border-2 transition-all shadow-sm {{ $isSelected ? 'border-green-500 bg-green-50 dark:bg-neutral-900 text-green-700 dark:text-green-200' : ($canSelect ? 'border-gray-200 dark:border-neutral-800 hover:border-green-400 dark:hover:border-green-500 text-gray-800 dark:text-gray-100' : 'border-gray-100 bg-gray-50 text-gray-400 cursor-not-allowed') }}"
                                {{ $canSelect ? '' : 'disabled' }}>
                                <div class="flex items-center justify-between">
                                    <div>
                                        <span class="font-semibold text-base sm:text-lg">{{ $subject->name }}</span>
                                        <flux:text class="text-xs text-gray-500 dark:text-gray-400">{{ $questionInfo }}</flux:text>
                                    </div>
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
                <div class="p-5 rounded-2xl border border-purple-200 dark:border-purple-900 bg-purple-50 dark:bg-neutral-900 shadow-sm space-y-4">
                    <flux:heading size="md" level="3">
                        @if($isJamb)
                            JAMB Mock Specifications
                        @elseif($isSsce)
                            SSCE Mock Specifications
                        @else
                            Mock Exam Specifications
                        @endif
                    </flux:heading>

                    <div class="space-y-3 text-sm">
                        @if($isJamb)
                            <div class="flex items-start gap-2">
                                <svg class="w-5 h-5 text-purple-600 dark:text-purple-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                <div>
                                    <div class="font-semibold text-gray-900 dark:text-gray-100">English: 70 questions</div>
                                    <div class="text-gray-600 dark:text-gray-400">All other subjects: 50 questions</div>
                                </div>
                            </div>

                            <div class="flex items-start gap-2">
                                <svg class="w-5 h-5 text-purple-600 dark:text-purple-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                </svg>
                                <div>
                                    <div class="font-semibold text-gray-900 dark:text-gray-100">Duration: 100 minutes</div>
                                    <div class="text-gray-600 dark:text-gray-400">For all 4 subjects combined</div>
                                </div>
                            </div>
                        @elseif($isSsce)
                            <div class="flex items-start gap-2">
                                <svg class="w-5 h-5 text-purple-600 dark:text-purple-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                <div>
                                    <div class="font-semibold text-gray-900 dark:text-gray-100">English: 110 questions</div>
                                    <div class="text-gray-600 dark:text-gray-400">Duration: 50 minutes</div>
                                </div>
                            </div>

                            <div class="flex items-start gap-2">
                                <svg class="w-5 h-5 text-purple-600 dark:text-purple-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                <div>
                                    <div class="font-semibold text-gray-900 dark:text-gray-100">Maths & Further Maths: 60 questions</div>
                                    <div class="text-gray-600 dark:text-gray-400">Duration: 50 minutes each</div>
                                </div>
                            </div>

                            <div class="flex items-start gap-2">
                                <svg class="w-5 h-5 text-purple-600 dark:text-purple-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                <div>
                                    <div class="font-semibold text-gray-900 dark:text-gray-100">All other subjects: 60 questions</div>
                                    <div class="text-gray-600 dark:text-gray-400">Duration: 35 minutes each</div>
                                </div>
                            </div>
                        @endif

                        <div class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-purple-600 dark:text-purple-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <div>
                                <div class="font-semibold text-gray-900 dark:text-gray-100">Mixed Questions</div>
                                <div class="text-gray-600 dark:text-gray-400">
                                    @if($isSsce)
                                        From WAEC, NECO, and NAPTEB
                                    @else
                                        Curated from multiple sources
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-purple-600 dark:text-purple-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <div>
                                <div class="font-semibold text-gray-900 dark:text-gray-100">Real Exam Format</div>
                                <div class="text-gray-600 dark:text-gray-400">Results shown after submission</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-5 rounded-2xl border border-blue-200 dark:border-blue-900 bg-blue-50 dark:bg-neutral-900 shadow-sm space-y-3">
                    <flux:heading size="sm" class="mb-1">Your Exam Summary</flux:heading>
                    @if(count($selectedSubjects) > 0)
                        @php
                            $totalQuestions = 0;
                            $totalTime = 0;

                            foreach($selectedSubjects as $subjectId) {
                                $subject = $subjects->find($subjectId);
                                $subjectName = $subject?->name ?? '';
                                $isEnglish = stripos($subjectName, 'English') !== false;
                                $isMaths = stripos($subjectName, 'Math') !== false || stripos($subjectName, 'Further') !== false;

                                if ($isJamb) {
                                    $totalQuestions += $isEnglish ? 70 : 50;
                                } elseif ($isSsce) {
                                    if ($isEnglish) {
                                        $totalQuestions += 110;
                                        $totalTime += 50;
                                    } elseif ($isMaths) {
                                        $totalQuestions += 60;
                                        $totalTime += 50;
                                    } else {
                                        $totalQuestions += 60;
                                        $totalTime += 35;
                                    }
                                } else {
                                    $totalQuestions += 50;
                                }
                            }

                            if ($isJamb) {
                                $totalTime = 100;
                            } elseif (!$isSsce) {
                                $totalTime = 100;
                            }
                        @endphp
                        <div class="flex items-center justify-between text-sm">
                            <span>Subjects Selected</span>
                            <span class="font-semibold text-blue-700 dark:text-blue-200">{{ count($selectedSubjects) }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span>Total Questions</span>
                            <span class="font-semibold text-blue-700 dark:text-blue-200">{{ $totalQuestions }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span>Time Allowed</span>
                            <span class="font-semibold text-blue-700 dark:text-blue-200">{{ $totalTime }} mins</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span>Per Question</span>
                            <span class="font-semibold text-blue-700 dark:text-blue-200">{{ round(($totalTime * 60) / max($totalQuestions, 1)) }}s</span>
                        </div>
                    @else
                        <flux:text class="text-sm text-gray-500 dark:text-gray-400">Select subjects to see exam details</flux:text>
                    @endif
                </div>
            </div>
        </div>

        <div class="fixed left-0 right-0 bottom-0 z-20 bg-white/95 dark:bg-neutral-950/95 border-t border-gray-200 dark:border-neutral-800 px-4 py-3 shadow-2xl sm:static sm:bg-transparent sm:dark:bg-transparent sm:border-0 sm:shadow-none sm:px-0 sm:py-0">
            <div class="max-w-7xl mx-auto flex flex-col sm:flex-row gap-3 sm:gap-4 sm:items-center sm:justify-between">
                <flux:text class="text-sm text-gray-600 dark:text-gray-400 hidden sm:block">Ready? Start your mock when you are satisfied with the setup.</flux:text>
                <div class="flex gap-3">
                    <flux:button wire:navigate href="{{ route('dashboard') }}" variant="ghost" class="flex-1 sm:flex-none">Back</flux:button>
                    @if(count($selectedSubjects) == 1)
                        @php
                            $singleSubjectId = $selectedSubjects[0];
                        @endphp
                        <button
                            wire:click="selectSingleSubject({{ $singleSubjectId }})"
                            wire:loading.attr="disabled"
                            class="flex-1 sm:flex-none px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition-all flex items-center justify-center gap-2">
                            <span wire:loading.remove>📚 Browse Mock Groups</span>
                            <span wire:loading class="flex items-center gap-2">
                                <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Loading...
                            </span>
                        </button>
                    @endif
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
