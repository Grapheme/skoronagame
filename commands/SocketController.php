<?php
namespace app\commands;

use Yii;
use yii\base\Exception;
use yii\console\Controller;
use app\modules\socket\models\Chat;

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

use React\EventLoop;
use React\Socket;

class SocketController extends Controller
{
    public function actionTest()
    {
        require dirname(__DIR__) . '/vendor/autoload.php';


        $server = IoServer::factory(
            new HttpServer(
                new WsServer(
                    new Chat()
                )
            ),
            8080
        );


        $server->run();
    }
    public function actionInit()
    {
        $loop = EventLoop\Factory::create();
        $socket = new Socket\Server($loop);
        $socket->listen(8080);
        $server = new IoServer(
            new HttpServer(new WsServer(new Chat($loop))),
            $socket,
            $loop
        );


        $server->run();
    }


    public function actionKill()
    {
        posix_kill('65049', SIGKILL);
    }
}
