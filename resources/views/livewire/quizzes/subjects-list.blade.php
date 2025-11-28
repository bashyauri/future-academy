<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="2xl">{{ __('Browse Quizzes by Subject') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Select a subject to view available quizzes and topics') }}</flux:text>
        </div>
        <flux:button href="{{ route('quizzes.all') }}" variant="ghost" icon="list-bullet" wire:navigate>
            {{ __('Browse All') }}
        </flux:button>
    </div>

    {{-- Subjects Grid --}}
    @if($subjects->isEmpty())
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-12 text-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-neutral-400 mb-3" fill="none"
                viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <flux:text class="text-neutral-500">{{ __('No quizzes available at the moment') }}</flux:text>
        </div>
    @else
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach($subjects as $subject)
                <a href="{{ route('quizzes.subject', $subject->id) }}" wire:navigate
                    class="group block rounded-xl border border-neutral-200 dark:border-neutral-700 p-6 hover:border-neutral-300 dark:hover:border-neutral-600 hover:shadow-lg transition-all">
                    <div class="flex items-start gap-4">
                        <div
                            class="flex-shrink-0 w-12 h-12 rounded-lg bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold text-xl">
                            {{ substr($subject->name, 0, 1) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <flux:heading size="lg"
                                class="mb-1 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition">
                                {{ $subject->name }}
                            </flux:heading>
                            @if($subject->description)
                                <flux:text class="text-sm text-neutral-500 line-clamp-2 mb-3">
                                    {{ $subject->description }}
                                </flux:text>
                            @endif
                            <div class="flex items-center gap-4 text-sm">
                                <div class="flex items-center gap-1.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-500" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <flux:text class="font-medium">{{ $subject->quizzes_count }} {{ __('Quizzes') }}</flux:text>
                                </div>
                                @if($subject->topics_with_quizzes > 0)
                                    <div class="flex items-center gap-1.5">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-purple-500" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                        </svg>
                                        <flux:text class="font-medium">{{ $subject->topics_with_quizzes }} {{ __('Topics') }}
                                        </flux:text>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>