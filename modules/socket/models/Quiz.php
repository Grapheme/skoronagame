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


class Quiz extends Game {

    public function __construct(Game &$model) {
        $this->setParams($model);
    }

    public function startQuiz($id_game, $duel = false) {


        $question = Questions::getQuiz();

        if (!$duel) {

            $lvl = $this->addLevel($id_game, $question['question'], $question['answer'],null,$question['id'], 'quiz');
            $this->sendInGame($id_game,['quiz', $question['question'], $this->quiz_time]);

            //установка времени ответа ботам
            $this->bot->timerBot($id_game, ['quiz','answerBot']);
        } else {
            print_r("выдается вопрос дуэль \n");
            print_r($duel['players']);

            //обратить внимане на параметры в sendInGame
            $lvl = $this->addLevel($id_game, $question['question'], $question['answer'], null, $question['id'], 'quiz', ['region' => $duel['region'], 'players' => array_fill_keys(array_keys($duel['players']),null)]);
            $this->sendInGame($id_game, ['quiz', $question['question'], $this->quiz_time, null, $duel['region']], array_keys($duel['players']), ['quest_passiv', $duel['region']]);

            //установка времени ответа ботам
            $this->bot->timerBot($id_game, ['quiz','answerBot'], array_keys($duel['players']));
        }

        print_r("установка таймера quiz ответа \n");

        //установка максимального времени ожидания
        $timer = $this->loop->addPeriodicTimer($this->quiz_time + $this->server_time,function() use($id_game, $lvl){$this->endQuiz($id_game,$lvl,true);});
        $this->games[$id_game]['timer'] = $timer;
    }

    public function answerQuiz($conn_id, $answer) {

        print_r("отвечает игрок $conn_id\n");

        $id_game = $this->players[$conn_id]['game'];
        $lvl = sizeof($this->games[$id_game]['levels'])-1;

        //проверка очередности игрока
        if(!$this->checkPlayer($conn_id, $lvl, $id_game, 'quiz'))
            return;

        print_r("очередность проверена\n");

        $time = $this->games[$id_game]['levels'][$lvl]['time'];

        //заносим ответ и время
        $this->games[$id_game]['levels'][$lvl]['players'][$conn_id] = [
            'answer'    => $answer,
            'time'      => time() - $time,
            'points'    => 0,
        ];

        print_r("ответ занесен\n");

        $last = $this->isLastPlayer($id_game, $lvl);

        //если ответ последнего игрока, подводим итоги вопроса
        if($last) {

            //сброс таймера
            $this->stopQuizTimer($id_game);

            $this->endQuiz($id_game,$lvl);
        }
    }

    public function answerBot($time, $conn_id) {

        print_r("отвечает бот $conn_id\n");

        $id_game = $this->players[$conn_id]['game'];
        $lvl = sizeof($this->games[$id_game]['levels'])-1;

        $this->loop->cancelTimer($this->games[$id_game]['players'][$conn_id]['timer']);

        //выбор ответа
        $answer = $this->games[$id_game]['levels'][$lvl]['answer'];
        $kof = ($answer/100)*$this->bots['quiz'];
        $answer = rand($answer-$kof, $answer+$kof);

        //заносим ответ и время
        $this->games[$id_game]['levels'][$lvl]['players'][$conn_id] = [
            'answer'    => $answer,
            'time'      => $time,
            'points'    => 0,
        ];

        $last = $this->isLastPlayer($id_game, $lvl);

        //если ответ последнего игрока, подводим итоги вопроса
        if($last) {
            print_r("подведение итогов вопроса \n");
            $this->endQuiz($id_game,$lvl);
        }
    }

    public function endQuiz($id_game, $lvl, $timer = false) {

        if($timer) {
           $tm = time()-$this->games[$id_game]['levels'][$lvl]['time'];
           print_r("сработал таймер конца времени Quiz ответа через $tm секунд \n");

           if($tm < $this->server_time + $this->quiz_time) return;
        }

        //сброс таймера
        $this->stopQuizTimer($id_game);

        $rez = [];
        $timeOut = 0;
        foreach ($this->games[$id_game]['levels'][$lvl]['players'] as $key => $val) {

            if(!isset($val['answer']) || $val['time'] > $this->quiz_time)
                $timeOut++;
            else {
                //разница с правильным ответом
                $rez[] = [
                    'id_conn' => $key,
                    'mod' => abs($val['answer'] - $this->games[$id_game]['levels'][$lvl]['answer']),
                    'time' => $val['time']
                ];
            }
        }

        $rez = $this->topQuiz($rez);

        //была дуэль
        if($this->isDuel($id_game,$lvl)) {

            //время вышло у игроков
            if($timeOut > 1) {
                print_r("у игроков дуэли вышло время ответа:\n");

                $players = $this->games[$id_game]['levels'][$lvl]['players'];
                $region = $this->games[$id_game]['levels'][$lvl]['region'];
                $this->startQuiz($id_game,['players' => $players, 'region'=>$region]);
            } else
                $this->conquest->winnInConquest($rez[0]['id_conn'], $id_game, $lvl);

        } else {

            //время вышло у игроков
            if($timeOut > 1) {

                //пишем в базу
                $this->bdlog->lvlGame($id_game, $lvl, Game::REPEAT);

                $this->startQuiz($id_game);
            } else {
                //ставим в очередь на получение территории
                $this->turn_map[$id_game] = [
                    $rez[0]['id_conn'],
                    $rez[1]['id_conn'],
                    $rez[0]['id_conn'],
                ];

                foreach($rez as $key => $val)
                    $this->games[$id_game]['levels'][$lvl]['players'][$val['id_conn']]['place'] = $key + 1;

                print_r("места распределились так:\n");
                print_r($this->games[$id_game]['levels'][$lvl]['players']);
                $this->stepUp($id_game);
            }
        }
    }

    public function stopQuizTimer ($id_game) {

        if(isset($this->games[$id_game]['timer']) && $this->loop->isTimerActive($this->games[$id_game]['timer'])) {

            print_r("остановка Quiz таймера\n");
            $this->loop->cancelTimer($this->games[$id_game]['timer']);
            //unset($this->games[$id_game]['timer']);
        } else
            print_r("таймер конца времени Quiz не активен \n");
    }

    private function topQuiz ($arr) {

        usort($arr, function ($a, $b) {

                if ($a['mod'] == $b['mod'])
                    return ($a['time'] < $b['time']) ? -1 : 1;

                return ($a['mod'] < $b['mod']) ? -1 : 1;
            }
        );

        return $arr;
    }
}
