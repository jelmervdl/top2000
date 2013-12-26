<?php
header('Content-Type: application/json');

date_default_timezone_set('Europe/Amsterdam');

ini_set('display_errors', false);
error_reporting(E_ALL);

$now_playing_data = file_get_contents('http://top2012.radio2.nl/data/cache/json/nowplaying.json?_' . time(), 'r');

$site = file_get_contents('2013.html');

$now_playing = json_decode($now_playing_data);

if (preg_match('{<u data-prev="\d+" data-daletid="' . preg_quote($now_playing->dalet_id) . '">(\d+)</u>}', $site, $match))
	$now_playing->position = $match[1];

echo json_encode($now_playing);

