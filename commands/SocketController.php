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
//        $socket->listen(8888, '0.0.0.0');
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

    public function actionSid($sid = false, $sessionpath = '')
    {

        $usr = Chat::NO_PARAMS;

        if($sid !== false) {

            session_save_path($sessionpath);

            session_id($sid);
            session_start();

            $usr = isset($_SESSION['__id'])?$_SESSION['__id'].'|'.$_SESSION['nickname']:Chat::NO_AUTH;
            session_write_close();

        }
        echo $usr;
        return;
    }

    public function actionSes()
    {
     $sid = '5e2d32281d6aebadc9092cba4d3c085e';

            session_id($sid);
            session_start();

            print_r($_SESSION);
    }

    public function actionTest()
    {
        Yii::info('test','sserver');
        print_r(time());
    }

}
