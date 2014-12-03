<?php

namespace app\modules\socket\models;

use Evenement\EventEmitterInterface;
use Yii;
use app\helpers\MainHelper;
use yii\data\ActiveDataProvider;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use React\EventLoop;
use app\models\Questions;

class Conquest {

    public function __construct() {

    }

    public static function turnConquest($players) {

        //определение очередности ходов
        $id_players = array_keys($players);


        shuffle($id_players);

        $rez = [];
        $poz = [0,1,2,1,2,0,2,0,1];
        $poz = [0,1,2];

        foreach($poz as $val)
            $rez[] = $id_players[$val];

        return $rez;
    }

    public static function turnConquestRem(&$turn, $player) {

        foreach($turn as $key=>$val)
            if($val ==  $player) unset($turn[$key]);
    }


    public static function getLevel(&$turn, $player) {

        $users = array_unique($turn);

    }
}
