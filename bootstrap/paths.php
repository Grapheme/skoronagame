<?php
define("PICHESKY", (bool)(@substr($_SERVER['HTTP_HOST'], -3) == ".ru"));
$paths = array(
	'app' => __DIR__.'/../app',
	'public' => __DIR__.'/../htdocs/',
	'base' => __DIR__.'/..',
);
$paths['storage'] = @PICHESKY ? '/srv/www/skoronagame/data/storage' : __DIR__.'/../app/storage';
Helper::tad($paths);
return $paths;