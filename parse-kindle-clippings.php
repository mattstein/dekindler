<?php

include "src/KindleClippingExtractor.php";
include "src/KindleClipping.php";

use mattstein\utilities\KindleClippingExtractor;

// TODO: add option to append process date to filename and add metadata (find Goodreads or Amazon URL?)

$testMode = true;
$clippingsPath = $testMode ? 'test-data/My Clippings.txt' : '/Volumes/Kindle/documents/My Clippings.txt';
$parser = new KindleClippingExtractor();
//$parser->silent = true;
$clippings = $parser->parse(file_get_contents($clippingsPath));

$parser->overwrite = true;
$parser->write(
    KindleClippingExtractor::OUTPUT_FORMAT_MARKDOWN,
//    [ KindleClipping::TYPE_HIGHLIGHT ]
);
