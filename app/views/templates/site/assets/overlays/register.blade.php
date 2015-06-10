<div id="register" class="popup">
    <a href="#close" class="close"></a>
    <div class="title">Регистрация</div>
    {{ Form::open(array('url'=>URL::route('quick-register'),'data-result'=>'authorisation-send')) }}
    {{ Form::text('name',NULL,array('placeholder'=>'Введите имя','maxlength'=>12)) }}
        <div class="i">
          <div class="text-wrapper">
            <div class="text">
              “Имя” должно быть уникальным,
              может содержать кириллические
              и латинские буквы, цифры, знак пробела.
              Длина не более 12 символов.
            </div>
          </div>
        </div>
        {{ Form::email('email',NULL,array('placeholder'=>'Ваш e-mail')) }}
        {{ Form::button('Отправить',array('type'=>'submit')) }}
    {{ Form::close() }}
</div>