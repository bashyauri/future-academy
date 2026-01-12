<x-filament::page>
    <div class="space-y-6">
        {{-- Command Selector --}}
        <x-filament::card>
            <div class="space-y-3">
                <h3 class="text-base font-semibold">Run Command</h3>
                <div class="flex items-end gap-3">
                    <div class="flex-1">
                        <x-filament::input.wrapper>
                            <x-filament::input.select wire:model.live="command">
                                @foreach($this->getAllowedCommands() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    </div>
                    <x-filament::button
                        wire:click="runCommand()"
                        wire:loading.attr="disabled"
                        wire:confirm="Are you sure you want to run this command?">
                        <x-filament::loading-indicator class="h-4 w-4" wire:loading wire:target="runCommand" />
                        <span wire:loading.remove wire:target="runCommand">Run</span>
                    </x-filament::button>
                </div>
            </div>
        </x-filament::card>

        {{-- Quick Actions --}}
        <x-filament::card>
            <div class="space-y-4">
                <h3 class="text-base font-semibold">Quick Actions</h3>

                {{-- Cache Commands --}}
                <div>
                    <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Cache & Optimization</h4>
                    <div class="flex flex-wrap gap-2">
                        @foreach(\App\Enums\MaintenanceCommandType::cases() as $commandType)
                            @if(in_array($commandType->value, ['optimize', 'optimize:clear', 'cache:clear', 'config:clear', 'view:clear', 'route:clear', 'event:clear']))
                                <x-filament::button
                                    size="sm"
                                    :color="$commandType->color()"
                                    :icon="$commandType->icon()"
                                    wire:click="runCommand('{{ $commandType->value }}')"
                                    wire:loading.attr="disabled"
                                    wire:target="runCommand"
                                    wire:confirm="{{ $commandType->isDestructive() ? 'WARNING: This is a destructive action! Are you sure?' : 'Run ' . $commandType->value . '?' }}">
                                    {{ str_replace([':', '-'], [': ', ' '], ucwords($commandType->name, ':-')) }}
                                </x-filament::button>
                            @endif
                        @endforeach
                    </div>
                </div>

                {{-- System Commands --}}
                <div>
                    <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">System</h4>
                    <div class="flex flex-wrap gap-2">
                        @foreach(\App\Enums\MaintenanceCommandType::cases() as $commandType)
                            @if(in_array($commandType->value, ['queue:restart', 'storage:link']))
                                <x-filament::button
                                    size="sm"
                                    :color="$commandType->color()"
                                    :icon="$commandType->icon()"
                                    wire:click="runCommand('{{ $commandType->value }}')"
                                    wire:loading.attr="disabled"
                                    wire:target="runCommand"
                                    wire:confirm="{{ $commandType->isDestructive() ? 'WARNING: This is a destructive action! Are you sure?' : 'Run ' . $commandType->value . '?' }}">
                                    {{ str_replace([':', '-'], [': ', ' '], ucwords($commandType->name, ':-')) }}
                                </x-filament::button>
                            @endif
                        @endforeach
                    </div>
                </div>

                {{-- Database Commands --}}
                <div>
                    <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Database</h4>
                    <div class="flex flex-wrap gap-2">
                        @foreach(\App\Enums\MaintenanceCommandType::cases() as $commandType)
                            @if(in_array($commandType->value, ['migrate', 'migrate:rollback', 'db:seed']))
                                <x-filament::button
                                    size="sm"
                                    :color="$commandType->color()"
                                    :icon="$commandType->icon()"
                                    wire:click="runCommand('{{ $commandType->value }}')"
                                    wire:loading.attr="disabled"
                                    wire:target="runCommand"
                                    wire:confirm="{{ $commandType->isDestructive() ? 'WARNING: This is a destructive action! Are you sure?' : 'Run ' . $commandType->value . '?' }}">
                                    {{ str_replace([':', '-'], [': ', ' '], ucwords($commandType->name, ':-')) }}
                                </x-filament::button>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </x-filament::card>

        {{-- Output --}}
        <x-filament::card class="!bg-gray-900 dark:!bg-gray-950">
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <h3 class="text-sm font-semibold text-gray-100">Command Output</h3>
                        @if($lastRunAt)
                            <span class="text-xs text-gray-400">Last run: {{ $lastRunAt }}</span>
                        @endif
                    </div>
                    <x-filament::badge color="warning" size="sm" wire:loading wire:target="runCommand">
                        <x-filament::loading-indicator class="h-3 w-3" />
                        Running...
                    </x-filament::badge>
                </div>
                <pre class="text-xs text-green-400 whitespace-pre-wrap leading-relaxed font-mono p-4 bg-black rounded-lg overflow-auto max-h-96">{{ $output ?? 'No command run yet. Select and run a command to see output.' }}</pre>
            </div>
        </x-filament::card>
    </div>
</x-filament::page>
