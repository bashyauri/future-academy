@php
    // Receive statePath from viewData
    $statePath = $statePath ?? 'data.video_url';
@endphp

<div
    x-data="videoChunkUploader({
        createUrl: '{{ route('admin.video.create') }}',
        uploadUrl: '{{ route('admin.video.upload-chunk') }}',
        csrf: '{{ csrf_token() }}',
        statePath: '{{ $statePath }}',
    })"
    class="space-y-4"
>

    <!-- DROP ZONE -->
    <div
        x-show="!uploading && !videoId"
        @drop.prevent="handleDrop($event)"
        @dragover.prevent="dragOver = true"
        @dragleave.prevent="dragOver = false"
        :class="dragOver ? 'border-primary-500 bg-primary-50' : 'border-gray-300'"
        class="border-2 border-dashed rounded-xl p-10 text-center transition hover:border-primary-400"
    >
        <x-filament::icon
            icon="heroicon-o-video-camera"
            class="mx-auto h-12 w-12 text-gray-400"
        />

        <p class="mt-3 text-sm font-medium text-gray-900">
            Drop video file here
        </p>

        <p class="mt-1 text-xs text-gray-500">
            MP4, MOV, AVI, WebM • Max 500MB
        </p>

        <div class="mt-4">
            <x-filament::button
                type="button"
                @click="$refs.fileInput.click()"
                color="gray"
                outlined
            >
                <x-filament::icon icon="heroicon-o-arrow-up-tray" class="w-4 h-4 mr-2" />
                Choose File
            </x-filament::button>
        </div>

        <input
            type="file"
            x-ref="fileInput"
            @change="handleFileSelect($event)"
            accept="video/*"
            class="hidden"
        >
    </div>

    <!-- PROGRESS -->
    <div x-show="uploading" class="space-y-2">
        <div class="flex justify-between text-sm">
            <span class="font-medium" x-text="fileName"></span>
            <span x-text="Math.round(progress) + '%'"></span>
        </div>

        <div class="w-full bg-gray-200 rounded-full h-2">
            <div
                class="bg-primary-600 h-2 rounded-full transition-all"
                :style="`width: ${progress}%`"
            ></div>
        </div>

        <p class="text-xs text-gray-500" x-text="statusMessage"></p>
    </div>

    <!-- SUCCESS -->
    <div
        x-show="videoId && !uploading"
        class="flex items-center gap-3 p-4 bg-success-50 border border-success-200 rounded-xl"
    >
        <x-filament::icon
            icon="heroicon-o-check-circle"
            class="h-5 w-5 text-success-500"
        />

        <div class="flex-1">
            <p class="text-sm font-medium text-success-900">
                Video uploaded successfully
            </p>

            <p class="text-xs text-success-700 mt-1">
                Video ID: <span x-text="videoId"></span>
            </p>
        </div>

        <x-filament::button
            type="button"
            size="sm"
            color="gray"
            @click="reset()"
        >
            Upload Different
        </x-filament::button>
    </div>

    <!-- ERROR -->
    <div
        x-show="error"
        class="flex items-center gap-3 p-4 bg-danger-50 border border-danger-200 rounded-xl"
    >
        <x-filament::icon
            icon="heroicon-o-exclamation-triangle"
            class="h-5 w-5 text-danger-500"
        />

        <div class="flex-1">
            <p class="text-sm font-medium text-danger-900">
                Upload failed
            </p>

            <p class="text-xs text-danger-700 mt-1" x-text="error"></p>
        </div>

        <x-filament::button
            type="button"
            size="sm"
            color="danger"
            @click="reset()"
        >
            Try Again
        </x-filament::button>
    </div>

</div>

@once
<script>
document.addEventListener('alpine:init', () => {

    Alpine.data('videoChunkUploader', (config) => ({

        file: null,
        fileName: '',
        uploading: false,
        progress: 0,
        videoId: '',
        error: null,
        dragOver: false,
        statusMessage: 'Preparing upload...',
        CHUNK_SIZE: 5 * 1024 * 1024,

        handleDrop(e) {
            this.dragOver = false;
            if (e.dataTransfer.files.length > 0) {
                this.processFile(e.dataTransfer.files[0]);
            }
        },

        handleFileSelect(e) {
            if (e.target.files.length > 0) {
                this.processFile(e.target.files[0]);
            }
        },

        processFile(file) {

            if (!file.type.startsWith('video/')) {
                this.error = 'Please select a valid video file';
                return;
            }

            if (file.size > 500 * 1024 * 1024) {
                this.error = 'File exceeds 500MB limit';
                return;
            }

            this.file = file;
            this.fileName = file.name;
            this.error = null;

            this.startUpload();
        },

        async startUpload() {

            this.uploading = true;
            this.progress = 0;
            this.statusMessage = 'Creating video on Bunny...';

            try {

                // CREATE VIDEO
                const createResponse = await fetch(config.createUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': config.csrf,
                    },
                    body: JSON.stringify({
                        title: this.fileName
                    })
                });

                if (!createResponse.ok) {
                    const errorData = await createResponse.json();
                    throw new Error(errorData.message || 'Failed to create video');
                }

                const { video_id } = await createResponse.json();
                this.videoId = video_id;

                this.statusMessage = 'Uploading video...';

                await this.uploadInChunks(video_id);

                // 🔥 Dynamic Filament state update
                this.$wire.set(config.statePath, video_id);

                this.progress = 100;
                this.statusMessage = 'Upload complete!';

            } catch (err) {
                this.error = err.message;
                this.videoId = '';
            } finally {
                this.uploading = false;
            }
        },

        async uploadInChunks(videoId) {

            const totalChunks = Math.ceil(this.file.size / this.CHUNK_SIZE);

            for (let i = 0; i < totalChunks; i++) {

                const start = i * this.CHUNK_SIZE;
                const end = Math.min(start + this.CHUNK_SIZE, this.file.size);
                const chunk = this.file.slice(start, end);

                const formData = new FormData();
                formData.append('chunk', chunk);
                formData.append('video_id', videoId);
                formData.append('chunk_index', i);
                formData.append('total_chunks', totalChunks);

                const response = await fetch(config.uploadUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': config.csrf,
                    },
                    body: formData
                });

                if (!response.ok) {
                    throw new Error(`Failed uploading chunk ${i + 1}`);
                }

                this.progress = ((i + 1) / totalChunks) * 100;
                this.statusMessage = `Uploading ${i + 1} of ${totalChunks}...`;
            }
        },

        reset() {
            this.file = null;
            this.fileName = '';
            this.uploading = false;
            this.progress = 0;
            this.videoId = '';
            this.error = null;
            this.statusMessage = 'Preparing upload...';

            this.$wire.set(config.statePath, '');
        }

    }))
})
</script>
@endonce
