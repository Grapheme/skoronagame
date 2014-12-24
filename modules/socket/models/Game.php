<?php

namespace app\modules\socket\models;

use app\models\Settings;
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
use app\modules\socket\models\Main;

class Game extends Main{

    const BOT = 'bot';
    const USER = 'user';

    const CSTL_DEF = 'CSTL_DEF';
    const LCLT_DEF = 'LCLT_DEF';
    const CSTL_GRAB = 'CSTL_GRAB';
    const CSTL_GRAB_LF = 'CSTL_GRAB_LF';
    const LCLT_GRAB = 'LCLT_GRAB';
    const SEGMENT = 'SEGMENT';
    const REPEAT = 'REPEAT';

    public $waiting_list = [];
    public $games = [];
    public $players;
    public $loop;

    public $timer_settings;
    public $settings_time = 1800;

    public $turn_map = [];
    public $turn_conquest = [];

    public $colors = ['red','green','blue','orange','gray','yellow','pink'];

    public $bots = [
        'names'     => ['Roza','Podsolnuh','Klever','Mak'],
        'quiz'      => 40,
        'quest'     => 50,
        'min_time'  => 3,
        'max_time'  => 7,
    ];

    public $points = [
        'points_castle'     => 1000,
        'points_segmentmap' => 200,
        'points_attackmap'  => 400,
        'points_defaultmap' => 200,
        'points_defence'    => 100,
    ];

    public $answer_time = 10;
    public $select_time = 10;
    public $quest_time = 15;
    public $quiz_time = 15;
    public $server_time = 10;

    public $living_castle = 3;

    public $raiting = [
        '1' =>  100,
        '2' =>  50,
    ];

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

    public $map_elements = 15;

    public function __construct($loop) {

        Yii::$app->cache->delete('settings');
        $this->loop = $loop;
        $this->settings();

        $this->timer_settings = $this->loop->addPeriodicTimer($this->settings_time,function() {$this->settings();});

        $this->quiz = new Quiz($this);
        $this->quest = new Quest($this);
        $this->segment = new Segment($this);
        $this->conquest = new Conquest($this);
        $this->bot = new Bot($this);
        $this->bdlog = new BDlog($this);
    }

    /**
     *
     */
    public function settings() {

        $settings = Settings::getAllSettings();
        $settings = MainHelper::array_column($settings,'value','name');

        $this->bots['names']    = explode("\r\n",$settings['bot_names']);
        $this->bots['quiz']     = $settings['bot_quiz'];
        $this->bots['quest']    = $settings['bot_quest'];
        $this->bots['min_time'] = $settings['bot_min_time'];
        $this->bots['max_time'] = $settings['bot_max_time'];

        $this->points['points_castle']      = $settings['points_castle'];
        $this->points['points_segmentmap']  = $settings['points_segmentmap'];
        $this->points['points_attackmap']   = $settings['points_attackmap'];
        $this->points['points_defaultmap']  = $settings['points_defaultmap'];
        $this->points['points_defence']     = $settings['points_defence'];

//        $this->answer_time  = $settings['answer_time'];
        $this->quest_time   = $settings['quest_time'];
        $this->quiz_time    = $settings['quiz_time'];
        $this->server_time  = $settings['server_time'];

        $this->settings_time  = $settings['settings_time'];

        $this->living_castle = $settings['living_castle'];

        if(!empty($this->timer_settings)) $this->loop->cancelTimer($this->timer_settings);
        $this->timer_settings = $this->loop->addPeriodicTimer($this->settings_time,function() {$this->settings();});
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

        foreach($players as $key => &$val) {

            //установка номера игры
            $this->players[$key]['game'] = $game_id;

            //установка цвета игроку
            $color = array_shift($color_castle);
            $val['color'] = $color;
            $val['points'] = $this->points['points_castle'];

            if ($val['type'] == self::USER)
                Chat::sender($this->players[$key]['conn'], ['initgame', $castle, $color, $this->points['points_castle']]);
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

            //remove from waiting_list
            $wait = $this->existInList($conn,'waiting_list');

            //ожидал игры
            if ($wait !== false) {

                //убрать из списка игроков
                unset ($this->players[$conn_id]);

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
                    $this->bot->playerToBot($conn_id);
                    print_r("игрок $conn_id заменен ботом\n");
                } else {

                    //убрать из списка игроков
                    unset ($this->players[$conn_id]);

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

    public function searchGame(ConnectionInterface $conn = null) {

        if(isset($this->waiting_list[0])) {

            if(sizeof($conn) > 0) {
                print_r("connect not bot \n");

                $this->sendInList($conn, 'Подключился игрок ID' . $this->players[$conn->resourceId]['nickname'], 'waiting_list');

                //добавляем игрока в список ожидания
                $this->waiting_list[0]['players'][$conn->resourceId] = ['type' => self::USER, 'name' => $this->players[$conn->resourceId]['nickname']];


            } else {
                print_r("connect bot \n");

                while(sizeof($this->waiting_list[0]['players']) != 3) {

                    $bot = $this->bot->getBot();

                    //добавляем бота в список ожидания
                    $this->waiting_list[0]['players'][$bot['id']] = ['type' => self::BOT, 'name' => $bot['nickname']];
                }
            }

            echo "добавляется игрок в лист ожидания\nвсего в листе: ".sizeof($this->waiting_list[0]['players'])."\n";
            print_r(array_keys($this->waiting_list[0]['players']));

            if(sizeof($this->waiting_list[0]['players']) == 3) {

                $this->loop->cancelTimer($this->waiting_list[0]['timer']);

                $game_id = md5(time());
                $this->games[$game_id]['players'] = $this->waiting_list[0]['players'];
                print_r($this->games[$game_id]['players']);
                print_r($this->waiting_list[0]['players']);
                unset($this->waiting_list[0]);

                //установка параметров игры игрокам
                $this->initGame($game_id, $this->games[$game_id]['players']);

                $this->stepUp($game_id);

                //START
                echo "START \n";
                $this->bdlog->startGame($game_id);


            } else {
                $this->waiting_list[0]['timer_col'] = 0;
            }

        } else {
            echo "создается таймер и запись в листе ожидания\n";
            $timer = $this->loop->addPeriodicTimer(5,function() {$this->sendTime();});

            $this->waiting_list[0] = [
                'players'   => [$conn->resourceId => ['type' => self::USER, 'name' => $this->players[$conn->resourceId]['nickname']]],
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

        //таймер выполнился 3 раза
        if($this->waiting_list[0]['timer_col'] == 1) {

            $this->loop->cancelTimer($this->waiting_list[0]['timer']);

            //игра с ботами
            $this->searchGame();
//            $msg = Chat::send_format('Соперников не нашлось попробуйте еще раз');
//
//            foreach($this->waiting_list[0]['players'] as $player_id => $conn) {
//                $this->players[$player_id]['conn']->send($msg);
//                $this->closeGame($this->players[$player_id]['conn']);
//            }
//
//            unset($this->waiting_list[0]);

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

        $top = MainHelper::array_column($top_players,'points');

        //распределение мест
       $i = 0;
       while ($i < 2) {

           $points = pos($top);

           $mas = array_keys($top,$points);

           foreach ($mas as $val) {
               $top_players[$val]['place'] = $i + 1;
               $top_players[$val]['raiting']= $this->raiting[$i+1];
               unset($top[$val]);
           }

            $i++;
       }

        print_r($top_players);

        //пишем в базу
        $this->bdlog->endGameBD($id_game, $top_players);

        //сообщение в игру с результатами
        $rezult = [];
        foreach($top_players as $val) {

            $place = isset($val['place'])? $val['place'] : 3;

            $rezult[$place][] = ['color' => $val['color'], 'points' => $val['points'], 'raiting' => isset($val['raiting'])?$val['raiting']:0];
        }
        $this->sendInGameStatus($id_game,['endgame', $rezult]);

        //убрать игроков из игры
        $players = $this->getPlayersGame($id_game);
        foreach ($players as $key => $val) {
            unset($this->players[$key]);
        }

        //убрать игру
        unset($this->games[$id_game]);
    }

    public function topPointPlayers ($arr) {

        usort($arr, function ($a, $b) {
            return ($a['points'] > $b['points']) ? -1 : 1;
            }
        );

        return $arr;
    }

    ///// STEP UP GAME /////
    public function stepUp($game_id) {

//        $size_map = sizeof($this->games[$game_id]['map']);
//        //пропустить распределение земель
//        if($this->debug && $size_map != $this->map_elements){
//            $colors = array_values($this->games[$game_id]['castle']);
//
//            $i=1;
//            while ($i <= 15) {
//                $color = array_rand($colors, 1);
//                if(!isset($this->games[$game_id]['map'][$i]))
//                    $this->games[$game_id]['map'][$i] = $colors[$color];
//                $i++;
//            }
//        }

        $this->sendInGameStatus($game_id,['status']);

        $size_map = sizeof($this->games[$game_id]['map']);

        //не все земли распределены
        if($size_map != $this->map_elements) {

            //есть очередь на получение территории
            if(isset($this->turn_map[$game_id])) {
                $this->segment->segmentMap($game_id);
            }
            else
                $this->quiz->startQuiz($game_id);
        } else {

            $this->conquest->stepConquest($game_id);
        }

    }
}