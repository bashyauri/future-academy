<?php

namespace App\Livewire\Lessons;

use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\UserProgress;
use App\Models\VideoProgress;
use App\Models\QuizAttempt;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;

class LessonView extends Component
{
    public Lesson $lesson;
    public $progress;
    public $startTime;
    public $resumeTime = 0;
    public ?Quiz $lessonQuiz = null;
    public bool $lessonQuizCompleted = false;

    public function mount($id)
    {
        $this->lesson = Lesson::with(['subject', 'topic', 'questions.options'])->findOrFail($id);

        if (!$this->lesson->canUserAccess(auth()->user())) {
            abort(403, 'You do not have access to this lesson.');
        }

        // Get or create progress
        $this->progress = UserProgress::firstOrCreate([
            'user_id' => auth()->id(),
            'lesson_id' => $this->lesson->id,
        ], [
            'type' => 'lesson',
            'started_at' => now(),
            'current_time_seconds' => 0,
        ]);

        // Set resume time from previous session
        $this->resumeTime = $this->progress->current_time_seconds ?? 0;

        $this->startTime = now();

        $this->lessonQuiz = Quiz::query()
            ->active()
            ->available()
            ->where('lesson_id', $this->lesson->id)
            ->orderByDesc('created_at')
            ->first();

        if ($this->lessonQuiz) {
            $this->lessonQuizCompleted = QuizAttempt::query()
                ->where('quiz_id', $this->lessonQuiz->id)
                ->where('user_id', auth()->id())
                ->where('status', 'completed')
                ->exists();
        }
    }

    public function markComplete()
    {
        if ($this->lessonQuiz) {
            $this->lessonQuizCompleted = QuizAttempt::query()
                ->where('quiz_id', $this->lessonQuiz->id)
                ->where('user_id', auth()->id())
                ->where('status', 'completed')
                ->exists();

            if (!$this->lessonQuizCompleted) {
                session()->flash('error', 'Please complete the lesson quiz before marking this lesson as complete.');
                return redirect()->route('quiz.take', $this->lessonQuiz);
            }
        }

        // Track video progress if lesson has a video
        if ($this->lesson->video_url && $this->lesson->video_type === 'bunny') {
            $this->trackVideoProgress();
        }

        $this->progress->markCompleted();

        session()->flash('success', 'Lesson marked as complete!');

        $quiz = $this->lessonQuiz;

        // Find next lesson
        $nextLesson = Lesson::where('subject_id', $this->lesson->subject_id)
            ->where('status', 'published')
            ->where('order', '>', $this->lesson->order)
            ->ordered()
            ->first();

        if ($nextLesson) {
            return redirect()->route('lessons.view', $nextLesson);
        }

        return redirect()->route('lessons.list', $this->lesson->subject_id);
    }

    /**
     * Track video watch progress
     * For Bunny videos, we record that the user watched based on time spent on page
     */
    private function trackVideoProgress(): void
    {
        // Only track if lesson has a video
        if (!is_string($this->lesson->video_url)) {
            return;
        }

        $timeSpent = now()->diffInSeconds($this->startTime);

        // If user spent at least 2 minutes on the page, consider video partially watched
        // If 5+ minutes, consider it fully watched (assuming min 5 min video)
        $watchPercentage = min(100, ($timeSpent / 300) * 100); // 5 minutes = 100%

        VideoProgress::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'lesson_id' => $this->lesson->id,
            ],
            [
                'watch_time' => $timeSpent,
                'percentage' => (int) $watchPercentage,
                'completed' => $watchPercentage >= 90,
            ]
        );
    }

    public function updateProgress($percentage)
    {
        $this->progress->updateProgress($percentage);
    }

    /**
     * Track video watch time - Event-driven approach (called only when progress changes significantly)
     * Accepts percentage from client-side event tracking
     */
    public function trackVideoWatch($watchPercentage = 0, $timeSpent = 0)
    {
        \Log::info('trackVideoWatch called', [
            'lesson_id' => $this->lesson->id,
            'user_id' => auth()->id(),
            'percentage' => $watchPercentage,
            'time_spent' => $timeSpent,
            'video_type' => $this->lesson->video_type
        ]);

        if (is_string($this->lesson->video_url) && $this->lesson->video_type === 'bunny') {
            // Use provided parameters or calculate based on session time
            if ($timeSpent === 0) {
                $timeSpent = now()->diffInSeconds($this->startTime);
            }

            if ($watchPercentage === 0) {
                $watchPercentage = min(100, ($timeSpent / 300) * 100);
            }

            try {
                // Update VideoProgress table (for analytics)
                // Use lesson_id as unique key instead of video_id (which is a numeric FK)
                VideoProgress::updateOrCreate(
                    [
                        'user_id' => auth()->id(),
                        'lesson_id' => $this->lesson->id,
                    ],
                    [
                        'watch_time' => $timeSpent,
                        'percentage' => (int) $watchPercentage,
                        'completed' => $watchPercentage >= 90,
                    ]
                );

                // Update UserProgress table (for UI display)
                $this->progress->update([
                    'progress_percentage' => (int) $watchPercentage,
                    'time_spent_seconds' => $timeSpent,
                ]);

                // Refresh the component property to update the UI
                $this->progress->refresh();

                \Log::info('Video progress saved successfully', [
                    'lesson_id' => $this->lesson->id,
                    'percentage' => $watchPercentage
                ]);
            } catch (\Exception $e) {
                \Log::error('Failed to save video progress', [
                    'error' => $e->getMessage(),
                    'lesson_id' => $this->lesson->id
                ]);
            }
        }

        $this->skipRender();
    }

    /**
     * Update the current playback time for video resume functionality
     * Called periodically by the Bunny Player SDK
     */
    public function updateVideoTime($currentTime)
    {
        if (is_string($this->lesson->video_url) && $this->lesson->video_type === 'bunny') {
            // Update UserProgress with current playback position
            $this->progress->update([
                'current_time_seconds' => (int) $currentTime,
            ]);

            // Also store in VideoProgress for analytics
            $videoProgress = VideoProgress::where('user_id', auth()->id())
                ->where('lesson_id', $this->lesson->id)
                ->first();

            if ($videoProgress) {
                $videoProgress->update([
                    'current_time' => (int) $currentTime,
                ]);
            }
        }

        $this->skipRender();
    }

    public function refreshVideoStatus()
    {
        // Reload the lesson from database to get latest video_status
        $this->lesson = $this->lesson->fresh();

        // If video is ready, dispatch event to play
        if ($this->lesson->video_type === 'local' && $this->lesson->video_status === 'ready') {
            $this->dispatch('video-ready');
        }
    }

    /**
     * Fetch and sync video analytics from Bunny API
     * Currently uses time-based tracking, but this prepares for direct Bunny API integration
     */
    public function syncVideoAnalytics()
    {
        try {
            if ($this->lesson->video_type !== 'bunny' || !$this->lesson->video_url) {
                return;
            }

            // For now, use our tracked progress
            // In the future, this could query Bunny's analytics API directly
            $this->trackVideoProgress();

            \Log::info('Video analytics synced', ['lesson_id' => $this->lesson->id, 'user_id' => auth()->id()]);
        } catch (\Exception $e) {
            \Log::error('Error syncing video analytics', ['error' => $e->getMessage()]);
        }
    }

    public function destroy()
    {
        // Track time spent on page before leaving
        if ($this->startTime) {
            $timeSpent = now()->diffInSeconds($this->startTime);
            $this->progress->addTimeSpent($timeSpent);

            // If lesson has a Bunny video, also track video progress
            if ($this->lesson->video_url && $this->lesson->video_type === 'bunny') {
                $this->trackVideoProgress();
            }
        }
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        $nextLesson = Lesson::where('subject_id', $this->lesson->subject_id)
            ->where('status', 'published')
            ->where('order', '>', $this->lesson->order)
            ->ordered()
            ->first();

        $previousLesson = Lesson::where('subject_id', $this->lesson->subject_id)
            ->where('status', 'published')
            ->where('order', '<', $this->lesson->order)
            ->orderBy('order', 'desc')
            ->first();

        $lessonQuiz = $this->lessonQuiz;

        if ($lessonQuiz) {
            $service = app(\App\Services\QuizGeneratorService::class);
            $stats = $service->getUserStats($lessonQuiz, auth()->user());
            $lessonQuiz->user_stats = $stats;
            $lessonQuiz->can_attempt = $lessonQuiz->canUserAttempt(auth()->user());
        }

        return view('livewire.lessons.lesson-view', [
            'nextLesson' => $nextLesson,
            'previousLesson' => $previousLesson,
            'lessonQuiz' => $lessonQuiz,
            'lessonQuizCompleted' => $this->lessonQuizCompleted,
        ]);
    }
}
