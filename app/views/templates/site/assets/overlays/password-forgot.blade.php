<div id="password-forgot" class="popup">
    <a href="#close" class="close"></a>
    <div class="title">Получить пароль</div>
    {{ Form::open(array('action'=>'RemindersController@postRemind','data-result'=>'password-send')) }}
        {{ Form::email('email',NULL,array('placeholder'=>'Ваш e-mail')) }}
        {{ Form::button('Отправить',array('type'=>'submit')) }}
    {{ Form::close() }}
</div>