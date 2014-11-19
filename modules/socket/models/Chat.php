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

    public function onOpen(ConnectionInterface $conn) {
        // Store the new connection to send messages to later
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $numRecv = count($this->clients) - 1;

        $arr = explode(':', $msg);

        switch ($arr[0]){
            case 'new':

                if($this->GameModel->existInList($from, 'waiting_list') !== false)
                    $from->send('соперник уже ищется');
                else if ($this->GameModel->existInList($from, 'games') !== false)
                    $from->send('вы уже в игре');
                else {
                    $this->GameModel->players[$from->resourceId]['conn'] = $from;
                    $this->GameModel->searchGame($from);
                };
                break;
            case 'qw':
                print_r($this->GameModel->$arr[1]);
                break;
            default:
        }
        echo sprintf('Connection %d sending message "%s" to %d other connection%s' . "\n"
            , $from->resourceId, $msg, $numRecv, $numRecv == 1 ? '' : 's');

    }

    public function onClose(ConnectionInterface $conn) {

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
