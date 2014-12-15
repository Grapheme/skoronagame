<?
use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;
use app\helpers\LoaderFH;
use yii\helpers\ArrayHelper;
?>
    <?php echo GridView::widget([
        'dataProvider' => $dp,
        'filterModel' => $model,
        'layout' => "{items}\n{pager}",
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            'nickname',
            'email',
            'winns',
            'points',
            'm_winns',
            'm_points',
            [
                'label'=>'Рег',
                'attribute' => 'ref',
                'format' => 'html',
                'options'=>['style'=>'width:70px'],
                'filter' => Yii::$app->params['socParams'],

                'value'=>function ($data) {
                    return Yii::$app->params['socParams'][$data->ref];
                },
            ],
            [
                'label'=>'Лог',
                'format' => 'raw',
                'enableSorting' => false,
                'filter' => false,
                'options'=>['style'=>'width:30px'],
                'value'=>function ($data) {
                        return '<div>'.Html::a('Смотреть лог',['default/gamelog','id'=>$data->id],['class'=>'btn btn-primary']).'</div>';
                    },
            ],
//            [
//                'label'=>'Управление',
//                'format' => 'raw',
//                'enableSorting' => false,
//                'filter' => false,
//                'options'=>['style'=>'width:30px'],
//                'value'=>function ($data) {
//                        return '<div>'.Html::a('Редактировать',['default/question','id'=>$data->id],['class'=>'btn btn-primary']).'<br/><br/>
//                               <span style="width: 100%;" class="btn btn-danger removePoint" onClick="return confirm(\'Действительно удалить '.$data->question.'?\')" data-id='.$data->id.'>Удалить</span></div>';
//                    },
//            ],
        ],
    ]); ?>

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
});";

$this->registerJs($js);?>