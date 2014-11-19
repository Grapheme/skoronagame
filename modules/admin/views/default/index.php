<?
use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;
use app\helpers\LoaderFH;
use yii\helpers\ArrayHelper;
?>

<?=Html::a('EXPORT',['default/index','export' => 'all'],['class'=>'btn btn-primary']);?>

    <?php echo GridView::widget([
        'dataProvider' => $dp,
        'filterModel' => $model,
        'layout' => "{items}\n{pager}",
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            'question',
            [
                'label'=>'Ответы',
                'attribute' => 'answer',
                'format' => 'html',
                'enableSorting' => false,
                'filter' => true,
                'value'=>function ($data) {
                    if($data->type == 'quiz')
                        return $data->answer;
                    else {
                        $model = json_decode($data->variants);
                        $model[$data->answer] = '<span style="color:#006611;font-weight: bold;background: #eee8aa;">'.$model[$data->answer].'</span>';
                        return implode('<br/>',$model);
                    }

                },
            ],
            [
                'label'=>'Тип',
                'attribute' => 'type',
                'format' => 'html',
                'options'=>['style'=>'width:70px'],
                'filter' => ['quiz' => 'QZ', 'quest' => 'QT'],

                'value'=>function ($data) {
                    return ($data->type == 'quiz')? 'quiz' : 'quest';
                },
            ],
            [
                'label'=>'Управление',
                'format' => 'raw',
                'enableSorting' => false,
                'filter' => false,
                'options'=>['style'=>'width:30px'],
                'value'=>function ($data) {
                        return '<div>'.Html::a('Редактировать',['default/question','id'=>$data->id],['class'=>'btn btn-primary']).'<br/><br/>
                               <span style="width: 100%;" class="btn btn-danger removePoint" onClick="return confirm(\'Действительно удалить '.$data->question.'?\')" data-id='.$data->id.'>Удалить</span></div>';
                    },
            ],
        ],
    ]); ?>
    <?=Html::a('Добавить квиз вопрос',['default/question','quiz' => ''],['class'=>'btn btn-primary']);?>
    <?=Html::a('Добавить вопрос c вариантами ответов',['default/question'],['class'=>'btn btn-primary']);?>

<?$js = "
$('.removePoint').on('click', function(e) {
    var id = $(this).data('id');
    var block = $(this).parent();

    $.ajax({
        type: 'POST',
        url: '".Url::toRoute('default/delete')."',
        data: {'id': id},
        success: function(data) {
        block.html(data);
    }
    });
});

$('.export').on('click', function(e) {
    var param = $(this).data('param');

    $.ajax({
        type: 'GET',
        url: '".Url::toRoute('default/index')."',
        data: {'param': param},
    });
});
";

$this->registerJs($js);?>