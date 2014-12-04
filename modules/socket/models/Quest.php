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

        $this->addLevel($id_game, $question['question'], $question['answer'], $question['variants'],$question['id'], 'quest', ['region' => $region, 'players' => array_flip($players)]);
        $this->sendInGame($id_game,['quest', $question['question'], $question['variants'], $region], $players, ['quest_passiv', $region]);

        $timer = $this->loop->addPeriodicTimer(5,function() {$this->sendTime();});
        $this->loop->cancelTimer($this->waiting_list[$wait]['timer']);
    }

    public function answerQuest($conn_id, $answer) {

        $id_game = $this->players[$conn_id]['game'];
        $lvl = sizeof($this->games[$id_game]['levels'])-1;

        //проверка очередности игрока
        $this->checkPlayer($conn_id, $lvl, $id_game, 'quest');

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

    public function endQuest($id_game, $lvl) {

        $rez = [];
        foreach ($this->games[$id_game]['levels'][$lvl]['players'] as $key => $val) {

            $time = $val['time'] <= $this->quest_time;
            $answer = $val['answer'] == $this->games[$id_game]['levels'][$lvl]['answer'];

            if($time && $answer) $rez[] = $key;
        }

        $region = $this->games[$id_game]['levels'][$lvl]['region'];

        //победитель определился
        if(sizeof($rez) == 1) {

            print_r("победитель определился \n");
            $this->conquest->winnInConquest($rez[0],$id_game,$lvl);
        } else {

            print_r("победитель не определился \n");
            $players = $this->games[$id_game]['levels'][$lvl]['players'];
            $this->quiz->startQuiz($id_game,['players' => $players, 'region'=>$region]);
        }

        print_r($this->games[$id_game]['map']);
        print_r($this->games[$id_game]['levels'][$lvl]);

    }
}
