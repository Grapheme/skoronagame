<?php

defined('DATA_PATH') or define('DATA_PATH',__DIR__.'/../../../data');
defined('CONF_PATH') or define('CONF_PATH',__DIR__.'/../../../conf');
defined('BASE_PATH') or define('BASE_PATH',dirname(__FILE__).'/..');

defined('RNTM_PATH') or define('RNTM_PATH',DATA_PATH.'/runtime');
defined('DB_PATH') or define('DB_PATH',CONF_PATH.'/database');

defined('ASSETS_PATH') or define('ASSETS_PATH',DATA_PATH.'/assets');
defined('ASSETS_URL') or define('ASSETS_URL','/data/assets');

defined('IMG_PATH') or define('IMG_PATH',DATA_PATH.'/images');

// comment out the following two lines when deployed to production
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

require(__DIR__ . '/../vendor/autoload.php');
require(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');

$config = require(__DIR__ . '/../config/web.php');

(new yii\web\Application($config))->run();
