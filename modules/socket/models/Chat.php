<?php

namespace app\modules\socket\models;

use Evenement\EventEmitterInterface;
use Yii;
use app\helpers\MainHelper;
use yii\data\ActiveDataProvider;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use React\EventLoop;
use yii\helpers\Url;

class Chat implements MessageComponentInterface {

    const NO_AUTH = 'no auth';
    const NO_PARAMS = 'params';

    protected $clients;
    protected $sessions = [];

    protected $GameModel;

    public function __construct($loop) {

        $this->clients = new \SplObjectStorage;
        $this->GameModel = new Game($loop);
    }

    public static function sender(ConnectionInterface $conn, $data, $status = false) {

        $data = Chat::send_format($data, $status);
        $conn->send($data);
    }

    public function checkConn($conn) {

        $rez = true;
        $sessionId = $conn->WebSocket->request->getCookies()['PHPSESSID'];

        //такой сессии нет на сервере
        if(!$sessionId) {
            print_r("сессия пользователя не найдена\n");
            return false;
        }

        //дважды с одной сессии
        if(isset($this->sessions[$sessionId])) {

            print_r("попытка дважды зайти в игру в одной сессии\n");

            if(DEBUG) {

                if($this->sessions[$sessionId]['usr'] == Server::getUsrId($sessionId)[0])
                    print_r("попытка дваждый войти одним пользователем в одной сессии\n");
                else {
                    print_r("попытка дваждый войти разными пользователями в одной сессии!!!\n");
                    print_r("сессия $sessionId пользователь ".Server::getUsrId($sessionId)." \n");
                    print_r($this->sessions[$sessionId]);
                }

            }
            return false;
        }

        //проверка пользователя
        $usr = Server::getUsrId($sessionId);
        switch($usr[0]) {

            case Chat::NO_AUTH:
                $rez = false;
                print_r("попытка играть неавторизованным \n");
                break;

            case Chat::NO_PARAMS:
                $rez = false;
                print_r("нет параметров для идентификации пользователя \n");
                break;
        }

        //пользователь с разных сессий (пока разрешено)
        $usrArr = MainHelper::array_column($this->sessions,'usr');
        if(in_array($usr,$usrArr)) {
            print_r("попытка играть одним пользователем в разных сессиях \n");
        }


        return $rez?[$sessionId => ['RID' => $conn->resourceId, 'usr' => $usr[0], 'nickname' => $usr[1]]] : false;
    }


    public static function send_format($data, $status = false) {

        $rez['action'] = is_array($data)? $data[0] : 'sendmsg';

        switch($rez['action']){

            case ('initgame'):
                $rez['castle'] = $data[1];
                $rez['color'] = $data[2];
                $rez['points'] = $data[3];
                break;

            case ('quiz'):
                $rez['question'] = $data[1];
                $rez['time'] = $data[2];
                break;

            case ('quest'):
                $rez['question'] = $data[1];
                $rez['variants'] = $data[2];
                $rez['region'] = $data[3];
                $rez['time'] = $data[4];
                break;

            case ('quest_passiv'):
                $rez['region'] = $data[1];
                break;

            case ('sendmsg'):
                $rez['msg'] = $data;
                break;

            case ('status'):
                break;

            case ('segmentmap'):
                $rez['color'] = $data[1];
                break;

            case ('conquest'):
                $rez['color'] = $data[1];
                $rez['map'] = $data[2];
                $rez['time'] = $data[3];
                break;

            case ('endgame'):
                $rez['rezult'] = $data[1];
                break;
        }


        if ($status) $rez = array_merge($rez, $data['info']);

        $data = ['data' => $rez];
        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    public function onOpen(ConnectionInterface $conn) {

        $check = $this->checkConn($conn);

        if($check !== false) {

            $this->sessions = array_merge($this->sessions, $check);
            // Store the new connection to send messages to later
            $this->clients->attach($conn);

            echo "New connection! ({$conn->resourceId})\n";
            echo count($this->clients)." \n";
        } else {

            self::sender($conn,'отказ в подключении');
            $conn->close();
        }
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $numRecv = count($this->clients) - 1;

        $arr = json_decode($msg,true);

        if(!isset($arr['action'])) return;

        switch ($arr['action']){
            case 'new':

                if($this->GameModel->existInList($from, 'waiting_list') !== false)
                    self::sender($from,'соперник уже ищется');
                else if ($this->GameModel->existInList($from, 'games') !== false)
                    self::sender($from,'вы уже в игре');
                else {
                    $this->GameModel->players[$from->resourceId]['conn'] = $from;

                    //определение имени игрока
                    $sess = MainHelper::array_column($this->sessions,'nickname', 'RID');
                    $name = $sess[$from->resourceId];

                    //определение id игрока
                    $sess = MainHelper::array_column($this->sessions,'usr', 'RID');
                    $usr = $sess[$from->resourceId];

                    $this->GameModel->players[$from->resourceId]['nickname'] = $name;
                    $this->GameModel->players[$from->resourceId]['usr'] = $usr;

                    $this->GameModel->searchGame($from);
                };
                break;

            case 'quiz':
                $this->GameModel->quiz->answerQuiz($from->resourceId, $arr['answer']);
                break;

            case 'quest':
                $this->GameModel->quest->answerQuest($from->resourceId, $arr['answer']);
                break;

            case 'segment':
                $this->GameModel->segment->grabMap($from->resourceId, $arr['map']);
                break;

            case 'conquest':
                $this->GameModel->conquest->startConquest($from->resourceId, $arr['map']);
                break;

            case 'qw':
                $this->GameModel->setMap();
                break;
            default:
        }
        echo sprintf('Connection %d sending message "%s" to %d other connection%s' . "\n"
            , $from->resourceId, $msg, $numRecv, $numRecv == 1 ? '' : 's');

    }

    public function onClose(ConnectionInterface $conn) {

        print_r("отключился от игры $conn->resourceId \n");

        //чистка сессии
        $sid = Server::getSid($conn);

//        print_r("состояние сессии до выбытия игрока \n");
//        print_r($this->sessions[$sid]);
//
//        print_r("сравнение ".$this->sessions[$sid]['RID']." и ".$conn->resourceId."\n");

        if($this->sessions[$sid]['RID'] == $conn->resourceId)
            unset($this->sessions[Server::getSid($conn)]);

//        print_r("состояние сессии после выбытия игрока \n");
//        print_r($this->sessions[$sid]);

        //выбытие из игрового процесса
        $this->GameModel->closeGame($conn);

        // The connection is closed, remove it, as we can no longer send it messages
        $this->clients->detach($conn);

        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();
    }
}
