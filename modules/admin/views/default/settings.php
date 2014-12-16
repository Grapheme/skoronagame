<?
use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use app\modules\socket\models\Game;
?>

<?php $form = ActiveForm::begin(); ?>



<table>

    <?foreach($settings as $setting):?>
        <tr>

            <td><?=$setting['desc']?></td>
            <td>

                <?if($setting['type']!='textarea'):?>
                    <?=Html::input($setting['type'],$setting['name'],$setting['value'])?>
                <?else:?>
                    <?=Html::textarea($setting['name'],$setting['value'])?>
                <?endif;?>

            </td>
        </tr>
    <?endforeach;?>

</table>
<?= Html::submitButton('Сохранить', ['class' => 'btn btn-primary']) ?>
<?php ActiveForm::end(); ?>