<?php

use mattstein\utilities\KindleClipping;
use mattstein\utilities\KindleClippingExtractor;

//test('extracts everything', function () {
//    $clippings = (new KindleClippingExtractor())
//        ->parse(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'My Clippings.txt'));
//
//    expect(count($clippings))->toBeGreaterThan(0);
//});

test('skips duplicates', function () {
    $content = "==========
A Pretend Book (Wordlesby, Samuel)
- Your Highlight on page 12 | Location 4061-4063 | Added on Monday, February 20, 2023 7:16:39 PM

We’re looking for highlights that were made and then corrected, which show up as separate chunks
==========
A Pretend Book (Wordlesby, Samuel)
- Your Highlight on page 12 | Location 4061-4063 | Added on Monday, February 20, 2023 7:16:45 PM

We’re looking for highlights that were made and then corrected, which show up as separate chunks in the clipping file.
==========
";
    $extractor = new KindleClippingExtractor();
    $clippings = $extractor->parse($content);
    expect(count($clippings))->toEqual(1);
    expect($extractor->duplicateCount)->toEqual(1);

    $extractor = new KindleClippingExtractor();
    $clippings = $extractor->parse($content, [], false);
    expect(count($clippings))->toEqual(2);
    expect($extractor->duplicateCount)->toEqual(1);
});

test('filters types', function () {
    $content = "==========
The Bullet Journal Method: Track Your Past, Order Your Present, Plan Your Future (Carroll, Ryder)
- Your Highlight on page 33 | Location 498-498 | Added on Saturday, December 21, 2019 3:54:53 PM

Being intentional about what you let into your life is a practice that shouldn’t be limited to the pages of your notebook.
==========
The Bullet Journal Method: Track Your Past, Order Your Present, Plan Your Future (Carroll, Ryder)
- Your Highlight on page 38 | Location 573-574 | Added on Saturday, December 21, 2019 5:20:41 PM

That’s right: The fact that it takes longer to write things out by hand gives handwriting its cognitive edge.
==========
The Bullet Journal Method: Track Your Past, Order Your Present, Plan Your Future (Carroll, Ryder)
- Your Note on page 38 | Location 574 | Added on Saturday, December 21, 2019 5:21:32 PM

Share with that guy that said handwriting is useless!
==========
Bird by Bird: Some Instructions on Writing and Life (Anne Lamott)
- Your Bookmark on page 70 | Location 1073 | Added on Friday, January 17, 2020 10:18:02 AM


==========
";
    // Everything
    $clippings = (new KindleClippingExtractor())->parse($content);
    expect(count($clippings))->toEqual(4);

    // Only notes
    $clippings = (new KindleClippingExtractor())->parse($content, [KindleClipping::TYPE_NOTE]);
    expect(count($clippings))->toEqual(1);
    expect($clippings[0]->text)->toContain('Share with that guy');

    // Only bookmarks
    $clippings = (new KindleClippingExtractor())->parse($content, [KindleClipping::TYPE_BOOKMARK]);
    expect(count($clippings))->toEqual(1);
    expect($clippings[0]->title)->toContain('Bird by Bird');

    // Only highlights and notes
    $extractor = new KindleClippingExtractor();
    $clippings = $extractor->parse($content, [KindleClipping::TYPE_NOTE, KindleClipping::TYPE_HIGHLIGHT], false);
    expect(count($clippings))->toEqual(3);
    expect($extractor->noteCount)->toEqual(1);
    expect($extractor->bookmarkCount)->toEqual(1);
    expect($extractor->highlightCount)->toEqual(2);
    expect($extractor->duplicateCount)->toEqual(0);
});

test('parses location-only highlights', function() {
    $content = "==========
A Pretend Book (Wordlesby, Samuel)
- Your Highlight on Location 587-588 | Added on Sunday, February 12, 2023 11:50:59 AM

For some reason highlights will sometimes come without a page number and only a location.
==========
A Pretend Book (Wordlesby, Samuel)
- Your Highlight on page 2 | Location 728-730 | Added on Sunday, February 12, 2023 12:06:43 PM

When they do have a page number, however, they’ll start with that and include location in another pipe-separated section.
==========
";
    $clippings = (new KindleClippingExtractor())->parse($content);
    $firstClipping = $clippings[0];
    $secondClipping = $clippings[1];

    expect($firstClipping->page)->toBeNull();
    expect($firstClipping->location)->toEqual('587-588');
    expect($secondClipping->page)->toEqual(2);
    expect($secondClipping->location)->toEqual('728-730');
});

test('handles different date formats', function () {
    $content = "==========
A Pretend Book (Wordlesby, Samuel)
- Your Highlight on Location 587-588 | Added on Sunday, February 12, 2023 11:50:59 AM

For some reason highlights will sometimes come without a page number and only a location.
==========
Fahrenheit 451 (Ray Bradbury)
- Your Highlight at location 784-785 | Added on Saturday, 26 March 2016 18:37:26

Who knows who might be the target of the well-read man?
==========
Zen and the Art of Motorcycle Maintenance (Robert M. Pirsig)
- Highlight on Page 6 | Loc. 190  | Added on Wednesday, 5 December 12 23:07:35 GMT+00:59

So we navigate mostly by dead reckoning,
==========
";
    $clippings = (new KindleClippingExtractor())->parse($content);

    expect($clippings[0]->date->format("Y-m-d H:i:s"))->toEqual('2023-02-12 11:50:59');
    expect($clippings[1]->date->format("Y-m-d H:i:s"))->toEqual('2016-03-26 18:37:26');
    expect($clippings[2]->date->format("Y-m-d H:i:s"))->toEqual('2012-12-05 23:07:35');
});

test('handles abbreviated location format', function() {
   $content = "Dive Into Python (Mark Pilgrim)
- Highlight Loc. 1150-51  | Added on Wednesday, 5 December 12 06:48:00 GMT+00:59

[0, 1, 2, 3, 4, 5, 6] >>> (MONDAY, TUESDAY, WEDNESDAY, THURSDAY, FRIDAY, SATURDAY, SUNDAY) = range(7)
";
    $clipping = new KindleClipping($content);
    expect($clipping->page)->toBeNull();
    expect($clipping->location)->toEqual('1150-51');
    expect($clipping->date->format("Y-m-d H:i:s"))->toEqual('2012-12-05 06:48:00');
});


