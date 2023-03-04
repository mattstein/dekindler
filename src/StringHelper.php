<?php

namespace mattstein\utilities;

class StringHelper
{
    /**
     * Remove BOM from provided string.
     * https://stackoverflow.com/a/15423899
     *
     * @param string $text
     * @return string
     */
    public static function removeUtf8Bom(string $text): string
    {
        $bom = pack('H*','EFBBBF');
        return preg_replace("/^$bom/", '', $text);
    }

    // https://stackoverflow.com/a/2955878
    public static function slugify($text, string $divider = '-'): string
    {
        $text = str_replace(['’', "'"], '', $text);

        // replace non letter or digits by divider
        $text = preg_replace('~[^\pL\d]+~u', $divider, $text);

        // transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        // trim
        $text = trim($text, $divider);

        // remove duplicate divider
        $text = preg_replace('~-+~', $divider, $text);

        // lowercase
        $text = strtolower($text);

        if (empty($text)) {
            return 'n-a';
        }

        return $text;
    }

    /**
     * Convert “last name, first name” author name to “first name last name”, or give it back untouched.
     * 
     * @param string $name Author’s name from the clipping
     * @return string
     */
    public static function normalizeAuthorName(string $name): string
    {
        // Split a lastname, firstname author format into pieces
        $nameParts = explode(', ', $name);

        // Standardize author name (`Watts, Alan W.` → `Alan W. Watts`)
        if (count($nameParts) === 2) {
           return trim(trim($nameParts[1]) . ' ' . trim($nameParts[0]));
        }

        return $name;
    }
}
