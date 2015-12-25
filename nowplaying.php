<?php
date_default_timezone_set('Europe/Amsterdam');

ini_set('display_errors', true);
error_reporting(E_ALL);

$now_playing_data = file_get_contents('http://top2012.radio2.nl/data/cache/json/nowplaying.json?_' . time(), 'r');

$now_playing = json_decode($now_playing_data);

if (isset($now_playing->songversion, $now_playing->songversion->id))
{
	$list = json_decode(file_get_contents('2015.json'));

	foreach ($list as $position => $song) {
		if ($song->aid == $now_playing->songversion->id) {
			$now_playing->position = $song->pos;
			break;
		}
	}
}

header('Content-Type: application/json');
echo json_encode($now_playing);
