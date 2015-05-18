<?
/**
 * TITLE: iframe главной страницы
 * AVAILABLE_ONLY_IN_ADVANCED_MODE
 */
?>
@extends(Helper::layout())
@section('style')
@stop
@section('content')
    <iframe src="{{ pageurl('mainpage') }}" width="1024" height="768" align="left">
        Ваш браузер не поддерживает плавающие фреймы!
    </iframe>
@stop

@section('scripts')
@stop