<?php
date_default_timezone_set('Europe/Amsterdam');

ini_set('display_errors', true);
error_reporting(E_ALL);

function curl_get_contents($url) {
	$curl = curl_init($url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($curl, CURLOPT_HEADER, 0);
	$out = curl_exec($curl);
	curl_close($curl);
	return $out;
}

function decode_entities($text) {
	return preg_replace_callback("/(&#[0-9]+;)/", function($m) { return mb_convert_encoding($m[1], "UTF-8", "HTML-ENTITIES"); }, $text);
}

$api_response = curl_get_contents('http://lyrics.wikia.com/api.php?fmt=json&action=lyrics&artist='.rawurlencode($_GET['artist']).'&song='.rawurlencode($_GET['song']));


if (preg_match("/'lyrics':'Not found'/", $api_response))
	$response['error'] = 'Not found';

else if (!preg_match("/'url':'(.+?)(?<!\\\\)(?:\\\\{2})*'/", $api_response, $match))
	$response['error'] = 'Could not find url';

else if (($webpage = curl_get_contents($match[1])) === false)
	$response['error'] = 'Could not fetch lyrics';

else if (preg_match('/((?:&#\d+;|<\\/?[ib]>|<br \\/>){10,})/', $webpage, $match))
	$response['lyrics'] = decode_entities($match[1]);
else if (preg_match('/<div class=\'lyricbox\'>(.+?)<div class=\'lyricsbreak\'>/', $webpage, $match))
	$response['lyrics'] = decode_entities($match[1]);
else
	$response['error'] = 'Could not find lyrics';

header('Content-Type: application/json');
echo json_encode($response);
