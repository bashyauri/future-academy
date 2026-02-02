<div class="min-h-screen bg-gradient-to-br from-neutral-50 to-neutral-100 dark:from-neutral-900 dark:to-neutral-800 -m-6 p-6">
    <div class="max-w-7xl mx-auto space-y-8">
        {{-- Header Section --}}
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
            <div>
                <flux:heading size="3xl" class="font-bold text-neutral-900 dark:text-white mb-2">{{ __('Welcome back, :name!', ['name' => auth()->user()->name]) }}</flux:heading>
                <flux:text class="text-neutral-600 dark:text-neutral-400 text-lg">
                    {{ __('Managing :count linked student(s)', ['count' => $stats['children_count']]) }}
                </flux:text>
            </div>
            @if($subscriptions->isNotEmpty())
            <div class="inline-flex items-center px-4 py-2 rounded-lg bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                <flux:text class="font-semibold text-sm">{{ __('Active Subscription') }}</flux:text>
            </div>
            @else
            <flux:button href="{{ route('pricing') }}" variant="primary" icon="shopping-cart" wire:navigate>
                {{ __('Get Subscription') }}
            </flux:button>
            @endif
        </div>

        {{-- Children Summary Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            {{-- Total Children --}}
            <div class="rounded-2xl border border-neutral-200 dark:border-neutral-700 p-6 bg-white dark:bg-neutral-800 hover:shadow-lg transition-shadow">
                <div class="flex items-start justify-between mb-4">
                    <div class="p-3 rounded-lg bg-blue-100 dark:bg-blue-900/30">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-2a6 6 0 0112 0v2zm0 0h6v-2a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                    </div>
                    <span class="text-xs font-bold text-blue-600 bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400 px-3 py-1 rounded-full">
                        Linked
                    </span>
                </div>
                <flux:text class="text-sm text-neutral-600 dark:text-neutral-400 font-medium mb-1">{{ __('Total Students') }}</flux:text>
                <flux:heading size="xl" class="text-neutral-900 dark:text-white font-bold">{{ $stats['children_count'] }}</flux:heading>
            </div>

            {{-- Average Score --}}
            <div class="rounded-2xl border border-neutral-200 dark:border-neutral-700 p-6 bg-white dark:bg-neutral-800 hover:shadow-lg transition-shadow">
                <div class="flex items-start justify-between mb-4">
                    <div class="p-3 rounded-lg bg-amber-100 dark:bg-amber-900/30">
                        <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <span class="text-xs font-bold {{ $stats['average_score'] >= 70 ? 'text-green-600 bg-green-100 dark:bg-green-900/30 dark:text-green-400' : ($stats['average_score'] >= 50 ? 'text-amber-600 bg-amber-100 dark:bg-amber-900/30 dark:text-amber-400' : 'text-red-600 bg-red-100 dark:bg-red-900/30 dark:text-red-400') }} px-3 py-1 rounded-full">
                        {{ $stats['average_score'] >= 70 ? 'Excellent' : ($stats['average_score'] >= 50 ? 'Good' : 'Fair') }}
                    </span>
                </div>
                <flux:text class="text-sm text-neutral-600 dark:text-neutral-400 font-medium mb-1">{{ __('Average Score') }}</flux:text>
                <flux:heading size="xl" class="text-neutral-900 dark:text-white font-bold mb-3">{{ number_format($stats['average_score'], 1) }}%</flux:heading>
                <div class="w-full bg-neutral-200 dark:bg-neutral-700 rounded-full h-2 overflow-hidden">
                    <div class="bg-gradient-to-r from-amber-500 to-amber-600 h-full transition-all" style="width: {{ $stats['average_score'] }}%"></div>
                </div>
            </div>

            {{-- Mock Exams Taken --}}
            <div class="rounded-2xl border border-neutral-200 dark:border-neutral-700 p-6 bg-white dark:bg-neutral-800 hover:shadow-lg transition-shadow">
                <div class="flex items-start justify-between mb-4">
                    <div class="p-3 rounded-lg bg-purple-100 dark:bg-purple-900/30">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <span class="text-xs font-bold {{ $stats['best_mock_score'] >= 70 ? 'text-green-600 bg-green-100 dark:bg-green-900/30 dark:text-green-400' : 'text-purple-600 bg-purple-100 dark:bg-purple-900/30 dark:text-purple-400' }} px-3 py-1 rounded-full">
                        {{ $stats['mock_exams_taken'] > 0 ? 'Best: ' . number_format($stats['best_mock_score'], 0) . '%' : 'No Mocks' }}
                    </span>
                </div>
                <flux:text class="text-sm text-neutral-600 dark:text-neutral-400 font-medium mb-1">{{ __('Total Mock Exams') }}</flux:text>
                <flux:heading size="xl" class="text-neutral-900 dark:text-white font-bold">{{ $stats['mock_exams_taken'] }}</flux:heading>
            </div>

            {{-- Videos Watched --}}
            <div class="rounded-2xl border border-neutral-200 dark:border-neutral-700 p-6 bg-white dark:bg-neutral-800 hover:shadow-lg transition-shadow">
                <div class="flex items-start justify-between mb-4">
                    <div class="p-3 rounded-lg bg-green-100 dark:bg-green-900/30">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                        </svg>
                    </div>
                    <span class="text-xs font-bold text-green-600 bg-green-100 dark:bg-green-900/30 dark:text-green-400 px-3 py-1 rounded-full">
                        {{ $stats['total_videos'] > 0 ? round(($stats['videos_watched'] / $stats['total_videos']) * 100) : 0 }}%
                    </span>
                </div>
                <flux:text class="text-sm text-neutral-600 dark:text-neutral-400 font-medium mb-1">{{ __('Videos Watched') }}</flux:text>
                <div class="flex items-baseline gap-2 mb-3">
                    <flux:heading size="xl" class="text-neutral-900 dark:text-white font-bold">{{ $stats['videos_watched'] }}</flux:heading>
                    <flux:text class="text-neutral-500 dark:text-neutral-400">/ {{ $stats['total_videos'] }}</flux:text>
                </div>
                @if($stats['total_videos'] > 0)
                <div class="w-full bg-neutral-200 dark:bg-neutral-700 rounded-full h-2 overflow-hidden">
                    <div class="bg-gradient-to-r from-green-500 to-green-600 h-full transition-all" style="width: {{ ($stats['videos_watched'] / $stats['total_videos']) * 100 }}%"></div>
                </div>
                @endif
            </div>
        </div>

        {{-- Link Student Section --}}
        <div class="rounded-2xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 p-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div>
                    <flux:heading size="lg" class="font-bold text-neutral-900 dark:text-white">{{ __('Link a Student') }}</flux:heading>
                    <flux:text class="text-sm text-neutral-500 dark:text-neutral-400 mt-1">
                        {{ __('Enter the student email to link them to your account.') }}
                    </flux:text>
                </div>
            </div>

            @if($linkSuccessMessage)
                <div class="mb-4 rounded-lg bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 px-4 py-2 text-sm font-semibold">
                    {{ $linkSuccessMessage }}
                </div>
            @endif

            <form wire:submit.prevent="linkStudent" class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                <div class="sm:col-span-2">
                    <flux:input
                        wire:model.defer="studentEmail"
                        type="email"
                        :label="__('Student Email')"
                        autocomplete="email"
                        placeholder="student@email.com"
                        required
                    />
                    @error('studentEmail')
                        <div class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                    @enderror
                </div>
                <div>
                    <flux:button type="submit" variant="primary" class="w-full" icon="link">
                        {{ __('Link Student') }}
                    </flux:button>
                </div>
            </form>
        </div>

        {{-- Subscription Section --}}
        @if($subscriptions->isNotEmpty())
        <div class="rounded-2xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 p-8 overflow-hidden">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <flux:heading size="lg" class="font-bold text-neutral-900 dark:text-white">{{ __('Your Subscription') }}</flux:heading>
                    <flux:text class="text-sm text-neutral-500 dark:text-neutral-400 mt-1">{{ __('Covers all linked students') }}</flux:text>
                </div>
                <livewire:subscription.cancel />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($subscriptions as $subscription)
                <div class="p-6 rounded-xl border {{ $subscription->status === 'active' ? 'border-green-200 dark:border-green-900/30 bg-green-50 dark:bg-green-900/10' : 'border-neutral-200 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-700/50' }} transition-all">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <flux:heading size="sm" class="font-bold text-neutral-900 dark:text-white capitalize">
                                {{ $subscription->plan }} {{ __('Plan') }}
                            </flux:heading>
                            <flux:text class="text-xs text-neutral-500 dark:text-neutral-400 mt-1">
                                ₦{{ number_format($subscription->amount) }}
                            </flux:text>
                        </div>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold {{ $subscription->status === 'active' ? 'bg-green-200 dark:bg-green-900/50 text-green-700 dark:text-green-300' : ($subscription->status === 'pending' ? 'bg-amber-200 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300' : 'bg-red-200 dark:bg-red-900/50 text-red-700 dark:text-red-300') }}">
                            {{ ucfirst($subscription->status) }}
                        </span>
                    </div>

                    <div class="space-y-3 mb-4 pb-4 border-b border-current border-opacity-10">
                        <div class="flex items-center justify-between text-sm">
                            <flux:text class="text-neutral-600 dark:text-neutral-400">{{ __('Started') }}</flux:text>
                            <flux:text class="font-semibold text-neutral-900 dark:text-white">{{ $subscription->created_at->format('M j, Y') }}</flux:text>
                        </div>
                        @if($subscription->ends_at)
                        <div class="flex items-center justify-between text-sm">
                            <flux:text class="text-neutral-600 dark:text-neutral-400">{{ __('Expires') }}</flux:text>
                            <flux:text class="font-semibold {{ $subscription->ends_at->isPast() ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                                {{ $subscription->ends_at->format('M j, Y') }}
                            </flux:text>
                        </div>
                        @endif
                    </div>

                    @if($subscription->status === 'active')
                    <flux:text class="text-xs text-green-600 dark:text-green-400 font-medium">
                        ✓ {{ __('Active & protecting all students') }}
                    </flux:text>
                    @elseif($subscription->status === 'pending')
                    <flux:text class="text-xs text-amber-600 dark:text-amber-400 font-medium">
                        ⏳ {{ __('Pending activation') }}
                    </flux:text>
                    @else
                    <flux:text class="text-xs text-red-600 dark:text-red-400 font-medium">
                        ✗ {{ __('Inactive or expired') }}
                    </flux:text>
                    @endif
                </div>
                @endforeach
            </div>

            <div class="mt-6 pt-6 border-t border-neutral-200 dark:border-neutral-700">
                <flux:button href="{{ route('pricing') }}" variant="subtle" wire:navigate class="w-full">
                    {{ __('Manage Subscription') }}
                </flux:button>
            </div>
        </div>
        @else
        <div class="rounded-2xl border-2 border-dashed border-amber-300 dark:border-amber-700 bg-amber-50 dark:bg-amber-900/20 p-8">
            <div class="flex items-start gap-6">
                <div class="flex-shrink-0">
                    <div class="flex items-center justify-center h-12 w-12 rounded-md bg-amber-500 text-white">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4v2m0 0v2m0-6v2m0-4v2"></path>
                        </svg>
                    </div>
                </div>
                <div class="flex-1">
                    <flux:heading size="lg" class="font-bold text-amber-900 dark:text-amber-100 mb-2">
                        {{ __('No Active Subscription') }}
                    </flux:heading>
                    <flux:text class="text-amber-800 dark:text-amber-200 mb-4">
                        {{ __('Get a subscription to unlock content for all your linked students and start their learning journey!') }}
                    </flux:text>
                    <flux:button href="{{ route('pricing') }}" variant="primary" wire:navigate>
                        {{ __('View Subscription Plans') }}
                    </flux:button>
                </div>
            </div>
        </div>
        @endif

        {{-- Children Progress Cards --}}
        <div>
            <div class="mb-6">
                <flux:heading size="lg" class="font-bold text-neutral-900 dark:text-white">{{ __('Students Progress') }}</flux:heading>
                <flux:text class="text-neutral-600 dark:text-neutral-400 mt-1">{{ __('Track learning metrics for each student') }}</flux:text>
            </div>

            @if($children->isEmpty())
            <div class="rounded-2xl border-2 border-dashed border-neutral-300 dark:border-neutral-600 bg-neutral-50 dark:bg-neutral-800/50 p-12 text-center">
                <svg class="w-16 h-16 text-neutral-300 dark:text-neutral-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-2a6 6 0 0112 0v2zm0 0h6v-2a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
                <flux:heading size="lg" class="text-neutral-500 dark:text-neutral-400 font-bold mb-2">
                    {{ __('No Linked Students Yet') }}
                </flux:heading>
                <flux:text class="text-neutral-600 dark:text-neutral-400 max-w-md mx-auto mb-6">
                    {{ __('Start linking students to begin monitoring their progress and performance across all exams.') }}
                </flux:text>
                <flux:button href="{{ route('profile.edit') }}" variant="primary" wire:navigate>
                    {{ __('Link Your First Student') }}
                </flux:button>
            </div>
            @else
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                @foreach($children as $child)
                <div class="rounded-2xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 overflow-hidden hover:shadow-lg transition-shadow">
                    {{-- Child Header --}}
                    <div class="px-8 py-5 border-b border-neutral-200 dark:border-neutral-700 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 flex items-center justify-between">
                        <div>
                            <flux:heading size="md" class="font-bold text-neutral-900 dark:text-white">
                                {{ $child->name }}
                            </flux:heading>
                            <flux:text class="text-sm text-neutral-500 dark:text-neutral-400 mt-1">
                                {{ $child->enrolledSubjects()->count() }} subject(s) enrolled
                            </flux:text>
                        </div>
                        <div class="text-right">
                            @if($child->has_completed_onboarding)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300">
                                ✓ Ready
                            </span>
                            @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300">
                                ⚠ Setup Needed
                            </span>
                            @endif
                        </div>
                    </div>

                    {{-- Child Stats --}}
                    <div class="p-6">
                        <div class="grid grid-cols-2 gap-4 mb-6">
                            {{-- Videos --}}
                            <div class="p-4 rounded-lg bg-neutral-50 dark:bg-neutral-700/30">
                                <div class="flex items-center justify-between mb-2">
                                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                                    </svg>
                                    <flux:text class="text-xs font-bold text-green-600 dark:text-green-400">
                                        {{ $childrenStats[$child->id]['videos_percentage'] }}%
                                    </flux:text>
                                </div>
                                <flux:text class="text-xs text-neutral-600 dark:text-neutral-400 mb-2 block">{{ __('Videos') }}</flux:text>
                                <div class="text-sm font-bold text-neutral-900 dark:text-white">
                                    {{ $childrenStats[$child->id]['videos_watched'] }}/{{ $childrenStats[$child->id]['total_videos'] }}
                                </div>
                                <div class="w-full bg-neutral-200 dark:bg-neutral-700 rounded-full h-1.5 mt-2 overflow-hidden">
                                    <div class="bg-gradient-to-r from-green-500 to-green-600 h-full" style="width: {{ $childrenStats[$child->id]['videos_percentage'] }}%"></div>
                                </div>
                            </div>

                            {{-- Score --}}
                            <div class="p-4 rounded-lg bg-neutral-50 dark:bg-neutral-700/30">
                                <div class="flex items-center justify-between mb-2">
                                    <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                    <flux:text class="text-xs font-bold {{ $childrenStats[$child->id]['average_score'] >= 70 ? 'text-green-600 dark:text-green-400' : ($childrenStats[$child->id]['average_score'] >= 50 ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400') }}">
                                        {{ $childrenStats[$child->id]['average_score'] >= 70 ? 'Good' : ($childrenStats[$child->id]['average_score'] >= 50 ? 'Fair' : 'Low') }}
                                    </flux:text>
                                </div>
                                <flux:text class="text-xs text-neutral-600 dark:text-neutral-400 mb-2 block">{{ __('Avg Score') }}</flux:text>
                                <div class="text-sm font-bold text-neutral-900 dark:text-white">
                                    {{ $childrenStats[$child->id]['average_score'] }}%
                                </div>
                            </div>

                            {{-- Mocks --}}
                            <div class="p-4 rounded-lg bg-neutral-50 dark:bg-neutral-700/30">
                                <div class="flex items-center justify-between mb-2">
                                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <flux:text class="text-xs font-bold text-purple-600 dark:text-purple-400">Best</flux:text>
                                </div>
                                <flux:text class="text-xs text-neutral-600 dark:text-neutral-400 mb-2 block">{{ __('Mock Exams') }}</flux:text>
                                <div class="text-sm font-bold text-neutral-900 dark:text-white">
                                    {{ $childrenStats[$child->id]['mock_exams_taken'] }} taken
                                </div>
                                @if($childrenStats[$child->id]['mock_exams_taken'] > 0)
                                <flux:text class="text-xs text-purple-600 dark:text-purple-400 mt-1">
                                    Best: {{ $childrenStats[$child->id]['best_mock_score'] }}%
                                </flux:text>
                                @endif
                            </div>

                            {{-- Subjects --}}
                            <div class="p-4 rounded-lg bg-neutral-50 dark:bg-neutral-700/30">
                                <div class="flex items-center justify-between mb-2">
                                    <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C6.5 6.253 2 10.998 2 17s4.5 10.747 10 10.747c5.5 0 10-4.998 10-10.747S17.5 6.253 12 6.253z"></path>
                                    </svg>
                                    <flux:text class="text-xs font-bold text-indigo-600 dark:text-indigo-400">Active</flux:text>
                                </div>
                                <flux:text class="text-xs text-neutral-600 dark:text-neutral-400 mb-2 block">{{ __('Subjects') }}</flux:text>
                                <div class="text-sm font-bold text-neutral-900 dark:text-white">
                                    {{ $childrenStats[$child->id]['subjects_enrolled'] }}
                                </div>
                            </div>
                        </div>

                        {{-- Quick Action --}}
                        <flux:button href="{{ route('dashboard') }}" variant="subtle" size="sm" class="w-full" wire:navigate>
                            {{ __('View Full Profile') }} →
                        </flux:button>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>

        {{-- Quick Actions Section --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <a href="{{ route('pricing') }}" class="group p-6 rounded-2xl border border-neutral-200 dark:border-neutral-700 hover:border-blue-500 dark:hover:border-blue-400 bg-white dark:bg-neutral-800 hover:shadow-lg transition-all">
                <div class="p-3 w-fit rounded-lg bg-blue-100 dark:bg-blue-900/30 group-hover:bg-blue-200 dark:group-hover:bg-blue-900/50 transition-colors mb-4">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <flux:heading size="sm" class="font-bold text-neutral-900 dark:text-white mb-1">{{ __('Plans & Pricing') }}</flux:heading>
                <flux:text class="text-sm text-neutral-600 dark:text-neutral-400">{{ __('View subscription options') }}</flux:text>
            </a>

            <a href="{{ route('profile.edit') }}" class="group p-6 rounded-2xl border border-neutral-200 dark:border-neutral-700 hover:border-purple-500 dark:hover:border-purple-400 bg-white dark:bg-neutral-800 hover:shadow-lg transition-all">
                <div class="p-3 w-fit rounded-lg bg-purple-100 dark:bg-purple-900/30 group-hover:bg-purple-200 dark:group-hover:bg-purple-900/50 transition-colors mb-4">
                    <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-2a6 6 0 0112 0v2zm0 0h6v-2a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                </div>
                <flux:heading size="sm" class="font-bold text-neutral-900 dark:text-white mb-1">{{ __('Manage Students') }}</flux:heading>
                <flux:text class="text-sm text-neutral-600 dark:text-neutral-400">{{ __('Link or remove students') }}</flux:text>
            </a>

            <a href="{{ route('analytics') }}" class="group p-6 rounded-2xl border border-neutral-200 dark:border-neutral-700 hover:border-red-500 dark:hover:border-red-400 bg-white dark:bg-neutral-800 hover:shadow-lg transition-all">
                <div class="p-3 w-fit rounded-lg bg-red-100 dark:bg-red-900/30 group-hover:bg-red-200 dark:group-hover:bg-red-900/50 transition-colors mb-4">
                    <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <flux:heading size="sm" class="font-bold text-neutral-900 dark:text-white mb-1">{{ __('View Analytics') }}</flux:heading>
                <flux:text class="text-sm text-neutral-600 dark:text-neutral-400">{{ __('Detailed performance reports') }}</flux:text>
            </a>
        </div>
    </div>
</div>
