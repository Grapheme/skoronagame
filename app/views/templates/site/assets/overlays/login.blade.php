<div id="login" class="popup">
    <div class="title">Войти в игру</div>
    {{ Form::open(array('url'=>URL::route('quick-auth'),'data-result'=>'menu')) }}
        {{ Form::email('email',NULL,array('placeholder'=>'Ваш e-mail')) }}
        {{ Form::password('password',array('placeholder'=>'Введите пароль')) }}
        {{ Form::button('Отправить',array('type'=>'submit')) }}
    {{ Form::close() }}
    <a href="#register" class="left caps">Зарегистрироваться</a>
    <a href="#password-forgot" class="right caps">Забыли пароль?</a>
    <div class="cf"></div>
    <br>
    <hr>
    <div class="socials">
        <div id="uLogin_35223736" data-ulogin="display=buttons;fields=first_name,last_name,email,photo,photo_big;redirect_uri={{ URL::route('signin.ulogin') }}">
            <div class="auth__socials">
                <a href="javascript:void(0);" data-uloginbutton="vkontakte" class="socials-vk"><img src="{{ asset(Config::get('site.theme_path').'/images/ico-vk.png') }}"></a>
                <a href="javascript:void(0);" data-uloginbutton="odnoklassniki" class="socials-ok"><img src="{{ asset(Config::get('site.theme_path').'/images/ico-ok.png') }}"></a>
                <a href="javascript:void(0);" data-uloginbutton="facebook" class="socials-facebook"><img src="{{ asset(Config::get('site.theme_path').'/images/ico-fb.png') }}"></a>
            </div>
        </div>
    </div>
</div>