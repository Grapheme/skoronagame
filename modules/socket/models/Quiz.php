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

    protected function startQuiz($id_game, $duel = false) {

        $question = Questions::getQuiz();

        if (!$duel) {

            $this->addLevel($id_game, $question['question'], $question['answer'],null,$question['id'], 'quiz');
            $this->sendInGame($id_game,['quiz', $question['question']]);
        } else {
            print_r("выдается вопрос дуэль \n");
            print_r($duel['players']);

            $this->addLevel($id_game, $question['question'], $question['answer'], null, $question['id'], 'quiz', ['region' => $duel['region'], 'players' => array_fill_keys(array_keys($duel['players']),null)]);
            $this->sendInGame($id_game, ['quiz', $question['question'], null, $duel['region']], array_keys($duel['players']), ['quest_passiv', $duel['region']]);
        }

    }

    public function answerQuiz($conn_id, $answer) {

        $id_game = $this->players[$conn_id]['game'];
        $lvl = sizeof($this->games[$id_game]['levels'])-1;

        //проверка очередности игрока
        $this->checkPlayer($conn_id, $lvl, $id_game, 'quiz');

        $time = $this->games[$id_game]['levels'][$lvl]['time'];

        //заносим ответ и время
        $this->games[$id_game]['levels'][$lvl]['players'][$conn_id] = [
            'answer'    => $answer,
            'time'      => time() - $time,
            'points'    => 0,
        ];

        $last = $this->isLastPlayer($id_game, $lvl);

        //если ответ последнего игрока, подводим итоги вопроса
        if($last) {

            $this->endQuiz($id_game,$lvl);
        }
    }

    public function endQuiz($id_game, $lvl) {

        $rez = [];
        $timeOut = 0;
        foreach ($this->games[$id_game]['levels'][$lvl]['players'] as $key => $val) {

            if($val['time'] > $this->quiz_time)
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
            }

        }

        $this->stepUp($id_game);
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
