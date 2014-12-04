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

use app\modules\socket\models\Game;


class Segment extends Game {

    public function __construct(Game &$model) {
        $this->setParams($model);
    }

    public function segmentMap ($game_id) {

        //есть ли кто-то в очереди
        if(isset($this->turn_map[$game_id])) {
            $turn_map = $this->turn_map[$game_id];

            $id_conn = array_shift($turn_map);
            $this->sendInGameStatus($game_id,['segmentmap', $this->games[$game_id]['players'][$id_conn]['color']]);
            unset ($turn_map);
        } else {
            $this->sendInGameStatus($game_id,['status']);
            $this->stepUp($game_id);
        }
    }

    public function grabMap ($conn_id_, $map) {

        if (!isset($this->players[$conn_id_])) {
            echo "игрок не игрок \n";
            return false;
        }

        $game_id = $this->players[$conn_id_]['game'];

        if (!isset($this->turn_map[$game_id])) {
            echo "нет очереди\n";
            return false;
        }

        $turn_map = $this->turn_map[$game_id];
        $conn_id = array_shift($turn_map);

        //того ли игрока очередь
        if($conn_id != $conn_id_) {
            echo "игрок вне очереди или вне списка на получение территории \n";
            return false;
        }

        if(array_key_exists($map,$this->games[$game_id]['map'])) {
            echo "попытка получить занятую территорию \n";
            return false;
        }

        if(sizeof($this->turn_map[$game_id])==1)
            unset($this->turn_map[$game_id]);
        else
            //забираем пользователя из очереди
            $id_conn = array_shift($this->turn_map[$game_id]);

        //присваиваем территорию
        $color = $this->games[$game_id]['players'][$conn_id]['color'];
        $this->games[$game_id]['map'][$map] = $color;

        //начисление очков
        $this->addPoints($game_id, $conn_id, $this->points['points_segmentmap']);

        unset ($conn_id);
        $this->segmentMap($game_id);
    }
}
