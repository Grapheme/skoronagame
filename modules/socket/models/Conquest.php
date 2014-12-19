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


class Conquest extends Game {

    public function __construct(Game &$model) {
        $this->setParams($model);
        $this->settings();
    }

    public function stepConquest($id_game) {

        if(!isset($this->turn_conquest[$id_game])) {
            print_r("очереди нет создаем \n");
            $this->turn_conquest[$id_game] = Conquest::turnConquest($this->getPlayersGame($id_game));
        }

        //остался один замок либо закончились левелы
        if(sizeof($this->turn_conquest[$id_game]) == 0 || sizeof($this->games[$id_game]['castle']) == 1) {

            print_r("ПОДВЕДЕНИЕ ИТОГОВ \n");
            $this->endGame($id_game);
            return;
        }

        $player = $this->turn_conquest[$id_game];
        $player = array_shift($player);

        $color = $this->getColorPlayer($player, $id_game);

        $map = $this->frontMap($id_game, $color);

        $this->sendInGame($id_game,['conquest', $color, $map, $this->select_time]);

        //должен нападать бот
        if($this->games[$id_game]['players'][$player]['type'] == Game::BOT){

            //установка времени ответа боту
            $this->bot->timerBot($id_game, ['conquest','startConquestBOT'], [$player]);
        } else {

            //установка максимального времени ожидания
            $time = time();
            $timer = $this->loop->addPeriodicTimer($this->select_time + $this->server_time,function() use($id_game, $time){$this->timeoutConquest($id_game, $time);});
            $this->games[$id_game]['timer'] = $timer;
        }
    }

    public function startConquest($conn_act, $region) {

        //если не игрок
        if(!isset($this->players[$conn_act])) return false;

        $id_game = $this->players[$conn_act]['game'];

        //номер обороняющегося игрока
        $conn_pass = $this->getPlayerOfRegion($region, $id_game);
        echo "обороняетя игрок ".$conn_pass."\n";

        if(!$this->isOnlinePlayer($conn_pass, $id_game)){
            print_r("Нападение на офлайн игрока'\n");
            $this->players[$conn_act]['conn']->send('Данный игрок offline');
            return false;
        }

        //проверка очередности
        $player = $this->turn_conquest[$id_game];
        $player = array_shift($player);

        if($player != $conn_act) {
            echo "нападание не в очереди\n";
            return false;
        }

        $color = $this->getColorPlayer($player, $id_game);
        $map = $this->frontMap($id_game, $color);

        echo "карта возможного нападения игрока\n";
        print_r($map);

        echo "игрок хочет напасть на $region \n";

        if(!in_array($region, $map)) {
            echo "нападание на недоступную территорию\n";
            return false;
        }

        if(isset($this->turn_conquest[$id_game]) && sizeof($this->turn_conquest[$id_game])!=0) {

            print_r("остановка таймера\n");
            $this->loop->cancelTimer($this->games[$id_game]['timer']);

            print_r("изъятие из очереди игрока \n");

            //изъятие из очереди
            $player = array_shift($this->turn_conquest[$id_game]);

            $this->quest->startQuest($id_game, [$player, $conn_pass], $region);
        }
    }

    public function timeoutConquest($id_game,$timer) {

        $tm = time()-$timer;

        print_r("сработал таймер конца времени выбра территориии нападения через $tm секунд\n");
        if($tm < $this->select_time + $this->quest_time) return;

        print_r("остановка таймера\n");
        $this->loop->cancelTimer($this->games[$id_game]['timer']);

        //изъятие из очереди
        $player = array_shift($this->turn_conquest[$id_game]);
        $this->stepUp($id_game);
    }

    public function startConquestBOT($time, $conn_act) {

        $id_game = $this->players[$conn_act]['game'];

        $this->loop->cancelTimer($this->games[$id_game]['players'][$conn_act]['timer']);

        //выбор на кого на пасть
        $color = $this->getColorPlayer($conn_act, $id_game);
        $map = $this->frontMap($id_game, $color);
        $region = $map[array_rand($map)];

        echo "карта возможного нападения бота\n";
        print_r($map);

        echo "бот хочет напасть на $region \n";

        //номер обороняющегося игрока
        $conn_pass = $this->getPlayerOfRegion($region, $id_game);
        echo "обороняетя игрок ".$conn_pass."\n";

        if(!$this->isOnlinePlayer($conn_pass, $id_game)){
            print_r("Нападение на офлайн игрока'\n");
            return false;
        }

        //проверка очередности
        $player = $this->turn_conquest[$id_game];
        $player = array_shift($player);

        if($player != $conn_act) {
            echo "нападание не в очереди\n";
            return false;
        }

        if(!in_array($region, $map)) {
            echo "нападание на недоступную территорию\n";
            return false;
        }

        if(isset($this->turn_conquest[$id_game]) && sizeof($this->turn_conquest[$id_game])!=0) {

            print_r("остановка таймера\n");
            $this->loop->cancelTimer($this->games[$id_game]['timer']);

            print_r("изъятие из очереди игрока \n");

            //изъятие из очереди
            $player = array_shift($this->turn_conquest[$id_game]);

            $this->quest->startQuest($id_game, [$player, $conn_pass], $region);
        }
    }

    public static function turnConquest($players) {

        //определение очередности ходов
        $id_players = array_keys($players);


        shuffle($id_players);

        $rez = [];
        $poz = [0,1,2,1,2,0,2,0,1];
        $poz = [0,1,2];

        foreach($poz as $val)
            $rez[] = $id_players[$val];

        return $rez;
    }

    public static function turnConquestRem(&$turn, $player) {

        foreach($turn as $key=>$val)
            if($val ==  $player) unset($turn[$key]);
    }


    public static function getLevel(&$turn, $player) {

        $users = array_unique($turn);

    }

    public function winnInConquest($id_winn, $id_game, $lvl) {

        $region = $this->games[$id_game]['levels'][$lvl]['region'];
        $owner_region =  $this->getPlayerOfRegion($region, $id_game);

        $this->games[$id_game]['levels'][$lvl]['players'][$id_winn]['place'] = 1;

        $castle = $this->isCastle($id_game, $region);

        print_r("карта замков \n");
        print_r($this->games[$id_game]['castle']);

        print_r("очередь нападения \n");
        print_r($this->turn_conquest[$id_game]);

        //отразил нападение
        if ($id_winn == $owner_region) {
            print_r("отразил нападение \n");

            print_r("ид игры $id_game\n");
            print_r("ид победителя $id_winn\n");

            //отразил нападение на замок
            if ($castle !== false) {
                $this->addPoints($id_game, $id_winn, $this->points['points_defence'] * $castle);

                //пишем в базу
                $this->bdlog->lvlGame($id_game, $lvl, Game::CSTL_DEF);
            } else {
                $this->addPoints($id_game, $id_winn, $this->points['points_defence']);

                //пишем в базу
                $this->bdlog->lvlGame($id_game, $lvl, Game::LCLT_DEF);
            }

            $this->stepUp($id_game);
        } else {

            print_r("захватил территорию \n");

            //захват жизни замка
            if ($castle !== false) {

                print_r("зазват одной жизни замка \n");
                //не последняя жизнь замка
                if ($castle != 1) {

                    print_r("жизнь не последняя\n");

                    $this->games[$id_game]['living_castle'][$region] = $castle - 1;

                    $players = array_keys($this->games[$id_game]['levels'][$lvl]['players']);

                    //пишем в базу
                    $this->bdlog->lvlGame($id_game, $lvl, Game::CSTL_GRAB_LF);

                    $this->quest->startQuest($id_game, $players, $region);
                } else {
                    print_r("жизнь последняя\n");

                    $this->castleConquest($id_winn, $id_game, $owner_region);

                    //пишем в базу
                    $this->bdlog->lvlGame($id_game, $lvl, Game::CSTL_GRAB);

                    $this->stepUp($id_game);
                }

            } else {
                print_r("начисление очков за захват \n");

                //начисление очков
                $points = $this->getCostRegion($region, $id_game);
                $this->addPoints($id_game, $id_winn, $points);

                print_r("передача территории \n");

                //передача территории
                $this->games[$id_game]['map'][$region] = $this->getColorPlayer($id_winn, $id_game);

                //смена стоимости терриотрии
                $this->games[$id_game]['region_cost'][$region] = $this->points['points_attackmap'];

                //пишем в базу
                $this->bdlog->lvlGame($id_game, $lvl, Game::LCLT_GRAB);

                $this->stepUp($id_game);
            }
        }
    }

    public function castleConquest($id_winn, $id_game, $owner_region){

        //цвет который нужно заменить
        $color_castle = $this->getColorPlayer($owner_region,$id_game);
        $castle = array_search($color_castle, $this->games[$id_game]['castle']);

        //цвет замены
        $color = $this->getColorPlayer($id_winn,$id_game);

        //передача терриории
        foreach($this->games[$id_game]['map'] as &$clr)
            if($clr == $color_castle) $clr = $color;

        $this->addPoints($id_game, $id_winn, $this->points['points_castle']);

        //убрать замок из списков
        unset($this->games[$id_game]['castle'][$castle]);
        unset($this->games[$id_game]['living_castle'][$castle]);

        print_r("убрать игрока из очереди\n");

        print_r($this->turn_conquest[$id_game]);

        //убрать из очереди нападения
        Conquest::turnConquestRem($this->turn_conquest[$id_game], $owner_region);
        print_r($this->turn_conquest[$id_game]);

    }
}
