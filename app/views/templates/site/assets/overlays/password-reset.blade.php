<div id="password-reset" class="popup">
    <a href="#close" class="close"></a>
    <div class="title">Введите новый пароль</div>
    {{ Form::open(array('action'=>'RemindersController@postReset','data-result'=>'password-reset')) }}
        {{ Form::hidden('token',Session::get('reset_token')) }}
        {{ Form::hidden('email',Session::get('reset_email')) }}
        {{ Form::password('password',Input::old('password'),['class' => 'form-control','placeholder'=>'','required'=>'']) }}
        {{ Form::password('password_confirmation',Input::old('password_confirmation'),['class' => 'form-control','placeholder'=>'','required'=>'']) }}
        {{ Form::button('Отправить',array('type'=>'submit')) }}
    {{ Form::close() }}
</div>