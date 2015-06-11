<?
/**
 * TITLE: Страница помощи
 * AVAILABLE_ONLY_IN_ADVANCED_MODE
 */
?>
@extends(Helper::layout())
@section('style')
@stop
@section('content')
<div id="tutorial">
    <div class="screen"></div>
    <a href="../" style="position: absolute;color: white;font-size: 18px;right: 30px;top: 30px;">Пропустить</a>
    <div class="tip help-1 players"><img src="{{ asset(Config::get('site.theme_path')).'/images/help-1.png' }}"></div>
    <div class="tip help-2 info"><img src="{{ asset(Config::get('site.theme_path')).'/images/help-2.png' }}"></div>
    <div class="tip help-3 area"><img src="{{ asset(Config::get('site.theme_path')).'/images/help-3.png' }}"></div>
    <div class="tip help-4 turns"><img src="{{ asset(Config::get('site.theme_path')).'/images/help-4.png' }}"></div>
    <div class="tip help-5 help"><img src="{{ asset(Config::get('site.theme_path')).'/images/help-5.png' }}"></div>
    <img src="{{ asset(Config::get('site.theme_path')).'/images/full_map' }}.jpg">
</div>
@stop
@section('overlays')
@stop
@section('scripts')
<script>
    var _skoronagame_ = {
        open_frame: 'none'
    } //открыть фрейс login
</script>
@stop