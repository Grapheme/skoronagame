<?
/**
 * MENU_PLACEMENTS: main_menu=Основное меню
 */
?>
<!DOCTYPE html>
<!--[if lt IE 7]>      <html class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>         <html class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js"> <!--<![endif]-->
<head>
    @include(Helper::layout('assets.head'))
    @yield('style')
</head>
<body>
    <!--[if lt IE 7]>
        <p class="browsehappy">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> to improve your experience.</p>
    <![endif]-->

    @include(Helper::layout('assets.header'))
    @if(Session::has('message'))
        <p>{{ Session::get('message') }}<p>
    @endif
    @section('content')
        {{ @$content }}
    @show
    @section('game')
    @show
    @section('json_response')
    @show
    @section('overlays')
    @show
    @include(Helper::layout('assets.footer'))
    @section('footer')
    @show
    @include(Helper::layout('assets.scripts'))
    @section('scripts')
    @show
</body>
</html>