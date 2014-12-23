<?php

namespace app\helpers;

use yii\helpers\FileHelper;
use app\helpers\ImageHandler;
use yii\helpers\Url;
use Yii;

class MainHelper {

    public static function mailSend($title, $subject,$to,$from = false){
        if(!$from)$from = Yii::$app->params['adminEmail'];

        Yii::$app->mailer
            ->compose('main/reg')
            ->setFrom($from)
            ->setTo($to)
            ->setSubject($title)
            ->setHtmlBody($subject)
            ->send();
    }

    public static function array_column($input = null, $columnKey = null, $indexKey = null) {

        // Using func_get_args() in order to check for proper number of
        // parameters and trigger errors exactly as the built-in array_column()
        // does in PHP 5.5.
        $argc = func_num_args();
        $params = func_get_args();
        if ($argc < 2) {
            trigger_error("array_column() expects at least 2 parameters, {$argc} given", E_USER_WARNING);
            return null;
        }
        if (!is_array($params[0])) {
            trigger_error('array_column() expects parameter 1 to be array, ' . gettype($params[0]) . ' given', E_USER_WARNING);
            return null;
        }
        if (!is_int($params[1])
            && !is_float($params[1])
            && !is_string($params[1])
            && $params[1] !== null
            && !(is_object($params[1]) && method_exists($params[1], '__toString'))
        ) {
            trigger_error('array_column(): The column key should be either a string or an integer', E_USER_WARNING);
            return false;
        }
        if (isset($params[2])
            && !is_int($params[2])
            && !is_float($params[2])
            && !is_string($params[2])
            && !(is_object($params[2]) && method_exists($params[2], '__toString'))
        ) {
            trigger_error('array_column(): The index key should be either a string or an integer', E_USER_WARNING);
            return false;
        }
        $paramsInput = $params[0];
        $paramsColumnKey = ($params[1] !== null) ? (string) $params[1] : null;
        $paramsIndexKey = null;
        if (isset($params[2])) {
            if (is_float($params[2]) || is_int($params[2])) {
                $paramsIndexKey = (int) $params[2];
            } else {
                $paramsIndexKey = (string) $params[2];
            }
        }
        $resultArray = array();
        foreach ($paramsInput as $row) {
            $key = $value = null;
            $keySet = $valueSet = false;
            if ($paramsIndexKey !== null && array_key_exists($paramsIndexKey, $row)) {
                $keySet = true;
                $key = (string) $row[$paramsIndexKey];
            }
            if ($paramsColumnKey === null) {
                $valueSet = true;
                $value = $row;
            } elseif (is_array($row) && array_key_exists($paramsColumnKey, $row)) {
                $valueSet = true;
                $value = $row[$paramsColumnKey];
            }
            if ($valueSet) {
                if ($keySet) {
                    $resultArray[$key] = $value;
                } else {
                    $resultArray[] = $value;
                }
            }
        }
        return $resultArray;
    }
//
//    public static function CheckEmail($email){
//
//        $email_arr = explode("@" , $email);
//        $host = $email_arr[1];
//
//        if (!getmxrr($host, $mxhostsarr))
//        {
//            echo "На адрес $email отправка почты невозможна";
//            exit;
//        }
//
//        getmxrr($host, $mxhostsarr, $weight);
//        echo "На $email письма могут отправляться через следующие хосты: ";
//        for ($i=0; $i < count($mxhostsarr); $i++)
//        {
//            echo ("$mxhostsarr[$i] = $weight[$i] ");
//        }
//    }

    public static function CheckEmail ($email = "", $spam=false){

        $host= substr(strstr($email, '@'), 1) . ".";
        if (!getmxrr($host, $mxhosts )) $mxhosts= array($host);
        $localhost= $_SERVER['HTTP_HOST'];
        $sender= "info@$localhost";
        $result= false;
        foreach ($mxhosts as $host)
        {
            if ($connection= @fsockopen($host, 25, $errno, $error, 10))
            {
                fputs($connection,"HELO $localhost\r\n"); // 250
                $response= fgets($connection,1024);
                if ($response[0] == '2') // 200, 250 etc.
                {
                    fputs($connection,"MAIL FROM:<$sender>\r\n");
                    $response= fgets($connection,1024);
                    if ($response[0] == '2') // 200, 250 etc.
                    {
                        fputs($connection,"RCPT TO:<$email>\r\n");
                        $response= fgets($connection,1024);
                        if ($response[0] == '2') // 200, 250 etc.
                        {
                            fputs ($connection,"data\r\n");
                            $response= fgets($connection,1024);
                            if ($response[0] == '2') $result= true;
                        }
                    }
                }
                @fputs ($connection,"QUIT\r\n");
                fclose ($connection);
                if ($result) return true;
            }
        }
        return false;
    }
}