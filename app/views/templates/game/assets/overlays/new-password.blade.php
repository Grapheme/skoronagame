<div id="new-password" class="popup">
    <a href="#close" class="close"></a>
    <div class="title">Новый пароль</div>
    {{ Form::open(array('url'=>URL::route('profile-password-save'),'data-result'=>'password-is-new')) }}
        {{ Form::password('old_password',array('placeholder'=>'Введите пароль')) }}
        {{ Form::password('password',array('placeholder'=>'Новый пароль')) }}
        <div class="i">
            <div class="text-wrapper">
                <div class="text">
                    Задайте сложный пароль, используя заглавные
                    и строчные буквы (A-Z, a-z), цифры (0-9).
                    Длина пароля должны быть не мение 6 символов.
                </div>
            </div>
        </div>
        {{ Form::password('ver_password',['placeholder'=>'Повторный ввод нового пароля']) }}
        {{ Form::button('Отправить',array('type'=>'submit')) }}
    {{ Form::close() }}
</div>