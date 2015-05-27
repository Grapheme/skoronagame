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
    @if(Session::has('message'))
        <p>{{ Session::get('message') }}<p>
    @endif
    @section('content')
        {{ @$content }}
    @show
    <div class="popup-wrapper">
        <div class="popup-holder">
            @section('overlays')
            @show
        </div>
    </div>
    @include(Helper::layout('assets.footer'))
    @section('footer')
    @show
    @section('scripts')
    @show
    @include(Helper::layout('assets.scripts'))
</body>
</html>