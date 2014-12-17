<?php

namespace app\modules\user\models;

use app\modules\user\models\User;
use Yii;
use yii\base\Model;

/**
 * LoginForm is the model behind the login form.
 */
class LoginForm extends Model
{
    public $username;
    public $password;
    public $rememberMe = true;

    private $_user = false;
    public $ref = 'site';

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            // username and password are both required
            [['username', 'password'], 'required'],
            // rememberMe must be a boolean value
            ['rememberMe', 'boolean'],
            // password is validated by validatePassword()
            ['password', 'validatePassword'],
        ];
    }

    public function attributeLabels(){
        return [
            'username' => 'Логин',
            'password' => 'Пароль',
            'rememberMe' => 'Запомнить'
        ];
    }

    /**
     * Validates the password.
     * This method serves as the inline validation for password.
     *
     * @param string $attribute the attribute currently being validated
     * @param array $params the additional name-value pairs given in the rule
     */
    public function validatePassword($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $user = $this->getUser();


            if (!$user || !$user->validatePassword($this->password)) {
                $this->addError($attribute, 'Incorrect username or password.');
            }
        }
    }

    /**
     * Logs in a user using the provided username and password.
     * @return boolean whether the user is logged in successfully
     */
    public function login()
    {

//        print_r($usr->getId());

        if($this->ref != 'site') {
            $usr = $this->getUser();
            $rez = Yii::$app->user->login($usr, $this->rememberMe ? 3600*24*30 : 0);
            if ($rez) Yii::$app->session['nickname'] = $usr->nickname;
            return $rez;
        }

        if ($this->validate()) {

            $usr = $this->getUser();
            $rez = Yii::$app->user->login($usr, $this->rememberMe ? 3600*24*30 : 0);
            if ($rez) Yii::$app->session['nickname'] = $usr->nickname;
            return $rez;
        } else {

            return false;
        }
    }

    /**
     * Finds user by [[username]]
     *
     * @return User|null
     */
    public function getUser()
    {
        $ref = $this->ref;
        if ($this->_user === false) {
            $this->_user = User::findByUsername($this->username, $ref);
        }

        return $this->_user;
    }

}
