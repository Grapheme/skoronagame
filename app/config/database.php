<?php

$config = array(

	'fetch' => PDO::FETCH_CLASS,
	'default' => 'mysql',
	'connections' => array(
		'sqlite' => array(
			'driver'   => 'sqlite',
			'database' => __DIR__.'/../database/production.sqlite',
			'prefix'   => '',
		),
		'mysql' => array(
			'driver'    => 'mysql',
			'host'      => '',
			'database'  => '',
			'username'  => '',
			'password'  => '',
			'charset'   => 'utf8',
			'collation' => 'utf8_unicode_ci',
			'prefix'    => '',
		),
		'pgsql' => array(
			'driver'   => 'pgsql',
			'host'     => 'localhost',
			'database' => '',
			'username' => 'root',
			'password' => '',
			'charset'  => 'utf8',
			'prefix'   => '',
			'schema'   => 'public',
		),
		'sqlsrv' => array(
			'driver'   => 'sqlsrv',
			'host'     => 'localhost',
			'database' => '',
			'username' => 'root',
			'password' => '',
			'prefix'   => '',
		),
	),
	'migrations' => 'migrations',
	'redis' => array(
		'cluster' => false,
		'default' => array(
			'host'     => '127.0.0.1',
			'port'     => 6379,
			'database' => 0,
		),
	),
);

$config_file_path = '/srv/www/skoronagame/conf/database';
if(file_exists($config_file_path)):
    $config_data = file_get_contents($config_file_path);
    $lines = explode("\n", $config_data);
    $conf_lines = array();
    foreach($lines as $line):
        $conf_lines[] = explode('=',$line);
    endforeach;
    foreach($conf_lines as $conf):
        if(trim($conf[0]) == 'DB_HOST'):
            $config['connections']['mysql']['host'] = trim($conf[1]);
        elseif(trim($conf[0]) == 'DB_NAME'):
            $config['connections']['mysql']['database'] = trim($conf[1]);
        elseif(trim($conf[0]) == 'DB_USER'):
            $config['connections']['mysql']['username'] = trim($conf[1]);
        elseif(trim($conf[0]) == 'DB_PASSWORD'):
            $config['connections']['mysql']['password'] = trim($conf[1]);
        endif;
    endforeach;
endif;
return $config;