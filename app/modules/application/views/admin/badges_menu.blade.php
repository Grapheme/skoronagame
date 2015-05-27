<?
$menus = array();
$menus[] = array(
        'link' => URL::route('badges.create'),
        'title' => 'Добавить',
        'class' => 'btn btn-primary'
);
?>
<h1 class="top-module-menu">
    <a href="{{ Request::path() }}">Вопросы</a>
</h1>

{{ Helper::drawmenu($menus) }}
