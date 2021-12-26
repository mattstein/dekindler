<?php

namespace mattstein\utilities;

use RuntimeException;

/**
 * Object that represents a single item from `My Clippings.txt`.
 */
class KindleClipping
{
    /**
     * @var string A single, specific location without a note.
     */
    public const TYPE_BOOKMARK = 'bookmark';

    /**
     * @var string A specific location range along with the highlighted text.
     */
    public const TYPE_HIGHLIGHT = 'highlight';

    /**
     * @var string A single, specific location with a personal note.
     */
    public const TYPE_NOTE = 'note';

    /**
     * @var array Available clipping types.
     */
    public const TYPES = [
        self::TYPE_BOOKMARK,
        self::TYPE_HIGHLIGHT,
        self::TYPE_NOTE,
    ];

    /**
     * @var string Original, unaltered text from `My Clippings.txt`.
     */
    public string $rawText;

    /**
     * @var string Clipping type, expected to be one of `self::TYPES`.
     */
    public string $type;

    /**
     * @var string Title of the clipping’s book.
     */
    public string $title;

    /**
     * @var string Author of the clipping’s book.
     */
    public string $author;

    /**
     * @var string Relevant location number or range of numbers.
     */
    public string $location;

    /**
     * @var string Relevant page number.
     */
    public string $page;

    /**
     * @var string Original date string.
     */
    public string $rawDate;

    /**
     * @var \DateTime Date string represented as a `DateTime` object for formatting and other adventures.
     */
    public \DateTime $date;

    /**
     * @var string Text body of the highlight or note.
     */
    public string $text;

    /**
     * @var array Internal options for parser.
     */
    private array $options = [];

    /**
     * Grab the `My Clippings.txt` string and parse it.
     *
     * @param string $text                 Text content from `My Clippings.txt`.
     * @param bool   $normalizeAuthorName  Whether the parser should try and normalize author names.
     * @throws \Exception
     */
    public function __construct(string $text, bool $normalizeAuthorName = true)
    {
        $this->rawText = $text;
        $this->options['normalizeAuthorName'] = $normalizeAuthorName;
        $this->parse();
    }

    /**
     * Parses raw text into this object’s properties.
     *
     * @throws RuntimeException if an invalid clipping type is encountered.
     */
    private function parse(): void
    {
        // Split the clipping text into lines
        $clippingParts = array_filter(explode("\n", trim($this->rawText)));

        // Get the line containing the title and author
        $titleAndAuthor = StringHelper::removeUtf8Bom($clippingParts[0]);

        // Extract the author name, which is in parentheses
        preg_match('/\((.*)\)/', $titleAndAuthor, $authorMatches);

        // Capture author name without parentheses
        $this->author = $authorMatches[1];

        // Split a lastname, firstname author format into pieces
        $authorParts = explode(', ', $this->author);

        // Standardize author name (`Watts, Alan W.` → `Alan W. Watts`)
        if ($this->options['normalizeAuthorName'] && count($authorParts) === 2) {
            $this->author = trim(trim($authorParts[1]) . ' ' . trim($authorParts[0]));
        }

        // Capture title without author name
        $this->title = trim(str_replace($authorMatches[0], '', $titleAndAuthor));

        // Get the line with the clipping type, page number, location, and timestamp
        $clipped = $clippingParts[1];

        // Split the meta clipping line into pieces
        $clippedParts = explode(" | ", $clipped);

        // Split up the type and page number pieces
        $typeAndPage = str_replace('- Your ', '', $clippedParts[0]);
        $typeAndPageParts = explode(' on page ', $typeAndPage);
        $this->type = strtolower(trim($typeAndPageParts[0]));
        $this->page = trim($typeAndPageParts[1]);
        $this->location = str_replace('Location ', '', $clippedParts[1]);

        // Get the date string
        $dateString = trim(str_replace('Added on ', '', $clippedParts[2]));
        $this->rawDate = $dateString;
        $this->date = \DateTime::createFromFormat('l, F j, Y g:i:s A', $dateString);

        // Don’t quietly tolerate nonsense
        if (!in_array($this->type, self::TYPES, true)) {
            throw new RuntimeException("Invalid type {$this->type}.");
        }

        // Join the remaining lines as our highlight or note text
        $textLines = array_slice($clippingParts, 2);
        $this->text = trim(implode("\n", $textLines));
    }
}
