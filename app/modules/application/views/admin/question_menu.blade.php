<?
$menus = array();
$menus[] = array(
        'link' => URL::to('admin/game/questions/'.Request::segment(4).'/create'),
        'title' => 'Добавить',
        'class' => 'btn btn-primary'
);
$menus[] = array(
        'link' => 'javascript:void(0);',
        'title' => 'Импортировать',
        'class' => 'btn btn-primary js-load-import-file',
);
?>
<h1 class="top-module-menu">
    <a href="{{ URL::to('admin/game/questions/'.Request::segment(4)) }}">Вопросы</a>
</h1>

{{ Helper::drawmenu($menus) }}
{{ Form::open(array('route'=>array('questions.import',Request::segment(4)),'class'=>'smart-form','id'=>'import-file-form','files'=>TRUE,'role'=>'form','method'=>'post')) }}
    {{ Form::file('questions',array('style'=>'display:none;','id'=>'import-file-input')) }}
{{ Form::close() }}