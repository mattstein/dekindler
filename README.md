# Kindle Clipping Extractor

Reads Amazon Kindle’s `My Clippings.txt` and parses into something useful.

## Usage

Write Markdown files, one per book, to an `output/` directory:

```php
<?php

include "src/KindleClippingExtractor.php";

$rawText = file_get_contents('/Volumes/Kindle/documents/My Clippings.txt');
$parser = new mattstein\utilities\KindleClippingExtractor();
$parser->parse($rawText);
$parser->write(KindleClippingExtractor::OUTPUT_FORMAT_MARKDOWN);
```

Customize output:

```php
// No console output
$parser->silent = true;

// Overwrite existing files in the output directory
$parser->overwrite = true;

// Change default `output/` directory (created if it doesn’t exist)
$parser->outputDir = 'someplace-else/';

// Change default JSON filename
$parser->jsonFilename = 'my-kindle-clippins.json';
```

Write a single JSON file instead, and only include highlights—omitting bookmarks and notes:

```php
// ...
include "src/KindleClipping.php";

$parser = new mattstein\utilities\KindleClippingExtractor();
$parser->parse($rawText);
$parser->write(
    KindleClippingExtractor::OUTPUT_FORMAT_JSON,
    [ \mattstein\utilities\KindleClipping::TYPE_HIGHLIGHT ]
);
```

## Output Examples

### Markdown

output/on-writing-well-30th-anniversary-edition-an-informal-guide-to-writing-nonfiction.md

```markdown
---
- title: On Writing Well, 30th Anniversary Edition: An Informal Guide to Writing Nonfiction
- author: William Zinsser
---

# On Writing Well, 30th Anniversary Edition: An Informal Guide to Writing Nonfiction by William Zinsser

> They sit down to commit an act of literature, and the self who emerges on paper is far stiffer than the person who sat down to write.

– page 10, location 148-148, 11/10/20 at 09:35pm


> But the secret of good writing is to strip every sentence to its cleanest components. Every word that serves no function, every long word that could be a short word, every adverb that carries the same meaning that’s already in the verb, every passive construction that leaves the reader unsure of who is doing what—these are the thousand and one adulterants that weaken the strength of a sentence. And they usually occur in proportion to education and rank.

– page 11, location 166-169, 11/10/20 at 09:38pm


> Clear thinking becomes clear writing; one can’t exist without the other.

– page 12, location 182-182, 11/10/20 at 09:42pm
```

### JSON

output/kindle-clippings.json:

```json
[
  {
    "rawText": "\r\nOn Writing Well, 30th Anniversary Edition: An Informal Guide to Writing Nonfiction (William Zinsser)\r\n- Your Highlight on page 10 | Location 148-149 | Added on Tuesday, November 10, 2020 9:36:12 PM\r\n\r\nThey sit down to commit an act of literature, and the self who emerges on paper is far stiffer than the person who sat down to write. The problem is to find the real man or woman behind the tension.\r\n",
    "type": "highlight",
    "title": "On Writing Well, 30th Anniversary Edition: An Informal Guide to Writing Nonfiction",
    "author": "William Zinsser",
    "location": "148-149",
    "page": "10",
    "date":
    {
      "date": "2020-11-10 21:36:12.000000",
      "timezone_type": 3,
      "timezone": "UTC"
    },
    "text": "They sit down to commit an act of literature, and the self who emerges on paper is far stiffer than the person who sat down to write. The problem is to find the real man or woman behind the tension."
  },
  {
    "rawText": "\r\nOn Writing Well, 30th Anniversary Edition: An Informal Guide to Writing Nonfiction (William Zinsser)\r\n- Your Highlight on page 11 | Location 166-169 | Added on Tuesday, November 10, 2020 9:38:42 PM\r\n\r\nBut the secret of good writing is to strip every sentence to its cleanest components. Every word that serves no function, every long word that could be a short word, every adverb that carries the same meaning that’s already in the verb, every passive construction that leaves the reader unsure of who is doing what—these are the thousand and one adulterants that weaken the strength of a sentence. And they usually occur in proportion to education and rank.\r\n",
    "type": "highlight",
    "title": "On Writing Well, 30th Anniversary Edition: An Informal Guide to Writing Nonfiction",
    "author": "William Zinsser",
    "location": "166-169",
    "page": "11",
    "date":
    {
      "date": "2020-11-10 21:38:42.000000",
      "timezone_type": 3,
      "timezone": "UTC"
    },
    "text": "But the secret of good writing is to strip every sentence to its cleanest components. Every word that serves no function, every long word that could be a short word, every adverb that carries the same meaning that’s already in the verb, every passive construction that leaves the reader unsure of who is doing what—these are the thousand and one adulterants that weaken the strength of a sentence. And they usually occur in proportion to education and rank."
  },
  {
    "rawText": "\r\nOn Writing Well, 30th Anniversary Edition: An Informal Guide to Writing Nonfiction (William Zinsser)\r\n- Your Highlight on page 12 | Location 182-182 | Added on Tuesday, November 10, 2020 9:42:15 PM\r\n\r\nClear thinking becomes clear writing; one can’t exist without the other.\r\n",
    "type": "highlight",
    "title": "On Writing Well, 30th Anniversary Edition: An Informal Guide to Writing Nonfiction",
    "author": "William Zinsser",
    "location": "182-182",
    "page": "12",
    "date":
    {
      "date": "2020-11-10 21:42:15.000000",
      "timezone_type": 3,
      "timezone": "UTC"
    },
    "text": "Clear thinking becomes clear writing; one can’t exist without the other."
  }
]
```
