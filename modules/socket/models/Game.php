<?php

namespace app\modules\socket\models;

use Evenement\EventEmitterInterface;
use Ratchet\Wamp\Exception;
use Yii;
use app\helpers\MainHelper;
use yii\data\ActiveDataProvider;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use React\EventLoop;

use app\models\Questions;
use app\modules\socket\models\Conquest;
use app\modules\socket\models\Helper;

class Game {

    public $conquest;
    public $helper;

    public $debug = true;
    public $waiting_list = [];
    public $games = [];
    public $players;
    public $loop;

    public $turn_map = [];
    public $turn_conquest = [];

    public $colors = ['red','green','blue','orange','gray','yellow','pink'];
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

    public $points_castle = 1000;
    public $points_segmentmap = 200;
    public $points_attackmap = 400;
    public $points_defaultmap = 200;
    public $points_defence = 100;

    public $answer_time = 10;
    public $quest_time = 1000;
    public $quiz_time = 10;

    public $living_castle = 3;

    public $map_elements = 15;

    public function __construct($loop) {

        $this->loop = $loop;
        $this->conquest = new Conquest();
        $this->helper = new Helper($this->games,$this->map);
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

    public function sendInGame($id_game, $msg, $players = false, $other = false) {

        $msg = Chat::send_format($msg);
        $other = (!$other)?: Chat::send_format($other);

        foreach($this->games[$id_game]['players'] as $player_id=>$conn) {

            //пользователь находится в этой игре
            if($this->isOnlinePlayer($player_id, $id_game)) {

                if ($players === false || in_array($player_id, $players))
                    $this->players[$player_id]['conn']->send($msg);
                else
                    $this->players[$player_id]['conn']->send($other);
            }
        }
    }

    public function sendInGameStatus($id_game, $msg) {

        foreach($this->games[$id_game]['players'] as $player_id=>$conn) {

            //пользователь находится в этой игре
            if($this->isOnlinePlayer($player_id, $id_game)) {

                $info = [
                    'points' => $conn['points'],
                    'map' => $this->games[$id_game]['map']
                ];

                $msg['info'] = $info;
                $conn = $this->players[$player_id]['conn'];

                Chat::sender($conn, $msg, true);
            }
        }
    }

    public function addLevel($id_game, $question, $answer, $variants = null, $id_question, $type, $other = false) {

        $lvl = isset($this->games[$id_game]['levels'])? sizeof($this->games[$id_game]['levels']): 0;

        $this->games[$id_game]['levels'][$lvl] = [
            'question'      => $question,
            'answer'        => $answer,
            'variants'      => $variants,
            'id_question'   => $id_question,
            'time'          => time(),
            'type'          => $type,
        ];

        if($other !== false)
            $this->games[$id_game]['levels'][$lvl] = array_merge($this->games[$id_game]['levels'][$lvl], $other);
    }

    public function addPoints($id_game, $id_conn, $points) {

        print_r('начисление очков'."\n");
        print_r( $this->games[$id_game]['players']);

        $this->games[$id_game]['players'][$id_conn]['points'] += $points;

        $lvl = sizeof($this->games[$id_game]['levels']) - 1;
        $this->games[$id_game]['levels'][$lvl]['players'][$id_conn]['points'] += $points;

        print_r( $this->games[$id_game]['players']);
    }

    public function getColorPlayer($id_conn, $id_game) {

        $color = $this->games[$id_game]['players'][$id_conn]['color'];

        return $color;
    }

    public function getPlayersGame($id_game) {

        return $this->games[$id_game]['players'];
    }

    public function getPlayerOfRegion($region, $id_game) {

        $color = $this->games[$id_game]['map'][$region];

        foreach ($this->games[$id_game]['players'] as $key => $val) {
            if($val['color'] == $color) return $key;
        }
    }

    public function getPointsPlayers($id_game) {

        $castles = $this->games[$id_game]['castle'];
        $players = $this->games[$id_game]['players'];

        $rez = [];
        foreach($players as $id_conn => $val) {

            if(in_array($val['color'], $castles)) {
                $rez[$id_conn] = ['points' => $val['points'], 'color' => $val['color']];
            }
        }

        return $rez;
    }

    public function getCostRegion($region, $id_game) {

        $cost = isset($this->games[$id_game]['region_cost'][$region])?
            $this->games[$id_game]['region_cost'][$region]: $this->points_defaultmap;

        return $cost;
    }

    public function frontMap($id_game, $color) {

        $map = array_keys($this->games[$id_game]['map'], $color);

        $front_map = [];
        foreach($map as $val)
            $front_map = array_merge($front_map,$this->map[$val]);

        $front_map = array_diff($front_map, $map);

        return array_unique($front_map);
    }

    public function checkPlayer ($conn_id, $lvl, $id_game, $type) {

        //если не игрок
        if(!isset($this->players[$conn_id])) return false;

        //если не должен отвечать
        if($this->isOnlinePlayer($conn_id,$id_game)) {
            echo 'игрок не онлайн в этой игре';
            return false;
        }

        //если не должен отвечать
        if(!isset($this->games[$id_game]['levels'][$lvl]['players'][$conn_id])) {
            echo 'игрок не должен отвечать';
            return false;
        }

        //если уже ответил
        if(isset($this->games[$id_game]['levels'][$lvl]['players'][$conn_id]['answer'])) {
            echo 'уже отвечал';
            return false;
        }

        //если не тот тип
        if($this->games[$id_game]['levels'][$lvl]['type']!== $type) {
            echo 'игрок отвечает на другой тип вопроса';
            return false;
        }
    }

    public function isLastPlayer($id_game,$lvl) {

        //если ответ последнего игрока, подводим итоги вопроса
        $answers = array_column($this->games[$id_game]['levels'][$lvl]['players'],'answer');
        $players = sizeof($this->games[$id_game]['levels'][$lvl]['players']);

        return (sizeof($answers) == $players)? true:false;
    }

    public function isDuel($id_game,$lvl) {

        $players = sizeof($this->games[$id_game]['levels'][$lvl]['players']);

        return ($players == 2)? true:false;
    }

    public function isCastle($id_game, $region) {

        $castle = (isset($this->games[$id_game]['castle'][$region]))? true:false;

        if($castle) {
            return isset($this->games[$id_game]['living_castle'][$region]) ?
                $this->games[$id_game]['living_castle'][$region] : $this->living_castle;
        }

        return false;
    }

    public function isOnlinePlayer($id_conn, $id_game) {

        $player = isset($this->players[$id_conn]);

        if($player && $this->players[$id_conn]['game']== $id_game)
            return true;

        return false;
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

    ///////CONQUEST////////

    public function castleConquest($id_winn, $id_game, $owner_region){

        //цвет который нужно заменить
        $color_castle = $this->getColorPlayer($owner_region,$id_game);
        $castle = array_search($color_castle, $this->games[$id_game]['castle']);

        //цвет замены
        $color = $this->getColorPlayer($id_winn,$id_game);

        //передача терриории
        foreach($this->games[$id_game]['map'] as &$clr)
            if($clr == $color_castle) $clr = $color;

        $this->addPoints($id_game, $id_winn, $this->points_castle);

        //убрать замок из списков
        unset($this->games[$id_game]['castle'][$castle]);
        unset($this->games[$id_game]['living_castle'][$castle]);

        print_r("убрать игрока из очереди\n");

        print_r($this->turn_conquest[$id_game]);

        //убрать из очереди нападения
        Conquest::turnConquestRem($this->turn_conquest[$id_game], $owner_region);
        print_r($this->turn_conquest[$id_game]);

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

        $map = $this->helper->frontMap($id_game, $color);

        $this->sendInGame($id_game,['conquest', $color, $map]);
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

            print_r("изъятие из очереди игрока \n");

            //изъятие из очереди
            $player = array_shift($this->turn_conquest[$id_game]);

            $this->startQuest($id_game, [$player, $conn_pass], $region);
        }
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
            if ($castle !== false)
                $this->addPoints($id_game, $id_winn, $this->points_defence * $castle);
            else
                $this->addPoints($id_game, $id_winn, $this->points_defence);

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
                    $this->startQuest($id_game, $players, $region);
                } else {
                    print_r("жизнь последняя\n");

                    $this->castleConquest($id_winn, $id_game, $owner_region);
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

                $this->games[$id_game]['region_cost'][$region] = $this->points_attackmap;
                $this->stepUp($id_game);
            }
        }
    }
    ///////QUEST//////

    public function startQuest($id_game, $players, $region) {

        $question = Questions::getQuest();

        $this->addLevel($id_game, $question['question'], $question['answer'], $question['variants'],$question['id'], 'quest', ['region' => $region, 'players' => array_flip($players)]);
        $this->sendInGame($id_game,['quest', $question['question'], $question['variants'], $region], $players, ['quest_passiv', $region]);
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
            $this->winnInConquest($rez[0],$id_game,$lvl);
        } else {

            print_r("победитель не определился \n");
            $players = $this->games[$id_game]['levels'][$lvl]['players'];
            $this->startQuiz($id_game,['players' => $players, 'region'=>$region]);
        }

        print_r($this->games[$id_game]['map']);
        print_r($this->games[$id_game]['levels'][$lvl]);

    }
    ///////QUIZ////////

    public function startQuiz($id_game, $duel = false) {

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
                $this->winnInConquest($rez[0]['id_conn'], $id_game, $lvl);

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

    public function topQuiz ($arr) {

        usort($arr, function ($a, $b) {

                if ($a['mod'] == $b['mod'])
                    return ($a['time'] < $b['time']) ? -1 : 1;

                return ($a['mod'] < $b['mod']) ? -1 : 1;
            }
        );

        return $arr;
    }

    ////// SEGMENT MAP /////
    public function segmentMap ($game_id) {

         //есть ли кто-то в очереди
        if(isset($this->turn_map[$game_id])) {
            $turn_map = $this->turn_map[$game_id];

            $id_conn = array_shift($turn_map);
            $this->sendInGameStatus($game_id,['segmentmap', $this->games[$game_id]['players'][$id_conn]['color']]);
            unset ($turn_map);
        } else {
            $this->sendInGameStatus($game_id,['status']);
            $this->stepUp($game_id);
        }
    }

    public function grabMap ($conn_id_, $map) {

         if (!isset($this->players[$conn_id_])) {
             echo "игрок не игрок \n";
             return false;
         }

        $game_id = $this->players[$conn_id_]['game'];

        if (!isset($this->turn_map[$game_id])) {
            echo "нет очереди\n";
            return false;
        }

        $turn_map = $this->turn_map[$game_id];
        $conn_id = array_shift($turn_map);

        //того ли игрока очередь
        if($conn_id != $conn_id_) {
            echo "игрок вне очереди или вне списка на получение территории \n";
            return false;
        }

        if(array_key_exists($map,$this->games[$game_id]['map'])) {
            echo "попытка получить занятую территорию \n";
            return false;
        }

        if(sizeof($this->turn_map[$game_id])==1)
            unset($this->turn_map[$game_id]);
        else
            //забираем пользователя из очереди
            $id_conn = array_shift($this->turn_map[$game_id]);

        //присваиваем территорию
        $color = $this->games[$game_id]['players'][$conn_id]['color'];
        $this->games[$game_id]['map'][$map] = $color;

        //начисление очков
        $this->addPoints($game_id, $conn_id, $this->points_segmentmap);

        unset ($conn_id);
        $this->segmentMap($game_id);
    }

    ///// INIT GAME ////

    public function initGame ($game_id, $players){

        //выбор территории замков
        $castle_coord = $this->setCastle();

        //набор цветов
        $colors = $this->setColor();

        //установка цветов замкам
        $castle = array_combine($castle_coord,$colors);
        $this->games[$game_id]['castle'] = $castle;
        $this->games[$game_id]['map'] = $castle;
        $this->games[$game_id]['living_castle'] = null;

        $color_castle = $castle;

        foreach($players as $key=>&$val) {

            //установка номера игры
            $this->players[$key]['game'] = $game_id;

            //установка цвета игроку
            $color = array_shift($color_castle);
            $val['color'] = $color;
            $val['points'] = $this->points_castle;

            Chat::sender($this->players[$key]['conn'], ['initgame', $castle, $color, $this->points_castle]);
        }

        $this->games[$game_id]['players']= $players;
        //отправить игру в бд !!!!!!!!!!!!!
    }

    public function setColor() {

        $colors = $this->colors;
        shuffle($colors);
        $colors = array_slice($colors, 0, 3);

        return $colors;
    }

    public function setCastle() {

        $my_map = $this->map;
        $kol = 0;
        $map=[];

        while ($kol < 3) {

            if(sizeof($my_map)==0) {
                echo "карта не сгенерировалась, пробую еще \n";
                $my_map = $this->map;
                $kol = 0;
                $map=[];
            }

            $index = rand(1,sizeof($my_map));
            $key = array_keys($my_map)[$index - 1];
            $map[] = $key;

            //исключаемые значения
            $exit_keys = array_flip(array_values($my_map)[$index - 1]);

            //исключить из массива ключи
            $my_map = array_diff_key($my_map, $exit_keys, [$key => null]);

            $kol++;
        }

        return $map;
    }

    ///// CLOSE GAME /////

    public function closeGame(ConnectionInterface $conn) {

        $conn_id = $conn->resourceId;

        //выход на этапе подбора игроков
        if(isset($this->players[$conn_id])) {


            //убрать из списка игроков
            unset ($this->players[$conn_id]);

            //remove from waiting_list
            $wait = $this->existInList($conn,'waiting_list');

            //ожидал игры
            if ($wait !== false) {

                print_r("вышел на этапе подбора игроков\n");
                if(sizeof($this->waiting_list[$wait]['players']) == 1){

                    echo "Последний игрок в листе ожидания, остановка таймера\n";
                    print_r(array_keys($this->waiting_list[$wait]['players']));

                    $this->loop->cancelTimer($this->waiting_list[$wait]['timer']);
                    unset ($this->waiting_list[$wait]);
                }
                else
                    unset ($this->waiting_list[$wait]['players'][$conn_id]);

            } else
                if ($wait = $this->existInList($conn,'games')) {

                print_r("игрок был в игре\n");

                $size_map = sizeof($this->games[$wait]['map']);

                //не все земли распределены
                if($size_map != $this->map_elements) {
                    print_r("вышел на этапе распределения земель\n");
                    //замена игрока ботом
                } else {

                    print_r("вышел на этапе игры\n");

                    //убрать замок
                    $this->castleRemove($conn_id, $wait);

                    //убрать из очереди нападения
                    if(isset($this->turn_conquest[$wait]))
                        Conquest::turnConquestRem($this->turn_conquest[$wait], $conn_id);

                    //остался один замок
                    if(sizeof($this->games[$wait]['castle']) == 1)
                        $this->endGame($wait);

                }
            }
        }
    }

    ////// SEARCH GAME //////

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
                $this->initGame($game_id, $this->games[$game_id]['players']);

                $this->stepUp($game_id);

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
                $this->closeGame($this->players[$player_id]['conn']);
            }

            unset($this->waiting_list[0]);

        } else {

            $this->waiting_list[0]['timer_col']++;

            echo "выполнился таймер, счетчик в позиции: ".$this->waiting_list[0]['timer_col']."\n";

            $this->sendInList(null,'поиск соперников...','waiting_list');
        }
    }

    ///// END GAME ////
    public function endGame($id_game) {

        $players = $this->getPointsPlayers($id_game);

        $top_players = $this->topPointPlayers($players);

        print_r("КОНЕЦ ИГРЫ\n");
        print_r($top_players);


        $this->sendInGameStatus($id_game,['endgame']);
    }

    public function topPointPlayers ($arr) {

        usort($arr, function ($a, $b) {
            return ($a['points'] < $b['points']) ? -1 : 1;
            }
        );

        return $arr;
    }

    ///// STEP UP GAME /////
    public function stepUp($game_id) {

        $size_map = sizeof($this->games[$game_id]['map']);
        //пропустить распределение земель
        if($this->debug && $size_map != $this->map_elements){
            $colors = array_values($this->games[$game_id]['castle']);

            $i=1;
            while ($i <= 15) {
                $color = array_rand($colors, 1);
                if(!isset($this->games[$game_id]['map'][$i]))
                    $this->games[$game_id]['map'][$i] = $colors[$color];
                $i++;
            }
        }

        $this->sendInGameStatus($game_id,['status']);

        $size_map = sizeof($this->games[$game_id]['map']);

        //не все земли распределены
        if($size_map != $this->map_elements) {

            //есть очередь на получение территории
            if(isset($this->turn_map[$game_id])) {
                $this->segmentMap($game_id);
            }
            else
                $this->startQuiz($game_id);
        } else {

            $this->stepConquest($game_id);
        }

    }


}
