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
