<x-filament::page>
    <div class="space-y-6">
        {{-- Page Header --}}
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Application Logs</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">View and manage application log files</p>
        </div>

        {{-- Controls Card --}}
        <x-filament::card>
            <div class="space-y-4">
                <div class="grid gap-4 grid-cols-1 md:grid-cols-4">
                    {{-- Log File Selection --}}
                    <div>
                        <x-filament::input.wrapper label="Log File">
                            <x-filament::input.select wire:model.live="selectedFile">
                                <option value="">Select a log file...</option>
                                @foreach($this->logFiles as $file)
                                    <option value="{{ $file['path'] }}">
                                        {{ $file['name'] }} ({{ number_format($file['size'] / 1024, 2) }} KB)
                                    </option>
                                @endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    </div>

                    {{-- Line Count --}}
                    <div>
                        <x-filament::input.wrapper label="Show Lines">
                            <x-filament::input.select wire:model.live="lineCount">
                                <option value="50">Last 50</option>
                                <option value="100">Last 100</option>
                                <option value="250">Last 250</option>
                                <option value="500">Last 500</option>
                                <option value="1000">Last 1000</option>
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    </div>

                    {{-- Filter Level --}}
                    <div>
                        <x-filament::input.wrapper label="Filter Level">
                            <x-filament::input.select wire:model.live="filterLevel">
                                <option value="">All</option>
                                <option value="ERROR">Errors</option>
                                <option value="WARNING">Warnings</option>
                                <option value="INFO">Info</option>
                                <option value="DEBUG">Debug</option>
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    </div>

                    {{-- Search --}}
                    <div>
                        <x-filament::input.wrapper label="Search">
                            <x-filament::input
                                type="text"
                                wire:model.live.debounce.500ms="searchQuery"
                                placeholder="Search logs..." />
                        </x-filament::input.wrapper>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex flex-wrap gap-2 pt-2 border-t border-gray-200 dark:border-gray-700">
                    <x-filament::button
                        wire:click="downloadLog"
                        color="info"
                        size="sm"
                        icon="heroicon-o-arrow-down-tray">
                        Download
                    </x-filament::button>
                    <x-filament::button
                        wire:click="clearSelectedLog"
                        wire:confirm="Clear this log file? This cannot be undone."
                        color="warning"
                        size="sm"
                        icon="heroicon-o-trash">
                        Clear File
                    </x-filament::button>
                    <x-filament::button
                        wire:click="clearAllLogs"
                        wire:confirm="Clear ALL log files? This cannot be undone."
                        color="danger"
                        size="sm"
                        icon="heroicon-o-exclamation-triangle">
                        Clear All
                    </x-filament::button>
                </div>
            </div>
        </x-filament::card>

        {{-- Log Content --}}
        <x-filament::card>
            <div class="space-y-3">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-900/30">
                            <x-filament::icon icon="heroicon-o-document-text" class="h-4 w-4 text-gray-600 dark:text-gray-400" />
                        </div>
                        <h3 class="font-semibold text-gray-900 dark:text-white">
                            @if($selectedFile)
                                {{ $selectedFile }}
                            @else
                                Log Content
                            @endif
                        </h3>
                    </div>
                    @if($selectedFile)
                        <x-filament::badge color="gray">
                            Live
                        </x-filament::badge>
                    @endif
                </div>

                {{-- Log Terminal --}}
                <div class="mt-4 rounded-lg border border-gray-200 bg-gray-950 p-4 font-mono text-xs dark:border-gray-700">
                    <div class="space-y-1 max-h-[600px] overflow-y-auto overflow-x-auto whitespace-pre-wrap break-words text-gray-200">
                        @php
                            $lines = explode("\n", $logContent);
                        @endphp

                        @foreach($lines as $line)
                            @php
                                $lineClass = 'text-gray-300';
                                if (stripos($line, 'ERROR') !== false) {
                                    $lineClass = 'text-red-400';
                                } elseif (stripos($line, 'WARNING') !== false || stripos($line, 'WARN') !== false) {
                                    $lineClass = 'text-amber-400';
                                } elseif (stripos($line, 'INFO') !== false) {
                                    $lineClass = 'text-blue-400';
                                } elseif (stripos($line, 'DEBUG') !== false) {
                                    $lineClass = 'text-purple-400';
                                }
                            @endphp
                            <div class="{{ $lineClass }}">{{ $line }}</div>
                        @endforeach
                    </div>
                </div>

                {{-- Info --}}
                <div class="mt-4 flex items-center gap-2 rounded-lg bg-blue-50 p-3 dark:bg-blue-900/20">
                    <x-filament::icon icon="heroicon-o-information-circle" class="h-5 w-5 text-blue-600 dark:text-blue-400 shrink-0" />
                    <p class="text-sm text-blue-800 dark:text-blue-300">
                        <span class="font-semibold">Tip:</span> Logs are shown in reverse order (newest first). Use filters to find specific entries.
                    </p>
                </div>
            </div>
        </x-filament::card>

        {{-- Log Stats --}}
        @if(count($this->logFiles) > 0)
            <x-filament::card>
                <div class="space-y-3">
                    <h3 class="font-semibold text-gray-900 dark:text-white">Log Files</h3>
                    <div class="grid gap-3 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($this->logFiles as $file)
                            <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-900/30 transition-colors cursor-pointer" wire:click="$set('selectedFile', '{{ $file['path'] }}')">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $file['name'] }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ number_format($file['size'] / 1024, 2) }} KB
                                            · {{ date('M d, H:i', $file['date']) }}
                                        </p>
                                    </div>
                                    <x-filament::icon icon="heroicon-o-chevron-right" class="h-4 w-4 text-gray-400 shrink-0" />
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </x-filament::card>
        @endif
    </div>
</x-filament::page>
