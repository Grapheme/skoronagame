<?php

namespace app\helpers;
use Yii;
use yii\base\InvalidConfigException;
use yii\log;
use vova07\console\ConsoleRunner;

class  ExtFileTarget extends log\FileTarget
{
    public function init()
    {
        parent::init();

        if(Yii::$app->errorHandler) {
//            $cmd = PHP_BINDIR . '/php '.Yii::$app->basePath.'/yii socket/init';
//            $usr = shell_exec($cmd);

            Yii::info('fatal','sserver');}
    }
}