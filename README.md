# Kindle Clipping Extractor

Reads Amazon Kindle’s `My Clippings.txt`, parses them, and writes them out as Markdown or JSON.

## Setup

1. Check out this project.
2. Run `composer install` to install dependencies.
3. Run `chmod +x extractor` so you can execute commands.

## Usage

Connect your Kindle to a Mac<sup>[1](#footnote1)</sup> so it’s mounted.

Write Markdown files, one per book, to an `output/` directory:

```shell
./extractor extract
```

Customize output:

```shell
./extractor extract --overwrite true --webSafeFilenames false someplace-else/
```

Write a single JSON file instead, and only include highlights—omitting bookmarks and notes:

```shell
./extractor extract --format json --omitHighlights true --omitBookmarks true
```

## Arguments

| Argument    | Description                                           | Default Value |
| ----------- | ----------------------------------------------------- | ------------- |
| `outputDir` | (optional) Directory where file(s) should be written. | `'output/'`   |

## Options

| Option           | Description                                       | Default Value                                  |
| ---------------- | ------------------------------------------------- | ---------------------------------------------- |
| `sourceFilePath`   | The plain-text file to be read and parsed.      | `'/Volumes/Kindle/documents/My Clippings.txt'` |
| `format`           | Output format. (`'json'` or `'markdown'`)       | `'markdown'`                                   |
| `overwrite`        | Whether to overwrite existing output file(s).   | `false`                                        |
| `webSafeFilenames` | Whether Markdown filenames should be slugified. | `true`                                         |
| `jsonFilename`     | Filename to use if writing JSON.                | `'kindle-clippings.json'`                      |
| `omitHighlights`   | Whether to skip processing highlights.          | `false`                                        |
| `omitNotes`        | Whether to skip processing notes.               | `false`                                        |
| `omitBookmarks`    | Whether to skip processing bookmarks.           | `true`                                         |

## Output Examples

### Markdown

output/on-writing-well-30th-anniversary-edition-an-informal-guide-to-writing-nonfiction.md

```markdown
---
title: On Writing Well, 30th Anniversary Edition: An Informal Guide to Writing Nonfiction
author: William Zinsser
---

# On Writing Well, 30th Anniversary Edition: An Informal Guide to Writing Nonfiction by William Zinsser

> They sit down to commit an act of literature, and the self who emerges on paper is far stiffer than the person who sat down to write.

– page 10, location 148-148, 11/10/20 at 9:35pm


> But the secret of good writing is to strip every sentence to its cleanest components. Every word that serves no function, every long word that could be a short word, every adverb that carries the same meaning that’s already in the verb, every passive construction that leaves the reader unsure of who is doing what—these are the thousand and one adulterants that weaken the strength of a sentence. And they usually occur in proportion to education and rank.

– page 11, location 166-169, 11/10/20 at 9:38pm


> Clear thinking becomes clear writing; one can’t exist without the other.

– page 12, location 182-182, 11/10/20 at 9:42pm
```

### JSON

output/kindle-clippings.json

```json
[
  {
    "rawText": "\r\nOn Writing Well, 30th Anniversary Edition: An Informal Guide to Writing Nonfiction (William Zinsser)\r\n- Your Highlight on page 10 | Location 148-149 | Added on Tuesday, November 10, 2020 9:36:12 PM\r\n\r\nThey sit down to commit an act of literature, and the self who emerges on paper is far stiffer than the person who sat down to write. The problem is to find the real man or woman behind the tension.\r\n",
    "type": "highlight",
    "title": "On Writing Well, 30th Anniversary Edition: An Informal Guide to Writing Nonfiction",
    "author": "William Zinsser",
    "location": "148-149",
    "page": "10",
    "rawDate": "Tuesday, November 10, 2020 9:35:48 PM",
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
    "rawDate": "Tuesday, November 10, 2020 9:38:42 PM",
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
    "rawDate": "Tuesday, November 10, 2020 9:42:15 PM",
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

## Support

This is a spare time project I made for myself and figured I’d publish. I’ll review PRs and respond to issues when I have time.

---

<a name="footnote1">1</a>: Doesn’t have to be a Mac, but otherwise you’ll need to specify a <code>sourceFilePath</code> for your system.
