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
