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


class Bot extends Game {

    public function __construct(Game &$model) {
        $this->setParams($model);
        $this->settings();
    }

    public function getBot() {

        $id = 'bot_'.time();
        $name = array_rand($this->bots['names']);
        return ['id' => $id, 'nickname' => $this->bots['names'][$name]];
    }

    public function getBotsInGame($id_game) {

        $players = $this->games[$id_game]['players'];
        $rez = [];

        foreach($players as $key => $player) {

            if($player['type'] == Game::BOT)
                $rez[] = $key;
        }

        return $rez;
    }

    public function playerToBot ($id_conn) {

        $id_game = $this->players[$id_conn]['game'];
        $this->games[$id_game]['players'][$id_conn]['type'] = Game::BOT;

        unset($this->players[$id_conn]['conn']);
    }

    public function timerBot($id_game, $callback, $bots = false) {

        $bots = ($bots === false)? $this->getBotsInGame($id_game) : $bots;

        foreach($bots as $conn) {

            if ($this->games[$id_game]['players'][$conn]['type'] == Game::BOT) {

                $time = rand($this->bots['min_time'], $this->bots['max_time']);
                $timer = $this->loop->addTimer($time,
                    function () use ($time, $conn, $callback) {
                        $this->$callback[0]->$callback[1]($time, $conn);
                    });
                $this->games[$id_game]['players'][$conn]['timer'] = $timer;
            }
        }

    }
}
