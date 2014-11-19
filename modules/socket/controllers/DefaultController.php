<?php
namespace app\modules\socket\controllers;

use app\helpers\LoaderFH;
use app\models\Questions;
use app\modules\user\models\User;
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

class DefaultController extends Controller
{
    public $layout = 'admin';

    public function behaviors()
    {
        return [
//            'access' => [
//                'class' => AccessControl::className(),
//                // 'only' => ['*'],
//                'rules' => [
//                    [
//                        'actions' => ['question','index','delete','users'],
//                        'allow' => true,
//                        'roles' => ['moderator'],
//                    ],
//                ],
//            ],
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
}