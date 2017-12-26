<?php
date_default_timezone_set('Europe/Amsterdam');

ini_set('display_errors', true);
error_reporting(E_ALL);

function decode_entities($text) {
	return preg_replace_callback("/(&#[0-9]+;)/", function($m) { return mb_convert_encoding($m[1], "UTF-8", "HTML-ENTITIES"); }, $text);
}

$api_response = file_get_contents('http://lyrics.wikia.com/api.php?fmt=json&action=lyrics&artist='.rawurlencode($_GET['artist']).'&song='.rawurlencode($_GET['song']));

if (preg_match("/'lyrics':'Not found'/", $api_response))
	$response['error'] = 'Not found';

else if (!preg_match("/'url':'(.+?)(?<!\\\\)(?:\\\\{2})*'/", $api_response, $match))
	$response['error'] = 'Could not find url';

else if (($webpage = file_get_contents($match[1])) === false)
	$response['error'] = 'Could not fetch lyrics';

else if (preg_match('/((?:&#\d+;|<\\/?i>|<br \\/>){10,})/', $webpage, $match))
	$response['lyrics'] = decode_entities($match[1]);
else if (preg_match('/<div class=\'lyricbox\'>(.+?)<div class=\'lyricsbreak\'>/', $webpage, $match))
	$response['lyrics'] = decode_entities($match[1]);
else
	$response['error'] = 'Could not find lyrics';

header('Content-Type: application/json');
echo json_encode($response);
