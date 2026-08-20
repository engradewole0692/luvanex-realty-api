<?php

namespace App\Support;

class HtmlSanitizer
{
    /**
     * Allow a conservative set of editorial HTML tags for blog content.
     */
    public static function clean(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        $allowed = '<p><br><strong><b><em><i><u><ul><ol><li><h2><h3><h4><blockquote><a><img><figure><figcaption><hr>';

        $cleaned = strip_tags($html, $allowed);

        // Strip dangerous URI schemes from href/src.
        $cleaned = preg_replace_callback(
            '/\s(href|src)=([\'"])(.*?)\2/i',
            static function (array $matches): string {
                $attr = strtolower($matches[1]);
                $quote = $matches[2];
                $url = trim($matches[3]);

                if (preg_match('/^\s*javascript:/i', $url) || preg_match('/^\s*data:/i', $url)) {
                    return '';
                }

                return ' '.$attr.'='.$quote.$url.$quote;
            },
            $cleaned
        ) ?? $cleaned;

        return $cleaned;
    }
}
