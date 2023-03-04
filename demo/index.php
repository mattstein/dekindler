<?php

require '../vendor/autoload.php';

use mattstein\utilities\KindleClipping;
use mattstein\utilities\KindleClippingExtractor;

$source = $_POST['clipping'] ?? null;
$format = $_POST['format'] ?? null;
$types = $_POST['types'] ?? [KindleClipping::TYPE_NOTE, KindleClipping::TYPE_HIGHLIGHT, KindleClipping::TYPE_BOOKMARK];
$ignoreDuplicates = isset($_POST['ignoreDuplicates']) && $_POST['ignoreDuplicates'] === 'y';
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
<body class="bg-gray-200">
	<div class="mx-auto max-w-5xl my-12 bg-white rounded-lg shadow-xl p-8">
		<h1 class="text-4xl font-bold">Kindle Clipping Extractor Demo</h1>
		<form action="" method="post">
			<div class="flex my-4">
				<fieldset>
                    <div class="flex items-center my-1">
                        <label for="format" class="font-bold text-slate-500 text-sm mr-4">Output Format</label>
                        <input id="format" name="format" type="radio" value="markdown" class="mr-1" disabled> Markdown
                        <input id="format" name="format" type="radio" value="json" class="ml-2 mr-1" checked disabled> JSON
                    </div>
                    <div class="flex items-center my-1">
                        <label for="ignoreDuplicates" class="font-bold text-slate-500 text-sm mr-4">Ignore Duplicates</label>
                        <input id="ignoreDuplicates" name="ignoreDuplicates" type="checkbox" value="y"<?php echo $ignoreDuplicates ? ' checked' : '' ?>>
                    </div>
                    <div class="flex items-center my-1">
                        <label for="types" class="font-bold text-slate-500 text-sm mr-4">Types</label>
                        <div><input id="types-note" name="types[]" type="checkbox" class="ml-2 mr-1" value="note"<?php echo in_array('note', $types) ? ' checked' : '' ?>> <label for="types-note">Note</label></div>
                        <div><input id="types-highlight" name="types[]" type="checkbox" class="ml-2 mr-1" value="highlight"<?php echo in_array('highlight', $types) ? ' checked' : '' ?>> <label for="types-highlight">Highlight</label></div>
                        <div><input id="types-bookmark" name="types[]" type="checkbox" class="ml-2 mr-1" value="bookmark"<?php echo in_array('bookmark', $types) ? ' checked' : '' ?>> <label for="types-bookmark">Bookmark</label></div>
                    </div>
				</fieldset>
			</div>
			<div class="flex space-x-2 my-4">
				<div class="w-1/2">
					<label for="clipping" class="font-bold text-slate-500 text-sm">Source Text</label>
					<textarea name="clipping" id="clipping" cols="30" rows="30" class="w-full border p-3 font-mono text-xs"><?php echo $source; ?></textarea>
				</div>
				<div class="w-1/2">
					<label for="output" class="font-bold text-slate-500 text-sm">Output</label>
					<textarea name="output" id="output" cols="30" rows="30" class="w-full border p-3 font-mono text-xs" readonly><?php echo $output; ?></textarea>
				</div>
			</div>
            <div class="flex space-x-4 items-center">
                <button class="rounded px-12 py-2 bg-blue-500 text-white font-bold">Parse</button>
                <?php if ($error): ?>
                    <div class="text-red-500 text-sm font-bold"><?php echo $error; ?></div>
                <?php endif; ?>
            </div>
		</form>
	</div>
</body>
</html>

