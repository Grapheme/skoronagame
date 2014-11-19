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

    public function __construct($loop) {

        $this->loop = $loop;
    }

    public function existInList(ConnectionInterface $conn, $list = 'games') {

        $list = ($list == 'games')? $this->games:$this->waiting_list;

        foreach($list as $key => $game)
            if(array_key_exists($conn->resourceId,$game['players'])) return $key;

        return false;
    }

    public function sendInList(ConnectionInterface $conn, $msg, $list = 'games') {

        $index = $this->existInList($conn,$list);
        $list = ($list == 'games')? $this->games:$this->waiting_list;

        if(isset($list[$index]))
            foreach($list[$index]['players'] as $conn)
                $conn->send($msg);

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
            $this->waiting_list[0]['players'][$conn->resourceId] = $conn;

            echo "добавляется игрок в лист ожидания\nвсего в листе: ".sizeof($this->waiting_list[0]['players'])."\n";
            print_r(array_keys($this->waiting_list[0]['players']));

            if(sizeof($this->waiting_list[0]['players']) == 3) {

                $this->loop->cancelTimer($this->waiting_list[0]['timer']);

                $this->games[md5(time())] = $this->waiting_list[0];
                unset($this->waiting_list[0]);
                //START
                echo 'START';

            } else {
                $this->waiting_list[0]['timer_col'] = 0;
            }

        } else {
            echo "создается таймер и запись в листе ожидания\n";
            $timer = $this->loop->addPeriodicTimer(5,function() {$this->sendTime();});

            $this->waiting_list[0] = [
                'players'   => [$conn->resourceId => $conn],
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

            foreach($this->waiting_list[0]['players'] as $conn) {
                $conn->send('Соперников не нашлось попробуйте еще раз');
                $this->closeGame($conn);
            }

            unset($this->waiting_list[0]);
            var_dump($this->waiting_list);

        } else {

            $this->waiting_list[0]['timer_col']++;

            echo "выполнился таймер, счетчик в позиции: ".$this->waiting_list[0]['timer_col']."\n";
            foreach($this->waiting_list[0]['players'] as $conn)
                $conn->send('поиск соперников...');
        }

    }
}
