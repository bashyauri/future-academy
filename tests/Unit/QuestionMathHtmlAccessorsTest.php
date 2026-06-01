<?php

declare(strict_types=1);

use App\Models\Option;
use App\Models\Question;

require_once __DIR__.'/../../app/Helpers/MathLatexHelper.php';

it('renders question explanation using math html accessor', function (): void {
    $question = new Question([
        'explanation' => 'So ln y=2x+ln3 -> y=e^(2x+ln3)=3e^(2x).',
    ]);

    expect($question->explanation_html)
        ->toBe('So ln y=2x+ln3 -> y=$e^{(2x+ln3)}$=3$e^{(2x)}$.');
});

it('renders option text using math html accessor', function (): void {
    $option = new Option([
        'option_text' => 'y = 3e^(2x)',
    ]);

    expect($option->option_text_html)->toBe('y = 3$e^{(2x)}$');
});
