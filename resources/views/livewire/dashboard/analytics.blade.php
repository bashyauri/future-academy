<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <flux:heading size="2xl">{{ __('Performance Analytics') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Track your learning progress and performance trends') }}</flux:text>
        </div>
        <flux:button href="{{ route('dashboard') }}" variant="ghost" icon="arrow-left" wire:navigate>
            {{ __('Back to Dashboard') }}
        </flux:button>
    </div>

    {{-- Stats Overview --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div
            class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-6 bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-950/30 dark:to-blue-900/20">
            <flux:text class="text-sm text-blue-600 dark:text-blue-400 font-medium">{{ __('Total Quizzes') }}
            </flux:text>
            <flux:heading size="2xl" class="text-blue-900 dark:text-blue-100 mt-2">{{ $totalQuizzes }}</flux:heading>
        </div>

        <div
            class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-6 bg-gradient-to-br from-green-50 to-green-100 dark:from-green-950/30 dark:to-green-900/20">
            <flux:text class="text-sm text-green-600 dark:text-green-400 font-medium">{{ __('Avg Score') }}</flux:text>
            <flux:heading size="2xl" class="text-green-900 dark:text-green-100 mt-2">{{ $averageScore }}%</flux:heading>
        </div>

        <div
            class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-6 bg-gradient-to-br from-amber-50 to-amber-100 dark:from-amber-950/30 dark:to-amber-900/20">
            <flux:text class="text-sm text-amber-600 dark:text-amber-400 font-medium">{{ __('Current Streak') }}
            </flux:text>
            <flux:heading size="2xl" class="text-amber-900 dark:text-amber-100 mt-2">{{ $currentStreak }}
                {{ __('days') }}</flux:heading>
        </div>

        <div
            class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-6 bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-950/30 dark:to-purple-900/20">
            <flux:text class="text-sm text-purple-600 dark:text-purple-400 font-medium">{{ __('Total Time') }}
            </flux:text>
            <flux:heading size="2xl" class="text-purple-900 dark:text-purple-100 mt-2">
                {{ floor($totalTimeSpent / 3600) }}h {{ floor(($totalTimeSpent % 3600) / 60) }}m
            </flux:heading>
        </div>
    </div>

    {{-- Quiz Scores Over Time --}}
    <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-6 bg-white dark:bg-zinc-900">
        <flux:heading size="lg" class="mb-4">{{ __('Quiz Scores Over Time') }}</flux:heading>
        <flux:text class="text-sm text-neutral-500 mb-4">{{ __('Last 30 days') }}</flux:text>
        @if($quizScoresOverTime->isEmpty())
            <div class="text-center py-12">
                <flux:text class="text-neutral-500">{{ __('No quiz data available yet') }}</flux:text>
            </div>
        @else
            <div class="h-80">
                <canvas id="quizScoresChart"></canvas>
            </div>
        @endif
    </div>

    {{-- Subject Performance & Topic Mastery --}}
    <div class="grid lg:grid-cols-2 gap-6">
        {{-- Subject Performance --}}
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-6 bg-white dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">{{ __('Performance by Subject') }}</flux:heading>
            @if($subjectPerformance->isEmpty())
                <div class="text-center py-12">
                    <flux:text class="text-neutral-500">{{ __('No subject data available yet') }}</flux:text>
                </div>
            @else
                <div class="h-80">
                    <canvas id="subjectPerformanceChart"></canvas>
                </div>
            @endif
        </div>

        {{-- Topic Mastery --}}
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-6 bg-white dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">{{ __('Topic Mastery Levels') }}</flux:heading>
            @if($topicMastery->isEmpty())
                <div class="text-center py-12">
                    <flux:text class="text-neutral-500">{{ __('No topic data available yet') }}</flux:text>
                </div>
            @else
                <div class="h-80">
                    <canvas id="topicMasteryChart"></canvas>
                </div>
            @endif
        </div>
    </div>

    {{-- Study Streak & Time Spent --}}
    <div class="grid lg:grid-cols-2 gap-6">
        {{-- Study Streak --}}
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-6 bg-white dark:bg-zinc-900">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <flux:heading size="lg">{{ __('Study Streak') }}</flux:heading>
                    <flux:text class="text-sm text-neutral-500">{{ __('Last 30 days activity') }}</flux:text>
                </div>
                <div class="text-right">
                    <flux:text class="text-sm text-neutral-500">{{ __('Longest') }}</flux:text>
                    <flux:heading size="lg" class="text-amber-600 dark:text-amber-400">{{ $longestStreak }}
                        {{ __('days') }}</flux:heading>
                </div>
            </div>
            <div class="h-64">
                <canvas id="studyStreakChart"></canvas>
            </div>
        </div>

        {{-- Time Spent Daily --}}
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-6 bg-white dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">{{ __('Daily Study Time') }}</flux:heading>
            <flux:text class="text-sm text-neutral-500 mb-4">{{ __('Last 14 days') }}</flux:text>
            <div class="h-64">
                <canvas id="timeSpentChart"></canvas>
            </div>
        </div>
    </div>
</div>

@script
<script>
    // Import Chart.js
    import Chart from 'chart.js/auto';

    // Chart colors
    const colors = {
        primary: 'rgb(59, 130, 246)',
        success: 'rgb(34, 197, 94)',
        warning: 'rgb(251, 146, 60)',
        danger: 'rgb(239, 68, 68)',
        purple: 'rgb(168, 85, 247)',
        amber: 'rgb(245, 158, 11)',
        gray: 'rgb(156, 163, 175)',
    };

    // Chart.js defaults
    Chart.defaults.color = getComputedStyle(document.documentElement).getPropertyValue('color-scheme') === 'dark'
        ? 'rgb(229, 231, 235)'
        : 'rgb(55, 65, 81)';
    Chart.defaults.borderColor = 'rgba(156, 163, 175, 0.2)';
    Chart.defaults.font.family = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';

    // Quiz Scores Over Time Chart
    @if($quizScoresOverTime->isNotEmpty())
        const quizScoresData = @json($quizScoresOverTime);
        const quizScoresCtx = document.getElementById('quizScoresChart').getContext('2d');
        new Chart(quizScoresCtx, {
            type: 'line',
            data: {
                labels: quizScoresData.map(d => d.date),
                datasets: [{
                    label: 'Score %',
                    data: quizScoresData.map(d => d.score),
                    borderColor: colors.primary,
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            title: (items) => quizScoresData[items[0].dataIndex].title,
                            label: (item) => `Score: ${item.parsed.y}%`
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: { callback: (value) => value + '%' }
                    }
                }
            }
        });
    @endif

        // Subject Performance Chart
        @if($subjectPerformance->isNotEmpty())
            const subjectData = @json($subjectPerformance);
            const subjectCtx = document.getElementById('subjectPerformanceChart').getContext('2d');
            new Chart(subjectCtx, {
                type: 'bar',
                data: {
                    labels: subjectData.map(d => d.subject),
                    datasets: [{
                        label: 'Average Score',
                        data: subjectData.map(d => d.avg_score),
                        backgroundColor: [
                            'rgba(59, 130, 246, 0.8)',
                            'rgba(34, 197, 94, 0.8)',
                            'rgba(168, 85, 247, 0.8)',
                            'rgba(251, 146, 60, 0.8)',
                            'rgba(245, 158, 11, 0.8)',
                        ],
                        borderWidth: 0,
                        borderRadius: 8,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (item) => `Score: ${item.parsed.y}% (${subjectData[item.dataIndex].total_attempts} quizzes)`
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            ticks: { callback: (value) => value + '%' }
                        }
                    }
                }
            });
        @endif

        // Topic Mastery Chart
        @if($topicMastery->isNotEmpty())
            const topicData = @json($topicMastery);
            const topicCtx = document.getElementById('topicMasteryChart').getContext('2d');
            new Chart(topicCtx, {
                type: 'doughnut',
                data: {
                    labels: topicData.map(d => d.topic),
                    datasets: [{
                        data: topicData.map(d => d.avg_score),
                        backgroundColor: [
                            'rgba(34, 197, 94, 0.8)',
                            'rgba(59, 130, 246, 0.8)',
                            'rgba(168, 85, 247, 0.8)',
                            'rgba(251, 146, 60, 0.8)',
                            'rgba(245, 158, 11, 0.8)',
                            'rgba(239, 68, 68, 0.8)',
                        ],
                        borderWidth: 0,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'right' },
                        tooltip: {
                            callbacks: {
                                label: (item) => {
                                    const topic = topicData[item.dataIndex];
                                    return `${topic.topic}: ${topic.avg_score}% (${topic.mastery_level})`;
                                }
                            }
                        }
                    }
                }
            });
        @endif

    // Study Streak Chart
    const streakData = @json($studyStreak);
    const streakCtx = document.getElementById('studyStreakChart').getContext('2d');
    new Chart(streakCtx, {
        type: 'bar',
        data: {
            labels: streakData.map(d => d.date),
            datasets: [{
                label: 'Active Days',
                data: streakData.map(d => d.active),
                backgroundColor: streakData.map(d => d.active ? 'rgba(34, 197, 94, 0.8)' : 'rgba(229, 231, 235, 0.3)'),
                borderWidth: 0,
                borderRadius: 4,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (item) => item.parsed.y === 1 ? 'Active' : 'No activity'
                    }
                }
            },
            scales: {
                y: {
                    display: false,
                    beginAtZero: true,
                    max: 1
                },
                x: {
                    ticks: {
                        maxRotation: 90,
                        minRotation: 45,
                        autoSkip: true,
                        maxTicksLimit: 10
                    }
                }
            }
        }
    });

    // Time Spent Chart
    const timeData = @json($timeSpentDaily);
    const timeCtx = document.getElementById('timeSpentChart').getContext('2d');
    new Chart(timeCtx, {
        type: 'bar',
        data: {
            labels: timeData.map(d => d.date),
            datasets: [{
                label: 'Minutes',
                data: timeData.map(d => d.minutes),
                backgroundColor: 'rgba(168, 85, 247, 0.8)',
                borderWidth: 0,
                borderRadius: 8,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (item) => `${item.parsed.y} minutes`
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { callback: (value) => value + 'm' }
                }
            }
        }
    });
</script>
@endscript