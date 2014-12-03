<?php

namespace app\modules\socket\models;

use Evenement\EventEmitterInterface;
use Yii;
use app\helpers\MainHelper;
use yii\data\ActiveDataProvider;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use React\EventLoop;

class Chat implements MessageComponentInterface {

    protected $clients;
    protected $GameModel;

    public function __construct($loop) {

        $this->clients = new \SplObjectStorage;
        $this->GameModel = new Game($loop);
    }

    public static function sender(ConnectionInterface $conn, $data, $status = false) {

        $data = Chat::send_format($data, $status);
        $conn->send($data);
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
                break;

            case ('quest'):
                $rez['question'] = $data[1];
                $rez['variants'] = $data[2];
                $rez['region'] = $data[3];
                break;

            case ('quest_passiv'):
                $rez['region'] = $data[1];
                break;

            case ('sendmsg'):
                $rez['msg'] = $data;
                break;

            case ('status'):
                break;

            case ('segmentgame'):
                $rez['color'] = $data[1];
                break;

            case ('conquest'):
                $rez['color'] = $data[1];
                $rez['map'] = $data[2];
                break;

            case ('endgame'):
                break;
        }


        if ($status) $rez = array_merge($rez, $data['info']);

        $data = ['data' => $rez];
        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    public function onOpen(ConnectionInterface $conn) {
        // Store the new connection to send messages to later
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
        echo count($this->clients)." \n";
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
                    $this->GameModel->searchGame($from);
                };
                break;

            case 'quiz':
                $this->GameModel->answerQuiz($from->resourceId, $arr['answer']);
                break;

            case 'quest':
                $this->GameModel->answerQuest($from->resourceId, $arr['answer']);
                break;

            case 'segment':
                $this->GameModel->grabMap($from->resourceId, $arr['map']);
                break;

            case 'conquest':
                $this->GameModel->startConquest($from->resourceId, $arr['map']);
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
