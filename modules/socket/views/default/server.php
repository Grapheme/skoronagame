<?
use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;
use app\helpers\LoaderFH;
use yii\helpers\ArrayHelper;
?>

<?if($pid):?>
    PID: <?=$pid?>
    <?=Html::a('STOP',['/socket/default/server','server' => 'stop'],['class' => 'btn btn-danger']);?>

<?else:?>

    <?=Html::a('START',['/socket/default/server','server' => 'start'],['class' => 'btn btn-success']);?>

<?endif?>
