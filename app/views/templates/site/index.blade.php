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
    <h2>Список пользователей</h2>
    <ul>
    @foreach(User::where('group_id',3)->where('active',1)->get() as $user)
        <li><a href="{{ URL::route('auto-auth',$user->id) }}">{{ $user->name }}</a></li>
    @endforeach
    </ul>
@stop
@section('scripts')
@stop