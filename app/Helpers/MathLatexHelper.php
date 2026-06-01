<?php

if (! function_exists('to_latex_exponents')) {
    /**
     * Convert simple exponent expressions to inline KaTeX fragments.
     * Example: "Find 7^43 mod 10" => "Find $7^{43}$ mod 10"
     */
    function to_latex_exponents(string $text): string
    {
        // Wrap base^exponent tokens in inline math while keeping surrounding sentence spacing intact.
        // Supports simple bases (x^2, 7^43) and parenthesized bases ((1+2x)^5).
        $pattern = '/(?<!\\\\)(\([^()]+\)|[A-Za-z0-9]+)\^([A-Za-z0-9()]+)/';

        return preg_replace_callback($pattern, static function (array $matches): string {
            return '$'.$matches[1].'^{'.$matches[2].'}$';
        }, $text) ?? $text;
    }
}
