<?php

namespace app\helpers;

use yii\helpers\FileHelper;
use app\helpers\ImageHandler;
use yii\helpers\Url;
use Yii;

class MainHelper {

    public static function mailSend($subject,$to,$from = false){
        if(!$from)$from = Yii::$app->params['adminEmail'];

        Yii::$app->mailer
            ->compose('contact/test')
            ->setFrom($from)
            ->setTo($to)
            ->setSubject($subject)
            ->send();
    }
}