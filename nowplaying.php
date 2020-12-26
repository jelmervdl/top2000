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

function get_song(array $list, $index)
{
	$song = $list[$index];
	
	return [
		'index' => $index,
		'title' => $song->s,
		'artist' => $song->a,
		'position' => $song->pos,
		'image' => $song->img,
	];
}

function find_song(array $list, array $now_playing)
{
	// If it is already linked by songfile, use that information
	if (!empty($now_playing['aid']))
		foreach ($list as $i => $song)
			if ($song->aid == $now_playing['aid'])
				return get_song($list, $i);

	// But sometimes Radio2 messed up, and we are just going try to match it on artist and title
	$simplified_artist = simplify($now_playing['artist']);
	$simplified_title = simplify($now_playing['title']);

	foreach ($list as $i => $song)
		if (simplify($song->s) == $simplified_title && simplify($song->a) == $simplified_artist)
			return get_song($list, $i);
	
	return null;
}

function simplify($text)
{
	$text = preg_replace('/[^a-z0-9\s]/', '', strtolower($text));
	$text = preg_replace('/^the\s/', '', $text);
	return $text;
}

function main()
{
	$year = intval(date('Y'));

	if (file_exists(sprintf('%d.json', $year)))
		$list = json_decode(file_get_contents(sprintf('%d.json', $year)));

	if (file_exists(sprintf('%d.json', $year - 1)))
		$list_prev_year = json_decode(file_get_contents(sprintf('%d.json', $year - 1)));

	$now_playing_data = curl_get_contents('https://www.nporadio2.nl/?option=com_ajax&plugin=nowplaying&format=json&channel=nporadio2');

	$now_playing = json_decode($now_playing_data)->data[0];

	$song = [
		'aid' => $now_playing->id,
		'artist' => $now_playing->artist,
		'title' => $now_playing->title
	];

	if (!empty($list) && $song_in_list = find_song($list, $song))
		$song = array_merge($song, $song_in_list);
	
	// Find the current song in previous year's list
	if (!empty($list_prev_year) && $song_in_prev_list = find_song($list_prev_year, $song))
		$song['prev_position'] = $song_in_prev_list['position'];

	return $song;
}

header('Content-Type: application/json');
echo json_encode(main());
