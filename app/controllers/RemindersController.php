<?php

class RemindersController extends Controller {

	public function getRemind(){

        return Redirect::back();
	}

	public function postRemind(){

        if (!Request::ajax()) return App::abort(404);
        $json_request = array('status'=>FALSE,'responseText'=>'','redirect'=> FALSE);
        $validator = Validator::make(Input::all(),array('email'=>'required|email'));
        if($validator->passes()):
            $response = Password::remind(Input::only('email'),function($message, $user){
                $message->from(Config::get('mail.from.address'),Config::get('mail.from.name'));
                $message->subject('Новый пароль');
            });
            switch ($response):
                case Password::REMINDER_SENT:
                    $json_request['status'] = TRUE; break;
                case Password::INVALID_USER:
                    $json_request['responseText'] = 'E-mail не зарегистрирован.'; break;
            endswitch;
        else:
            $json_request['responseText'] = 'Неверно заполнены поля';
        endif;
        return Response::json($json_request,200);
	}

	public function getReset($token = null){

		if (is_null($token)) App::abort(404);
        if (Auth::check()):
            return Redirect::to(AuthAccount::getStartPage());
        else:
            $email = DB::table('password_reminders')->where('token',$token)->pluck('email');
            return Redirect::route('mainpage')->with('reset_token',$token)->with('reset_email',$email);
        endif;
	}

	public function postReset(){

        if (!Request::ajax()) return App::abort(404);
        $json_request = array('status'=>FALSE,'responseText'=>'','redirect'=> FALSE);
        $credentials = Input::only('email','password','password_confirmation','token');
        $response = Password::reset($credentials, function($user,$password){
            $user->password = Hash::make($password);
            $user->save();
        });
        switch ($response):
            case Password::INVALID_PASSWORD:
                break;
            case Password::INVALID_TOKEN:
                break;
            case Password::INVALID_USER:
                $json_request['responseText'] = 'E-mail не зарегистрирован.';
                break;
            case Password::PASSWORD_RESET:
                Auth::attempt(array('email'=>Input::get('email'),'password'=>Input::get('password'),'active'=>1),TRUE);
                if (Auth::check()):
                    $json_request['redirect'] = AuthAccount::getStartPage();
                endif;
                $json_request['status'] = TRUE;
                break;
        endswitch;
        return Response::json($json_request,200);
	}
}
