<?php

class QuestionsController extends BaseController {

    public static $name = 'questions';
    public static $group = 'application';

    /****************************************************************************/
    public function __construct(GameQuestions $question){

        $this->question = $question;
        $this->module = array(
            'tpl' => static::returnTpl('admin'),
        );
        View::share('module', $this->module);
    }
    /****************************************************************************/
    public static function returnRoutes($prefix = null) {

        $class = __CLASS__;
        Route::group(array('before' => 'admin.auth', 'prefix' => 'admin'), function () use ($class) {
            Route::resource('game/questions/quiz', $class,
                array(
                    'except' => array('show'),
                    'names' => array(
                        'index' => 'question.index',
                        'create' => 'question.create',
                        'store' => 'question.store',
                        'edit' => 'question.edit',
                        'update' => 'question.update',
                        'destroy' => 'question.destroy'
                    )
                )
            );
            Route::resource('game/questions/normal', $class,
                array(
                    'except' => array('show'),
                    'names' => array(
                        'index' => 'question.index',
                        'create' => 'question.create',
                        'store' => 'question.store',
                        'edit' => 'question.edit',
                        'update' => 'question.update',
                        'destroy' => 'question.destroy'
                    )
                )
            );
        });
    }
    /****************************************************************************/
    public static function returnInfo() {}

    public static function returnMenu() {}

    public static function returnActions() {}
    /****************************************************************************/

    public function index(){

        $questions = GameQuestions::where('type',Request::segment(4))->orderBy('title')->get();
        return View::make($this->module['tpl'].'question_index', compact('questions'));
    }

    public function create(){

        return View::make($this->module['tpl'].'question_create');
    }

    public function store(){

        $validator = Validator::make(Input::all(),GameQuestions::$rules);
        if ($validator->passes()):
            $answers = array();
            foreach (Input::get('answers') as $index => $answer):
                $answers[] = array('title' => $answer, 'current' => Input::get("current.$index"));
            endforeach;
            $question = array('title' => Input::get('title'), 'type' => Input::get('type'),
                'question' => Input::get('question'), 'answers' => json_encode($answers));
            $this->question = GameQuestions::create($question);
            $json_request['responseText'] = "Вопрос добавлен";
            $json_request['redirect'] = URL::to('admin/game/questions/'.Input::get('type'));
            $json_request['status'] = TRUE;
        else:
            $json_request['responseText'] = 'Неверно заполнены поля';
            $json_request['responseErrorText'] = implode($validator->messages()->all(),'<br />');
        endif;
        return Response::json($json_request, 200);
    }

    public function edit($question_id){

        if($question = GameQuestions::where('id',$question_id)->first()):
            return View::make($this->module['tpl'].'question_edit',compact('question'));
        else:
            App::abort(404);
        endif;
    }

    public function update($question_id){

        $validator = Validator::make(Input::all(),GameQuestions::$rules);
        if ($validator->passes()):
            $this->question = GameQuestions::where('id',$question_id)->firstOrFail();
            $answers = array();
            foreach (Input::get('answers') as $index => $answer):
                $answers[] = array('title' => $answer, 'current' => Input::get("current.$index"));
            endforeach;
            $this->question->title = Input::get('title');
            $this->question->type = Input::get('type');
            $this->question->question = Input::get('question');
            $this->question->answers = json_encode($answers);
            $this->question->save();
            $this->question->touch();

            $json_request['responseText'] = "Вопрос сохранен";
            $json_request['redirect'] = URL::to('admin/game/questions/'.Input::get('type'));
            $json_request['status'] = TRUE;
        else:
            $json_request['responseText'] = 'Неверно заполнены поля';
            $json_request['responseErrorText'] = implode($validator->messages()->all(),'<br />');
        endif;
        return Response::json($json_request, 200);
    }

    public function destroy($question_id){

        if (GameQuestions::where('id',$question_id)->delete()):
            $json_request['responseText'] = "Вопрос удален.";
            $json_request['status'] = TRUE;
        else:
            App::abort(404);
        endif;
    }
}