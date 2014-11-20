<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
?>
<div class="site-login">
    <h1>Введите Ваш Ник</h1>


    <?php $form = ActiveForm::begin([
        'id' => 'signup-form',
        'options' => ['class' => 'form-horizontal'],
        'fieldConfig' => [
            'template' => "<div class=\"col-lg-3\">{input}</div>",
            'labelOptions' => ['class' => 'col-lg-1 control-label'],
        ],
    ]); ?>

        <?= $form->field($model, 'nickname') ?>


    <div class="form-group">
        <div class="col-lg-offset-1 col-lg-11">
            <?= Html::submitButton('Register', ['class' => 'btn btn-primary', 'name' => 'login-button']) ?>
        </div>
    </div>

    <?= $form->errorSummary($model,['header' => ''])?>
    <?php ActiveForm::end(); ?>


</div>
