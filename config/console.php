<?php

Yii::setAlias('@tests', dirname(__DIR__) . '/tests');

$params = require(__DIR__ . '/params.php');
$db = require(__DIR__ . '/db.php');

return [
    'id' => 'basic-console',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'app\commands',
    'components' => [
        'authManager' => [
            'class' => 'yii\rbac\PhpManager',
            'defaultRoles' => ['admin','user'], // here define your roles
            //'authFile' => '@console/data/rbac.php' //the default path for rbac.php | OLD CONFIGURATION
            'itemFile' => '@app/rbac/items.php', //Default path to items.php | NEW CONFIGURATIONS
            // 'assignmentFile' => '@console/data/assignments.php', //Default path to assignments.php | NEW CONFIGURATIONS
            'ruleFile' => '@app/rbac/GroupRule.php', //Default path to rules.php | NEW CONFIGURATIONS
        ],
        'urlManager' => [
            'baseUrl' => 'http://skoronagame:8888/',
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                '<_c:[\w\-]+>/<id:\d+>' => '<_c>/view',
                '<_c:[\w\-]+>' => '<_c>/index',
                '<_c:[\w\-]+>/<_a:[\w\-]+>/<id:\d+>' => '<_c>/<_a>',
            ],
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'log' => [
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                    'logFile' => DATA_PATH.'/runtime/logs/console.log'
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['info'],
                    'categories' => ['sserver'],
                    'logVars' => [null],
                    'logFile' => DATA_PATH.'/runtime/logs/sserver.log',
                    'maxFileSize' => 1024 * 2,
                    'maxLogFiles' => 20,
                ],
            ],
        ],
        'db' => $db,
    ],
    'params' => $params,
];
