<div class="space-y-6">
    {{-- Breadcrumb & Header --}}
    <div>
        <div class="flex items-center gap-2 text-sm mb-2">
            <a href="{{ route('lessons.subjects') }}" wire:navigate
                class="text-blue-600 dark:text-blue-400 hover:underline">
                {{ __('Lessons') }}
            </a>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-neutral-400" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            <flux:text>{{ $subject->name }}</flux:text>
        </div>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <flux:heading size="2xl">{{ $subject->name }}</flux:heading>
                @if($subject->description)
                    <flux:text class="mt-1">{{ $subject->description }}</flux:text>
                @endif
            </div>
        </div>
    </div>

    <div class="grid lg:grid-cols-4 gap-6">
        {{-- Topics Sidebar --}}
        @if($topics->isNotEmpty())
            <div class="lg:col-span-1">
                <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-4 sticky top-4">
                    <flux:heading size="lg" class="mb-4">{{ __('Topics') }}</flux:heading>
                    <div class="space-y-2">
                        <button wire:click="$set('topicId', null)"
                            class="w-full text-left px-3 py-2 rounded-lg transition {{ !$topicId ? 'bg-blue-50 dark:bg-blue-950 text-blue-600 dark:text-blue-400 font-medium' : 'hover:bg-neutral-100 dark:hover:bg-neutral-800' }}">
                            <div class="flex items-center justify-between">
                                <flux:text>{{ __('All Topics') }}</flux:text>
                                <flux:badge size="sm" color="neutral">{{ $lessons->count() }}</flux:badge>
                            </div>
                        </button>
                        @foreach($topics as $topic)
                            <button wire:click="$set('topicId', {{ $topic->id }})"
                                class="w-full text-left px-3 py-2 rounded-lg transition {{ $topicId == $topic->id ? 'bg-blue-50 dark:bg-blue-950 text-blue-600 dark:text-blue-400 font-medium' : 'hover:bg-neutral-100 dark:hover:bg-neutral-800' }}">
                                <div class="flex items-center justify-between">
                                    <flux:text class="flex-1">{{ $topic->name }}</flux:text>
                                    <flux:badge size="sm" color="neutral">{{ $topic->lessons_count }}</flux:badge>
                                </div>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        {{-- Lessons List --}}
        <div class="{{ $topics->isNotEmpty() ? 'lg:col-span-3' : 'lg:col-span-4' }}">
            @if($lessons->isEmpty())
                <div class="text-center py-12 rounded-xl border border-neutral-200 dark:border-neutral-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-neutral-400 mb-4" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    <flux:heading size="lg" class="text-neutral-500 dark:text-neutral-400">
                        {{ __('No lessons available') }}
                    </flux:heading>
                    <flux:text class="mt-2 text-neutral-400">
                        {{ __('Lessons will appear here once they are published') }}
                    </flux:text>
                </div>
            @else
                <div class="space-y-4">
                    @foreach($lessons as $lesson)
                        @php
                            $userProgress = $lesson->userProgress(auth()->user());
                            $isCompleted = $userProgress?->is_completed ?? false;
                            $progressPercentage = $userProgress?->progress_percentage ?? 0;
                        @endphp

                        <a href="{{ route('lessons.view', $lesson->id) }}" wire:navigate
                            class="group block rounded-xl border border-neutral-200 dark:border-neutral-700 hover:border-blue-500 dark:hover:border-blue-500 hover:shadow-md transition-all duration-200 overflow-hidden">
                            <div class="flex gap-4 p-4">
                                {{-- Thumbnail --}}
                                <div class="flex-shrink-0 relative group/thumb">
                                    <div
                                        class="w-32 h-24 rounded-lg bg-gradient-to-br from-blue-100 to-purple-100 dark:from-blue-900 dark:to-purple-900 flex items-center justify-center overflow-hidden">
                                        @if($lesson->video_type === 'bunny' && $lesson->video_url)
                                            {{-- Show Bunny video thumbnail with preview animation on hover --}}
                                            <img src="{{ $lesson->getBunnyThumbnailUrl() }}"
                                                alt="{{ $lesson->title }}"
                                                class="w-full h-full object-cover group-hover/thumb:opacity-0 transition-opacity duration-200">

                                            {{-- Preview animation on hover --}}
                                            <img src="{{ $lesson->getBunnyPreviewAnimationUrl() }}"
                                                alt="{{ $lesson->title }}"
                                                class="w-full h-full object-cover absolute inset-0 opacity-0 group-hover/thumb:opacity-100 transition-opacity duration-200">
                                        @elseif($lesson->thumbnail)
                                            <img src="{{ Storage::url($lesson->thumbnail) }}" alt="{{ $lesson->title }}"
                                                class="w-full h-full object-cover">
                                        @else
                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                class="h-12 w-12 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        @endif
                                    </div>
                                    @if($isCompleted)
                                        <div
                                            class="absolute -top-2 -right-2 w-8 h-8 rounded-full bg-green-600 flex items-center justify-center text-white">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 13l4 4L19 7" />
                                            </svg>
                                        </div>
                                    @endif
                                </div>

                                {{-- Lesson Info --}}
                                <div class="flex-1 min-w-0">
                                    <flux:heading size="lg"
                                        class="group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors truncate">
                                        {{ $lesson->title }}
                                    </flux:heading>

                                    @if($lesson->description)
                                        <flux:text class="mt-1 text-sm line-clamp-2">
                                            {{ $lesson->description }}
                                        </flux:text>
                                    @endif

                                    <div class="flex flex-wrap items-center gap-4 mt-3">
                                        @if($lesson->topic)
                                            <flux:badge color="blue" size="sm">{{ $lesson->topic->name }}</flux:badge>
                                        @endif

                                        @if($lesson->duration_minutes)
                                            <div class="flex items-center gap-1 text-sm text-neutral-500">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <span>{{ $lesson->duration_minutes }} min</span>
                                            </div>
                                        @endif

                                        @if($lesson->is_free)
                                            <flux:badge color="green" size="sm">{{ __('Free') }}</flux:badge>
                                        @endif

                                        @if($progressPercentage > 0 && !$isCompleted)
                                            <div class="flex items-center gap-2 flex-1 min-w-[120px] max-w-[200px]">
                                                <div class="flex-1 bg-neutral-200 dark:bg-neutral-700 rounded-full h-1.5">
                                                    <div class="bg-blue-600 dark:bg-blue-500 h-1.5 rounded-full transition-all"
                                                        style="width: {{ $progressPercentage }}%"></div>
                                                </div>
                                                <flux:text class="text-xs text-neutral-500">{{ $progressPercentage }}%</flux:text>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                {{-- Arrow --}}
                                <div
                                    class="flex-shrink-0 flex items-center opacity-0 group-hover:opacity-100 transition-opacity">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600 dark:text-blue-400"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
