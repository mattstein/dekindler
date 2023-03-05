<?php

namespace mattstein\dekindler;

use JsonException;
use RuntimeException;

/**
 * Writes collected KindleClipping objects to a JSON file or individual Markdown files.
 */
class Writer
{
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
     * @var string Default output format.
     */
    public string $outputFormat = self::OUTPUT_FORMAT_MARKDOWN;

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
     * @var bool Whether individual book filenames should be web-friendly
     */
    public bool $webSafeFilenames = true;

    private Extractor $extractor;

    /**
     * @var array Collection of warnings encountered when trying to write file(s)
     */
    private array $warnings = [];

    /**
     * @var array Collection of files written
     */
    private array $filesWritten = [];

    /**
     * Provides the writer a reference to the extractor so it can get what it needs for writing
     *
     * @param Extractor $extractor
     */
    public function setExtractor(Extractor $extractor): void
    {
        $this->extractor = $extractor;
    }

    /**
     * Writes parsed clippings to JSON or Markdown output
     *
     * @param array  $types Desired types—leave empty to include all clipping types
     * @throws JsonException
     */
    public function write(array $types = []): void
    {
        if (!file_exists($this->outputDir) && !mkdir($this->outputDir) && !is_dir($this->outputDir)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created.', $this->outputDir));
        }

        if ($this->outputFormat === self::OUTPUT_FORMAT_JSON) {
            $this->writeJson($types);
        } elseif ($this->outputFormat === self::OUTPUT_FORMAT_MARKDOWN) {
            $this->writeMarkdown($types);
        }
    }

    /**
     * Writes a single JSON file with all the parsed clippings
     *
     * @param array $types
     * @throws JsonException
     * @throws RuntimeException when a file exists that should not be overwritten
     */
    private function writeJson(array $types = []): void
    {
        $clippings = $this->extractor->getClippingsOfType($types);
        $json = json_encode($clippings, JSON_THROW_ON_ERROR);
        $filePath = $this->outputDir . $this->jsonFilename;

        if ($this->overwrite === false && file_exists($filePath)) {
            throw new RuntimeException("Not writing `$filePath`; file already exists!");
        }

        $this->writeFile($filePath, $json);
    }

    /**
     * Writes multiple Markdown files with clippings, one file per book
     *
     * @param array $types
     */
    private function writeMarkdown(array $types = []): void
    {
        $clippingsByBook = $this->extractor->getClippingsByBook($types);

        foreach ($clippingsByBook as $clippings) {
            $firstClipping = $clippings[0];

            if ($this->webSafeFilenames) {
                $filename = StringHelper::slugify($firstClipping->title) . ".md";
            } else {
                $filename = $firstClipping->title . ".md";
            }

            $markdown = "---" . PHP_EOL;
            $markdown .= "title: " . $firstClipping->title . PHP_EOL;
            $markdown .= "author: " . $firstClipping->author . PHP_EOL;
            $markdown .= "---" . PHP_EOL . PHP_EOL;
            $markdown .= "# $firstClipping->title by $firstClipping->author" . PHP_EOL;

            foreach ($clippings as $clipping) {
                $markdown .= PHP_EOL;

                if ($clipping->type === KindleClipping::TYPE_NOTE) {
                    $markdown .= $clipping->text . PHP_EOL . PHP_EOL;
                } elseif ($clipping->type === KindleClipping::TYPE_HIGHLIGHT) {
                    $markdown .= "> $clipping->text" . PHP_EOL . PHP_EOL;
                }

				if ($clipping->page) {
					$markdown .= sprintf(
						'– page %s, location %s, %s' . PHP_EOL . PHP_EOL,
						$clipping->page,
						$clipping->location,
						$clipping->date->format('n/j/y \a\t g:ia ')
					);
				} else {
					$markdown .= sprintf(
						'– location %s, %s' . PHP_EOL . PHP_EOL,
						$clipping->location,
						$clipping->date->format('n/j/y \a\t g:ia ')
					);
				}
            }

            $filePath = $this->outputDir . $filename;

            if ($this->overwrite === false && file_exists($filePath)) {
                $this->warnings[] = "Skipped `$filePath`; file already exists.";
                continue;
            }

            $this->writeFile($filePath, $markdown);
        }
    }

    /**
     * Returns warnings
     *
     * @return array
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    /**
     * Returns files written
     *
     * @return array
     */
    public function getFilesWritten(): array
    {
        return $this->filesWritten;
    }

    /**
     * Saves a file to disk at the provided path
     *
     * @param $path
     * @param $contents
     */
    private function writeFile($path, $contents): void
    {
        file_put_contents($path, $contents);
        $this->filesWritten[] = $path;
    }
}
