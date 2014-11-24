<?php
namespace app\commands;

use Yii;
use yii\base\Exception;
use yii\console\Controller;
use app\modules\socket\models\Chat;
use app\modules\socket\models\Server;

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

use React\EventLoop;
use React\Socket;

class SocketController extends Controller
{

    public function actionInit()
    {
        $loop = EventLoop\Factory::create();

        $socket = new Socket\Server($loop);
        $socket->listen(8080);

        $server = new IoServer(
            new HttpServer(
                new WsServer(
                    new Chat($loop))),
            $socket,
            $loop
        );

        Server::setPID();
        $server->run();
    }

    public function actionStop()
    {
        Server::stop();
    }

    public function actionPort()
    {
        echo $this->port;
    }

}
