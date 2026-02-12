@php
    // Receive videoId from viewData
    $videoId = $videoId ?? null;
@endphp

@if ($videoId)
    @php
        try {
            $service = app(\App\Services\BunnyStreamService::class);
            $embedUrl = $service->getEmbedUrl(
                $videoId,
                now()->addDay()->timestamp
            );
        } catch (\Exception $e) {
            $embedUrl = null;
        }
    @endphp

    @if ($embedUrl)
        <div class="space-y-4">
            <div class="flex items-center gap-2 p-3 bg-success-50 border border-success-200 rounded-xl">
                <x-filament::icon
                    icon="heroicon-o-check-circle"
                    class="h-5 w-5 text-success-500"
                />
                <span class="text-sm font-medium text-success-800">
                    Ready to watch
                </span>
            </div>

            <div class="rounded-xl overflow-hidden border border-gray-200 shadow-sm aspect-video">
                <iframe
                    src="{{ $embedUrl }}"
                    loading="lazy"
                    class="w-full h-full"
                    allow="accelerometer; gyroscope; autoplay; encrypted-media; picture-in-picture"
                    allowfullscreen>
                </iframe>
            </div>

            <p class="text-xs text-gray-500">
                This is how students will see the video.
            </p>
        </div>
    @endif
@endif
