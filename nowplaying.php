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

function cast_song($song)
{
	if (isset($song->track)) { // 2022, 2023
		return [
			'title' => $song->track->title,
			'artist' => $song->track->artist,
			'image' => $song->track->coverUrl,
			'position' => $song->position->current,
			'prev_position' => $song->position->previous,
			'year' => $song->track->year ?? null,
		];
	} else { // 2021 and older
		return [
			'title' => $song->s ?? $song->title,
			'artist' => $song->a ?? $song->artist,
			'position' => $song->pos ?? $song->position,
			'image' => $song->img ?? $song->imageUrl,
		];
	}
}

function find_song(array $list, array $now_playing)
{
	// If it is already linked by songfile, use that information
	if (!empty($now_playing['id'])) {
		foreach ($list as $song)
			if (isset($song['id']) && $song['id'] == $now_playing['id'])
				return $song;
	}

	if (isset($now_playing['artist'], $now_playing['title'])) {
		// But sometimes Radio2 messed up, and we are just going try to match it on artist and title
		$simplified_artist = simplify($now_playing['artist']);
		$simplified_title = simplify($now_playing['title']);

		foreach ($list as $song)
			if (simplify($song['title']) == $simplified_title && simplify($song['artist']) == $simplified_artist)
				return $song;
	}
	
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
		$list = array_map('cast_song', json_decode(file_get_contents(sprintf('%d.json', $year))));

	if (file_exists(sprintf('%d.json', $year - 1)))
		$list_prev_year = array_map('cast_song', json_decode(file_get_contents(sprintf('%d.json', $year - 1))));

	$now_playing_data = curl_get_contents('https://www.nporadio2.nl/api/miniplayer/info?channel=npo-radio-2');

	$now_playing = json_decode($now_playing_data)->data->radioTrackPlays->data[0];

	$song = [
		'id' => $now_playing->radioTracks->id,
		'title' => $now_playing->radioTracks->name,
		'artist' => $now_playing->radioTracks->artist,
		'image' => $now_playing->radioTracks->coverUrl,
		'position' => intval($now_playing->cmsChartEditionPositions->position),
		'prev_position' => intval($now_playing->cmsChartEditionPositions->lastPosition)
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
