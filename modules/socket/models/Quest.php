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


class Quest extends Game {

    public function __construct(Game &$model) {
        $this->setParams($model);
    }

    public function startQuest($id_game, $players, $region) {

        $question = Questions::getQuest();

        $lvl = $this->addLevel($id_game, $question['question'], $question['answer'], $question['variants'],$question['id'], 'quest', ['region' => $region, 'players' => array_flip($players)]);
        $this->sendInGame($id_game,['quest', $question['question'], $question['variants'], $region, $this->quest_time], $players, ['quest_passiv', $region]);


        print_r("установка таймера Quest ответа \n");

        //установка максимального времени ожидания
        $timer = $this->loop->addPeriodicTimer($this->quest_time + $this->server_time,function() use($id_game, $lvl){$this->endQuest($id_game,$lvl,true);});
        $this->games[$id_game]['timer'] = $timer;

        //установка времени ответа ботам
        $this->bot->timerBot($id_game, ['quest','answerBot'], $players);
    }

    public function answerQuest($conn_id, $answer) {

        $id_game = $this->players[$conn_id]['game'];
        $lvl = sizeof($this->games[$id_game]['levels'])-1;

        //проверка очередности игрока
        if(!$this->checkPlayer($conn_id, $lvl, $id_game, 'quest'))
            return;

        $time = $this->games[$id_game]['levels'][$lvl]['time'];

        //заносим ответ и время
        $this->games[$id_game]['levels'][$lvl]['players'][$conn_id] = [
            'answer'    => $answer,
            'time'      => time() - $time,
            'points'    => 0,
        ];

        $last = $this->isLastPlayer($id_game, $lvl);

        if($last) {

            $this->endQuest($id_game,$lvl);

        }
    }

    public function answerBot($time, $conn_id) {

        $id_game = $this->players[$conn_id]['game'];
        $lvl = sizeof($this->games[$id_game]['levels'])-1;

        $this->loop->cancelTimer($this->games[$id_game]['players'][$conn_id]['timer']);

        //выбор ответа ботом
        $variants = json_decode($this->games[$id_game]['levels'][$lvl]['variants']);

        $yes = $this->games[$id_game]['levels'][$lvl]['answer'];

        $no = array_diff_key($variants, [$yes=>null]);
        $no = array_rand($no);

        $answer = rand(1,100);
        $answer = ($answer <= $this->bots['quest'])? $yes : $no;

        //заносим ответ и время
        $this->games[$id_game]['levels'][$lvl]['players'][$conn_id] = [
            'answer'    => $answer,
            'time'      => $time,
            'points'    => 0,
        ];

        $last = $this->isLastPlayer($id_game, $lvl);

        if($last) {

            //сброс таймера
            $this->stopQuestTimer($id_game);

            $this->endQuest($id_game,$lvl);
        }
    }

    public function endQuest($id_game, $lvl, $timer = false) {

        if($timer) {
            $tm = time()-$this->games[$id_game]['levels'][$lvl]['time'];

            print_r("сработал таймер конца времени Quest ответа через $tm секунд\n");
            if($tm < $this->server_time + $this->quest_time) return;
        }

        //сброс таймера
        $this->stopQuestTimer($id_game);

        $rez = [];
        foreach ($this->games[$id_game]['levels'][$lvl]['players'] as $key => $val) {

            if(isset($val['answer'])) {

                $time = $val['time'] <= $this->quest_time;
                $answer = $val['answer'] == $this->games[$id_game]['levels'][$lvl]['answer'];
            } else {

                $time = false;
                $answer = false;
            }

            if($time && $answer) $rez[] = $key;
        }

        $region = $this->games[$id_game]['levels'][$lvl]['region'];

        //победитель определился
        if(sizeof($rez) == 1) {

            print_r("победитель определился \n");
            $this->conquest->winnInConquest($rez[0],$id_game,$lvl);
        } else {

            print_r("победитель не определился \n");

            //пишем в базу
            $this->bdlog->lvlGame($id_game, $lvl, Game::REPEAT);

            $players = $this->games[$id_game]['levels'][$lvl]['players'];
            $this->quiz->startQuiz($id_game,['players' => $players, 'region'=>$region]);
        }

    }

    public function stopQuestTimer ($id_game) {

        if(isset($this->games[$id_game]['timer']) && $this->loop->isTimerActive($this->games[$id_game]['timer'])) {

            print_r("остановка Quest таймера\n");
            $this->loop->cancelTimer($this->games[$id_game]['timer']);
            //unset($this->games[$id_game]['timer']);
        } else
            print_r("таймер конца времени Quest не активен \n");
    }
}
