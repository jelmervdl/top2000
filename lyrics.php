<?php
header('Content-Type: application/json');

date_default_timezone_set('Europe/Amsterdam');

ini_set('display_errors', false);
error_reporting(E_ALL);

$fhandle = fopen('http://lystener.com/lyrics/'.urlencode($_GET['artist']).'/'.urlencode($_GET['song']), 'r');

if ($fhandle)
	fpassthru($fhandle);
