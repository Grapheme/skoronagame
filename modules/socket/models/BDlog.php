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
use app\models\Levels;
use app\models\Gamess;
use yii\db\Expression;

use app\modules\socket\models\Game;
use app\modules\user\models\User;

class BDlog extends Game {

    public function __construct(Game &$model) {
        $this->setParams($model);
    }

    public function startGame($id_game) {

        $players = $this->getPlayersGame($id_game);
        $time  = date('Y-m-d H:i:s',time());

        foreach($players as $key => $val) {

                $model = new Gamess();
                $model->game = $id_game;
                $model->date_start = $time;

                if(isset($this->players[$key]['usr']))
                    $model->player = $this->players[$key]['usr'];

                $model->color = $val['color'];
                $model->nickname = $val['name'];
                $model->type = $val['type'];
                $model->save();
        }
    }

    public function lvlGame($id_game, $lvl, $type) {

        print_r("Занесение левела в БД\n");
        $level = $this->games[$id_game]['levels'][$lvl];

        $model = new Levels();
        $model->answer = $level['answer'];
        $model->quest = $level['question'];
        $model->lvl = $lvl;
        $model->id_game = $id_game;
        $model->type = $type;

        if(isset($level['variants'])) $model->variants = $level['variants'];

        $model->date =  date('Y-m-d H:i:s',$level['time']);

        if(isset($level['region']))
            $model->region = $level['region'];

        $pl_answer = [];

        print_r($level['players']);

        foreach($level['players'] as $key => $val) {

            $color = $this->games[$id_game]['players'][$key]['color'];
            $type = $this->games[$id_game]['players'][$key]['type'];

            if(is_array($val)) $val['type'] = $type;
            $pl_answer[$color] = $val;
        }

        $model->pl_answer = json_encode($pl_answer);
        $model->save();

        print_r($model->getErrors());
        print_r("Занесен левел в БД\n");

    }

    public function endGameBD($id_game, $players) {

        print_r("Занесение результатов в БД\n");

        $month = Gamess::find()->select(['date_start'=>'MONTH(date_start)'])->orderBy('id DESC')->one()->toArray()['date_start'];
        $today = date('m', time());
        if($today != $month) {
            User::clearMonth();
        }

        foreach ($players as $val) {

            $place = isset($val['place'])?$val['place']:'';
            $raiting = isset($val['raiting'])?$val['raiting']:'';

            $all_map = sizeof(array_unique($this->games[$id_game]['map']));

            if($val['type'] != Game::BOT) {

                $id = $this->getIdbyColor($id_game, $val['color']);
                $field = ['points' => $val['points'], 'm_points' => $val['points']];

                //если захватил всю карту
                if($all_map == 1 && $val['color'] == $this->games[$id_game]['map']['1'])
                    $field['all_map'] = 1;

                if($place == 1) {
                    $field['winns'] = 1;
                    $field['m_winns'] = 1;
                }

                User::updateAllCounters($field, ['id' => $id]);

                //награды пользователю
                $gifts = User::gifts($id);
                if(sizeof($gifts) > 0)
                    User::updateAll(['gift' => json_encode($gifts)], ['id' => $id]);
            }

            $today = time();
            Gamess::updateAll(
                [
                    'place'     =>  $place,
                    'points'    =>  $val['points'],
                    'raiting'   =>  $raiting,
                    'type'      =>  $val['type'],
                    'date_stop' =>  $today,
                ],
                [
                    'game'  =>  $id_game,
                    'color' =>  $val['color'],
                ]);
        }

        print_r("Занесены результаты в БД\n");
    }
}
