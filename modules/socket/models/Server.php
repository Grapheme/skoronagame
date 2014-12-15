<?php

namespace app\modules\socket\models;

use Evenement\EventEmitterInterface;
use Yii;
use app\helpers\MainHelper;
use yii\data\ActiveDataProvider;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use React\EventLoop;

class Server {


    public static function setPID() {

        file_put_contents(Yii::getAlias("@app/PID"), posix_getpid());
    }

    public static function getPID() {

        $path_pid = Yii::getAlias("@app/PID");
        $pid = @file_get_contents($path_pid);

        return $pid;
    }

    public static function stop() {

        $pid = Server::getPID();

        if ($pid) {
            posix_kill($pid, SIGTERM);
            unlink(Yii::getAlias("@app/PID"));
            echo('Сервер успешно остановлен!');
        } else {
            die("already stopped\r\n");
        }
    }

    public static function getPidInfo($pid, $ps_opt="aux"){

        $ps=shell_exec("ps ".$ps_opt."p ".$pid);
        $ps=explode("\n", $ps);

        if(count($ps)<2){
            trigger_error("PID ".$pid." doesn't exists", E_USER_WARNING);
            return false;
        }

        foreach($ps as $key=>$val){
            $ps[$key]=trim($ps[$key]);//explode(" ", preg_replace(" +", " ", trim($ps[$key])));
        }

//        foreach($ps[0] as $key=>$val){
//            $pidinfo[$val] = $ps[1][$key];
//            unset($ps[1][$key]);
//        }
//
//        if(is_array($ps[1])){
//            $pidinfo[$val].=" ".implode(" ", $ps[1]);
//        }
        return $ps;
    }

    public static function getUsrId($sessionId) {

        $usr = shell_exec('php '.Yii::$app->basePath.'/yii socket/sid '.$sessionId);

        $usr = explode('|',$usr);
        return $usr;
    }

    public static function getSid($conn) {

        $sessionId = isset($conn->WebSocket->request->getCookies()['PHPSESSID'])?
            $conn->WebSocket->request->getCookies()['PHPSESSID'] : false;

        return $sessionId;
    }

}
