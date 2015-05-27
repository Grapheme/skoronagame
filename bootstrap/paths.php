<?php
define("PICHESKY", (bool)(@substr($_SERVER['HTTP_HOST'], -11) == "pichesky.ru"));
$paths = array(
	'app' => __DIR__.'/../app',
	'public' => __DIR__.'/../htdocs/',
	'base' => __DIR__.'/..',
);
$paths['storage'] = @PICHESKY ? '/srv/www/skoronagame/data/storage' : __DIR__.'/../app/storage';
return $paths;