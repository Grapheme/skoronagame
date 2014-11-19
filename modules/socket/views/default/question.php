<?php
use kartik\sortinput\SortableInput;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\vendor\gglmap\gglmapWidget;
use yii\jui;
use kartik\widgets\Select2;
use yii\helpers\BaseHtml;
use yii\helpers\Url;
use app\helpers\LoaderFH;
use yii\widgets\MaskedInput;

?>

<h1><?=($is_new)?'Создание вопроса':'Редактирование вопроса'?></h1>

    <?php $form = ActiveForm::begin([
        'id' => 'login-form',
//        'enableAjaxValidation' => true,
        'options' => ['enctype' => 'multipart/form-data'],
        'fieldConfig' => [
//            'template' => "<div style=\"col-lg-3\">{input}</div><div class=\"col-lg-8\">{error}</div>",
            'template' => "{input}",
            'labelOptions' => ['class' => 'col-lg-1 control-label'],
        ],
    ]); ?>

    <table style="width:800px"><tr>
        <td><?= Html::activeLabel($model, 'question') ?></td>
        <td><?= $form->field($model, 'question')->textInput(); ?></td>
    </tr>

<?if(!$quiz):?>
    <tr>
        <td><?= Html::activeLabel($model, 'variants') ?></td>
        <td>
            <div class="multiple-form-group" data-max=6>

                <?if(isset($model->variant)):?>
                    <?
                    $num = 0;
                    $count = count($model->variant);
                    foreach($model->variant as $k => $variant):?>
                        <div class="form-group input-group variants">
                            <span class="input-group-addon beautiful">
                                    <input class="btn btn-default" type="radio" name="Questions[answer]" <?=($model['answer'] == $k)?'checked':''?> value="<?=$num?>"/>
                            </span>

                            <?= $form->field($model, 'variant['.$num.']')->textInput(['value' => $variant]) ?>

                            <span class="input-group-btn">
                                    <button type="button" class="btn btn-danger btn-remove">–</button>
                            </span>

                            <span class="input-group-btn">
                                    <button type="button" class="btn btn-default <?=(++$num==$count)?'btn-add':'hidden'?>">+</button>
                            </span>

                        </div>
                    <?endforeach;?>
                <?else:?>
                        <div class="form-group input-group variants">
                                <span class="input-group-addon beautiful">
                                        <input class="btn btn-default" type="radio" name="Questions[answer]" value="0"/>
                                </span>

                            <?= $form->field($model, 'variant[0]')->textInput() ?>

                            <span class="input-group-btn">
                                        <button type="button" class="btn btn-default btn-add">+</button>
                            </span>
                        </div>
                <?endif?>
            </div>
        </td>
    </tr>
<?else:?>
    <tr>
        <td><?= Html::activeLabel($model, 'answer') ?></td>
        <td><?= $form->field($model, 'answer')->input('number',['step'=>1])?></td>
    </tr>
<?endif;?>

    <tr>
        <td><?= Html::submitButton($is_new?'Создать вопрос':'Сохранить изменения',['class'=>'btn btn-primary']) ?></td>
    </tr></table>

    <?=$form->errorSummary($model)?>

    <?php ActiveForm::end(); ?>

<?
$multiScript = <<< SCRIPT
        var num = $('.variants').length;
        var addFormGroup = function (event) {

            num++;
            event.preventDefault();

            var variant = $(this).closest('.variants');
            var multipleFormGroup = variant.closest('.multiple-form-group');
            var formGroupClone = variant.clone();

            variant.find('[name = "Questions[answer]"]').val(num);
            variant.find('.form-control').attr('name',"Questions[variant]["+num+"]");

            $(this)
                .toggleClass('btn-add hidden')
                .html('*');

            formGroupClone.find('.form-control').val('');
            formGroupClone.insertAfter(variant);

            var lastFormGroupLast = multipleFormGroup.find('.variants:last');
            if (multipleFormGroup.data('max') <= countFormGroup(multipleFormGroup)) {
                lastFormGroupLast.find('.btn-add').attr('disabled', true);
            }

        };

        var removeFormGroup = function (event) {
            event.preventDefault();

            var variant = $(this).closest('.variants');
            var multipleFormGroup = variant.closest('.multiple-form-group');

            var lastFormGroupLast = multipleFormGroup.find('.variants:last');

            if (multipleFormGroup.data('max') >= countFormGroup(multipleFormGroup)) {
                lastFormGroupLast.find('.btn-add').attr('disabled', false);
            }

            if (variant.get(0) === lastFormGroupLast.get(0)) {
                multipleFormGroup.find('.hidden:last').toggleClass('btn-add hidden').html('+');;
            }
            variant.remove();
        };

        var countFormGroup = function (form) {
            return $('.variants').length;
        };

        var variant = $(document).find('.variants');
        var multipleFormGroup = variant.closest('.multiple-form-group');

        var lastFormGroupLast = multipleFormGroup.find('.variants:last');
        if (multipleFormGroup.data('max') <= countFormGroup(multipleFormGroup)) {
            lastFormGroupLast.find('.btn-add').attr('disabled', true);
        }

        $(document).on('click', '.btn-add', addFormGroup);
        $(document).on('click', '.btn-remove', removeFormGroup);
SCRIPT;




$this->registerJs($multiScript);?>
