<?php

namespace mattstein\dekindler;

use Exception;

/**
 * Parses plain text, presumably from the Kindle’s “My Clippings.txt”, into KindleClipping objects.
 */
class Extractor
{
    /**
     * @var string Pattern that separates each clipping from the next
     */
    public const CLIPPING_SEPARATOR = '==========';

    /**
     * @var KindleClipping[]
     */
    public array $clippings = [];

    /**
     * @var int Number of parsed highlight clippings
     */
    public int $highlightCount = 0;

    /**
     * @var int Number of parsed bookmark clippings
     */
    public int $bookmarkCount = 0;

    /**
     * @var int Number of parsed note clippings
     */
    public int $noteCount = 0;

    /**
     * @var int Number of duplicate clippings
     */
    public int $duplicateCount = 0;

    /**
     * @var array|null Memoized clippings by book title
     */
    private ?array $_clippingsByBook = null;

    /**
     * Parses Kindle’s text file content into KindleClipping objects
     *
     * @param string $text              Contents of `My Clippings.txt` from Kindle
     * @param array  $types             Desired clipping types—leave empty to collect all types
     * @param bool   $ignoreDuplicates  Whether to remove duplicate highlights
     * @return KindleClipping[]
     * @throws Exception
     */
    public function parse(string $text, array $types = [], bool $ignoreDuplicates = true): array
    {
        $text = StringHelper::removeUtf8Bom($text);

        $this->clippings = [];
        $this->highlightCount = 0;
        $this->noteCount = 0;
        $this->bookmarkCount = 0;
        $this->duplicateCount = 0;
        $this->_clippingsByBook = null;

        $chunks = array_filter(explode(self::CLIPPING_SEPARATOR, $text), static function($value) {
            return ! empty(trim($value));
        });

        foreach ($chunks as $chunk) {
            $clipping = new KindleClipping($chunk);

            if ($clipping->type === KindleClipping::TYPE_HIGHLIGHT) {
                $this->highlightCount++;
            } elseif ($clipping->type === KindleClipping::TYPE_NOTE) {
                $this->noteCount++;
            } elseif ($clipping->type === KindleClipping::TYPE_BOOKMARK) {
                $this->bookmarkCount++;
            }

            $isDuplicate = false;

            if (count($this->clippings) > 0) {
                $previousClipping = array_slice($this->clippings, -1, 1)[0];
                $isDuplicate = $clipping->isDuplicateOf($previousClipping);

                if ($isDuplicate) {
                    $this->duplicateCount++;
                }
            }

            $isCollectible = empty($types) || in_array($clipping->type, $types, true);

            if ($isCollectible) {
                if ($ignoreDuplicates && $isDuplicate) {
                    // Remove previous, duplicate item
                    array_pop($this->clippings);
                }

                // Add it to the collection
                $this->clippings[] = $clipping;
            }
        }

        return $this->clippings;
    }

    /**
     * Returns parsed clippings indexed by book title
     *
     * @param array $types
     * @return array
     */
    public function getClippingsByBook(array $types = []): array
    {
        if ($this->_clippingsByBook !== null) {
            return $this->_clippingsByBook;
        }

        $clippingsByBook = [];
        $clippings = $this->getClippingsOfType($types);

        foreach ($clippings as $clipping) {
            // Add this book if we don’t already have it
            if (! isset($clippingsByBook[$clipping->title])) {
                $clippingsByBook[$clipping->title] = [];
            }

            $clippingsByBook[$clipping->title][] = $clipping;
        }

        $this->_clippingsByBook = $clippingsByBook;

        return $clippingsByBook;
    }

    /**
     * Returns clippings filtered by the provided types
     *
     * @param array $types Desired types, or empty array to return all clipping types
     * @return array
     */
    public function getClippingsOfType(array $types = []): array
    {
        return array_filter($this->clippings, static function($clipping) use ($types) {
            return (empty($types) || in_array($clipping->type, $types, true));
        });
    }
}
