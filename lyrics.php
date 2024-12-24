<?php
require 'config.php';
date_default_timezone_set('Europe/Amsterdam');

ini_set('display_errors', true);
error_reporting(E_ALL);

function curl_get_contents($url) {
	$headers = [
		sprintf('Authorization: Bearer %s', GENIUS_ACCESS_TOKEN)
	];

	$curl = curl_init($url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($curl, CURLOPT_HEADER, 0);
	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	$out = curl_exec($curl);
	curl_close($curl);
	return $out;
}

function decode_entities($text) {
	return preg_replace_callback("/(&#[0-9]+;)/", function($m) { return mb_convert_encoding($m[1], "UTF-8", "HTML-ENTITIES"); }, $text);
}

function first($items, $test) {
	foreach ($items as $item)
		if ($test($item))
			return $item;

	return null;
}

function scrape_text($root) {
	$text = '';

	if ($root->nodeType == XML_ELEMENT_NODE && $root->nodeName == 'br')
		$text .= "\n";

	foreach ($root->childNodes as $child) {
		switch ($child->nodeType) {
			case XML_ELEMENT_NODE:
				$text .= scrape_text($child);
				break;
			case XML_TEXT_NODE:
				$text .= $child->nodeValue;
				break;
		}
	}

	return $text;
}

function scrape_lyrics($url) {
	$html = curl_get_contents($url);

	$dom = new DOMDocument();
	$dom->loadHTML($html, LIBXML_NOERROR);

	$text = '';

	foreach ($dom->getElementsByTagName('div') as $div) {
		if (strstr($div->getAttribute('class'), 'Lyrics__Container') !== false)
			$text .= scrape_text($div) . "\n";
	}

	return $text;
}

function get_lyrics($artist, $song) {
	$track_search = json_decode(curl_get_contents(sprintf('https://api.genius.com/search?q=%s+%s',
		rawurlencode($artist),
		rawurlencode($song))));

	if ($track_search->meta->status != 200)
		return ['error' => 'Could not search for track info'];

	$song = first($track_search->response->hits, function($hit) { return $hit->type == 'song'; });

	if (!$song)
		return ['error' => 'Could not find track in database'];

	$lyrics = scrape_lyrics($song->result->url);

	if (!$lyrics)
		return ['song' => $song, 'error' => 'Could not scrape lyrics'];

	return ['song' => $song, 'lyrics' => $lyrics];
}

function get_lyrics_cached($artist, $song) {
	$cache_key = md5(sprintf('%s#%s', $artist, $song));

	$cache_file = sprintf('cache/%s.json', $cache_key);

	if (file_exists($cache_file))
		return json_decode(file_get_contents($cache_file));

	$lyrics = get_lyrics($artist, $song);

	file_put_contents($cache_file, json_encode($lyrics));

	return $lyrics;
}

function main() {
	return get_lyrics_cached($_GET['artist'], $_GET['song']);
}

header('Content-Type: text/plain; charset=utf-8');
echo json_encode(main());
