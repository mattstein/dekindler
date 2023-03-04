<?php

require '../vendor/autoload.php';

use mattstein\utilities\KindleClipping;
use mattstein\utilities\KindleClippingExtractor;

$source = $_POST['clipping'] ?? null;
$format = $_POST['format'] ?? null;
$types = $_POST['types'] ?? [KindleClipping::TYPE_NOTE, KindleClipping::TYPE_HIGHLIGHT, KindleClipping::TYPE_BOOKMARK];
$ignoreDuplicates = !isset($_POST['ignoreDuplicates']) || $_POST['ignoreDuplicates'] === 'y';
$clippings = [];
$output = '';
$error = '';

if ($source) {
    $extractor = new KindleClippingExtractor();

	try {
		$clippings = $extractor->parse($source, $types, $ignoreDuplicates);
	} catch (Exception $e) {
        $error = $e->getMessage();
    }

    if (empty($error) && empty($clippings)) {
		$error = 'Couldn’t parse any clippings.';
	} else {
		$output = json_encode($clippings, JSON_PRETTY_PRINT);
	}
}

?>
<!doctype html>
<html>
<head>
    <title>Kindle Clipping Extractor Demo</title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-sky-100">
	<div class="mx-auto max-w-6xl my-12 bg-white rounded-lg shadow-2xl ring-slate-900/5">
        <div class="flex p-8 justify-between">
            <h1 class="text-3xl font-bold">Kindle Clipping Extractor</h1>
            <a href="https://github.com/mattstein/kindle-clipping-extractor" target="_blank" class="text-blue-600">
                GitHub
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 inline-block relative bottom-0.5">
                    <path fill-rule="evenodd" d="M4.25 5.5a.75.75 0 00-.75.75v8.5c0 .414.336.75.75.75h8.5a.75.75 0 00.75-.75v-4a.75.75 0 011.5 0v4A2.25 2.25 0 0112.75 17h-8.5A2.25 2.25 0 012 14.75v-8.5A2.25 2.25 0 014.25 4h5a.75.75 0 010 1.5h-5z" clip-rule="evenodd" />
                    <path fill-rule="evenodd" d="M6.194 12.753a.75.75 0 001.06.053L16.5 4.44v2.81a.75.75 0 001.5 0v-4.5a.75.75 0 00-.75-.75h-4.5a.75.75 0 000 1.5h2.553l-9.056 8.194a.75.75 0 00-.053 1.06z" clip-rule="evenodd" />
                </svg>
            </a>
        </div>
		<form action="" method="post">
			<div class="flex mb-4 px-8">
				<fieldset>
                    <div class="flex items-center my-1">
                        <label for="format" class="font-bold text-slate-500 text-sm mr-4 w-36">Output Format</label>
                        <input id="format" name="format" type="radio" value="markdown" class="mr-1" disabled> Markdown
                        <input id="format" name="format" type="radio" value="json" class="ml-6 mr-1" checked disabled> JSON
                    </div>
                    <div class="flex items-center my-1">
                        <label for="ignoreDuplicates" class="font-bold text-slate-500 text-sm mr-4 w-36">Ignore Duplicates</label>
                        <input id="ignoreDuplicates" name="ignoreDuplicates" type="checkbox" value="y"<?php echo $ignoreDuplicates ? ' checked' : '' ?>>
                    </div>
                    <div class="flex items-center my-1">
                        <label for="types" class="font-bold text-slate-500 text-sm mr-4 w-36">Types</label>
                        <div><input id="types-note" name="types[]" type="checkbox" class="mr-1" value="note"<?php echo in_array('note', $types) ? ' checked' : '' ?>> <label for="types-note">Note</label></div>
                        <div><input id="types-highlight" name="types[]" type="checkbox" class="ml-6 mr-1" value="highlight"<?php echo in_array('highlight', $types) ? ' checked' : '' ?>> <label for="types-highlight">Highlight</label></div>
                        <div><input id="types-bookmark" name="types[]" type="checkbox" class="ml-6 mr-1" value="bookmark"<?php echo in_array('bookmark', $types) ? ' checked' : '' ?>> <label for="types-bookmark">Bookmark</label></div>
                    </div>
				</fieldset>
			</div>
            <hr class="h-1 w-full my-6 border-slate-900/5">
			<div class="flex space-x-2 my-4 px-8 pb-4 pt-0">
				<div class="w-1/2">
					<label for="clipping" class="block font-bold text-slate-500 text-sm mb-2">Source Text</label>
					<textarea name="clipping" id="clipping" cols="30" rows="30" class="w-full border p-3 font-mono text-xs rounded shadow-sm"><?php echo $source; ?></textarea>
				</div>
				<div class="w-1/2">
					<label for="output" class="block font-bold text-slate-500 text-sm mb-2">Output</label>
					<textarea name="output" id="output" cols="30" rows="30" class="w-full border p-3 font-mono text-xs rounded shadow-sm" readonly><?php echo $output; ?></textarea>
				</div>
			</div>
            <div class="flex space-x-4 items-center px-8 pb-8">
                <button class="rounded px-12 py-2 bg-blue-600 text-white font-medium hover:bg-blue-700">Parse</button>
                <?php if ($error): ?>
                    <div class="text-red-500 text-sm font-bold"><?php echo $error; ?></div>
                <?php endif; ?>
            </div>
		</form>
	</div>
</body>
</html>

