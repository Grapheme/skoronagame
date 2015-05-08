<div id="register" class="popup">
    <a href="#close" class="close"></a>
    <div class="title">Регистрация</div>
    {{ Form::open(array('url'=>URL::route('quick-register'),'data-result'=>'authorisation-send')) }}
        {{ Form::email('email',NULL,array('placeholder'=>'Ваш e-mail')) }}
        {{ Form::button('Отправить',array('type'=>'submit')) }}
    {{ Form::close() }}
</div>