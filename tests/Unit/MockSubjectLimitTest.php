<?php

use App\Livewire\Quizzes\MockSetup;

test('JAMB mock setup limits selection to four subjects', function () {
    expect(MockSetup::subjectLimitForFormat('jamb'))->toBe(4);
});

test('non-JAMB mock setup has no fixed subject limit', function (string $examFormat) {
    expect(MockSetup::subjectLimitForFormat($examFormat))->toBeNull();
})->with(['ssce', 'waec', 'default']);
