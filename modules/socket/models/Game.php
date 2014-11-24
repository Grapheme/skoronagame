<?php

namespace app\modules\socket\models;

use Evenement\EventEmitterInterface;
use Yii;
use app\helpers\MainHelper;
use yii\data\ActiveDataProvider;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use React\EventLoop;

class Game {

    public $waiting_list = [];
    public $games = [];
    public $players;
    public $loop;
    public $colors = ['red','green','blue'];
    public $map = [
        1 => [2,7,6],
        2 => [1,3,7,6,8],
        3 => [2,4,7,8,9],
        4 => [3,5,8,9,10],
        5 => [4,9,10],
        6 => [1,2,7,11,12],
        7 => [1,2,3,6,8,11,12,13],
        8 => [2,3,4,7,9,12,13,14],
        9 => [3,4,5,8,10,13,14,15],
        10 => [4,5,9,14,15],
        11 => [6,7,12],
        12 => [6,7,8,11,13],
        13 => [7,8,9,12,14],
        14 => [8,9,10,13,15],
        15 => [9,10,14]
    ];

    public function __construct($loop) {

        $this->loop = $loop;
    }


    public function existInList(ConnectionInterface $conn, $list = 'games') {

        $list = ($list == 'games')? $this->games:$this->waiting_list;

        foreach($list as $key => $game)
            if(array_key_exists($conn->resourceId,$game['players'])) return $key;

        return false;
    }

    public function sendInList(ConnectionInterface $conn = null, $msg, $list = 'games') {

        $waiting = ($list == 'waiting_list')?:false;
        $empty = empty($conn)?:false;

        $index = ($waiting || $empty)? 0 : $this->players[$conn->resourceId]['game'];
        $list = ($list == 'games')? $this->games:$this->waiting_list;

        $msg = Chat::send_format($msg);

        if(isset($list[$index]))
            foreach($list[$index]['players'] as $player_id=>$conn)
                $this->players[$player_id]['conn']->send($msg);
    }

    public function initGameInPlayers ($game_id, $players){

        //выбор территории замков
        $castle_coord = $this->setCastle();

        //набор цветов
        $colors = $this->setColor();

        //установка цветов замкам
        $castle = array_combine($castle_coord,$colors);
        $this->games[$game_id]['castle'] = $castle;

        $color_castle = $castle;

        foreach($players as $key=>&$val) {

            //установка номера игры
            $this->players[$key]['game'] = $game_id;

            //установка цвета игроку
            $color = array_shift($color_castle);
            $val['color'] = $color;

            Chat::sender($this->players[$key]['conn'], ['initgame', $castle, $color]);
        }

    }

    public function setColor(){

        $colors = $this->colors;
        shuffle($colors);
        $colors = array_slice($colors, 0, 3);

        return $colors;
    }

    public function setCastle(){

        $my_map = $this->map;
        $kol = 0;
        $map=[];

        while ($kol < 3){

            if(sizeof($my_map)==0) {
                echo 'вариантов нет';
                return false;
            }

            $index = rand(1,sizeof($my_map));
            $key = array_keys($my_map)[$index - 1];
            $map[] = $key;

            $kol++;

            //исключаемые значения
            $exit_keys = array_flip(array_values($my_map)[$index - 1]);

            //исключить из массива ключи
            $my_map = array_diff_key($my_map, $exit_keys, [$key => null]);

        }

        return $map;
    }

    public function closeGame(ConnectionInterface $conn) {

        if(isset($this->players[$conn->resourceId])) {

            //remove from players
            unset ($this->players[$conn->resourceId]);

            //remove from waiting_list
            $wait = $this->existInList($conn,'waiting_list');

            if ($wait !== false) {

                if(sizeof($this->waiting_list[$wait]['players']) == 1){

                    echo "Последний игрок в листе ожидания, остановка таймера\n";
                    print_r(array_keys($this->waiting_list[$wait]['players']));

                    $this->loop->cancelTimer($this->waiting_list[$wait]['timer']);
                    unset ($this->waiting_list[$wait]);
                }
                else
                    unset ($this->waiting_list[$wait]['players'][$conn->resourceId]);

            }
            else if ($wait = $this->existInList($conn,'games')) {

                if(sizeof($this->games[$wait]['players']) == 1)
                    unset ($this->games[$wait]);
                else
                    unset ($this->games[$wait]['players'][$conn->resourceId]);
            }
        }
    }

    public function searchGame(ConnectionInterface $conn) {

        if(isset($this->waiting_list[0])){

            $this->sendInList($conn, 'Подключился игрок ID'.$conn->resourceId,'waiting_list');

            //добавляем игрока в список ожидания
            $this->waiting_list[0]['players'][$conn->resourceId] = [];

            echo "добавляется игрок в лист ожидания\nвсего в листе: ".sizeof($this->waiting_list[0]['players'])."\n";
            print_r(array_keys($this->waiting_list[0]['players']));

            if(sizeof($this->waiting_list[0]['players']) == 3) {

                $this->loop->cancelTimer($this->waiting_list[0]['timer']);

                $game_id = md5(time());
                $this->games[$game_id]['players'] = $this->waiting_list[0]['players'];
                unset($this->waiting_list[0]);

                //установка параметров игры игрокам
                $this->initGameInPlayers($game_id, $this->games[$game_id]['players']);


                //START
                echo 'START';

            } else {
                $this->waiting_list[0]['timer_col'] = 0;
            }

        } else {
            echo "создается таймер и запись в листе ожидания\n";
            $timer = $this->loop->addPeriodicTimer(5,function() {$this->sendTime();});

            $this->waiting_list[0] = [
                'players'   => [$conn->resourceId => []],
                'timer_col' => 0,
                'timer'     => $timer];

            echo "лист ожидания содержит\n";
            print_r(array_keys($this->waiting_list[0]['players']));
        }
    }

    public function sendTime() {

        if(!isset($this->waiting_list[0])){
            print_r(array_keys($this->waiting_list));
            echo "выполнился таймер с пустым листом ожидания\n";
        }

        if($this->waiting_list[0]['timer_col'] == 3){

            $this->loop->cancelTimer($this->waiting_list[0]['timer']);

            $msg = Chat::send_format('Соперников не нашлось попробуйте еще раз');

            foreach($this->waiting_list[0]['players'] as $player_id => $conn) {
                $this->players[$player_id]['conn']->send($msg);
                $this->closeGame($conn);
            }

            unset($this->waiting_list[0]);

        } else {

            $this->waiting_list[0]['timer_col']++;

            echo "выполнился таймер, счетчик в позиции: ".$this->waiting_list[0]['timer_col']."\n";

            $this->sendInList(null,'поиск соперников...','waiting_list');
        }
    }


}
