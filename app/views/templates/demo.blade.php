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
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>DEMO</title>
    <meta name="description" content="">
    <meta name="keywords" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @yield('style')
</head>
<body>
@section('content')
{{ @$content }}
@show
@section('game')
@show
@section('json_response')
@show
@section('overlays')
@show
@section('footer')
@show
@section('scripts')
@show
@show
</body>
</html>