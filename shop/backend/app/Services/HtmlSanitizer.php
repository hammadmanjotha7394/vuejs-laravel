<?php

namespace App\Services;

class HtmlSanitizer
{
    /**
     * Sanitize rich text HTML input by stripping dangerous tags, event attributes, and javascript: URIs.
     */
    public static function clean(?string $html): ?string
    {
        if ($html === null || $html === '') {
            return $html;
        }

        // 1. Remove dangerous block elements completely with their inner contents
        $clean = preg_replace(
            '#<(script|style|iframe|object|embed|applet|form|meta|link|base)\b[^>]*>.*?</\1>#is',
            '',
            $html
        );

        // 2. Remove self-closing or unclosed dangerous tags
        $clean = preg_replace(
            '#</?(script|style|iframe|object|embed|applet|form|input|button|textarea|select|meta|link|base)\b[^>]*>#is',
            '',
            (string) $clean
        );

        // 3. Strip inline event handler attributes (onerror=, onload=, onclick=, onmouseover=, etc.)
        $clean = preg_replace(
            '/\s+on[a-z]+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i',
            '',
            (string) $clean
        );

        // 4. Neutralize javascript:, vbscript:, and data:text/html protocols in href/src attributes
        $clean = preg_replace(
            '/(href|src|xlink:href|action)\s*=\s*(["\']?)\s*(?:javascript|vbscript|data\s*:\s*text\/html)\s*:[^"\'>\s]*/i',
            '$1=$2#',
            (string) $clean
        );

        return trim((string) $clean);
    }
}
