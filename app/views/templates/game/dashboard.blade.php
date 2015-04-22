<?
/**
 * TITLE: Главная страница
 * AVAILABLE_ONLY_IN_ADVANCED_MODE
 */
?>
@extends(Helper::layout())
@section('style')
@stop
@section('content')
    <p>{{ Auth::user()->name }}, добро пожаловать в игру.</p>
    <ul>
        <li><a href="#">Начать игру</a></li>
        <li><a href="{{ URL::route('logout') }}">Выйти</a></li>
    </ul>
@stop
@section('scripts')
@stop