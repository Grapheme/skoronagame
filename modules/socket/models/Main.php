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

class Main {

    public $debug = DEBUG;
    public $games;
    public $map;
    public $players;

    public $points;
    public $living_castle;
    public $turn_conquest;
    public $turn_map;

    public $conquest;
    public $quest;
    public $quiz;
    public $bdlog;
    public $bot;
    public $segment;
    public $loop;

//    public function __construct(&$games, &$map, &$points, &$players,$living_castle) {
//
//        $this->games = &$games;
//        $this->map = &$map;
//        $this->points = &$points;
//        $this->players = &$players;
//        $this->living_castle = $living_castle;
//    }

    public function setParams(Main &$model) {

        $attr = ['games', 'map', 'points', 'players', 'living_castle', 'turn_conquest', 'turn_map', 'conquest', 'quest', 'quiz', 'segment','loop', 'bot', 'bdlog'];

        foreach($attr as $prop)
            $this->$prop = &$model->$prop;
    }

    public function sendInGameStatus($id_game, $msg) {

        $info = $this->getInfoPlayers($id_game);

        foreach($this->games[$id_game]['players'] as $player_id=>$conn) {

            //пользователь находится в этой игре
            if($this->isOnlinePlayer($player_id, $id_game) === true) {

                $msg['info'] = $info;
                $conn = $this->players[$player_id]['conn'];

                Chat::sender($conn, $msg, true);
            }
        }
    }

    public function getInfoPlayers($id_game) {

        $rez=[];
        foreach($this->games[$id_game]['players'] as $player_id=>$conn) {

            $rez['players'][$conn['color']] = [
                'nickname' => $conn['name'],
                'points' => $conn['points'],
                'type'  => ($this->isOnlinePlayer($player_id, $id_game) !== false)?'on' : 'off',
                ];
        }

        $rez['map'] = $this->games[$id_game]['map'];

        return $rez;
    }

    public function sendInGame($id_game, $msg, $players = false, $other = false) {

        $msg = Chat::send_format($msg);
        $other = (!$other)?: Chat::send_format($other);

        foreach($this->games[$id_game]['players'] as $player_id=>$conn) {

            //пользователь находится в этой игре
            if($this->isOnlinePlayer($player_id, $id_game) === true) {

                if ($players === false || in_array($player_id, $players))
                    $this->players[$player_id]['conn']->send($msg);
                else
                    $this->players[$player_id]['conn']->send($other);
            }
        }
    }

    ////// MAP //////

    public function frontMap($id_game, $color) {

        $map = array_keys($this->games[$id_game]['map'], $color);

        $front_map = [];
        foreach($map as $val)
            $front_map = array_merge($front_map,$this->map[$val]);

        $front_map = array_diff($front_map, $map);

        return array_unique($front_map);
    }

    public function getCostRegion($region, $id_game) {

        $cost = isset($this->games[$id_game]['region_cost'][$region])?
            $this->games[$id_game]['region_cost'][$region]: $this->points['points_defaultmap'];

        return $cost;
    }

    public function isCastle($id_game, $region) {

        $castle = (isset($this->games[$id_game]['castle'][$region]))? true:false;

        if($castle) {
            return isset($this->games[$id_game]['living_castle'][$region]) ?
                $this->games[$id_game]['living_castle'][$region] : $this->living_castle;
        }

        return false;
    }
    ////// PLAYER //////

    public function getPointsPlayers($id_game) {

        $castles = $this->games[$id_game]['castle'];
        $players = $this->games[$id_game]['players'];

        $rez = [];
        foreach($players as $id_conn => $val) {

            if(in_array($val['color'], $castles)) {
                $rez[$id_conn] = ['points' => $val['points'], 'color' => $val['color'], 'type' => $val['type']];
            }
        }
        return $rez;
    }

    public function getIdbyColor($id_game, $color) {

        $players = $this->games[$id_game]['players'];

        foreach($players as $id_conn => $val) {

            if($val['color'] == $color)
                return $this->players[$id_conn]['usr'];
        }
    }

    public function getPlayerOfRegion($region, $id_game) {

        $color = $this->games[$id_game]['map'][$region];

        foreach ($this->games[$id_game]['players'] as $key => $val) {
            if($val['color'] == $color) return $key;
        }
    }

    public function getPlayersGame($id_game) {

        return $this->games[$id_game]['players'];
    }

    public function getColorPlayer($id_conn, $id_game) {

        $color = $this->games[$id_game]['players'][$id_conn]['color'];

        return $color;
    }

    public function isOnlinePlayer($id_conn, $id_game) {

        $player = isset($this->players[$id_conn]);

        if($player && $this->players[$id_conn]['game']== $id_game) {

            if ($this->games[$id_game]['players'][$id_conn]['type'] == Game::BOT)
                return Game::BOT;
            else
                return true;
        }

        return false;
    }

    public function addPoints($id_game, $id_conn, $points) {

        print_r('начисление очков'."\n");

        $this->games[$id_game]['players'][$id_conn]['points'] += $points;

        $lvl = max(array_keys($this->games[$id_game]['levels']));
        $this->games[$id_game]['levels'][$lvl]['players'][$id_conn]['points'] += $points;

    }

    //// GAME ////

    public function addLevel($id_game, $question, $answer, $variants = null, $id_question, $type, $other = false) {

        $lvl = isset($this->games[$id_game]['levels'])? max(array_keys($this->games[$id_game]['levels'])) + 1: 0;

        $this->games[$id_game]['levels'][$lvl] = [
            'question'      => $question,
            'answer'        => $answer,
            'variants'      => $variants,
            'id_question'   => $id_question,
            'time'          => time(),
            'type'          => $type,
            'players'       => array_fill_keys(array_keys($this->games[$id_game]['players']),null),
        ];

        if($other !== false)
            $this->games[$id_game]['levels'][$lvl] = array_merge($this->games[$id_game]['levels'][$lvl], $other);

        return $lvl;
    }

    public function isDuel($id_game,$lvl) {

        $players = sizeof($this->games[$id_game]['levels'][$lvl]['players']);

        return ($players == 2)? true:false;
    }

    public function isLastPlayer($id_game,$lvl) {

        //если ответ последнего игрока, подводим итоги вопроса
        $answers = MainHelper::array_column($this->games[$id_game]['levels'][$lvl]['players'],'answer');
        $players = sizeof($this->games[$id_game]['levels'][$lvl]['players']);

        print_r($answers);
        print_r($players);
        return (sizeof($answers) == $players)? true:false;
    }

    public function castleRemove($id_conn, $id_game) {

        print_r("убраем замок\n");
        print_r($this->games[$id_game]['castle']);

        $player = isset($this->games[$id_game]['players'][$id_conn]);

        if($player) {

            $color = $this->games[$id_game]['players'][$id_conn]['color'];

            $index = array_search($color, $this->games[$id_game]['castle']);
            unset($this->games[$id_game]['castle'][$index]);
        }

        print_r($this->games[$id_game]['castle']);
    }

    public function checkPlayer ($conn_id, $lvl, $id_game, $type) {

        //если не игрок
        if(!isset($this->players[$conn_id])) {
            echo "игрок не в списке игроков \n";
            return false;
        }

        //если не должен отвечать
        if(!$this->isOnlinePlayer($conn_id,$id_game)) {
            echo "игрок не онлайн в этой игре\n";
            return false;
        }

        //если не должен отвечать
        if(!array_key_exists($conn_id, $this->games[$id_game]['levels'][$lvl]['players'])) {
            echo "игрок не должен отвечать\n";
            return false;
        }

        //если уже ответил
        if(isset($this->games[$id_game]['levels'][$lvl]['players'][$conn_id]['answer'])) {
            echo "уже отвечал\n";
            return false;
        }

        //если не тот тип
        if($this->games[$id_game]['levels'][$lvl]['type']!== $type) {
            echo "игрок отвечает на другой тип вопроса\n";
            return false;
        }

        return true;
    }
}
