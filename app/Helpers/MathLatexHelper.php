<?php

if (! function_exists('to_latex_exponents')) {
    /**
     * Normalize unicode superscript exponent sequences to caret notation.
     * Example: "2⁽ˣ⁺¹⁾" => "2^(x+1)"
     */
    function normalize_unicode_exponents(string $text): string
    {
        $superscriptMap = [
            '⁰' => '0',
            '¹' => '1',
            '²' => '2',
            '³' => '3',
            '⁴' => '4',
            '⁵' => '5',
            '⁶' => '6',
            '⁷' => '7',
            '⁸' => '8',
            '⁹' => '9',
            '⁺' => '+',
            '⁻' => '-',
            '⁼' => '=',
            '⁽' => '(',
            '⁾' => ')',
            'ᵃ' => 'a',
            'ᵇ' => 'b',
            'ᶜ' => 'c',
            'ᵈ' => 'd',
            'ᵉ' => 'e',
            'ᶠ' => 'f',
            'ᵍ' => 'g',
            'ʰ' => 'h',
            'ⁱ' => 'i',
            'ʲ' => 'j',
            'ᵏ' => 'k',
            'ˡ' => 'l',
            'ᵐ' => 'm',
            'ˣ' => 'x',
            'ⁿ' => 'n',
            'ᵒ' => 'o',
            'ᵖ' => 'p',
            'ʳ' => 'r',
            'ˢ' => 's',
            'ᵗ' => 't',
            'ᵘ' => 'u',
            'ᵛ' => 'v',
            'ʷ' => 'w',
            'ʸ' => 'y',
            'ᶻ' => 'z',
            'ᴬ' => 'A',
            'ᴮ' => 'B',
            'ᴰ' => 'D',
            'ᴱ' => 'E',
            'ᴳ' => 'G',
            'ᴴ' => 'H',
            'ᴵ' => 'I',
            'ᴶ' => 'J',
            'ᴷ' => 'K',
            'ᴸ' => 'L',
            'ᴹ' => 'M',
            'ᴺ' => 'N',
            'ᴼ' => 'O',
            'ᴾ' => 'P',
            'ᴿ' => 'R',
            'ᵀ' => 'T',
            'ᵁ' => 'U',
            'ⱽ' => 'V',
            'ᵂ' => 'W',
        ];

        $superscriptChars = implode('', array_keys($superscriptMap));
        $pattern = '/([A-Za-z0-9\)])(['.preg_quote($superscriptChars, '/').']+)/u';

        return preg_replace_callback(
            $pattern,
            static function (array $matches) use ($superscriptMap): string {
                return $matches[1].'^'.strtr($matches[2], $superscriptMap);
            },
            $text
        ) ?? $text;
    }

    /**
     * Convert simple exponent expressions to inline KaTeX fragments.
     * Example: "Find 7^43 mod 10" => "Find $7^{43}$ mod 10"
     */
    function to_latex_exponents(string $text): string
    {
        $text = normalize_unicode_exponents($text);

        // Wrap base^exponent tokens in inline math while keeping surrounding sentence spacing intact.
        // Supports simple exponents (x^2, 7^43), signed exponents (tan^-1), and parenthesized exponents (2^(x+1)).
        $pattern = '/(?<!\\\\)(\([^()]+\)|[A-Za-z0-9]+)\^(\([^()]+\)|[+-]?[A-Za-z0-9]+)/';

        return preg_replace_callback($pattern, static function (array $matches): string {
            $mathFragment = $matches[1].'^{'.$matches[2].'}';
            $mathFragment = preg_replace_callback(
                '/(?<!\\\\)\b(sin|cos|tan|cot|sec|csc|log|ln)\b/i',
                static fn (array $functionMatch): string => '\\'.strtolower($functionMatch[1]),
                $mathFragment
            ) ?? $mathFragment;

            return '$'.$mathFragment.'$';
        }, $text) ?? $text;
    }
}
