<?
/**
 * TEMPLATE_IS_NOT_SETTABLE
 */
?>
@extends(Helper::layout())
@section('style')
@stop
@section('content')
    @include(Helper::layout('blocks.map'))
@stop
@section('overlays')
<div class="popup-wrapper">
    <div class="popup-holder">
    @include(Helper::acclayout('assets.overlays.menu'))
    @include(Helper::acclayout('assets.overlays.help'))
    @include(Helper::acclayout('assets.overlays.rating'))
    @include(Helper::acclayout('assets.overlays.winer'))
    @include(Helper::acclayout('assets.overlays.profile'))
    @include(Helper::acclayout('assets.overlays.question-1'))
    @include(Helper::acclayout('assets.overlays.question-2'))
    @include(Helper::acclayout('assets.overlays.mathmaking'))
    @include(Helper::acclayout('assets.overlays.new-password'))
    @include(Helper::acclayout('assets.overlays.password-is-new'))
    @include(Helper::acclayout('assets.overlays.alert'))
    </div>
</div>
@stop
@section('scripts')
    <script>
        var _skoronagame_ = {}
        _skoronagame_.game_id = {{ is_object($game) ? $game->id : -1 }};
        _skoronagame_.use_bots = {{ (int) Config::get('game.use_bots') }};
        _skoronagame_.open_frame = 'menu';
    </script>
@stop