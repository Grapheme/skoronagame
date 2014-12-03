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

class Helper {

    public $games;
    public $map;

    public function __construct(&$games,&$map) {

        $this->games = &$games;
        $this->map = &$map;
    }

    public function frontMap($id_game, $color) {

        $map = array_keys($this->games[$id_game]['map'], $color);

        $front_map = [];
        foreach($map as $val)
            $front_map = array_merge($front_map,$this->map[$val]);

        $front_map = array_diff($front_map, $map);

        return array_unique($front_map);
    }
}
