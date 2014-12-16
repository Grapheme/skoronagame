<?php
namespace app\modules\admin\controllers;

use app\helpers\LoaderFH;
use app\models\Games;
use app\models\Levels;
use app\models\Questions;
use app\models\Settings;
use app\modules\socket\models\Game;
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
            'access' => [
                'class' => AccessControl::className(),
                // 'only' => ['*'],
                'rules' => [
                    [
                        'actions' => ['question','index','delete','users','gamelog','settings'],
                        'allow' => true,
                        'roles' => ['moderator'],
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

    public function actionQuestion($id = false)
    {
        $is_new = true;
        $quiz = true;

        if ($model = Questions::findOne(['id' => $id])){

            if($model->type != 'quiz'){
                $quiz = false;
                $model->variant = json_decode($model->variants);
            }

            $is_new = false;
        } else {

            $model = new Questions();
            $quiz = isset($_GET['quiz']);
        }

        if (Yii::$app->request->isPost) {

            $model->load(Yii::$app->request->post());

            if($model->validate()){
                $rez = $quiz? $model->addQuiz() : $model->addQuest();
                if($rez) $this->redirect('/admin/default/index');
            }
        }

        return $this->render('question', [
            'model'     => $model,
            'is_new'    => $is_new,
            'quiz'      => $quiz
        ]);
    }

    public function actionIndex()
    {
        $model = new Questions();
        $dp = new ActiveDataProvider([
            'query' => Questions::find(),
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);

        $dp = $model->search(Yii::$app->request->getQueryParams());

        if($param = Yii::$app->request->get('export')){

            $par = Questions::exportQuestions($param);

            $this->toExcel(array('Вопросы' => $par),
                array(
                    array(
                        'question::Вопрос',
                        'variants::Варианты',
                        'answer::Ответ',
                    ),
                ),
                'QUESTION ' . date('d-m-Y'),
                array(
                    'creator' => 'NIVEA Hair'
                ),
                'Excel5'
            );

        }

            return $this->render('index', [
            'dp' => $dp,
            'model' => $model]);
    }

    public function actionUsers()
    {

        $model = new User();

        $dp = $model->search(Yii::$app->request->getQueryParams());

        $mod = new ActiveDataProvider();
        $mod->setModels([User::find()]);

        return $this->render('users', [
            'dp' => $dp,
            'model' => $model]);
    }

    public function actionDelete()
    {

        if (Yii::$app->request->isAjax && Questions::findOne(['id' => Yii::$app->request->post('id')])->delete()){
            Yii::$app->cache->flush();
            return 'DELETED';}

        return 'error';
    }

    public function actionGamelog($id)
    {
        $game = [];
        $levels = [];
        $game = Games::getLastGame($id);

        if(sizeof($game)>0)
            $levels = Levels::getGameLevels($game['game'][0]['game']);

        return $this->render('gamelog', [
            'game'=>$game,
            'levels'=>$levels,
        ]);
    }

    public function actionSettings()
    {
        $settings = Settings::getAllSettings();

        return $this->render('settings', ['settings' => $settings]);
    }
}