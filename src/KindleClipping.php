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
    public ?string $location = null;

    /**
     * @var string|null Relevant page number.
     */
    public ?string $page = null;

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
     * @throws RuntimeException|Exception if an invalid clipping type is encountered.
     */
    private function parse(): void
    {
        // Split the clipping text into lines
        $rows = array_filter(explode("\n", trim($this->rawText)));

        // Get the line containing the title and author
        $titleAndAuthor = StringHelper::removeUtf8Bom($rows[0]);

        // Extract the author name, which is in parentheses
        preg_match('/\((.*)\)/', $titleAndAuthor, $authorMatches);

        if (count($authorMatches) !== 2) {
            throw new RuntimeException("Can’t parse author from `$titleAndAuthor`.");
        }

        // Capture author name without parentheses
        $this->author = $this->options['normalizeAuthorName'] ?
			StringHelper::normalizeAuthorName($authorMatches[1]) :
			$authorMatches[1];

        // Capture title without author name
        $this->title = trim(str_replace($authorMatches[0], '', $titleAndAuthor));

        // Get the line with the clipping type, page number, location, and timestamp
        $meta = $rows[1];

        // Split the meta clipping line into pieces
        $columns = explode(" | ", $meta);

		if (count($columns) === 2) {
			[$left, $right] = $columns;
			$middle = null;
		} else if (count($columns) === 3) {
			[$left, $middle, $right] = $columns;
		} else {
			throw new RuntimeException("Unexpected number of columns in `$meta`.");
		}

		// Remove leading `-` we won’t need
		$left = trim($left, '-');

		$this->validateMeta($left, $middle, $right);

		// Handle the middle if we have one
		if ($middle) {
			$this->location = preg_replace("/[^0-9-]/", "", $middle);
		}

		// Handle the right, which we should have
		$this->rawDate = trim(str_replace('Added on ', '', $right));

		// Determine the type of clipping
		if (mb_stripos($left, 'highlight') !== false) {
			$this->type = self::TYPE_HIGHLIGHT;
		} else if (mb_stripos($left, 'bookmark') !== false) {
			$this->type = self::TYPE_BOOKMARK;
		} else if (mb_stripos($left, 'note') !== false) {
			$this->type = self::TYPE_NOTE;
		} else {
			throw new RuntimeException("Can’t determine valid type from `$left`.");
		}

		if ( ! $this->location && mb_stripos($left, 'loc') !== false) {
			$this->location = preg_replace("/[^0-9-]/", "", $left);
		}

		if ( ! $this->page && mb_stripos($left, 'page') !== false) {
			$this->page = preg_replace("/[^0-9-]/", "", $left);
		}


        if (! $parsedDate = new DateTimeImmutable($this->normalizeDate($this->rawDate))) {
            throw new RuntimeException("Can’t parse date: `$this->rawDate`.");
        }

        $this->date = $parsedDate;

        // Join the remaining lines as our highlight or note text
        $textLines = array_slice($rows, 2);
        $this->text = trim(implode("\n", $textLines));
    }

	/**
	 * Throw exceptions if the clipping’s meta row has an unexpected format.
	 *
	 * @param string      $left
	 * @param string|null $middle
	 * @param string      $right
	 * @return void
	 */
	private function validateMeta(string $left, string|null $middle, string $right): void
	{
		// The clipping type should always be the leftmost column
		if (preg_match('/(bookmark|note|highlight)/i', $left) === 0) {
			throw new RuntimeException("First meta column must contain known type; got `$left`.");
		}

		// The clipping timestamp should always be the rightmost column
		if (preg_match('/(added on)/i', $right) === 0) {
			throw new RuntimeException("Last meta column must contain timestamp; got `$right`.");
		}

		// If we have a middle column, it should always include `Loc.` or `Location`
		if ($middle && preg_match('/(loc)/i', $middle) === 0) {
			throw new RuntimeException("Middle meta column must contain a location; got `$middle`.");
		}

		if ($middle && mb_stripos($left, 'page') === false) {
			throw new RuntimeException("A clipping with a location column must have a page number to the left; got `$left`.");
		}
	}

	/**
	 * Transform any unexpected date string pieces before PHP tries to parse it.
	 * @param string $date
	 * @return string
	 */
	private function normalizeDate(string $date): string
	{
		if (str_contains($date, 'Greenwich Mean Time')) {
			$date = str_replace('Greenwich Mean Time', 'GMT', $date);
		}

		return $date;
	}
}
