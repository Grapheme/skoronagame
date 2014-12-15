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

            //должен отвечать бот
            if($this->games[$game_id]['players'][$id_conn]['type'] == Game::BOT){

                //установка времени ответа боту
                $this->bot->timerBot($game_id, ['segment','grabMapBOT'], [$id_conn]);
            }
            unset ($turn_map);
        } else {

            //пишем в базу
            $lvl = max(array_keys($this->games[$game_id]['levels']));
            $this->bdlog->lvlGame($game_id, $lvl, Game::SEGMENT);

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
            echo "попытка получить занятую территорию $map\n";
            print_r($this->games[$game_id]['map']);
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

    public function grabMapBOT($time, $conn_id_) {

        print_r("бот захватывает терриорию\n");

        $game_id = $this->players[$conn_id_]['game'];

        $this->loop->cancelTimer($this->games[$game_id]['players'][$conn_id_]['timer']);

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

        $color = $this->getColorPlayer($conn_id,$game_id);

        $map = array_diff_key($this->map,$this->games[$game_id]['map']);
        $map = array_keys($map);
        print_r("можно нападать на ");
        print_r($map);

        $index = array_rand($map,1);
        $map = $map[$index];

        if(array_key_exists($map,$this->games[$game_id]['map'])) {
            echo "попытка получить занятую территорию $map\n";
            print_r($this->games[$game_id]['map']);
            return false;
        }

        if(sizeof($this->turn_map[$game_id]) == 1)
            unset($this->turn_map[$game_id]);
        else
            //забираем пользователя из очереди
            $conn_id = array_shift($this->turn_map[$game_id]);

        //присваиваем территорию
        $color = $this->games[$game_id]['players'][$conn_id]['color'];
        $this->games[$game_id]['map'][$map] = $color;

        //начисление очков
        $this->addPoints($game_id, $conn_id, $this->points['points_segmentmap']);

        print_r("очки начислены $conn_id\n");

        unset ($conn_id);
        $this->segmentMap($game_id);
    }
}
