<?php

declare(strict_types=1);

require_once __DIR__.'/../../app/Helpers/MathLatexHelper.php';

it('converts a simple exponent into inline math', function (): void {
    $text = 'Find the last digit of 7^43 mod 10';

    expect(to_latex_exponents($text))->toBe('Find the last digit of $7^{43}$ mod 10');
});

it('converts a parenthesized base exponent into inline math', function (): void {
    $text = 'The coefficient of x3 in the expansion of (1+2x)^5 is:';

    expect(to_latex_exponents($text))->toBe('The coefficient of x3 in the expansion of $(1+2x)^{5}$ is:');
});

it('normalizes unicode superscript exponent sequences', function (): void {
    $text = 'Find the value of x if 2⁽ˣ⁺¹⁾ + 2⁽ˣ⁻¹⁾ = 40.';

    expect(normalize_unicode_exponents($text))->toBe('Find the value of x if 2^(x+1) + 2^(x-1) = 40.');
});

it('renders normalized unicode superscript expressions into inline KaTeX fragments', function (): void {
    $text = 'Find the value of x if 2⁽ˣ⁺¹⁾ + 2⁽ˣ⁻¹⁾ = 40. (Adapted from NECO 2019)';

    expect(to_latex_exponents($text))
        ->toBe('Find the value of x if $2^{(x+1)}$ + $2^{(x-1)}$ = 40. (Adapted from NECO 2019)');
});

it('normalizes unicode superscript letters beyond x and n', function (): void {
    $text = 'Simplify y⁽ᵃ⁺ᵇ⁻ᶜ⁾ and tⁿ.';

    expect(normalize_unicode_exponents($text))->toBe('Simplify y^(a+b-c) and t^n.');
});

it('renders uppercase superscript letters as KaTeX exponents', function (): void {
    $text = 'Evaluate Pᴬ + Qᴮ.';

    expect(to_latex_exponents($text))->toBe('Evaluate $P^{A}$ + $Q^{B}$.');
});

it('renders inverse tangent notation with signed exponents', function (): void {
    $text = 'Find the value of tan^-1(1) + tan^-1(2) + tan^-1(3).';

    expect(to_latex_exponents($text))
        ->toBe('Find the value of $\\tan^{-1}$(1) + $\\tan^{-1}$(2) + $\\tan^{-1}$(3).');
});

it('renders common trig names as KaTeX commands in exponent expressions', function (): void {
    $text = 'Evaluate sin^2 x + cos^2 x.';

    expect(to_latex_exponents($text))
        ->toBe('Evaluate $\\sin^{2}$ x + $\\cos^{2}$ x.');
});

it('renders chemistry charge exponents', function (): void {
    $text = 'What is the concentration of H^+ in a solution with pH = 3?';

    expect(to_latex_exponents($text))
        ->toBe('What is the concentration of $H^{+}$ in a solution with pH = 3?');
});

it('keeps signed variable exponents grouped', function (): void {
    $text = 'Solve: 4^x-1 = 8^x+2. If 3^x+2 = 9^x-1, find x.';

    expect(to_latex_exponents($text))
        ->toBe('Solve: $4^{x-1}$ = $8^{x+2}$. If $3^{x+2}$ = $9^{x-1}$, find x.');
});

it('renders compact electron configuration exponents separately', function (): void {
    $text = 'Which element has the electronic configuration 1s^22s^22p^63s^23p^64s^23d^104p^65s^24d^105p^66s^24f^145d^106p^67s^2?';

    expect(to_latex_exponents($text))
        ->toBe('Which element has the electronic configuration $1s^{2}$$2s^{2}$$2p^{6}$$3s^{2}$$3p^{6}$$4s^{2}$$3d^{10}$$4p^{6}$$5s^{2}$$4d^{10}$$5p^{6}$$6s^{2}$$4f^{14}$$5d^{10}$$6p^{6}$$7s^{2}$?');
});

it('renders compact trig degree notation with superscript degree', function (): void {
    $text = 'Find sin230° + cos260°.';

    expect(to_latex_exponents($text))
        ->toBe('Find $\\sin 230^{\\circ}$ + $\\cos 260^{\\circ}$.');
});

it('renders spaced trig degree notation with superscript degree', function (): void {
    $text = 'Find the value of sin 230° + cos 260°.';

    expect(to_latex_exponents($text))
        ->toBe('Find the value of $\\sin 230^{\\circ}$ + $\\cos 260^{\\circ}$.');
});

it('interprets trig caret degree notation as angle and not exponent', function (): void {
    $text = 'Find the value of sin^230° + cos^260°.';

    expect(to_latex_exponents($text))
        ->toBe('Find the value of $\\sin 230^{\\circ}$ + $\\cos 260^{\\circ}$.');
});

it('renders differential fractions in inline KaTeX', function (): void {
    $text = 'The solution of dy/dx = 2y is required.';

    expect(to_latex_exponents($text))
        ->toBe('The solution of $\\frac{dy}{dx}$ = 2y is required.');
});

it('renders mixed differential and exponential explanation text', function (): void {
    $text = 'Step 1: dy/dx = 2y. Step 4: y=3e^(2x).';

    expect(to_latex_exponents($text))
        ->toBe('Step 1: $\\frac{dy}{dx}$ = 2y. Step 4: y=3$e^{(2x)}$.');
});

it('renders grouped distance notation with explicit parentheses', function (): void {
    $text = 'A point P moves such that PA² + PB² = 40 cm².';

    expect(to_latex_exponents($text))
        ->toBe('A point P moves such that $(PA)^{2}$ + $(PB)^{2}$ = 40 $cm^{2}$.');
});

it('renders parenthesized rational expressions as fractions', function (): void {
    $text = 'Simplify: (x² - 4)/(x² - 5x + 6)';

    expect(to_latex_exponents($text))
        ->toBe('Simplify: $\\frac{x^{2} - 4}{x^{2} - 5x + 6}$');
});

it('preserves existing inline latex fraction blocks', function (): void {
    $text = 'Simplify \\(\\frac{x^2 - 4}{x^2 - 5x + 6}\\).';

    expect(to_latex_exponents($text))
        ->toBe('Simplify \\(\\frac{x^2 - 4}{x^2 - 5x + 6}\\).');
});

it('converts plain exponents outside existing latex blocks only', function (): void {
    $text = 'Find x^2 and \\(x^2 + 1\\).';

    expect(to_latex_exponents($text))
        ->toBe('Find $x^{2}$ and \\(x^2 + 1\\).');
});
