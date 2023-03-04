<?php

namespace mattstein\utilities;

use DateTimeImmutable;
use Exception;
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
     * @var string|null Relevant location number or range of numbers.
     */
    public ?string $location;

    /**
     * @var string|null Relevant page number.
     */
    public ?string $page;

    /**
     * @var string Original date string.
     */
    public string $rawDate;

    /**
     * @var DateTimeImmutable Date string represented as a `DateTime` object for formatting and other adventures.
     */
    public DateTimeImmutable $date;

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
     * @param string $text                 Text content from `My Clippings.txt` with delineators removed.
     * @param bool   $normalizeAuthorName  Whether the parser should try and normalize author names.
     * @throws Exception
     */
    public function __construct(string $text, bool $normalizeAuthorName = true)
    {
        $this->rawText = $text;
        $this->options['normalizeAuthorName'] = $normalizeAuthorName;

		$this->parse();
    }

    /**
     * Returns `true` if the provided clipping is considered a duplicate of this one.
     * @param KindleClipping $clipping
     * @return bool
     */
    public function isDuplicateOf(KindleClipping $clipping): bool
    {
        $textMatches = str_contains($this->text, $clipping->text) ||
            str_contains($clipping->text, $this->text);

        return $textMatches &&
            $this->title === $clipping->title
            && $this->type === $clipping->type;
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

        if (count($authorMatches) !== 2) {
            throw new RuntimeException("Can’t parse author from `$titleAndAuthor`.");
        }

        // Capture author name without parentheses
        $this->author = $authorMatches[1];

        // Standardize author name (`Watts, Alan W.` → `Alan W. Watts`)
        if ($this->options['normalizeAuthorName']) {
            $this->author = StringHelper::normalizeAuthorName($this->author);
        }

        // Capture title without author name
        $this->title = trim(str_replace($authorMatches[0], '', $titleAndAuthor));

        // Get the line with the clipping type, page number, location, and timestamp
        $clipped = $clippingParts[1];

        // Split the meta clipping line into pieces
        $clippedParts = explode(" | ", $clipped);

		// Split up the type and page number or location pieces
		$typeAndPage = strtolower(str_replace('- Your ', '', $clippedParts[0]));

		$isPageHighlight = str_contains($typeAndPage, 'on page');
		$isLocationOnHighlight = str_contains($typeAndPage, 'on location');
		$isLocationAtHighlight = str_contains($typeAndPage, 'at location');
        $isHighlightLoc = str_starts_with($clippedParts[0], '- Highlight Loc.');
        $isBookmarkLoc = str_starts_with($clippedParts[0], '- Bookmark Loc.');
        $isNoteLoc = str_starts_with($clippedParts[0], '- Note Loc.');

		if ($isPageHighlight) {
			$typeAndPageParts = explode(' on page ', $typeAndPage);
			$this->page = trim($typeAndPageParts[1]);

			if (count($clippedParts) === 2) {
				// No location
				$this->location = null;
				// Get the date string
				$dateString = trim(str_replace('Added on ', '', $clippedParts[1]));
			} else if (count($clippedParts) === 3) {
				$this->location = str_replace('Location ', '', $clippedParts[1]);
				// Get the date string
				$dateString = trim(str_replace('Added on ', '', $clippedParts[2]));
			}
		} elseif ($isLocationOnHighlight) {
			$typeAndPageParts = explode(' on location ', $typeAndPage);
			$this->page = null;
			$this->location = str_replace('location ', '', $typeAndPageParts[1]);
			// Get the date string
			$dateString = trim(str_replace('Added on ', '', $clippedParts[1]));
		} elseif ($isLocationAtHighlight) {
			$typeAndPageParts = explode(' at location ', $typeAndPage);
			$this->page = null;
			$this->location = str_replace('location ', '', $typeAndPageParts[1]);
			// Get the date string
			$dateString = trim(str_replace('Added on ', '', $clippedParts[1]));
		} elseif ($isHighlightLoc) {
			$this->page = null;
			$this->location = trim(str_replace('- Highlight Loc. ', '', $clippedParts[0]));
            $typeAndPageParts = ['highlight']; // cheat
			// Get the date string
			$dateString = trim(str_replace('Added on ', '', $clippedParts[1]));
		} elseif ($isBookmarkLoc) {
			$this->page = null;
			$this->location = trim(str_replace('- Bookmark Loc. ', '', $clippedParts[0]));
            $typeAndPageParts = ['bookmark']; // cheat
			// Get the date string
			$dateString = trim(str_replace('Added on ', '', $clippedParts[1]));
		} elseif ($isNoteLoc) {
			$this->page = null;
			$this->location = trim(str_replace('- Note Loc. ', '', $clippedParts[0]));
            $typeAndPageParts = ['note']; // cheat
			// Get the date string
			$dateString = trim(str_replace('Added on ', '', $clippedParts[1]));
		} else {
			throw new RuntimeException("Can’t parse type and location: `$typeAndPage`.");
		}

		$this->rawDate = $dateString;

        if (str_contains($dateString, 'Greenwich Mean Time')) {
            $dateString = str_replace('Greenwich Mean Time', 'GMT', $dateString);
        }

        if (! $parsedDate = new DateTimeImmutable($dateString)) {
            throw new RuntimeException("Can’t parse date: `$dateString`.");
        }

        $this->date = $parsedDate;
        // Trim standard characters and `-`
		$this->type = strtolower(trim($typeAndPageParts[0], " \t\n\r\0\x0B-"));

		// Don’t quietly tolerate nonsense
        if (!in_array($this->type, self::TYPES, true)) {
            throw new RuntimeException("Invalid type $this->type.");
        }

        // Join the remaining lines as our highlight or note text
        $textLines = array_slice($clippingParts, 2);
        $this->text = trim(implode("\n", $textLines));
    }
}
