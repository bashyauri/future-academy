<x-filament::page>
    <div class="grid gap-8 lg:grid-cols-3">
        <x-filament::section class="lg:col-span-2 space-y-6">
            <x-slot name="heading">
                Organize Mock Questions into Batches
            </x-slot>

            <x-slot name="description">
                Run this after uploading new mock questions. It will group them into Mock 1, Mock 2, etc. based on your configuration.
            </x-slot>

            <form wire:submit.prevent="groupQuestions" class="space-y-6">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="exam_type_id" class="block text-sm font-medium text-gray-900 dark:text-gray-100">
                            Exam Type <span class="text-red-500">*</span>
                        </label>
                        <x-filament::input.wrapper>
                            <x-filament::input.select id="exam_type_id" wire:model.live="exam_type_id">
                                <option value="">Select an exam type...</option>
                                @foreach(\App\Models\ExamType::where('is_active', true)->orderBy('name')->get() as $examType)
                                    <option value="{{ $examType->id }}">{{ $examType->name }}</option>
                                @endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    </div>

                    @if($exam_type_id)
                        <div>
                            <label for="subject_id" class="block text-sm font-medium text-gray-900 dark:text-gray-100">
                                Subject (optional — leave empty to group all subjects)
                            </label>
                            <x-filament::input.wrapper>
                                <x-filament::input.select id="subject_id" wire:model="subject_id">
                                    <option value="">All subjects</option>
                                    @foreach(\App\Models\Subject::whereHas('questions', function($q) use ($exam_type_id) {
                                        $q->where('exam_type_id', $exam_type_id)->where('is_mock', true);
                                    })->orderBy('name')->get() as $subject)
                                        <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                                    @endforeach
                                </x-filament::input.select>
                            </x-filament::input.wrapper>
                        </div>
                    @endif
                </div>

                <x-filament::card>
                    <div class="flex flex-col gap-3 text-sm sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-3">
                            <x-filament::badge color="primary" size="sm" icon="heroicon-o-information-circle" />
                            <div>
                                <div class="font-semibold">Safe to rerun</div>
                                <div class="text-gray-600 dark:text-gray-300">Existing groupings are cleared and rebuilt each time.</div>
                            </div>
                        </div>
                        <x-filament::button type="submit" icon="heroicon-o-arrow-path">
                            Group Mock Questions
                        </x-filament::button>
                    </div>
                </x-filament::card>
            </form>
        </x-filament::section>

        <x-filament::section class="space-y-6">
            <x-slot name="heading">
                How It Works
            </x-slot>

            <div class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-filament::card>
                        <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Batching</div>
                        <div class="mt-1 text-sm font-semibold">Uses mock config per subject</div>
                    </x-filament::card>
                    <x-filament::card>
                        <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Example</div>
                        <div class="mt-1 text-sm font-semibold">110 English → 70 + 40</div>
                    </x-filament::card>
                </div>

                <div class="prose dark:prose-invert max-w-none">
                    <ul class="space-y-2">
                        <li><strong>Automatic batching:</strong> Questions are grouped into batches based on your mock config (e.g., 70 questions per batch for English in JAMB).</li>
                        <li><strong>When to run:</strong> After importing or manually adding mock questions.</li>
                        <li><strong>Reruns safely:</strong> Existing groupings are cleared and recreated each time.</li>
                    </ul>
                </div>
            </div>
        </x-filament::section>
    </div>
</x-filament::page>
