<?php

if (! function_exists('to_latex_exponents')) {
    /**
     * Temporarily replace existing LaTeX-delimited fragments with placeholders
     * so conversion rules do not mutate already-valid math.
     *
     * @return array{0: string, 1: array<string, string>}
     */
    function protect_existing_latex_blocks(string $text): array
    {
        $placeholders = [];
        $index = 0;
        $pattern = '/\\\\\((.*?)\\\\\)|\\\\\[(.*?)\\\\\]|\$\$(.*?)\$\$|\$(.*?)\$/s';

        $protectedText = preg_replace_callback($pattern, static function (array $matches) use (&$placeholders, &$index): string {
            $token = '%%LATEX_BLOCK_'.$index.'%%';
            $placeholders[$token] = $matches[0];
            $index++;

            return $token;
        }, $text) ?? $text;

        return [$protectedText, $placeholders];
    }

    /**
     * Restore previously protected LaTeX-delimited fragments.
     *
     * @param  array<string, string>  $placeholders
     */
    function restore_protected_latex_blocks(string $text, array $placeholders): string
    {
        if ($placeholders === []) {
            return $text;
        }

        return strtr($text, $placeholders);
    }

    /**
     * Apply local math formatting rules within a fragment that will be rendered as KaTeX.
     */
    function latexize_math_fragment(string $fragment): string
    {
        $fragment = preg_replace_callback(
            '/(?<!\\\\)(\([^()]+\)|[A-Za-z]+|[0-9]+)\^(\([^()]+\)|[+-]?(?:[A-Za-z](?:[+-]\d+)?|\d+)|[+-])/',
            static function (array $matches): string {
                $mathFragment = $matches[1].'^{'.$matches[2].'}';

                return preg_replace_callback(
                    '/(?<!\\\\)\b(sin|cos|tan|cot|sec|csc|log|ln)\b/i',
                    static fn (array $functionMatch): string => '\\'.strtolower($functionMatch[1]),
                    $mathFragment
                ) ?? $mathFragment;
            },
            $fragment
        ) ?? $fragment;

        return preg_replace_callback(
            '/(?<!\\\\)\b(sin|cos|tan|cot|sec|csc|log|ln)\b/i',
            static fn (array $functionMatch): string => '\\'.strtolower($functionMatch[1]),
            $fragment
        ) ?? $fragment;
    }

    /**
     * Convert simple differential fractions to inline KaTeX fragments.
     * Example: "dy/dx = 2y" => "$\\frac{dy}{dx}$ = 2y"
     */
    function convert_differential_fractions(string $text): string
    {
        $pattern = '/(?<![A-Za-z0-9])d([A-Za-z])\/d([A-Za-z])(?![A-Za-z0-9])/';

        return preg_replace_callback($pattern, static function (array $matches): string {
            return '$\\frac{d'.$matches[1].'}{d'.$matches[2].'}$';
        }, $text) ?? $text;
    }

    /**
     * Convert parenthesized rational expressions into KaTeX fractions.
     * Example: "(x^2 - 4)/(x^2 - 5x + 6)" => "$\frac{x^{2} - 4}{x^{2} - 5x + 6}$"
     */
    function convert_parenthesized_fractions(string $text): string
    {
        $pattern = '/\(([^()]+)\)\s*\/\s*\(([^()]+)\)/';

        return preg_replace_callback($pattern, static function (array $matches): string {
            $numerator = latexize_math_fragment(trim($matches[1]));
            $denominator = latexize_math_fragment(trim($matches[2]));

            return '$\\frac{'.$numerator.'}{'.$denominator.'}$';
        }, $text) ?? $text;
    }

    /**
     * Render uppercase distance symbols with explicit grouping.
     * Example: "PA^2 + PB^2" => "$(PA)^{2}$ + $(PB)^{2}$"
     */
    function convert_grouped_distance_exponents(string $text): string
    {
        $pattern = '/(?<![A-Za-z0-9\\\\])([A-Z]{2})\^(\([^()]+\)|[+-]?\d+)(?![A-Za-z0-9])/';

        return preg_replace_callback($pattern, static function (array $matches): string {
            return '$('.$matches[1].')^{'.$matches[2].'}$';
        }, $text) ?? $text;
    }

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
     * Convert compact electron configuration notation before the generic exponent parser.
     * Example: "1s^22s^2" => "$1s^{2}$$2s^{2}$"
     */
    function convert_electron_configuration_exponents(string $text): string
    {
        $pattern = '/(?<![A-Za-z])([1-7][spdf])\^((?:10|14|[1-9])(?=[1-7][spdf]\^|[^A-Za-z0-9]|$))/';

        return preg_replace_callback($pattern, static function (array $matches): string {
            return '$'.$matches[1].'^{'.$matches[2].'}$';
        }, $text) ?? $text;
    }

    /**
     * Convert compact trig degree notation into a single inline KaTeX fragment.
     * Example: "sin230° + cos260°" => "$\sin 230^{\circ}$ + $\cos 260^{\circ}$"
     */
    function convert_trig_degree_angles(string $text): string
    {
        $pattern = '/(?<!\\\\)\b(sin|cos|tan|cot|sec|csc)\s*([+-]?\d{1,3})\s*(Â°|\x{00B0})/iu';

        return preg_replace_callback($pattern, static function (array $matches): string {
            return '$\\'.strtolower($matches[1]).' '.$matches[2].'^{\\circ}$';
        }, $text) ?? $text;
    }

    /**
     * Repair trig angle text written as caret + degrees.
     * Example: "sin^230°" => "$\sin 230^{\circ}$"
     */
    function convert_trig_caret_degree_angles(string $text): string
    {
        $pattern = '/(?<!\\\\)\b(sin|cos|tan|cot|sec|csc)\^([+-]?\d{1,3})\s*(Â°|\x{00B0})/iu';

        return preg_replace_callback($pattern, static function (array $matches): string {
            return '$\\'.strtolower($matches[1]).' '.$matches[2].'^{\\circ}$';
        }, $text) ?? $text;
    }

    /**
     * Convert simple exponent expressions to inline KaTeX fragments.
     * Example: "Find 7^43 mod 10" => "Find $7^{43}$ mod 10"
     */
    function to_latex_exponents(string $text): string
    {
        [$text, $protectedBlocks] = protect_existing_latex_blocks($text);

        $text = normalize_unicode_exponents($text);
        $text = convert_differential_fractions($text);
        $text = convert_electron_configuration_exponents($text);
        $text = convert_trig_caret_degree_angles($text);
        $text = convert_trig_degree_angles($text);
        $text = convert_parenthesized_fractions($text);
        $text = convert_grouped_distance_exponents($text);

        // Wrap base^exponent tokens in inline math while keeping surrounding sentence spacing intact.
        // Supports simple exponents (x^2, 7^43), signed exponents (tan^-1), and parenthesized exponents (2^(x+1)).
        $pattern = '/(?<!\\\\)(\([^()]+\)|[A-Za-z]+|[0-9]+)\^(\([^()]+\)|[+-]?(?:[A-Za-z](?:[+-]\d+)?|\d+)|[+-])/';

        $text = preg_replace_callback($pattern, static function (array $matches): string {
            return '$'.latexize_math_fragment($matches[1].'^{'.$matches[2].'}').'$';
        }, $text) ?? $text;

        return restore_protected_latex_blocks($text, $protectedBlocks);
    }
}
