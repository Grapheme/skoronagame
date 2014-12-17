<?php

namespace app\modules\user\models;

use Yii;
use app\helpers\MainHelper;
use yii\data\ActiveDataProvider;


/**
 * This is the model class for table "user".
 *
 * @property integer $id
 * @property string $email
 * @property string $pass
 * @property string $role
 * @property integer $status
 * @property string $nickname
 */

class User extends \yii\db\ActiveRecord implements \yii\web\IdentityInterface
{

//    public $id;
    public $username;
    public $pass2;
    public $authKey;
    public $accessToken;
    public static $service = [
        'odnoklassniki' => 'OK',
        'vkontakte' => 'VK',
        'facebook' => 'FB',
    ];


    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%users}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['pass','email','nickname'], 'filter', 'filter' => 'trim'],
            [['status','winns','points','m_winns','m_points'], 'integer'],

            [['pass','role','status'], 'required'],
            [['pass'], 'string', 'length' => [8, 70]],

            [['pass2'], 'required', 'on'=>'repass'],
            [['pass2'], 'compare', 'compareAttribute' => 'pass', 'message' => 'Пароли не совпадают'],

            [['email'], 'required', 'message' => 'e-mail обязателен для заполнения'],
            [['email'], 'unique', 'message' => 'e-mail уже зарегистрирован'],
            [['email','name'], 'string', 'max' => 100],

            [['role','ref'], 'string', 'max' => 10],

            [['nickname'], 'unique', 'message' => 'никнейм занят'],
            [['nickname'], 'string', 'length' => [2, 12],'tooShort' => "минимум {min} символа",'tooLong' => 'максимум {max}  символов'],
            [['nickname'], 'match', 'pattern' => '/^[a-zа-я ]*$/iu']
        ];
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios['signup'] = ['pass','email'];
        $scenarios['signup_soc'] = ['pass','name'];
        $scenarios['site_reg'] = ['email'];
        $scenarios['repass'] = ['pass','pass2'];
        return $scenarios;
    }

    /**
     * @return array
     */
    public function attributeLabels(){
        return [
            'id' => 'ID',
            'email' => 'email',
            'pass' => 'Пароль',
            'role' => 'Role',
            'status' => 'Status',
            'nickname' => 'Никнейм',
            'name' => 'Имя',
            'winns' => 'победы',
            'm_winns' => 'победы(мес)',
            'points' => 'очки',
            'm_points' => 'очки(мес)',
            'ref' => 'регистратор',
        ];
    }

    /**
     * @var array EAuth attributes
     */
    public $profile;

    public static function findIdentity($id) {

        if (Yii::$app->getSession()->has('user-'.$id)) {
            return new self(Yii::$app->getSession()->get('user-'.$id));
        }
        else {
            return static::findOne(['id' => $id]);
//            return isset(self::$users[$id]) ? new self(self::$users[$id]) : null;
        }

    }
    /**
     * @param \nodge\eauth\ServiceBase $service
     * @return User
     * @throws ErrorException
     */
    public static function findByEAuth($service) {
        if (!$service->getIsAuthenticated()) {
            throw new ErrorException('EAuth user should be authenticated before creating identity.');
        }

        $id = $service->getServiceName().'-'.$service->getId();
        $attributes = array(
            'id' => $id,
            'username' => $service->getAttribute('name'),
            'authKey' => md5($id),
            'profile' => $service->getAttributes(),
        );
        $attributes['profile']['service'] = $service->getServiceName();
        Yii::$app->getSession()->set('user-'.$id, $attributes);
        return new self($attributes);
    }


     /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        foreach (self::$users as $user) {
            if ($user['accessToken'] === $token) {
                return new static($user);
            }
        }

        return null;
    }

    /**
     * Finds user by username
     *
     * @param  string      $username
     * @return static|null
     */
    public static function findByUsername($username, $ref = 'site')
    {
        return User::findOne(['email' => $username, 'ref' => $ref]);
    }


    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->getPrimaryKey();
    }

    /**
     * @inheritdoc
     */
    public function getAuthKey() {

        return $this->authKey;
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey){

        return $this->authKey === $authKey;
    }

    /**
     * Validates password
     *
     * @param  string  $password password to validate
     * @return boolean if password provided is valid for current user
     */
    public function validatePassword($password) {

        return Yii::$app->getSecurity()->validatePassword($password, $this->pass);
    }

    /**
     * Generates "remember me" authentication key
     */
    public function generateAuthKey() {

        $this->authKey = Yii::$app->security->generateRandomString();
    }

    public function generatePassword($password) {

        return Yii::$app->getSecurity()->generatePasswordHash($password);
    }

    /**
     * Registration
     * @param bool $email
     * @return bool
     */
    public function signup($email = false) {

        $this->setScenario('signup');
        $this->load(Yii::$app->request->post());

        if($email){
            $pass = Yii::$app->security->generateRandomString(7);
            $this->pass = $pass;
        }

        if($this->validate()){

            $this->pass=$this->generatePassword($this->pass);
            $this->role = 'user';
            $this->save(false);

            $auth = Yii::$app->authManager;
            $adminRole = $auth->getRole('user');
            $auth->assign($adminRole, $this->getId());

            if($email) MainHelper::mailSend('Вы зарегистрировались на сайте ххх: </br>Логин: '.$this->email.'</br>Пароль: '.$pass, $this->email);

            return true;
        }

        return false;

    }

    public static function signupSoc($identity) {

        $model = new User();
        $model->setScenario('signup_soc');
        $model->name = $identity['name'];
        $model->email = $identity['id'];
        $model->ref = self::$service[$identity['service']];
        $model->pass = $identity['id'];

        if($model->validate()){

            $model->role = 'user';
            $model->save(false);

            $auth = Yii::$app->authManager;
            $adminRole = $auth->getRole('user');
            $auth->assign($adminRole, $model->getId());

            return true;
        }

        return false;

    }

    public function nicknameSet($nickname){

        if(Yii::$app->user->identity['status'] == 2) return false;

        $this->nickname = $nickname;
        $valid = $this->validate(['nickname']) && !empty($nickname);

        if($valid && User::updateAll(['status' => 2,'nickname' => $this->nickname],'id = '.Yii::$app->user->id))
            return true;

        return false;
    }

    public static function profile(){
        $model = Yii::$app->user->identity;

        $answer = [
            'nickname'  =>  $model['nickname'],
            'winns'     =>  $model['winns'],
            'points'    =>  $model['points'],
        ];

        return $answer;
    }

    public function repass($post){

        $this->setScenario('repass');
        $this->pass = $post['pass'];
        $this->pass2 = $post['pass2'];

        if(!$this->validate()) return false;

        $password = User::generatePassword($post['pass']);
        if(User::updateAll(['pass' => $password],'id = '.Yii::$app->user->id)) return true;

        return false;
    }

    public static function clearMonth() {

        User::updateAll(['m_winns'=>0,'m_points'=>0]);
    }

    public static function getMplace($my_points) {

        $place = User::find()
            ->where('m_points > ' . $my_points . ' and role != "BAN" and role != "auto_ban"')
            ->count('id');

        return $place;
    }

    public static function getMtop() {

        $top = User::find()->select(['id', 'nickname', 'm_points', 'm_winns'])->orderBy('m_points DESC')->limit(10)->asArray()->all();

        return $top;
    }

    public static function getPlace($my_points) {

        $place = User::find()
            ->where('points > ' . $my_points . ' and role != "BAN" and role != "auto_ban"')
            ->count('id');

        return $place;
    }

    public static function getTop() {

        $top = User::find()->select(['id', 'nickname', 'points', 'winns'])->orderBy('points DESC')->limit(10)->asArray()->all();

        return $top;
    }

    public function search($params)
    {
        $query = User::find();
        $dataProvider=new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);

        if (!($this->load($params))) {
            return $dataProvider;
        }

        $query
//            ->andFilterWhere(['or',
//                    ['and',['type' => 'quiz'],['like', 'answer', $this->answer]],
//                    ['and',['type' => 'quest'],['like', 'variants', $this->answer]]]
//            )
            ->andFilterWhere(['ref' => $this->ref])
            ->andFilterWhere(['like', 'email', $this->email])
            ->andFilterWhere(['like', 'nickname', $this->nickname])
            ->andFilterWhere(['winns' => $this->winns])
            ->andFilterWhere(['points' => $this->points]);

        return $dataProvider;
    }

}
