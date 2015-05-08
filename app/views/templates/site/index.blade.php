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
    @include(Helper::layout('blocks.map'))
@stop
@section('overlays')
    @include(Helper::layout('assets.overlays.login'))
    @include(Helper::layout('assets.overlays.register'))
    @include(Helper::layout('assets.overlays.password-forgot'))
    @include(Helper::layout('assets.overlays.authorisation-send'))
    @include(Helper::layout('assets.overlays.password-send'))
    @if(Session::has('reset_token') && Session::has('reset_email'))
        @include(Helper::layout('assets.overlays.password-reset'))
    @endif
@stop
@section('scripts')
<script>
    var _skoronagame_ = {}
    @if(Auth::check())
        location.href = "{{ AuthAccount::getStartPage() }}";
    @elseif(Session::has('reset_token') && Session::has('reset_email'))
    _skoronagame_.open_frame = 'password-reset';
    @else
    _skoronagame_.open_frame = 'login';
    @endif
</script>
@stop