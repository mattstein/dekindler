<?php

// TODO: support JSON export as option
// TODO: add option to append process date to filename and add metadata (find Goodreads or Amazon URL?)
$testMode = true;
$clippingsPath = $testMode ? 'test-data/My Clippings.txt' : '/Volumes/Kindle/documents/My Clippings.txt';
$outputDir = "output/";

$separator = "==========";
$data = remove_utf8_bom(file_get_contents($clippingsPath));

$clippings = array_filter(explode($separator, $data), function($value) {
    return ! empty(trim($value));
});

$clippingsByBook = [];
// TODO: track clipping and bookmark counts separately + report at end
$clippingCount = 0;
$bookmarkCount = 0;
$count = 0;

foreach ($clippings as $clipping) {
    $clipping = trim($clipping);
    $clippingParts = array_filter(explode("\n", $clipping));

    $titleAndAuthor = remove_utf8_bom($clippingParts[0]);
    $clipped = $clippingParts[1];

    preg_match('/\((.*)\)/', $titleAndAuthor, $authorMatches);

    $author = $authorMatches[1];
    $authorParts = explode(', ', $author);

    // standardize author name
        // Watts, Alan W. → Alan W. Watts
    if (count($authorParts) === 2) {
        $author = trim(trim($authorParts[1]) . ' ' . trim($authorParts[0]));
    }

    $title = trim(str_replace($authorMatches[0], '', $titleAndAuthor));


    $passageLines = array_slice($clippingParts, 2);
    $passage = trim(join("\n", $passageLines));

    // add this item if we don’t already have it
    if (! isset($clippingsByBook[$titleAndAuthor])) {
        $clippingsByBook[$titleAndAuthor] = [];
    }

    $clippedParts = explode(" | ", $clipped);

    $typeAndPage = str_replace('- Your ', '', $clippedParts[0]);
    $location = str_replace('Location ', '', $clippedParts[1]);
    $date = str_replace('Added on ', '', $clippedParts[2]);

    // skip bookmarks
    if (str_contains(strtolower($typeAndPage), 'bookmark')) {
        continue;
    }

    $clippingsByBook[$titleAndAuthor][] = [
        'title' => $title,
        'author' => $author,
        'clipped' => $clipped,
        'typeAndPage' => $typeAndPage,
        'location' => $location,
        'date' => $date,
        'passage' => $passage
    ];

    $count++;

    // line 1: Title of Book (Author)
    // line 2: - Your Highlight on page 77 | Location 795-796 | Added on Tuesday, December 31, 2019 10:57:15 PM
        // OR - Your Bookmark on page 106 | Location 1612 | Added on Friday, January 3, 2020 10:40:51 PM
    // line 3: [empty]
    // line 4+: highlighted passage
}

output(count($clippingsByBook) . " books, " . $count . " clippings." . PHP_EOL);

if ( ! file_exists($outputDir)) {
    mkdir($outputDir);
}

foreach ($clippingsByBook as $book => $clippings) {
    if (empty($book) || !isset($clippings[0])) {
        output(sprintf("!! Skipping %s", $book));
        continue;
    }
    
    $firstClipping = $clippings[0];
    $clippingCount = count($clippings);

    output($firstClipping['title'] . ' (' . $clippingCount . ' clippings)');

    $filename = slugify($firstClipping['title']) . ".md";

    $markdown = "---\n";
    $markdown .= "- title: " . $firstClipping['title'] . PHP_EOL;
    $markdown .= "- author: " . $firstClipping['author'] . PHP_EOL;
    $markdown .= "---\n\n";
    $markdown .= "# ${firstClipping['title']} by ${firstClipping['author']}" . PHP_EOL;

    // TODO: include location in markdown output
    // TODO: shorten date in markdown output

    foreach ($clippings as $clipping) {
        $markdown .= "\n";
        $markdown .= "> ${clipping['passage']}\n\n";
        $markdown .= "– ${clipping['typeAndPage']}, ${clipping['date']}" . PHP_EOL;
        $markdown .= "\n";
    }

    file_put_contents($outputDir . $filename, $markdown);
}


// https://stackoverflow.com/a/15423899
function remove_utf8_bom($text)
{
    $bom = pack('H*','EFBBBF');
    $text = preg_replace("/^$bom/", '', $text);
    return $text;
}

// https://stackoverflow.com/a/2955878
function slugify($text, string $divider = '-')
{
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


function output($message)
{
    $message = $message . PHP_EOL;
    // print($message);
    fwrite(\STDOUT, $message);
    // @flush();
    // @ob_flush();
}
