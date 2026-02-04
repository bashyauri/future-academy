<x-filament::page>
    <div class="space-y-6">
        {{-- Page Header --}}
        <div class="mb-4">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Maintenance Tools</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Run system commands and manage application maintenance</p>
        </div>

        {{-- Quick Stats Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- Optimize Card --}}
            <x-filament::card class="bg-blue-50 dark:bg-blue-950 border-l-4 border-l-blue-500">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <p class="text-sm font-medium text-blue-600 dark:text-blue-300">Quick Optimize</p>
                        <p class="text-xs text-blue-500 dark:text-blue-400 mt-1">Compile & cache</p>
                    </div>
                    <x-filament::icon icon="heroicon-o-rocket-launch" class="h-8 w-8 text-blue-200 dark:text-blue-700 opacity-50" />
                </div>
                <x-filament::button wire:click="runCommand('optimize')" wire:loading.attr="disabled" wire:confirm="Run optimize command?" class="w-full">
                    <x-filament::loading-indicator wire:loading wire:target="runCommand" class="h-4 w-4" />
                    <span wire:loading.remove wire:target="runCommand">Run Now</span>
                </x-filament::button>
            </x-filament::card>

            {{-- Cache Clear Card --}}
            <x-filament::card class="bg-orange-50 dark:bg-orange-950 border-l-4 border-l-orange-500">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <p class="text-sm font-medium text-orange-600 dark:text-orange-300">Clear Cache</p>
                        <p class="text-xs text-orange-500 dark:text-orange-400 mt-1">Reset all caches</p>
                    </div>
                    <x-filament::icon icon="heroicon-o-arrow-path" class="h-8 w-8 text-orange-200 dark:text-orange-700 opacity-50" />
                </div>
                <x-filament::button wire:click="runCommand('cache:clear')" wire:loading.attr="disabled" wire:confirm="Clear all caches?" class="w-full" color="warning">
                    <x-filament::loading-indicator wire:loading wire:target="runCommand" class="h-4 w-4" />
                    <span wire:loading.remove wire:target="runCommand">Run Now</span>
                </x-filament::button>
            </x-filament::card>

            {{-- Database Migrate Card --}}
            <x-filament::card class="bg-cyan-50 dark:bg-cyan-950 border-l-4 border-l-cyan-500">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <p class="text-sm font-medium text-cyan-600 dark:text-cyan-300">Run Migrations</p>
                        <p class="text-xs text-cyan-500 dark:text-cyan-400 mt-1">Apply pending migrations</p>
                    </div>
                    <x-filament::icon icon="heroicon-o-circle-stack" class="h-8 w-8 text-cyan-200 dark:text-cyan-700 opacity-50" />
                </div>
                <x-filament::button wire:click="runCommand('migrate')" wire:loading.attr="disabled" wire:confirm="WARNING: This will run database migrations! Are you sure?" class="w-full" color="info">
                    <x-filament::loading-indicator wire:loading wire:target="runCommand" class="h-4 w-4" />
                    <span wire:loading.remove wire:target="runCommand">Run Now</span>
                </x-filament::button>
            </x-filament::card>
        </div>

        {{-- Command Selector Card --}}
        <x-filament::card>
            <div class="space-y-4">
                <div class="flex items-center space-x-3">
                    <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
                        <x-filament::icon icon="heroicon-o-command-line" class="h-5 w-5 text-blue-600 dark:text-blue-300" />
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white">Run Custom Command</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Select and run any maintenance command</p>
                    </div>
                </div>
                <div class="flex items-end gap-3">
                    <div class="flex-1">
                        <x-filament::input.wrapper label="Command">
                            <x-filament::input.select wire:model.live="command">
                                @foreach($this->getAllowedCommands() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    </div>
                    <x-filament::button wire:click="runCommand()" wire:loading.attr="disabled" wire:confirm="Are you sure you want to run this command?" icon="heroicon-m-play">
                        <x-filament::loading-indicator wire:loading wire:target="runCommand" class="h-4 w-4" />
                        <span wire:loading.remove wire:target="runCommand">Run</span>
                    </x-filament::button>
                </div>
            </div>
        </x-filament::card>

        {{-- Quick Actions Sections --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- Cache & Optimization --}}
            <x-filament::card class="border-l-4 border-l-blue-500">
                <div class="space-y-3">
                    <div class="flex items-center space-x-2">
                        <x-filament::icon icon="heroicon-o-bolt" class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                        <h3 class="font-semibold text-gray-900 dark:text-white">Cache & Optimization</h3>
                    </div>
                    <div class="space-y-2">
                        @foreach(\App\Enums\MaintenanceCommandType::cases() as $commandType)
                            @if(in_array($commandType->value, ['optimize', 'optimize:clear', 'cache:clear', 'config:clear', 'view:clear', 'route:clear', 'event:clear']))
                                <x-filament::button wire:click="runCommand('{{ $commandType->value }}')" wire:loading.attr="disabled" wire:target="runCommand" wire:confirm="{{ $commandType->isDestructive() ? 'WARNING: This is a destructive action! Are you sure?' : 'Run ' . $commandType->value . '?' }}" size="sm" class="w-full" :color="$commandType->color()">
                                    {{ str_replace([':', '-'], [': ', ' '], ucwords($commandType->name, ':-')) }}
                                </x-filament::button>
                            @endif
                        @endforeach
                    </div>
                </div>
            </x-filament::card>

            {{-- System Commands --}}
            <x-filament::card class="border-l-4 border-l-orange-500">
                <div class="space-y-3">
                    <div class="flex items-center space-x-2">
                        <x-filament::icon icon="heroicon-o-cog-6-tooth" class="h-5 w-5 text-orange-600 dark:text-orange-400" />
                        <h3 class="font-semibold text-gray-900 dark:text-white">System</h3>
                    </div>
                    <div class="space-y-2">
                        @foreach(\App\Enums\MaintenanceCommandType::cases() as $commandType)
                            @if(in_array($commandType->value, ['queue:restart', 'storage:link']))
                                <x-filament::button wire:click="runCommand('{{ $commandType->value }}')" wire:loading.attr="disabled" wire:target="runCommand" wire:confirm="{{ $commandType->isDestructive() ? 'WARNING: This is a destructive action! Are you sure?' : 'Run ' . $commandType->value . '?' }}" size="sm" class="w-full" :color="$commandType->color()">
                                    {{ str_replace([':', '-'], [': ', ' '], ucwords($commandType->name, ':-')) }}
                                </x-filament::button>
                            @endif
                        @endforeach
                    </div>
                </div>
            </x-filament::card>

            {{-- Database Commands --}}
            <x-filament::card class="border-l-4 border-l-cyan-500">
                <div class="space-y-3">
                    <div class="flex items-center space-x-2">
                        <x-filament::icon icon="heroicon-o-circle-stack" class="h-5 w-5 text-cyan-600 dark:text-cyan-400" />
                        <h3 class="font-semibold text-gray-900 dark:text-white">Database</h3>
                    </div>
                    <div class="space-y-2">
                        @foreach(\App\Enums\MaintenanceCommandType::cases() as $commandType)
                            @if(in_array($commandType->value, ['migrate', 'migrate:rollback', 'db:seed']))
                                <x-filament::button wire:click="runCommand('{{ $commandType->value }}')" wire:loading.attr="disabled" wire:target="runCommand" wire:confirm="{{ $commandType->isDestructive() ? 'WARNING: This is a destructive action! Are you sure?' : 'Run ' . $commandType->value . '?' }}" size="sm" class="w-full" :color="$commandType->color()">
                                    {{ str_replace([':', '-'], [': ', ' '], ucwords($commandType->name, ':-')) }}
                                </x-filament::button>
                            @endif
                        @endforeach
                    </div>
                </div>
            </x-filament::card>
        </div>

        {{-- Command Output --}}
        <x-filament::card class="border-l-4 border-l-green-500">
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <x-filament::icon icon="heroicon-o-command-line" class="h-5 w-5 text-green-600 dark:text-green-400" />
                        <h3 class="font-semibold text-gray-900 dark:text-white">Command Output</h3>
                    </div>
                    @if($lastRunAt)
                        <x-filament::badge color="success">Last run: {{ $lastRunAt }}</x-filament::badge>
                    @endif
                </div>
                <pre class="bg-gray-900 text-green-400 p-4 rounded-lg overflow-x-auto text-xs max-h-96 overflow-y-auto font-mono leading-relaxed">{{ $output ?? 'No command run yet. Select and run a command to see output.' }}</pre>
            </div>
        </x-filament::card>
    </div>
</x-filament::page>
