<?php

$vendorDir = dirname(__DIR__);

return array (
  'yiisoft/yii2-swiftmailer' => 
  array (
    'name' => 'yiisoft/yii2-swiftmailer',
    'version' => '9999999-dev',
    'alias' => 
    array (
      '@yii/swiftmailer' => $vendorDir . '/yiisoft/yii2-swiftmailer',
    ),
  ),
  'yiisoft/yii2-codeception' => 
  array (
    'name' => 'yiisoft/yii2-codeception',
    'version' => '9999999-dev',
    'alias' => 
    array (
      '@yii/codeception' => $vendorDir . '/yiisoft/yii2-codeception',
    ),
  ),
  'yiisoft/yii2-debug' => 
  array (
    'name' => 'yiisoft/yii2-debug',
    'version' => '9999999-dev',
    'alias' => 
    array (
      '@yii/debug' => $vendorDir . '/yiisoft/yii2-debug',
    ),
  ),
  'yiisoft/yii2-gii' => 
  array (
    'name' => 'yiisoft/yii2-gii',
    'version' => '9999999-dev',
    'alias' => 
    array (
      '@yii/gii' => $vendorDir . '/yiisoft/yii2-gii',
    ),
  ),
  'vova07/yii2-rbac-module' => 
  array (
    'name' => 'vova07/yii2-rbac-module',
    'version' => '9999999-dev',
    'alias' => 
    array (
      '@vova07/rbac' => $vendorDir . '/vova07/yii2-rbac-module',
    ),
  ),
   'kartik-v/yii2-widgets' =>
        array (
            'name' => 'kartik-v/yii2-widgets',
            'version' => '9999999-dev',
            'alias' =>
                array (
                    '@kartik' => $vendorDir . '/kartik-v/yii2-widgets',
                ),
    ),
   'kartik-v/yii2-sortable' =>
        array (
            'name' => 'kartik-v/yii2-sortable',
            'version' => '9999999-dev',
            'alias' =>
                array (
                    '@kartik' => $vendorDir . '/kartik-v/yii2-sortable',
                ),
    ),
    'kartik-v/yii2-sortable-input' =>
        array (
            'name' => 'kartik-v/yii2-sortable-input',
            'version' => '9999999-dev',
            'alias' =>
                array (
                    '@kartik' => $vendorDir . '/kartik-v/yii2-sortable-input',
                ),
    ),
    'nodge/yii2-eauth' =>
        array (
            'name' => 'nodge/yii2-eauth',
            'version' => '9999999-dev',
            'alias' =>
                array (
                    '@nodge/eauth' => $vendorDir . '/nodge/yii2-eauth/src',
                ),
            'bootstrap' => 'nodge\\eauth\\Bootstrap',
    ),
   );
