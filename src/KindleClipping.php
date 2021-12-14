<?php

namespace mattstein\utilities;

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
     * Grab the `My Clippings.txt` string and parse it.
     * @param string $text Text content from `My Clippings.txt`.
     * @throws \Exception
     */
    public function __construct(string $text)
    {
        $this->rawText = $text;
        $this->parse();
    }

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

    /**
     *
     * @throws \Exception
     */
    private function parse(): void
    {
        // Split the clipping text into lines
        $clippingParts = array_filter(explode("\n", trim($this->rawText)));

        // Get the line containing the title and author
        $titleAndAuthor = self::removeUtf8Bom($clippingParts[0]);

        // Extract the author name, which is in parentheses
        preg_match('/\((.*)\)/', $titleAndAuthor, $authorMatches);

        // Capture author name without parentheses
        $this->author = $authorMatches[1];

        // Split a lastname, firstname author format into pieces
        $authorParts = explode(', ', $this->author);

        // Standardize author name (`Watts, Alan W.` → `Alan W. Watts`)
        if (count($authorParts) === 2) {
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
            throw new \Exception("Invalid type {$this->type}.");
        }

        // Join the remaining lines as our highlight or note text
        $textLines = array_slice($clippingParts, 2);
        $this->text = trim(implode("\n", $textLines));
    }
}
