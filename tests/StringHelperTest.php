<?php

use mattstein\dekindler\StringHelper;

test('generates expected slugs', function () {
    expect(StringHelper::slugify('Hello World'))->toEqual('hello-world');
    expect(StringHelper::slugify('Hello World', '_'))->toEqual('hello_world');
    expect(StringHelper::slugify('Let’s try this: use some interesting characters?'))->toEqual('lets-try-this-use-some-interesting-characters');
});

test('normalizes author names', function() {
    expect(StringHelper::normalizeAuthorName('Watts, Alan W.'))->toEqual('Alan W. Watts');
    expect(StringHelper::normalizeAuthorName('Alan W. Watts'))->toEqual('Alan W. Watts');
});