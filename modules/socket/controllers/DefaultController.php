<?php
namespace app\modules\socket\controllers;

use app\helpers\LoaderFH;
use app\models\Questions;

use app\modules\user\models\User;
use app\modules\socket\models\Server;

use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\UploadedFile;
use Yii;
use yii\helpers\Html;

use yii\web\Response;
use yii\bootstrap\ActiveForm;

use vova07\console\ConsoleRunner;

class DefaultController extends Controller
{
    public $layout = 'admin';

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['server'],
                'rules' => [
                    [
                        'actions' => ['server'],
                        'allow' => true,
                        'roles' => ['admin'],
                    ],
                ],
            ],
            'eexcel' => [
                'class' => 'eview\EExcelBehavior',
            ],
//            'verbs' => [
//                'class' => VerbFilter::className(),
//                'actions' => [
//                    'logout' => ['post'],
//                ],
//            ],
        ];
    }


    public function actionIndex()
    {
        return $this->render('index');
    }

    public function actionServer()
    {
        $this->layout = '@app/modules/admin/views/layouts/admin';
        $cr = new ConsoleRunner(['file' => '@app/yii']);

        if($get = Yii::$app->request->get('server')) {
            switch ($get) {
                case 'start':
                    $cr->run('socket/init');
                    break;
                case 'stop':
                    $cr->run('socket/stop');
                    break;
                case 'kill':
                    $cr->run('socket/kill');
                    break;
            }

            $this->redirect('server');
        }
        $pid = Server::getPID();

//        if ($pid) {
//            print_r(Server::getPidInfo($pid));
//        } else {
//            die("already stopped\r\n");
//        }

        return $this->render('server',[
            'pid' => $pid,
            ]);
    }
}