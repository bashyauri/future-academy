@if($videoUrl && $videoType === 'local')
    <div class="fi-section rounded-lg border border-gray-300 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
        <div class="space-y-4">
            <div>
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                    {{ __('Video Preview') }}
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    {{ __('Status') }}:
                    <span class="font-medium">
                        @if($videoStatus === 'ready')
                            <span class="text-green-600 dark:text-green-400">{{ __('Ready') }}</span>
                        @elseif($videoStatus === 'processing')
                            <span class="text-amber-600 dark:text-amber-400">{{ __('Processing') }}</span>
                        @elseif($videoStatus === 'failed')
                            <span class="text-red-600 dark:text-red-400">{{ __('Failed') }}</span>
                        @else
                            <span class="text-blue-600 dark:text-blue-400">{{ __('Pending') }}</span>
                        @endif
                    </span>
                </p>
            </div>

            <div class="bg-black rounded overflow-hidden">
                <video class="w-full max-h-96 bg-black" controls>
                    <source src="{{ $videoUrl }}" type="video/mp4">
                    {{ __('Your browser does not support the video tag.') }}
                </video>
            </div>

            <div class="text-xs text-gray-500 dark:text-gray-400 break-all">
                <span class="font-medium">{{ __('File') }}:</span> {{ basename($videoPath) }}
            </div>
        </div>
    </div>
@endif
