<?php

namespace mattstein\utilities;

class KindleClippingExtractor
{
    /**
     * @var string Pattern that separates each clipping from the next
     */
    public const CLIPPING_SEPARATOR = '==========';

    /**
     * @var string Markdown multi-file output format, where each file will be `[slugified book title].md`
     */
    public const OUTPUT_FORMAT_MARKDOWN = 'markdown';

    /**
     * @var string Single-file JSON output format
     */
    public const OUTPUT_FORMAT_JSON = 'json';

    /**
     * @var array Available output formats for the `write()` method
     */
    public const OUTPUT_FORMATS = [
        self::OUTPUT_FORMAT_MARKDOWN,
        self::OUTPUT_FORMAT_JSON,
    ];

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
     * @var string Target directory for `write()` function’s files
     */
    public string $outputDir = 'output/';

    /**
     * @var string Single-file JSON output filename
     */
    public string $jsonFilename = 'kindle-clippings.json';

    /**
     * @var bool Whether to overwrite existing Markdown or JSON file(s)
     */
    public bool $overwrite = false;

    /**
     * @var bool Whether to include console output
     */
    public bool $silent = false;

    /**
     * @var bool Whether individual book filenames should be web-friendly
     */
    public bool $webSafeFilenames = true;

    /**
     * @var array|null Memoized clippings by book title
     */
    private ?array $_clippingsByBook = null;

    /**
     * Parses Kindle’s text file content into KindleClipping objects
     *
     * @param string $text    Contents of `My Clippings.txt` from Kindle
     * @param array  $types   Desired clipping types—leave empty to collect all types
     * @return KindleClipping[]
     * @throws \Exception
     */
    public function parse(string $text, array $types = []): array
    {
        $text = KindleClipping::removeUtf8Bom($text);

        $this->highlightCount = 0;
        $this->noteCount = 0;
        $this->bookmarkCount = 0;
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

            if (empty($types) || in_array($clipping->type, $types, true)) {
                $this->clippings[] = $clipping;
            }
        }

        $this->print(sprintf('Found %d books with %d highlights, %d notes, and %d bookmarks.',
            count($this->clippings),
            $this->highlightCount,
            $this->noteCount,
            $this->bookmarkCount) . PHP_EOL
        );

        $clippingsByBook = $this->getClippingsByBook($types);

        foreach ($clippingsByBook as $book => $clippings) {
            $firstClipping = $clippings[0];
            $this->print($firstClipping->title . ' (' . count($clippings) . ' clippings)');
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
     * @param array $types Desired types, or empty array to return all clipping types
     * @return array
     */
    public function getClippingsOfType(array $types = []): array
    {
        return array_filter($this->clippings, static function($clipping) use ($types) {
            return (empty($types) || in_array($clipping->type, $types, true));
        });
    }

    /**
     * Writes parsed clippings to JSON or Markdown output
     *
     * @param string $format `json` or `markdown`
     * @param array  $types Desired types—leave empty to include all clipping types
     * @throws \JsonException
     */
    public function write(string $format = self::OUTPUT_FORMAT_JSON, array $types = []): void
    {
        if (!file_exists($this->outputDir) && !mkdir($this->outputDir) && !is_dir($this->outputDir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created.', $this->outputDir));
        }

        if ($format === self::OUTPUT_FORMAT_JSON) {
            $this->writeJson($types);
        } elseif ($format === self::OUTPUT_FORMAT_MARKDOWN) {
            $this->writeMarkdown($types);
        }
    }

    /**
     * Writes a single JSON file with all the parsed clippings
     * @param array $types
     * @throws \JsonException
     */
    private function writeJson(array $types = []): void
    {
        $clippings = $this->getClippingsOfType($types);
        $json = json_encode($clippings, JSON_THROW_ON_ERROR);
        $filePath = $this->outputDir . $this->jsonFilename;

        $this->print(PHP_EOL);

        if ($this->overwrite === false && file_exists($filePath)) {
            $this->print("!! Not writing `${filePath}`; file already exists!");
            return;
        }

        file_put_contents($filePath, $json);
    }

    /**
     * Writes multiple Markdown files with clippings, one file per book
     * @param array $types
     */
    private function writeMarkdown(array $types = []): void
    {
        $clippingsByBook = $this->getClippingsByBook($types);

        $this->print(PHP_EOL);

        foreach ($clippingsByBook as $book => $clippings) {
            $firstClipping = $clippings[0];

            if ($this->webSafeFilenames) {
                $filename = self::slugify($firstClipping->title) . ".md";
            } else {
                $filename = $firstClipping->title . ".md";
            }

            $markdown = "---" . PHP_EOL;
            $markdown .= "- title: " . $firstClipping->title . PHP_EOL;
            $markdown .= "- author: " . $firstClipping->author . PHP_EOL;
            $markdown .= "---" . PHP_EOL . PHP_EOL;
            $markdown .= "# {$firstClipping->title} by {$firstClipping->author}" . PHP_EOL;

            foreach ($clippings as $clipping) {
                $markdown .= PHP_EOL;

                if ($clipping->type === KindleClipping::TYPE_NOTE) {
                    $markdown .= $clipping->text . PHP_EOL . PHP_EOL;
                } elseif ($clipping->type === KindleClipping::TYPE_HIGHLIGHT) {
                    $markdown .= "> {$clipping->text}" . PHP_EOL . PHP_EOL;
                }

                $markdown .= sprintf(
                    '– page %s, location %s, %s' . PHP_EOL . PHP_EOL,
                    $clipping->page,
                    $clipping->location,
                    $clipping->date->format('n/j/y \a\t g:ia ')
                );
            }

            $filePath = $this->outputDir . $filename;

            if ($this->overwrite === false && file_exists($filePath)) {
                $this->print("[!] Skipping `${filePath}`; file already exists!");
                continue;
            }

            file_put_contents($filePath, $markdown);
        }
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
     * Outputs a message to the console
     * @param $message
     */
    private function print($message): void
    {
        if (!$this->silent) {
            $message .= PHP_EOL;
            fwrite(\STDOUT, $message);
        }
    }
}
