<?php
date_default_timezone_set('Europe/Amsterdam');

ini_set('display_errors', true);
error_reporting(E_ALL);

$now_playing_data = file_get_contents('http://radiobox2.omroep.nl/data/radiobox2/nowonair/2.json?npo_cc_skip_wall=1');

$now_playing = json_decode($now_playing_data)->results[0];

$response = [
	'artist' => $now_playing->songfile->artist,
	'title' => $now_playing->songfile->title,
	'expires' => strtotime($now_playing->stopdatetime)
];

function find_song(Callable $compare)
{
	$list = json_decode(file_get_contents('2015.json'));

	foreach ($list as $position => $song)
		if ($compare($song))
			return [
				'title' => $song->s,
				'artist' => $song->a,
				'position' => $song->pos,
				'image' => sprintf('http://radiobox2.omroep.nl/image/file/%d/title.jpg', $song->img),
			];
	
	return null;
}

function simplify($text)
{
	return preg_replace('/[^a-z0-9]/', '', strtolower($text));
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

if ($found)
	$response = array_merge($response, $found);	

header('Content-Type: application/json');
echo json_encode($response);
