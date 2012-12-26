<?php
header('Content-Type: application/json');

date_default_timezone_set('Europe/Amsterdam');

ini_set('display_errors', false);
error_reporting(E_ALL);

$fhandle = fopen('http://top2012.radio2.nl/data/cache/json/nowplaying.json?_' . time(), 'r');

if ($fhandle)
	fpassthru($fhandle);
