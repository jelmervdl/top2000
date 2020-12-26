<?php
require 'config.php';

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

function main() {
	$track_search = json_decode(curl_get_contents(sprintf('https://api.happi.dev/v1/music?limit=1&apikey=%s&type=track&q=%s+%s',
		rawurlencode(HAPPI_API_KEY),
		rawurlencode($_GET['artist']),
		rawurlencode($_GET['song']))));

	if (!$track_search->success)
		return ['error' => 'Could not search for track info'];

	if ($track_search->length < 1)
		return ['error' => 'Could not find track in database'];

	$lyrics_search = json_decode(curl_get_contents(sprintf('https://api.happi.dev/v1/music/artists/%d/albums/%d/tracks/%d/lyrics?apikey=%s',
		$track_search->result[0]->id_artist,
		$track_search->result[0]->id_album,
		$track_search->result[0]->id_track,
		rawurlencode(HAPPI_API_KEY))));

	if (!$lyrics_search->success)
		return ['error' => 'Could not fetch lyrics'];

	return ['lyrics' => nl2br($lyrics_search->result->lyrics)];
}

header('Content-Type: application/json');
echo json_encode(main());
