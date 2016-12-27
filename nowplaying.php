<?php
date_default_timezone_set('Europe/Amsterdam');

ini_set('display_errors', true);
error_reporting(E_ALL);

$list = json_decode(file_get_contents('2016.json'));

$now_playing_data = file_get_contents('http://radiobox2.omroep.nl/data/radiobox2/nowonair/2.json?npo_cc_skip_wall=1');

$now_playing = json_decode($now_playing_data)->results[0];

$response = [
	'artist' => $now_playing->songfile->artist,
	'title' => $now_playing->songfile->title,
	'expires' => strtotime($now_playing->stopdatetime)
];

function get_song($index)
{
	global $list;

	$song = $list[$index];
	
	return [
		'index' => $index,
		'title' => $song->s,
		'artist' => $song->a,
		'position' => $song->pos,
		'image' => sprintf('http://radiobox2.omroep.nl/image/file/%d/title.jpg', $song->img),
	];
}

function find_song(Callable $compare)
{
	global $list;

	foreach ($list as $index => $song)
		if ($compare($song))
			return get_song($index);
	
	return null;
}

function simplify($text)
{
	$text = preg_replace('/[^a-z0-9]/', '', strtolower($text));
	$text = preg_replace('/^the\s/', '', $text);
	return $text;
}

// If it is already linked by songfile, use that information
if (isset($now_playing->songfile, $now_playing->songfile->songversion)) {
	$found = find_song(function($song) use ($now_playing) {
		return $song->aid == $now_playing->songfile->songversion->id;
	});
}
// But sometimes Radio2 messed up, and we are just going try to match it on artist and title
else {
	$simplified_artist = simplify($now_playing->songfile->artist);
	$simplified_title = simplify($now_playing->songfile->title);
	$found = find_song(function($song) use ($simplified_artist, $simplified_title) {
		return simplify($song->s) == $simplified_title
			&& simplify($song->a) == $simplified_artist;
	});
}

if ($found) {
	$response = array_merge($response, $found);

	// If the song has already expired for about half a minute, skip on to the next
	if ($response['expires'] - time() < -30)
		$response = get_song($response['index'] - 1);
}

header('Content-Type: application/json');
echo json_encode($response);
