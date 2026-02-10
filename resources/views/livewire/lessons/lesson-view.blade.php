<div class="space-y-6">
    {{-- Breadcrumb - Hide on mobile, show on md+ --}}
    <div class="hidden md:flex items-center gap-2 text-xs md:text-sm overflow-x-auto pb-2">
        <a href="{{ route('lessons.subjects') }}" wire:navigate
            class="text-blue-600 dark:text-blue-400 hover:underline whitespace-nowrap">
            {{ __('Lessons') }}
        </a>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 md:h-4 md:w-4 text-neutral-400 flex-shrink-0" fill="none" viewBox="0 0 24 24"
            stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
        <a href="{{ route('lessons.list', $lesson->subject_id) }}" wire:navigate
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
            @if($lesson->video_url)
                <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 overflow-hidden bg-black">
                    <div class="aspect-video relative">
                        @if($lesson->video_type === 'local')
                            {{-- Local video with adaptive HLS streaming --}}
                            @php
                                $hlsUrl = app(\App\Services\VideoSigningService::class)->getHlsStreamingUrl($lesson->video_url);
                                $fallbackUrl = app(\App\Services\VideoSigningService::class)->getOptimizedUrl($lesson->video_url);
                            @endphp
                            <video id="lesson-video" class="w-full h-full" controls preload="metadata" x-ref="video">
                                <source src="{{ $fallbackUrl }}" type="video/mp4">
                                <p>Your browser doesn't support HTML5 video. Please update your browser.</p>
                            </video>
                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    const video = document.getElementById('lesson-video');
                                    const hlsUrl = '{{ $hlsUrl }}';

                                    // Check if HLS.js is available
                                    if (Hls.isSupported()) {
                                        const hls = new Hls({
                                            debug: false,
                                            enableWorker: true,
                                            lowLatencyMode: false,
                                        });
                                        hls.loadSource(hlsUrl);
                                        hls.attachMedia(video);
                                        hls.on(Hls.Events.MANIFEST_PARSED, function() {
                                            console.log('HLS stream loaded with adaptive bitrate');
                                        });
                                        hls.on(Hls.Events.ERROR, function(event, data) {
                                            console.warn('HLS error:', data);
                                            // Fallback to MP4
                                            video.src = '{{ $fallbackUrl }}';
                                        });
                                    } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
                                        // Safari native HLS support
                                        video.src = hlsUrl;
                                    } else {
                                        // Fallback to MP4
                                        console.log('Adaptive streaming not supported, using MP4 fallback');
                                    }
                                });
                            </script>
                        @elseif($lesson->video_type === 'bunny')
                            {{-- Bunny Stream Player (Alpine + fetch tracking, low frequency) --}}
                            <div class="w-full h-full" wire:ignore
                                x-data="bunnyTracker({{ $lesson->id }}, {{ (int) (($lesson->duration_minutes ?? 5) * 60) }})"
                                x-init="init()">
                                <iframe
                                    id="bunny-player"
                                    src="{{ $lesson->getVideoEmbedUrl() }}"
                                    class="w-full h-full"
                                    frameborder="0"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                    allowfullscreen>
                                </iframe>
                            </div>

                            <script>
                                function bunnyTracker(lessonId, totalSeconds) {
                                    return {
                                        lessonId,
                                        totalSeconds: totalSeconds || 300,
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
                                        init() {
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
                                                    this.progressQueue = JSON.parse(stored);
                                                    console.log(`Loaded ${this.progressQueue.length} queued items`);
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
                                            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
                                            if (!csrfToken) return false;

                                            try {
                                                const response = await fetch('/video-progress', {
                                                    method: 'POST',
                                                    headers: {
                                                        'Content-Type': 'application/json',
                                                        'X-CSRF-TOKEN': csrfToken,
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
                                            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
                                            if (!csrfToken) {
                                                console.warn('CSRF token not found');
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
                                            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
                                            if (!csrfToken) {
                                                console.warn('CSRF token not found');
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
                                            const payload = JSON.stringify({
                                                lesson_id: this.lessonId,
                                                watched_seconds: sessionTimeSpent,
                                                total_seconds: this.totalSeconds || 300,
                                                percentage: percentage,
                                            });
                                            const blob = new Blob([payload], { type: 'application/json' });
                                            navigator.sendBeacon('/video-progress', blob);
                                        }
                                    };
                                }
                            </script>
                        @else
                            {{-- YouTube/Vimeo embedded iframe --}}
                            <iframe src="{{ $lesson->getVideoEmbedUrl() }}" class="w-full h-full" frameborder="0"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                allowfullscreen>
                            </iframe>
                        @endif
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

                    @if(!$progress->is_completed)
                        <div class="flex flex-col gap-2 w-full md:w-auto md:items-end">
                            @if($lessonQuiz && !$lessonQuizCompleted)
                                <flux:badge color="amber" size="sm" class="text-xs">
                                    <span class="hidden sm:inline">{{ __('Complete the lesson quiz to unlock completion') }}</span>
                                    <span class="sm:hidden">{{ __('Complete quiz to unlock') }}</span>
                                </flux:badge>
                            @endif

                            <flux:button wire:click="markComplete" wire:loading.attr="disabled" wire:target="markComplete"
                                variant="primary" icon="check" class="w-full md:w-auto text-sm"
                                :disabled="$lessonQuiz && !$lessonQuizCompleted">
                                <span wire:loading.remove wire:target="markComplete">{{ __('Mark Complete') }}</span>
                                <span wire:loading wire:target="markComplete">{{ __('Saving...') }}</span>
                            </flux:button>
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
            @if($lesson->questions->isNotEmpty())
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
                    <flux:button href="{{ route('lessons.view', $previousLesson) }}" wire:navigate variant="ghost"
                        icon="arrow-left" class="w-full md:w-auto text-sm">
                        <span class="hidden sm:inline">{{ __('Previous Lesson') }}</span>
                        <span class="sm:hidden">{{ __('Previous') }}</span>
                    </flux:button>
                @else
                    <div></div>
                @endif

                @if($nextLesson)
                    <flux:button href="{{ route('lessons.view', $nextLesson) }}" wire:navigate variant="primary"
                        icon-trailing="arrow-right" class="w-full md:w-auto text-sm"
                        :disabled="$lessonQuiz && !$lessonQuizCompleted">
                        <span class="hidden sm:inline">{{ $lessonQuiz && !$lessonQuizCompleted ? __('Complete Quiz to Unlock') : __('Next Lesson') }}</span>
                        <span class="sm:hidden">{{ $lessonQuiz && !$lessonQuizCompleted ? __('Unlock') : __('Next') }}</span>
                    </flux:button>
                @else
                    <flux:button href="{{ route('lessons.list', $lesson->subject_id) }}" wire:navigate variant="primary" class="w-full md:w-auto text-sm">
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

{{-- Video tracking is now integrated into the player SDK scripts above --}}

