@php
    $videoId = $getState();
    $fieldWrapperView = $getFieldWrapperView();
    $statePath = $getStatePath();
@endphp

<x-dynamic-component
    :component="$fieldWrapperView"
    :field="$field"
>
    <div x-data="bunnyPaste($wire.entangle('{{ $statePath }}'))">
        <input
            type="text"
            placeholder="Paste Bunny URL or Video ID..."
            @input="onInput($event)"
            {{ $applyStateBindingModifiers('wire:model') }}="{{ $statePath }}"
            class="fi-input block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white focus:border-primary-500 dark:focus:border-primary-500 focus:ring-1 focus:ring-primary-500 disabled:opacity-50 rounded-lg shadow-sm"
        />
        <p class="mt-2 text-xs leading-tight text-gray-600 dark:text-gray-400">
            Paste a Bunny video URL or ID from your dashboard
        </p>
        <p x-show="error" class="mt-2 text-xs leading-tight text-danger-600 dark:text-danger-400" x-text="error"></p>
    </div>
</x-dynamic-component>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('bunnyPaste', (videoIdEntangle) => ({
        videoId: videoIdEntangle,
        validating: false,
        error: '',

        async onInput(event) {
            let input = event.target.value.trim();

            if (!input) {
                this.error = '';
                this.videoId = '';
                return;
            }

            // Extract video ID from URL if necessary
            let videoId = this.extractVideoId(input);

            if (!videoId) {
                this.error = 'Invalid format. Paste a Bunny URL or video ID.';
                return;
            }

            // Validate with Bunny
            this.validating = true;
            this.error = '';

            try {
                const res = await fetch('/admin/video/validate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ video_id: videoId }),
                });

                if (!res.ok) {
                    const data = await res.json().catch(() => ({}));
                    this.error = data.error || 'Video not found on Bunny';
                    this.videoId = '';
                } else {
                    this.videoId = videoId;
                    this.error = '';
                }
            } catch (err) {
                this.error = 'Failed to validate: ' + err.message;
                this.videoId = '';
            } finally {
                this.validating = false;
            }
        },

        extractVideoId(input) {
            // Format 1: URL - extract last part: https://iframe.mediadelivery.net/embed/abc123/[ID]
            const urlMatch = input.match(/embed\/[^\/]+\/([a-f0-9\-]+)/i);
            if (urlMatch && urlMatch[1].length === 36) return urlMatch[1];

            // Format 2: Direct GUID (UUID format with dashes and 36 chars)
            if (/^[a-f0-9\-]{36}$/i.test(input)) {
                return input;
            }

            return null;
        },
    }));
});
</script>

