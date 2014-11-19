<?php
namespace app\modules\user\controllers;

use app\modules\user\models\LoginForm;
use app\modules\user\models\User;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use Yii;
use app\modules\admin\views;
class DefaultController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout','signup','addnickname','profile','repass'],
                'rules' => [
                    [
                        'actions' => ['logout','addnickname','profile','repass'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                    [
                        'actions' => ['signup'],
                        'allow' => true,
                        'roles' => ['admin'],
                    ],
                ],
            ],
            'eauth' => [
                // required to disable csrf validation on OpenID requests
                'class' => \nodge\eauth\openid\ControllerBehavior::className(),
                'only' => ['login'],
            ],
//            'verbs' => [
//                'class' => VerbFilter::className(),
//                'actions' => [
//                    'logout' => ['post'],
//                ],
//            ],
        ];
    }

    public function actionLogin()
    {
        $serviceName = Yii::$app->getRequest()->getQueryParam('service');

        if (isset($serviceName)) {
            /** @var $eauth \nodge\eauth\ServiceBase */
            $eauth = Yii::$app->get('eauth')->getIdentity($serviceName);
            $eauth->setRedirectUrl(Yii::$app->getUser()->getReturnUrl());
            $eauth->setCancelUrl(Yii::$app->getUrlManager()->createAbsoluteUrl('site/login'));

            try {
                if ($eauth->authenticate()) {
//                  var_dump($eauth->getIsAuthenticated(), $eauth->getAttributes()); exit;

                    $identity = User::findByEAuth($eauth);
                    Yii::$app->getUser()->login($identity);

                    // special redirect with closing popup window
                    $eauth->redirect();
                }
                else {
                    // close popup window and redirect to cancelUrl
                    $eauth->cancel();
                }
            }
            catch (\nodge\eauth\ErrorException $e) {
                // save error to show it later
                Yii::$app->getSession()->setFlash('error', 'EAuthException: '.$e->getMessage());

                // close popup window and redirect to cancelUrl
//              $eauth->cancel();
                $eauth->redirect($eauth->getCancelUrl());
            }
        }
        //////
        if (!Yii::$app->user->isGuest)
            return $this->goHome();

        $model = new LoginForm();
        $user = new User();

        if(isset($_POST['LoginForm']))

            if ($model->load(Yii::$app->request->post()) && $model->login()){

                //check of status
                if(Yii::$app->user->identity['status'] == 1)
                    $this->redirect('/user/default/addnickname');
                else
                    return $this->goBack();
            }
        else
            if ($user->signup(true))
                return $this->goBack();

        return $this->render('login', [
            'model' => $model,
            'user' => $user,
        ]);
    }

    /**
     * LOGOUT USER
     * @return \yii\web\Response
     */
    public function actionLogout()
    {
        if (Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * REGISTER
     * @return string|\yii\web\Response
     */
    public function actionSignup()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new User();

        if (Yii::$app->request->isPost && $model->signup())
            return $this->goBack();
        else {
            return $this->render('signup', [
                'model' => $model,
            ]);
        }
    }

    /**
     * ADD NICKNAME OF USER
     * @return \yii\web\Response
     */
    public function actionAddnickname()
    {
        $model = new User();

        if (Yii::$app->request->isPost){
            $nickname = Yii::$app->request->post('User')['nickname'];

            if($model->nicknameSet($nickname))
                return $this->redirect('/user/default/profile');
        }

        return $this->render('addnickname', [
            'model' => $model,
        ]);
    }

    /**
     * VIEW PROFILE
     * @return \yii\web\Response
     */
    public function actionProfile()
    {
        $model = User::profile();

        return $this->render('profile',['model' => $model]);
    }

    /**
     * CHANGE PASSWORD
     * @return \yii\web\Response
     */
    public function actionRepass()
    {
        $model = new User();

        if (Yii::$app->request->isPost){
            $post = Yii::$app->request->post('User');

            if($model->repass($post))
                return $this->redirect('/user/default/profile');
        }

        return $this->render('repass',['model' => $model]);
    }
}