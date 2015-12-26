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

if (isset($now_playing->songfile, $now_playing->songfile->songversion))
{
	$list = json_decode(file_get_contents('2015.json'));

	foreach ($list as $position => $song) {
		if ($song->aid == $now_playing->songfile->songversion->id)
		{
			// Prefer the data from the listing as that one is(?) hand-curated
			$response['title'] = $song->s;
			$response['artist'] = $song->a;
			$response['position'] = $song->pos;
			$response['image'] = sprintf('http://radiobox2.omroep.nl/image/file/%d/title.jpg', $song->img);
			break;
		}
	}
}

header('Content-Type: application/json');
echo json_encode($response);
