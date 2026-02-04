<x-filament::page>
    <!-- Page Header -->
    <x-slot name="header">
        <div class="flex flex-col gap-1 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">
                    Subscription Debugger
                </h1>
                <p class="mt-1.5 text-lg text-gray-500 dark:text-gray-400">
                    Monitor & manage per-student Paystack subscriptions
                </p>
            </div>

            <x-filament::button
                wire:click="syncSubscriptions"
                wire:confirm="Sync all subscription codes with Paystack? This may take 30–90 seconds."
                color="primary"
                size="md"
                icon="heroicon-o-arrow-path"
                wire:loading.attr="disabled"
            >
                <x-filament::icon icon="heroicon-o-arrow-path" class="h-5 w-5 animate-spin" wire:loading />
                <span wire:loading.remove>Sync Now</span>
                <span wire:loading>Syncing…</span>
            </x-filament::button>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Statistics Overview -->
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-chart-bar-square" class="h-5 w-5 text-gray-500" />
                    <span>Subscription Overview</span>
                </div>
            </x-slot>
            <x-slot name="description">Real-time statistics and insights for all subscriptions</x-slot>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <!-- Total -->
                <x-filament::card class="transition hover:shadow-md hover:scale-[1.02] border-t-4 border-blue-500">
                    <div class="flex items-center justify-between mb-2">
                        <x-filament::icon icon="heroicon-o-chart-pie" class="h-7 w-7 text-blue-600 dark:text-blue-400" />
                        <x-filament::badge color="blue" size="sm">Total</x-filament::badge>
                    </div>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $this->stats['total'] ?? 0 }}</p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">All subscriptions</p>
                </x-filament::card>

                <!-- Active -->
                <x-filament::card class="transition hover:shadow-md hover:scale-[1.02] border-t-4 border-green-500">
                    <div class="flex items-center justify-between mb-2">
                        <x-filament::icon icon="heroicon-o-check-circle" class="h-7 w-7 text-green-600 dark:text-green-400" />
                        <x-filament::badge color="success" size="sm">Active</x-filament::badge>
                    </div>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $this->stats['active'] ?? 0 }}</p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Currently active</p>
                </x-filament::card>

                <!-- Inactive -->
                <x-filament::card class="transition hover:shadow-md hover:scale-[1.02] border-t-4 border-amber-500">
                    <div class="flex items-center justify-between mb-2">
                        <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-7 w-7 text-amber-600 dark:text-amber-400" />
                        <x-filament::badge color="warning" size="sm">Inactive</x-filament::badge>
                    </div>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $this->stats['inactive'] ?? 0 }}</p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Cancelled or inactive</p>
                </x-filament::card>

                <!-- With Student -->
                <x-filament::card class="transition hover:shadow-md hover:scale-[1.02] border-t-4 border-cyan-500">
                    <div class="flex items-center justify-between mb-2">
                        <x-filament::icon icon="heroicon-o-user" class="h-7 w-7 text-cyan-600 dark:text-cyan-400" />
                        <x-filament::badge color="info" size="sm">Linked</x-filament::badge>
                    </div>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $this->stats['with_student_id'] ?? 0 }}</p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Linked to students</p>
                </x-filament::card>

                <!-- Without Student -->
                <x-filament::card class="transition hover:shadow-md hover:scale-[1.02] border-t-4 border-rose-500">
                    <div class="flex items-center justify-between mb-2">
                        <x-filament::icon icon="heroicon-o-user-minus" class="h-7 w-7 text-rose-600 dark:text-rose-400" />
                        <x-filament::badge color="danger" size="sm">Unlinked</x-filament::badge>
                    </div>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $this->stats['without_student_id'] ?? 0 }}</p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">No student linked</p>
                </x-filament::card>
            </div>
        </x-filament::section>

        <!-- Quick Actions & Filter -->
        <div class="grid gap-4 md:grid-cols-2">
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-funnel" class="h-5 w-5 text-gray-500" />
                        <span>Filters & Actions</span>
                    </div>
                </x-slot>
                <x-slot name="description">Quickly filter and search subscriptions</x-slot>

                <div class="space-y-4">
                    <!-- Filter Controls -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2.5">
                            <x-filament::icon icon="heroicon-o-adjustments-horizontal" class="inline h-4 w-4 mr-1" />
                            Filter by Status
                        </label>
                        <div class="flex flex-wrap gap-2">
                            <x-filament::button
                                wire:click="$set('filterType', 'all')"
                                :color="$filterType === 'all' ? 'primary' : 'gray'"
                                size="sm"
                                icon="heroicon-o-squares-2x2"
                                class="flex-1 min-w-[90px]"
                            >All ({{ $this->stats['total'] ?? 0 }})</x-filament::button>

                            <x-filament::button
                                wire:click="$set('filterType', 'active')"
                                :color="$filterType === 'active' ? 'success' : 'gray'"
                                size="sm"
                                icon="heroicon-o-check-circle"
                                class="flex-1 min-w-[90px]"
                            >Active ({{ $this->stats['active'] ?? 0 }})</x-filament::button>

                            <x-filament::button
                                wire:click="$set('filterType', 'inactive')"
                                :color="$filterType === 'inactive' ? 'warning' : 'gray'"
                                size="sm"
                                icon="heroicon-o-x-circle"
                                class="flex-1 min-w-[90px]"
                            >Inactive ({{ $this->stats['inactive'] ?? 0 }})</x-filament::button>
                        </div>
                    </div>
                </div>
            </x-filament::section>

            <!-- Debug (collapsible) -->
            @if($this->debugOutput)
                <x-filament::section collapsible collapsed icon="heroicon-o-code-bracket">
                    <x-slot name="heading">
                        <div class="flex items-center gap-2">
                            <x-filament::icon icon="heroicon-o-bug-ant" class="h-5 w-5 text-gray-500" />
                            <span>Debug Output</span>
                        </div>
                    </x-slot>
                    <x-slot name="description">Detailed debugging information for subscription checks</x-slot>
                    @if($selectedCode)
                        <x-slot name="aside">
                            <x-filament::badge color="purple" icon="heroicon-o-hashtag">{{ $selectedCode }}</x-filament::badge>
                        </x-slot>
                    @endif

                    <div class="mt-4 rounded-xl bg-gray-950 p-5 ring-1 ring-gray-800 overflow-hidden">
                        <pre class="text-xs font-mono text-green-400 overflow-x-auto max-h-80 whitespace-pre-wrap break-words"><code>{{ $this->debugOutput }}</code></pre>
                    </div>
                </x-filament::section>
            @endif
        </div>

        <!-- Subscriptions Table -->
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-table-cells" class="h-5 w-5 text-gray-500" />
                    <span>Subscriptions List</span>
                </div>
            </x-slot>
            <x-slot name="description">All subscriptions matching your current filter criteria (Latest 50)</x-slot>
            <x-slot name="aside">
                <x-filament::badge color="gray" icon="heroicon-o-list-bullet">{{ count($this->subscriptions) }} records</x-filament::badge>
            </x-slot>

            @if(count($this->subscriptions) > 0)
                <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm mx-2">
                    <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800/60">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400">
                                    <div class="flex items-center gap-1.5">
                                        <x-filament::icon icon="heroicon-o-hashtag" class="h-4 w-4" />
                                        ID
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400">
                                    <div class="flex items-center gap-1.5">
                                        <x-filament::icon icon="heroicon-o-envelope" class="h-4 w-4" />
                                        User Email
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400">
                                    <div class="flex items-center gap-1.5">
                                        <x-filament::icon icon="heroicon-o-academic-cap" class="h-4 w-4" />
                                        Student
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400">
                                    <div class="flex items-center gap-1.5">
                                        <x-filament::icon icon="heroicon-o-key" class="h-4 w-4" />
                                        Code
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400">
                                    <div class="flex items-center gap-1.5">
                                        <x-filament::icon icon="heroicon-o-signal" class="h-4 w-4" />
                                        Status
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400">
                                    <div class="flex items-center gap-1.5">
                                        <x-filament::icon icon="heroicon-o-tag" class="h-4 w-4" />
                                        Type
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400">
                                    <div class="flex items-center gap-1.5">
                                        <x-filament::icon icon="heroicon-o-calendar-days" class="h-4 w-4" />
                                        Expires
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <x-filament::icon icon="heroicon-o-cog-6-tooth" class="h-4 w-4" />
                                        Actions
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800 bg-white dark:bg-gray-900">
                            @forelse ($this->subscriptions as $sub)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors duration-150">
                                    <td class="px-6 py-4 text-sm font-semibold text-gray-900 dark:text-white">
                                        #{{ $sub['id'] }}
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <div class="flex items-center gap-2">
                                            <x-filament::icon icon="heroicon-o-user-circle" class="h-5 w-5 text-gray-400" />
                                            <span class="text-gray-700 dark:text-gray-300 max-w-[250px] truncate" title="{{ $sub['user']['email'] ?? 'N/A' }}">
                                                {{ $sub['user']['email'] ?? 'No Email' }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        @if($sub['student_id'])
                                            <x-filament::badge color="info" icon="heroicon-o-user">
                                                Student #{{ $sub['student_id'] }}
                                            </x-filament::badge>
                                        @else
                                            <x-filament::badge color="gray" icon="heroicon-o-minus">
                                                Not Linked
                                            </x-filament::badge>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <button
                                            wire:click="checkSubscription('{{ $sub['subscription_code'] }}')"
                                            class="group flex items-center gap-1.5 font-mono text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 focus:outline-none transition-colors"
                                            title="Click to view full debug info">
                                            <x-filament::icon icon="heroicon-o-magnifying-glass" class="h-4 w-4 opacity-0 group-hover:opacity-100 transition-opacity" />
                                            <span class="group-hover:underline">{{ substr($sub['subscription_code'] ?? 'NULL', 0, 14) }}…</span>
                                        </button>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        @if($sub['status'] === 'active' && $sub['is_active'])
                                            <x-filament::badge color="success" icon="heroicon-o-check-circle" size="md">
                                                Active
                                            </x-filament::badge>
                                        @elseif($sub['status'] === 'cancelled')
                                            <x-filament::badge color="danger" icon="heroicon-o-x-circle" size="md">
                                                Cancelled
                                            </x-filament::badge>
                                        @else
                                            <x-filament::badge color="warning" icon="heroicon-o-clock" size="md">
                                                Inactive
                                            </x-filament::badge>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <x-filament::badge color="gray" size="sm">
                                            {{ ucfirst($sub['type'] ?? 'standard') }}
                                        </x-filament::badge>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                                        @if($sub['ends_at'])
                                            <div class="flex items-center gap-1.5">
                                                <x-filament::icon icon="heroicon-o-calendar" class="h-4 w-4 text-gray-400" />
                                                <span class="font-medium">{{ \Illuminate\Support\Str::substr($sub['ends_at'], 0, 10) }}</span>
                                            </div>
                                        @else
                                            <span class="text-gray-400 italic">No expiry</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            @if($sub['status'] !== 'active' || !$sub['is_active'])
                                                <x-filament::icon-button
                                                    icon="heroicon-o-check-circle"
                                                    wire:click="activateSubscription({{ $sub['id'] }})"
                                                    wire:confirm="Activate subscription #{{ $sub['id'] }}?"
                                                    color="success"
                                                    size="sm"
                                                    tooltip="Activate Subscription"
                                                />
                                            @endif

                                            @if($sub['status'] !== 'cancelled')
                                                <x-filament::icon-button
                                                    icon="heroicon-o-x-circle"
                                                    wire:click="cancelSubscription({{ $sub['id'] }})"
                                                    wire:confirm="Cancel subscription #{{ $sub['id'] }}? This cannot be undone."
                                                    color="warning"
                                                    size="sm"
                                                    tooltip="Cancel Subscription"
                                                />
                                            @endif

                                            <x-filament::icon-button
                                                icon="heroicon-o-trash"
                                                wire:click="deleteSubscription({{ $sub['id'] }})"
                                                wire:confirm="Delete subscription #{{ $sub['id'] }} permanently? This is irreversible!"
                                                color="danger"
                                                size="sm"
                                                tooltip="Delete Subscription"
                                            />
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-20 px-6">
                                        <div class="flex flex-col items-center justify-center gap-4 text-gray-500 dark:text-gray-400">
                                            <div class="rounded-full bg-gray-100 dark:bg-gray-800 p-6">
                                                <x-filament::icon icon="heroicon-o-inbox" class="h-16 w-16 text-gray-400" />
                                            </div>
                                            <div class="text-center">
                                                <p class="text-lg font-semibold text-gray-700 dark:text-gray-300">No subscriptions found</p>
                                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Try adjusting the filter or sync with Paystack to load data</p>
                                            </div>
                                            <x-filament::button
                                                wire:click="syncSubscriptions"
                                                color="primary"
                                                size="sm"
                                                icon="heroicon-o-arrow-path"
                                            >
                                                Sync Subscriptions
                                            </x-filament::button>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @else
                <div class="py-20 px-6 text-center">
                    <div class="flex flex-col items-center justify-center gap-4">
                        <div class="rounded-full bg-gray-100 dark:bg-gray-800 p-6">
                            <x-filament::icon icon="heroicon-o-inbox" class="h-16 w-16 text-gray-400" />
                        </div>
                        <div>
                            <p class="text-lg font-semibold text-gray-700 dark:text-gray-300">No subscriptions to display</p>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Sync with Paystack to load subscription data</p>
                        </div>
                        <x-filament::button
                            wire:click="syncSubscriptions"
                            color="primary"
                            icon="heroicon-o-arrow-path"
                        >
                            Sync with Paystack
                        </x-filament::button>
                    </div>
                </div>
            @endif
        </x-filament::section>
    </div>
</x-filament::page>
