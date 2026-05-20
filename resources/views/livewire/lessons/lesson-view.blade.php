<div class="space-y-6">
    {{-- Breadcrumb - Hide on mobile, show on md+ --}}
    <div class="hidden md:flex items-center gap-2 text-xs md:text-sm overflow-x-auto pb-2">
        <a href="{{ route('lessons.subjects', $isParentViewing ? ['student' => $viewingStudent->id] : []) }}" wire:navigate
            class="text-blue-600 dark:text-blue-400 hover:underline whitespace-nowrap">
            {{ __('Lessons') }}
        </a>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 md:h-4 md:w-4 text-neutral-400 flex-shrink-0" fill="none" viewBox="0 0 24 24"
            stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
        <a href="{{ route('lessons.list', ['subject' => $lesson->subject_id] + ($isParentViewing ? ['student' => $viewingStudent->id] : [])) }}" wire:navigate
            class="text-blue-600 dark:text-blue-400 hover:underline whitespace-nowrap">
            {{ $lesson->subject->name }}
        </a>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 md:h-4 md:w-4 text-neutral-400 flex-shrink-0" fill="none" viewBox="0 0 24 24"
            stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
        <flux:text class="truncate">{{ $lesson->title }}</flux:text>
    </div>

    <div class="grid lg:grid-cols-3 gap-4 md:gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-4 md:space-y-6">
            {{-- Video Player --}}
            {{-- Video Player --}}
            @if($lesson->video_url)
                <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 overflow-hidden bg-black">
                    <div class="aspect-video relative" id="video-container">
                        @if($lesson->video_type === 'local')
                            {{-- Local video - loads on click --}}
                            @php
                                $hlsUrl = app(\App\Services\VideoSigningService::class)->getHlsStreamingUrl($lesson->video_url);
                                $fallbackUrl = app(\App\Services\VideoSigningService::class)->getOptimizedUrl($lesson->video_url);
                            @endphp
                            <div id="local-video-wrapper" class="w-full h-full">
                                {{-- Video element will be inserted here on play --}}
                            </div>
                            <script>
                                window.localVideoData = {
                                    hlsUrl: @json($hlsUrl),
                                    fallbackUrl: @json($fallbackUrl)
                                };
                            </script>

                        @elseif($lesson->video_type === 'bunny')
                            @php
                                // Extract the video GUID from the storage path
                                $videoPath = $lesson->video_url;
                                $videoGuid = basename($videoPath, '.mp4');
                                // Generate signed embed URL with 24 hour expiration
                                $bunnyService = app(\App\Services\BunnyStreamService::class);
                                $expires = now()->addHours(24)->getTimestamp();
                                $signedEmbedUrl = $bunnyService->getEmbedUrl($videoGuid, $expires);
                            @endphp
                            {{-- Bunny Stream Player (Alpine + fetch tracking, loads on click) --}}
                            <div class="w-full h-full" wire:ignore
                                x-data="bunnyTracker({{ $lesson->id }}, {{ (int) (($lesson->duration_minutes ?? 5) * 60) }})"
                                x-init="init()"
                                data-embed-url="{{ $signedEmbedUrl }}">
                                <iframe
                                    id="bunny-player"
                                    x-ref="bunnyIframe"
                                    src=""
                                    class="w-full h-full"
                                    frameborder="0"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                    allowfullscreen>
                                </iframe>
                            </div>

                        @else
                            {{-- YouTube/Vimeo embedded iframe (loads on click) --}}
                            <iframe id="embed-player" src="" class="w-full h-full" frameborder="0"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                allowfullscreen>
                            </iframe>
                            @php
                                $embedUrl = $lesson->getVideoEmbedUrl();
                            @endphp
                            <script>
                                window.embedVideoUrl = @json($embedUrl);
                            </script>
                        @endif

                        {{-- Loading Overlay --}}
                        <div id="loading-overlay"
                             class="absolute inset-0 flex items-center justify-center bg-black/70 z-20 hidden">
                            <div class="flex flex-col items-center gap-3">
                                <svg class="animate-spin h-10 w-10 md:h-12 md:w-12 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span class="text-white text-sm font-medium">{{ __('Loading video...') }}</span>
                            </div>
                        </div>

                        {{-- Big Play Button Overlay --}}
                        <div id="play-overlay"
                             class="absolute inset-0 flex items-center justify-center bg-black/50 hover:bg-black/40 transition-all duration-300 cursor-pointer z-10">
                            <button onclick="startVideoPlayback()"
                                    class="w-20 h-20 md:w-28 md:h-28 rounded-full bg-white/95 hover:bg-white flex items-center justify-center shadow-2xl transition-all hover:scale-110 active:scale-95">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                     class="w-10 h-10 md:w-14 md:h-14 text-blue-600 ml-1"
                                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132z" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Lesson Header --}}
            <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-4 md:p-6">
                <div class="flex flex-col gap-4 mb-4">
                    <div class="flex-1">
                        <flux:heading size="2xl" class="text-xl md:text-2xl">{{ $lesson->title }}</flux:heading>
                        <div class="flex flex-wrap items-center gap-3 mt-2">
                            @if($lesson->topic)
                                <flux:badge color="blue">{{ $lesson->topic->name }}</flux:badge>
                            @endif
                            @if($lesson->duration_minutes)
                                <div class="flex items-center gap-1 text-xs md:text-sm text-neutral-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>{{ $lesson->duration_minutes }} minutes</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    @if($isParentViewing)
                        <flux:badge color="zinc" size="sm" class="w-fit">
                            {{ __('Viewing as guardian: :name', ['name' => $viewingStudent->name]) }}
                        </flux:badge>
                    @endif

                    @if(!$progress->is_completed)
                        <div class="flex flex-col gap-2 w-full md:w-auto md:items-end">
                            @if($lessonQuiz && !$lessonQuizCompleted)
                                <flux:badge color="amber" size="sm" class="text-xs">
                                    <span class="hidden sm:inline">{{ __('Complete the lesson quiz to unlock completion') }}</span>
                                    <span class="sm:hidden">{{ __('Complete quiz to unlock') }}</span>
                                </flux:badge>
                            @endif

                            @if($isParentViewing)
                                <flux:button variant="outline" class="w-full md:w-auto text-sm" disabled>
                                    {{ __('Student can complete this lesson from their account') }}
                                </flux:button>
                            @else
                                <flux:button wire:click="markComplete" wire:loading.attr="disabled" wire:target="markComplete"
                                    variant="primary" icon="check" class="w-full md:w-auto text-sm"
                                    :disabled="$lessonQuiz && !$lessonQuizCompleted">
                                    <span wire:loading.remove wire:target="markComplete">{{ __('Mark Complete') }}</span>
                                    <span wire:loading wire:target="markComplete">{{ __('Saving...') }}</span>
                                </flux:button>
                            @endif
                        </div>
                    @else
                        <flux:badge color="green" size="lg" class="flex items-center gap-2 w-fit">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            {{ __('Completed') }}
                        </flux:badge>
                    @endif
                </div>

                {{-- Progress Bar --}}
                @if(!$progress->is_completed && $progress->progress_percentage > 0)
                    <div class="mb-4">
                        <div class="flex items-center justify-between text-sm mb-2">
                            <flux:text>{{ __('Your Progress') }}</flux:text>
                            <flux:text class="font-semibold text-blue-600 dark:text-blue-400">
                                {{ $progress->progress_percentage }}%
                            </flux:text>
                        </div>
                        <div class="w-full bg-neutral-200 dark:bg-neutral-700 rounded-full h-2">
                            <div class="bg-blue-600 dark:bg-blue-500 h-2 rounded-full transition-all"
                                style="width: {{ $progress->progress_percentage }}%"></div>
                        </div>
                    </div>
                @endif

                @if($lesson->description)
                    <flux:text class="leading-relaxed">{{ $lesson->description }}</flux:text>
                @endif
            </div>

            {{-- Practice Questions - Positioned before notes for immediate reinforcement --}}
            @if(!$isParentViewing && $lesson->questions->isNotEmpty())
                <livewire:lessons.practice-questions :questions="$lesson->questions" :lesson-id="$lesson->id"
                    :key="'practice-' . $lesson->id" />
            @endif

            {{-- Lesson Content/Notes - Detailed reference material --}}
            @if($lesson->content)
                <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-4 md:p-6">
                    <flux:heading size="lg" class="mb-4 text-lg">{{ __('Lesson Notes') }}</flux:heading>
                    <flux:text class="text-xs md:text-sm text-neutral-600 dark:text-neutral-400 mb-4">
                        {{ __('Detailed notes and additional reading material for this lesson.') }}
                    </flux:text>
                    <div class="prose dark:prose-invert max-w-none prose-sm md:prose-base overflow-x-auto">
                        {!! $lesson->content !!}
                    </div>
                </div>
            @endif

            {{-- Navigation Buttons --}}
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between pt-4">
                @if($previousLesson)
                    <flux:button href="{{ route('lessons.view', ['id' => $previousLesson->id] + ($isParentViewing ? ['student' => $viewingStudent->id] : [])) }}" wire:navigate variant="ghost"
                        icon="arrow-left" class="w-full md:w-auto text-sm">
                        <span class="hidden sm:inline">{{ __('Previous Lesson') }}</span>
                        <span class="sm:hidden">{{ __('Previous') }}</span>
                    </flux:button>
                @else
                    <div></div>
                @endif

                @if($nextLesson)
                    <flux:button href="{{ route('lessons.view', ['id' => $nextLesson->id] + ($isParentViewing ? ['student' => $viewingStudent->id] : [])) }}" wire:navigate variant="primary"
                        icon-trailing="arrow-right" class="w-full md:w-auto text-sm"
                        :disabled="!$isParentViewing && $lessonQuiz && !$lessonQuizCompleted">
                        <span class="hidden sm:inline">{{ (!$isParentViewing && $lessonQuiz && !$lessonQuizCompleted) ? __('Complete Quiz to Unlock') : __('Next Lesson') }}</span>
                        <span class="sm:hidden">{{ (!$isParentViewing && $lessonQuiz && !$lessonQuizCompleted) ? __('Unlock') : __('Next') }}</span>
                    </flux:button>
                @else
                    <flux:button href="{{ route('lessons.list', ['subject' => $lesson->subject_id] + ($isParentViewing ? ['student' => $viewingStudent->id] : [])) }}" wire:navigate variant="primary" class="w-full md:w-auto text-sm">
                        {{ __('Back to Lessons') }}
                    </flux:button>
                @endif
            </div>
        </div>

        {{-- Sidebar - Responsive on mobile --}}
        <div class="lg:col-span-1 space-y-4 md:space-y-6">
            {{-- Quick Stats --}}
            <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-4 md:p-6 space-y-4">
                <flux:heading size="lg" class="text-lg">{{ __('Your Progress') }}</flux:heading>

                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 rounded-lg bg-blue-50 dark:bg-blue-950/30 gap-2">
                        <div class="flex items-center gap-2 md:gap-3 flex-1 min-w-0">
                            <div
                                class="w-8 h-8 md:w-10 md:h-10 rounded-full bg-blue-600 dark:bg-blue-500 flex items-center justify-center text-white flex-shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 md:h-5 md:w-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <flux:text class="text-xs text-blue-600 dark:text-blue-400">{{ __('Progress') }}</flux:text>
                                <flux:text class="font-semibold text-sm md:text-base">{{ $progress->progress_percentage }}%</flux:text>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between p-3 rounded-lg bg-purple-50 dark:bg-purple-950/30 gap-2">
                        <div class="flex items-center gap-2 md:gap-3 flex-1 min-w-0">
                            <div
                                class="w-8 h-8 md:w-10 md:h-10 rounded-full bg-purple-600 dark:bg-purple-500 flex items-center justify-center text-white flex-shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 md:h-5 md:w-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <flux:text class="text-xs text-purple-600 dark:text-purple-400">{{ __('Time Spent') }}</flux:text>
                                <flux:text class="font-semibold text-sm md:text-base">
                                    {{ floor($progress->time_spent_seconds / 60) }} min
                                </flux:text>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Lesson Quiz --}}
            <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-4 md:p-6">
                <flux:heading size="lg" class="mb-4 text-lg">{{ __('Lesson Quiz') }}</flux:heading>

                @php
                    // Defensive: check for multiple quizzes linked to this lesson
                    $lessonQuizzes = $lesson->quizzes ?? null;
                    $hasMultipleQuizzes = $lessonQuizzes && $lessonQuizzes->count() > 1;
                @endphp

                @if($hasMultipleQuizzes)
                    <div class="mb-4 p-3 rounded bg-red-100 text-red-800 border border-red-300 text-xs md:text-sm">
                        <strong>{{ __('Data Error:') }}</strong> {{ __('Multiple quizzes are linked to this lesson. Please contact the administrator to resolve this data integrity issue. Only the first quiz will be shown below.') }}
                    </div>
                @endif

                @php
                    // Use only the first quiz if multiple exist
                    $displayQuiz = $hasMultipleQuizzes ? $lessonQuizzes->first() : ($lessonQuiz ?? null);
                @endphp

                @if($displayQuiz)
                    @php
                        $typeEnum = $displayQuiz->type instanceof \App\Enums\QuizType
                            ? $displayQuiz->type
                            : \App\Enums\QuizType::tryFrom((string) $displayQuiz->type);

                        $typeColor = match ($typeEnum?->value) {
                            'practice' => 'blue',
                            'timed' => 'orange',
                            'mock' => 'purple',
                            default => 'zinc',
                        };

                        $typeLabel = $typeEnum?->label()
                            ?? (is_scalar($displayQuiz->type) ? ucfirst((string) $displayQuiz->type) : '-');
                    @endphp

                    <div class="space-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2">
                            <div class="min-w-0">
                                <flux:text class="text-xs text-neutral-500">{{ __('Linked to this lesson') }}</flux:text>
                                <flux:heading size="md" class="text-sm md:text-base truncate">{{ $displayQuiz->title }}</flux:heading>
                            </div>
                            <flux:badge :color="$typeColor" class="text-xs w-fit">{{ $typeLabel }}</flux:badge>
                        </div>

                        <div class="grid grid-cols-2 gap-2 md:gap-3 text-xs md:text-sm">
                            <div class="p-2 rounded bg-neutral-50 dark:bg-neutral-800">
                                <flux:text class="text-xs text-neutral-500">{{ __('Questions') }}</flux:text>
                                <flux:text class="font-semibold text-sm">{{ $displayQuiz->question_count }}</flux:text>
                            </div>

                            <div class="p-2 rounded bg-neutral-50 dark:bg-neutral-800">
                                <flux:text class="text-xs text-neutral-500">{{ __('Attempts') }}</flux:text>
                                <flux:text class="font-semibold text-sm">
                                    {{ $displayQuiz->user_stats['total_attempts'] ?? 0 }}
                                    @if($displayQuiz->max_attempts)
                                        / {{ $displayQuiz->max_attempts }}
                                    @endif
                                </flux:text>
                            </div>

                            @if(($displayQuiz->user_stats['best_score'] ?? null) !== null)
                                <div class="p-2 rounded bg-neutral-50 dark:bg-neutral-800">
                                    <flux:text class="text-xs text-neutral-500">{{ __('Best Score') }}</flux:text>
                                    <flux:text class="font-semibold text-sm">
                                        {{ round($displayQuiz->user_stats['best_score'], 1) }}%
                                    </flux:text>
                                </div>
                            @endif

                            @if($displayQuiz->isTimed())
                                <div class="p-2 rounded bg-neutral-50 dark:bg-neutral-800">
                                    <flux:text class="text-xs text-neutral-500">{{ __('Duration') }}</flux:text>
                                    <flux:text class="font-semibold text-sm">{{ $displayQuiz->duration_minutes }} min</flux:text>
                                </div>
                            @endif
                        </div>

                        @if($displayQuiz->can_attempt ?? false)
                            <flux:button href="{{ route('quiz.take', $displayQuiz) }}" variant="primary" class="w-full text-sm"
                                icon="play">
                                {{ ($displayQuiz->user_stats['total_attempts'] ?? 0) > 0 ? __('Retake Quiz') : __('Start Quiz') }}
                            </flux:button>
                        @else
                            <flux:button variant="ghost" disabled class="w-full text-sm">
                                {{ __('Max Attempts Reached') }}
                            </flux:button>
                        @endif
                    </div>
                @else
                    <flux:text class="text-xs md:text-sm mb-4">
                        {{ __('No quiz is linked to this lesson yet. You can still practice from the quiz library.') }}
                    </flux:text>
                    <flux:button href="{{ route('quizzes.index') }}" wire:navigate variant="primary" class="w-full text-sm"
                        icon="clipboard-document-list">
                        {{ __('Browse Quizzes') }}
                    </flux:button>
                @endif
            </div>

            {{-- Help Card --}}
            <div
                class="rounded-xl border-2 border-amber-200 dark:border-amber-800 p-4 md:p-6 bg-gradient-to-br from-amber-50 to-orange-50 dark:from-amber-950/30 dark:to-orange-950/30">
                <div class="flex items-start gap-3 mb-3">
                    <div
                        class="w-8 h-8 md:w-10 md:h-10 rounded-full bg-amber-600 dark:bg-amber-500 flex items-center justify-center flex-shrink-0 text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 md:h-5 md:w-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <flux:heading size="sm" class="text-amber-900 dark:text-amber-100 mb-2 text-sm">
                            {{ __('Need Help?') }}
                        </flux:heading>
                        <flux:text class="text-xs md:text-sm text-amber-800 dark:text-amber-200">
                            {{ __('If you have questions about this lesson, feel free to reach out to your instructor.') }}
                        </flux:text>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    function startVideoPlayback() {
        const playOverlay = document.getElementById('play-overlay');
        const loadingOverlay = document.getElementById('loading-overlay');

        if (!playOverlay) return;

        // Show loading overlay
        if (loadingOverlay) {
            loadingOverlay.classList.remove('hidden');
        }

        // Hide the play overlay
        playOverlay.style.opacity = '0';
        setTimeout(() => {
            playOverlay.style.display = 'none';
        }, 300);

        // Determine video type and load accordingly
        if (window.localVideoData) {
            loadLocalVideo(window.localVideoData, loadingOverlay);
        } else if (window.bunnyPlayCallback) {
            // Bunny Stream with Alpine.js - call the registered callback
            window.bunnyPlayCallback();
            // Hide loader after a delay (iframe will load via Alpine)
            setTimeout(() => {
                if (loadingOverlay) {
                    loadingOverlay.style.opacity = '0';
                    setTimeout(() => loadingOverlay.classList.add('hidden'), 300);
                }
            }, 1500);
        } else if (window.embedVideoUrl) {
            loadEmbedVideo(window.embedVideoUrl, loadingOverlay);
        }
    }

    function loadLocalVideo(data, loadingOverlay) {
        const wrapper = document.getElementById('local-video-wrapper');
        if (!wrapper) return;

        // Create video element
        const video = document.createElement('video');
        video.id = 'lesson-video';
        video.className = 'w-full h-full';
        video.controls = true;
        video.playsinline = true;
        video.autoplay = true;

        // Hide loader when ready
        video.addEventListener('canplay', () => {
            if (loadingOverlay) {
                loadingOverlay.style.opacity = '0';
                setTimeout(() => loadingOverlay.classList.add('hidden'), 300);
            }
        }, { once: true });

        video.addEventListener('playing', () => {
            if (loadingOverlay) {
                loadingOverlay.style.opacity = '0';
                setTimeout(() => loadingOverlay.classList.add('hidden'), 300);
            }
        }, { once: true });

        // Handle errors
        video.addEventListener('error', () => {
            console.warn('Video error, trying fallback');
            if (loadingOverlay) {
                loadingOverlay.style.opacity = '0';
                setTimeout(() => loadingOverlay.classList.add('hidden'), 300);
            }
        });

        // Clear wrapper and add video
        wrapper.innerHTML = '';
        wrapper.appendChild(video);

        // Initialize HLS if supported
        if (typeof Hls !== 'undefined' && Hls.isSupported() && data.hlsUrl) {
            const hls = new Hls({
                debug: false,
                enableWorker: true,
                lowLatencyMode: false,
            });

            hls.loadSource(data.hlsUrl);
            hls.attachMedia(video);

            hls.on(Hls.Events.MANIFEST_PARSED, function() {
                video.play().catch(err => console.warn('Autoplay prevented:', err));
            });

            hls.on(Hls.Events.ERROR, function(event, data) {
                if (data.fatal) {
                    console.warn('HLS error, falling back to MP4');
                    video.src = window.localVideoData.fallbackUrl;
                    video.play().catch(err => console.warn('Autoplay prevented:', err));
                }
            });
        } else if (video.canPlayType('application/vnd.apple.mpegurl') && data.hlsUrl) {
            // Safari native HLS
            video.src = data.hlsUrl;
            video.play().catch(err => console.warn('Autoplay prevented:', err));
        } else {
            // Fallback to MP4
            video.src = data.fallbackUrl;
            video.play().catch(err => console.warn('Autoplay prevented:', err));
        }
    }

    function loadEmbedVideo(embedUrl, loadingOverlay) {
        const iframe = document.getElementById('embed-player');
        if (!iframe) return;

        // Build URL safely
        const separator = embedUrl.includes('?') ? '&' : '?';
        iframe.src = embedUrl + separator + 'autoplay=1';

        // Hide loader after iframe loads
        iframe.onload = function() {
            if (loadingOverlay) {
                loadingOverlay.style.opacity = '0';
                setTimeout(() => loadingOverlay.classList.add('hidden'), 300);
            }
        };

        // Fallback if onload doesn't fire
        setTimeout(() => {
            if (loadingOverlay && !loadingOverlay.classList.contains('hidden')) {
                loadingOverlay.style.opacity = '0';
                setTimeout(() => loadingOverlay.classList.add('hidden'), 300);
            }
        }, 1500);
    }

    // Helper to get CSRF token with fallbacks
    function getCsrfToken() {
        // Try meta tag first
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) return meta.content;

        // Try Laravel global
        if (window.Laravel && window.Laravel.csrfToken) {
            return window.Laravel.csrfToken;
        }

        // Try any input with name _token
        const input = document.querySelector('input[name="_token"]');
        if (input) return input.value;

        return '';
    }

    // Bunny Stream tracking function
    function bunnyTracker(lessonId, totalSeconds) {
        return {
            lessonId,
            totalSeconds: totalSeconds || 300,
            signedEmbedUrl: '',
            sessionStartTime: null,
            lastSaveTime: null,
            lastSavedPercentage: 0,
            completionRecorded: false,
            saveThresholdMs: 120000,
            percentageThreshold: 15,
            intervalId: null,
            queueIntervalId: null,
            progressQueue: [],
            retryAttempts: new Map(),
            maxRetryDelay: 60000,
            queueKey: 'video_progress_queue',
            hasStarted: false,
            init() {
                // Get embed URL from data attribute
                const container = this.$el;
                this.signedEmbedUrl = container?.dataset?.embedUrl || '';

                // Register global callback for play button
                const self = this;
                window.bunnyPlayCallback = function() {
                    self.playVideo();
                };


                this.sessionStartTime = Date.now();
                this.lastSaveTime = this.sessionStartTime;
                this.loadQueue();
                document.addEventListener('visibilitychange', () => {
                    if (document.hidden) {
                        this.saveProgress(true);
                    }
                });
                window.addEventListener('beforeunload', () => {
                    this.sendBeaconProgress();
                });
                window.addEventListener('online', () => {
                    console.log('Connection restored, flushing queue');
                    this.processQueue(true);
                });
            },
            playVideo() {
                if (this.hasStarted) return;
                this.hasStarted = true;

                const iframe = this.$refs.bunnyIframe;
                if (!iframe) return;

                // Use signed embed URL and append autoplay
                const separator = this.signedEmbedUrl.includes('?') ? '&' : '?';
                iframe.src = this.signedEmbedUrl + separator + 'autoplay=true';

                // Start tracking intervals
                this.intervalId = setInterval(() => {
                    if (!document.hidden) {
                        this.saveProgress(false);
                    }
                }, 30000);

                this.queueIntervalId = setInterval(() => {
                    if (this.progressQueue.length > 0 && navigator.onLine) {
                        this.processQueue(false);
                    }
                }, 15000);
            },
            loadQueue() {
                try {
                    const stored = localStorage.getItem(this.queueKey);
                    if (stored) {
                        const allItems = JSON.parse(stored);
                        // Only keep items for current lesson, drop others
                        const currentLessonItems = allItems.filter(item => item.lesson_id === this.lessonId);
                        const otherLessonItems = allItems.filter(item => item.lesson_id !== this.lessonId);

                        if (otherLessonItems.length > 0) {
                            // Save back only current lesson items
                            localStorage.setItem(this.queueKey, JSON.stringify(currentLessonItems));
                        }
                        this.progressQueue = currentLessonItems;
                    }
                } catch (e) {
                    console.error('Failed to load queue:', e);
                    this.progressQueue = [];
                }
            },
            saveQueue() {
                try {
                    localStorage.setItem(this.queueKey, JSON.stringify(this.progressQueue));
                } catch (e) {
                    console.error('Failed to save queue:', e);
                }
            },
            queueProgress(payload) {
                const queueItem = {
                    ...payload,
                    timestamp: Date.now(),
                    attempts: 0
                };
                this.progressQueue.push(queueItem);
                this.saveQueue();
                console.log('Queued progress update (offline)');
            },
            async processQueue(flushAll = false) {
                if (this.progressQueue.length === 0) return;
                const now = Date.now();
                const itemsToProcess = flushAll ? this.progressQueue.slice() : this.progressQueue.slice(0, 3);
                for (let i = itemsToProcess.length - 1; i >= 0; i--) {
                    const item = itemsToProcess[i];
                    const itemKey = `${item.lesson_id}_${item.timestamp}`;
                    const attempts = this.retryAttempts.get(itemKey) || 0;
                    const delay = Math.min(Math.pow(2, attempts) * 1000, this.maxRetryDelay);
                    if ((now - item.timestamp) < delay && !flushAll) {
                        continue;
                    }
                    const success = await this.retryProgressRequest(item);
                    if (success) {
                        const queueIndex = this.progressQueue.findIndex(q =>
                            q.lesson_id === item.lesson_id && q.timestamp === item.timestamp
                        );
                        if (queueIndex !== -1) {
                            this.progressQueue.splice(queueIndex, 1);
                        }
                        this.retryAttempts.delete(itemKey);
                        console.log('Successfully sent queued progress');
                    } else {
                        this.retryAttempts.set(itemKey, attempts + 1);
                        if (attempts >= 5) {
                            console.warn('Max retries reached, removing old item');
                            const queueIndex = this.progressQueue.findIndex(q =>
                                q.lesson_id === item.lesson_id && q.timestamp === item.timestamp
                            );
                            if (queueIndex !== -1) {
                                this.progressQueue.splice(queueIndex, 1);
                            }
                            this.retryAttempts.delete(itemKey);
                        }
                    }
                }
                this.saveQueue();
            },
            async retryProgressRequest(item) {
                const csrfToken = getCsrfToken();
                if (!csrfToken) {
                    console.warn('CSRF token not found for retry');
                    return false;
                }
                try {
                    const response = await fetch('/video-progress', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            lesson_id: item.lesson_id,
                            watched_seconds: item.watched_seconds,
                            total_seconds: item.total_seconds,
                            percentage: item.percentage,
                        }),
                    });
                    return response.ok;
                } catch (err) {
                    return false;
                }
            },
            calculateWatchPercentage(timeSpentSeconds) {
                const total = this.totalSeconds || 300;
                return Math.min(100, Math.floor((timeSpentSeconds / total) * 100));
            },
            saveProgress(forceImmediate = false) {
                try {
                    const currentTime = Date.now();
                    const timeSinceLastSave = currentTime - this.lastSaveTime;
                    const sessionTimeSpent = Math.floor((currentTime - this.sessionStartTime) / 1000);
                    const currentPercentage = this.calculateWatchPercentage(sessionTimeSpent);
                    const percentageChange = Math.abs(currentPercentage - this.lastSavedPercentage);
                    if (forceImmediate ||
                        timeSinceLastSave >= this.saveThresholdMs ||
                        percentageChange >= this.percentageThreshold) {
                        if (currentPercentage > this.lastSavedPercentage || forceImmediate) {
                            this.sendProgress(sessionTimeSpent, currentPercentage);
                            this.lastSaveTime = currentTime;
                            this.lastSavedPercentage = currentPercentage;
                            this.recordCompletionIfNeeded();
                        }
                    }
                } catch (error) {
                    console.error('Failed to save progress:', error.message);
                }
            },
            recordCompletionIfNeeded() {
                if (this.completionRecorded) {
                    return;
                }
                if (this.lastSavedPercentage >= 90) {
                    this.completionRecorded = true;
                    this.sendCompletion();
                }
            },
            sendProgress(watchedSeconds, percentage) {
                const csrfToken = getCsrfToken();
                if (!csrfToken) {
                    console.warn('CSRF token not found, cannot send progress');
                    return;
                }
                const payload = {
                    lesson_id: this.lessonId,
                    watched_seconds: watchedSeconds,
                    total_seconds: this.totalSeconds || 300,
                    percentage: percentage,
                };
                fetch('/video-progress', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(payload),
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }
                })
                .catch((err) => {
                    console.error('Failed to report progress, queuing:', err.message);
                    this.queueProgress(payload);
                });
            },
            sendCompletion() {
                const csrfToken = getCsrfToken();
                if (!csrfToken) {
                    console.warn('CSRF token not found, cannot send completion');
                    return;
                }
                const payload = {
                    lesson_id: this.lessonId,
                    watched_percentage: 90,
                };
                fetch('/video-completion', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(payload),
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }
                })
                .catch((err) => {
                    console.error('Failed to report completion:', err.message);
                });
            },
            sendBeaconProgress() {
                const sessionTimeSpent = Math.floor((Date.now() - this.sessionStartTime) / 1000);
                if (sessionTimeSpent <= 0) {
                    return;
                }
                const percentage = this.calculateWatchPercentage(sessionTimeSpent);
                const csrfToken = getCsrfToken();
                const payload = JSON.stringify({
                    lesson_id: this.lessonId,
                    watched_seconds: sessionTimeSpent,
                    total_seconds: this.totalSeconds || 300,
                    percentage: percentage,
                    _token: csrfToken, // Include CSRF in body for beacon
                });
                const blob = new Blob([payload], { type: 'application/json' });
                navigator.sendBeacon('/video-progress', blob);
            }
        };
    }
</script>
{{-- Video tracking is now integrated into the player SDK scripts above --}}

