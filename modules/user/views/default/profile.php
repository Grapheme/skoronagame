<?php
use yii\helpers\Html;
use app\helpers\LoaderFH;
use yii\widgets\ActiveForm;
use yii\helpers\Url;
use app\modules\user\models\User;

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


    <div class='social-like'>
        <?=Html::a('VK', LoaderFH::shareLink('vk',Url::toRoute(['/login', 'ref'=>User::refCode()],true)))?>
<!--        <a class="vk" href="--><?//=LoaderFH::shareLink('vk')?><!--" data-url="--><?//=LoaderFH::shareLink('vk')?><!--">VK</a>-->
    </div>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
<script>
    $('.social-like a').on('click',function(){
        var lefto = screen.availWidth/2-150;
        var righto = screen.availHeight/2-125;
        window.open(this.href,'', 'menubar=no,toolbar=no,resizable=yes,scrollbars=yes,height=400,width=600,left=' + lefto + ', top='+righto);
        return false;
    });
</script>
