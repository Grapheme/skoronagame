<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
?>
<div class="site-login">
    <h1>Личный кабинет</h1>
    <br/>
    Имя: <?=Html::encode($model['nickname'])?>
    <br/>
    Общие баллы: <?=Html::encode($model['points'])?>
    <br/>
    Число побед: <?=Html::encode($model['winns'])?>
    <br/>
    --------------------
        НАГРАДЫ
    --------------------
    <br/>
    <?= Yii::$app->user->identity['ref'] == 'site'? Html::a('Сменить пароль',['/user/default/repass']):''?>
    <br/>
    ЗВУК
    <br/>
    МУЗЫКА
    <br/>
    ПРИГЛАСИТЬ ДРУЗЕЙ


</div>
