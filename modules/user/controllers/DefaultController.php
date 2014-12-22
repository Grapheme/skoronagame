<?php
namespace app\modules\user\controllers;

use app\helpers\LoaderFH;
use app\models\Gamess;
use app\modules\user\models\LoginForm;
use app\modules\user\models\User;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use Yii;
use app\helpers\MainHelper;
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

    public function actionLogin() {

        if(!Yii::$app->user->isGuest) return;

        $serviceName = Yii::$app->getRequest()->getQueryParam('service');

        $ref = Yii::$app->request->get('ref');
        $meta = LoaderFH::metaTag($ref);

        if (isset($serviceName)) {
            /** @var $eauth \nodge\eauth\ServiceBase */

            $eauth = Yii::$app->get('eauth')->getIdentity($serviceName);
            $eauth->setRedirectUrl(Yii::$app->getUser()->getReturnUrl());
            $eauth->setCancelUrl(Yii::$app->getUrlManager()->createAbsoluteUrl('/login'));

            try {
                if ($eauth->authenticate()) {

                    $identity = User::findByEAuth($eauth);

//                    print_r($identity->profile);
                    if(User::signupSoc($identity->profile, $ref)) {
                        $model = new LoginForm();
                        $model->username = $identity->profile['id'];
                        $model->password = $identity->profile['id'];
                        $model->ref = \Yii::$app->params['socParams'][$identity->profile['service']];

                        if($model->login()) {

                            //check of status
                            if (Yii::$app->user->identity['status'] == 1)
                                $eauth->redirect('/user/default/addnickname');
                            else
                                $eauth->redirect();
                        }
                    }

//                    Yii::$app->getUser()->login($identity);

                    // special redirect with closing popup window
                    $eauth->redirect();
                }
                else {
                    print_r('NO AUTH');
                    // close popup window and redirect to cancelUrl
                    $eauth->cancel();
                }
            }
            catch (\nodge\eauth\ErrorException $e) {

                print_r('ERROR');
                // save error to show it later
                Yii::$app->getSession()->setFlash('error', 'EAuthException: '.$e->getMessage());

                // close popup window and redirect to cancelUrl
//              $eauth->cancel();
//                $eauth->redirect($eauth->getCancelUrl());
            }
        }

        //////
        if (!Yii::$app->user->isGuest)
            return $this->goHome();

        $model = new LoginForm();
        $user = new User();

        if(isset($_POST['LoginForm'])) {

            if ($model->load(Yii::$app->request->post()) && $model->login()) {

                //check of status
                if (Yii::$app->user->identity['status'] == 1)
                    $this->redirect('/user/default/addnickname');
                else
                    return $this->goBack();
            }
        } else {

            if ($user->signup(true, $ref))
                return $this->goHome();
        }

        return $this->render('login', [
            'model' => $model,
            'user' => $user,
            'meta' => $meta,
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

        Yii::$app->cache->delete('EAuth.services');

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
        //check of status
        if (Yii::$app->user->identity['status'] == 1)

            $this->redirect('/user/default/addnickname');
        else {

            $model = User::profile();
            return $this->render('profile', ['model' => $model]);
        }
    }

    /**
     * VIEW RAITING
     * @return \yii\web\Response
     */
    public function actionRaiting()
    {
        $guest = Yii::$app->user->isGuest;
        $place = false;
        $m_place = false;

        if(!$guest) {
            $my_points = Yii::$app->user->identity->points;
            $place = User::getPlace($my_points);
        }

        $top = User::getTop();

        if(!$guest) {
            $my_mpoints = Yii::$app->user->identity->m_points;
            $m_place = User::getMplace($my_mpoints);
        }

        $m_top = User::getMtop();

        return $this->render('raiting',[
            'top'       => $top,
            'place'     => $place,
            'm_top'     => $m_top,
            'm_place'   => $m_place,
            'identity'   => Yii::$app->user->identity,
        ]);
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