<?
/**
 * TITLE: Главная страница
 * AVAILABLE_ONLY_IN_ADVANCED_MODE
 */
$activeUsers = Sessions::getUserIDsLastActivity();
?>
@extends(Helper::layout())
@section('style')
@stop
@section('content')
    @if(Auth::guest())
    <h2>Список пользователей</h2>
    <ul>
    @foreach(User::where('group_id',3)->whereNotIn('id',$activeUsers)->where('active',1)->get() as $user)
        <li><a href="{{ URL::route('auto-auth',$user->id) }}">{{ $user->name }}</a></li>
    @endforeach
    </ul>
    @elseif(Auth::user()->group_id == 3)
        <ul>
            <li><a href="{{ URL::route('game') }}">Играть</a></li>
            <li><a href="{{ URL::route('logout') }}">Выйти</a></li>
        </ul>
    @else
        <ul>
            <li><a href="{{ URL::to(AuthAccount::getStartPage()) }}">Управленение</a></li>
            <li><a href="{{ URL::route('logout') }}">Выйти</a></li>
        </ul>
    @endif
@stop
@section('scripts')
@stop