<?
use yii\helpers\Html;
?>
    <?=Yii::$app->user->isGuest?
        Html::a('Войти/Регистрация',['/login'],['class'=>'btn btn-primary']):
        Html::a('Выйти',['/logout'],['class'=>'btn btn-primary']);?>
    <br/>
    <?=Html::a('Начать игру',['/game'],['class'=>'btn btn-primary']);?>
    <br/>
    <?=Html::a('Рейтинг',['/raiting'],['class'=>'btn btn-primary']);?>
    <br/>
    <?=Html::a('Помощь',['#'],['class'=>'btn btn-primary']);?>
    <br/>
    <?=Html::a('Личный кабинет',['/profile'],['class'=>'btn btn-primary']);?>
