<?
$menus = array();
$menus[] = array(
        'link' => URL::to('admin/game/questions/'.Request::segment(4).'/create'),
        'title' => 'Добавить',
        'class' => 'btn btn-primary'
);
$menus[] = array(
        'link' => URL::to('admin/game/questions/'.Request::segment(4).'/import'),
        'title' => 'Импортировать',
        'class' => 'btn btn-primary'
);
?>
<h1 class="top-module-menu">
    <a href="{{ URL::to('admin/game/questions/'.Request::segment(4)) }}">Вопросы</a>
</h1>

{{ Helper::drawmenu($menus) }}
{{ Form::open(array('route'=>)) }}