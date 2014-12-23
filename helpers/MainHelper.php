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

    public static function CheckEmail ($email = ""){

        $debug = false;

        $timeout = 10;
//        $email_arr = explode("@" , $email);
//        $host = $email_arr[1];
        $host = substr (strstr ($email, '@'), 1);

        $domain_rules = array ("aol.com", "bigfoot.com", "brain.net.pk", "breathemail.net",
            "compuserve.com", "dialnet.co.uk", "glocksoft.com", "home.com",
            "msn.com", "rocketmail.com", "uu.net", "yahoo.com", "yahoo.de");

        if (in_array (strtolower ($host), $domain_rules)) return false;
        $host .= ".";

        if (getmxrr ($host, $mxhosts[0], $mxhosts[1]) == true)
            array_multisort ($mxhosts[1], $mxhosts[0]);
        else {
            $mxhosts[0] = $host;
            $mxhosts[1] = 10;
        }
        if ($debug) print_r ($mxhosts);

        $port = 25;
        $localhost = $_SERVER['HTTP_HOST'];
        $sender = 'info@' . $localhost;

        $result = false;
        $id = 0;
        while (!$result && $id < count ($mxhosts[0])) {

            if (function_exists ("fsockopen")) {

                if ($debug) print_r ($id . " " . $mxhosts[0][$id]);

                if ($connection = fsockopen ($mxhosts[0][$id], $port, $errno, $error, $timeout)) {

                    fputs ($connection,"HELO $localhost\r\n"); // 250
                    $data = fgets ($connection,1024);
                    $response = substr ($data,0,1);

                    if ($debug) print_r ($data);

                    if ($response == '2') {// 200, 250 etc.

                        fputs ($connection,"MAIL FROM:<$sender>\r\n");
                        $data = fgets($connection,1024);
                        $response = substr ($data,0,1);

                        if ($debug) print_r ($data);

                        if ($response == '2') { // 200, 250 etc.

                            fputs ($connection,"RCPT TO:<$email>\r\n");
                            $data = fgets($connection,1024);
                            $response = substr ($data,0,1);

                            if ($debug) print_r ($data);

                            if ($response == '2') {// 200, 250 etc.

                                fputs ($connection,"data\r\n");
                                $data = fgets($connection,1024);
                                $response = substr ($data,0,1);

                                if ($debug) print_r ($data);

                                if ($response == '2') { // 200, 250 etc.
                                    $result = true;
                                }
                            }
                        }
                    }

                    fputs ($connection,"QUIT\r\n");
                    fclose ($connection);

                    if ($result) return true;
                }
        } else
            break;

            $id++;
        } //while

        return false;
    }
}