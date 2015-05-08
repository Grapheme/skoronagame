<div id="login" class="popup">
    <div class="title">Войти в игру</div>
    {{ Form::open(array('url'=>URL::route('quick-auth'),'data-result'=>'menu')) }}
        {{ Form::email('email',NULL,array('placeholder'=>'Ваш e-mail')) }}
        {{ Form::password('password',array('placeholder'=>'Введите пароль')) }}
        {{ Form::button('Отправить',array('type'=>'submit')) }}
    {{ Form::close() }}
    <a href="#register" class="left caps">Зарегестрироваться</a>
    <a href="#password-forgot" class="right caps">Забыли пароль?</a>
    <div class="cf"></div>
    <br>
    <hr>
    <div class="socials">
        <a href=""><img src="{{ asset(Config::get('site.theme_path').'/images/ico-vk.png') }}"></a>
        <a href=""><img src="{{ asset(Config::get('site.theme_path').'/images/ico-ok.png') }}"></a>
        <a href=""><img src="{{ asset(Config::get('site.theme_path').'/images/ico-fb.png') }}"></a>
    </div>
</div>