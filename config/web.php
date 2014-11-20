<?php

$params = require(__DIR__ . '/params.php');

$config = [
    'id' => 'basic',
    'defaultRoute' => 'main/default/index',

    'basePath' => BASE_PATH,
    'runtimePath' =>RNTM_PATH,
    'bootstrap' => ['log'],
    'aliases' => [
        '@eview' => '@vendor/spiker/excelview',
    ],
    'components' => [

        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
        ],
        'eauth' => array(
            'class' => 'nodge\eauth\EAuth',
            'popup' => true, // Use the popup window instead of redirecting.
            'cache' => false, // Cache component name or false to disable cache. Defaults to 'cache' on production environments.
            'cacheExpire' => 0, // Cache lifetime. Defaults to 0 - means unlimited.
            'httpClient' => array(
                // uncomment this to use streams in safe_mode
                //'useStreamsFallback' => true,
            ),
            'services' => array( // You can change the providers and their classes.
                'facebook' => array(
                    // register your app here: https://developers.facebook.com/apps/
                    'class' => 'nodge\eauth\services\FacebookOAuth2Service',
                    'clientId' => '...',
                    'clientSecret' => '...',
                ),
                'vkontakte' => array(
                    // register your app here: https://vk.com/editapp?act=create&site=1
                    'class' => 'nodge\eauth\services\VKontakteOAuth2Service',
                    'clientId' => '3688869',
                    'clientSecret' => 'U0RpXo9X5xMPnkhEkRJe',
                ),
                'mailru' => array(
                    // register your app here: http://api.mail.ru/sites/my/add
                    'class' => 'nodge\eauth\services\MailruOAuth2Service',
                    'clientId' => '...',
                    'clientSecret' => '...',
                ),
                'odnoklassniki' => array(
                    // register your app here: http://dev.odnoklassniki.ru/wiki/pages/viewpage.action?pageId=13992188
                    // ... or here: http://www.odnoklassniki.ru/dk?st.cmd=appsInfoMyDevList&st._aid=Apps_Info_MyDev
                    'class' => 'nodge\eauth\services\OdnoklassnikiOAuth2Service',
//                    'clientId' => '1098102784',
//                    'clientPublic' => 'CBAJLEHCEBABABABA',
//                    'clientSecret' => 'F3FF18C9B870008AD62D5506',
                    'clientId' => '1106209280',
                    'clientSecret' => '9F95774CCE7A6D40D2384A48',
                    'clientPublic' => 'CBALGQOCEBABABABA',
                    'title' => 'Odnoklas.',
                ),
            ),
        ),

        'i18n' => array(
            'translations' => array(
                'eauth' => array(
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath' => '@eauth/messages',
                ),
            ),
        ),
        'assetManager' => [

            'basePath' =>ASSETS_PATH,
            'baseUrl' => ASSETS_URL,
        ],
        'authManager' => [
            'class' => 'yii\rbac\PhpManager',
            'defaultRoles' => ['user', 'moderator', 'admin'],
            'itemFile' => '@vova07/rbac/data/items.php',
//            'assignmentFile' => '@vova07/rbac/data/assignments.php',
            'ruleFile' => '@vova07/rbac/data/rules.php',
        ],
        'urlManager' => [
            'class' => 'yii\web\UrlManager',
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                '' => 'main/site/index',
                '/<id:\d+>' => 'main/site/index/',
                'contact' => 'contact/default/index',
                'login' => 'user/default/login',
                'site/login' => 'user/default/login',
                'signup' => 'user/default/signup',
                'logout' => 'user/default/logout',
                'profile' => 'user/default/profile',
                'game' => 'socket/default/index',
//                '<_a:(about|error)>' => 'main/default/<_a>',
//                '<_a:(login|logout)>' => 'user/default/<_a>',

                '<_m:[\w\-]+>/<_c:[\w\-]+>/<id:\d+>' => '<_m>/<_c>/view',
                '<_m:[\w\-]+>/<_c:[\w\-]+>' => '<_m>/<_c>/index',
                '<_m:[\w\-]+>/<_c:[\w\-]+>/<_a:[\w\-]+>/<id:\d+>' => '<_m>/<_c>/<_a>',
                '<_m:[\w\-]+>' => '<_m>/default/index',
            ],
        ],
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => 'PA2BrX8d2D4W0YJMcAv62JTwOt55NJjK',
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => 'app\modules\user\models\User',
            'enableAutoLogin' => true,
        ],
        'errorHandler' => [
            'errorAction' => 'main/site/error',
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            // send all mails to a file by default. You have to set
            // 'useFileTransport' to false and configure a transport
            // for the mailer to send real emails.
            'useFileTransport' => true,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => require(__DIR__ . '/db.php'),

    ],
    'modules' => [
        'main' => [
            'class' => 'app\modules\main\Module',
        ],
        'contact' => [
            'class' => 'app\modules\contact\Module',
        ],
        'user' => [
            'class' => 'app\modules\user\Module',
        ],
        'admin' => [
            'class' => 'app\modules\admin\Module',
        ],
        'socket' => [
            'class' => 'app\modules\socket\Module',
        ],
    ],
    'params' => $params,
];



if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = 'yii\debug\Module';

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = 'yii\gii\Module';
}

return $config;