<div class="space-y-6">
    {{-- Page Header --}}
    <div>
        <flux:heading size="2xl">{{ __('Browse Lessons') }}</flux:heading>
        <flux:text class="mt-2">{{ __('Choose a subject to start learning') }}</flux:text>
    </div>

    {{-- Subjects Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($subjects as $subject)
            <a href="{{ route('lessons.list', $subject->id) }}" wire:navigate
                class="group relative rounded-xl border-2 border-neutral-200 dark:border-neutral-700 p-6 hover:border-blue-500 dark:hover:border-blue-500 hover:shadow-lg transition-all duration-200 overflow-hidden">

                {{-- Background Gradient --}}
                <div class="absolute inset-0 bg-gradient-to-br opacity-0 group-hover:opacity-10 transition-opacity"
                    style="background: linear-gradient(135deg, {{ $subject->color ?? '#3B82F6' }}, {{ $subject->color ?? '#3B82F6' }}88);">
                </div>

                <div class="relative">
                    {{-- Icon --}}
                    <div class="w-16 h-16 rounded-xl flex items-center justify-center mb-4 transition-transform group-hover:scale-110"
                        style="background-color: {{ $subject->color ?? '#3B82F6' }}20;">
                        @if($subject->icon)
                            <span class="text-3xl">{{ $subject->icon }}</span>
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8"
                                style="color: {{ $subject->color ?? '#3B82F6' }}" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                        @endif
                    </div>

                    {{-- Subject Info --}}
                    <div>
                        <flux:heading size="lg"
                            class="group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                            {{ $subject->name }}
                        </flux:heading>

                        @if($subject->description)
                            <flux:text class="mt-2 text-sm line-clamp-2">
                                {{ $subject->description }}
                            </flux:text>
                        @endif

                        {{-- Lesson Count --}}
                        <div class="flex items-center gap-2 mt-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-neutral-500" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            <flux:text class="text-sm text-neutral-600 dark:text-neutral-400">
                                {{ $subject->lessons_count }} {{ $subject->lessons_count === 1 ? 'lesson' : 'lessons' }}
                            </flux:text>
                        </div>
                    </div>

                    {{-- Arrow Icon --}}
                    <div class="absolute top-0 right-0 opacity-0 group-hover:opacity-100 transition-opacity">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                    </div>
                </div>
            </a>
        @empty
            <div class="col-span-full text-center py-12">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-neutral-400 mb-4" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
                <flux:heading size="lg" class="text-neutral-500 dark:text-neutral-400">
                    {{ __('No subjects available yet') }}
                </flux:heading>
                <flux:text class="mt-2 text-neutral-400">
                    {{ __('Check back later for new content') }}
                </flux:text>
            </div>
        @endforelse
    </div>
</div>