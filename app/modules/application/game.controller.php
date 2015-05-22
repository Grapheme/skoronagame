<?php

class GameController extends BaseController {

    public static $name = 'game';
    public static $group = 'application';

    private $game;
    private $user;
    private $game_statuses = array('wait','start','ready','over');
    private $game_answers;
    private $game_winners = array();
    private $json_request = array('status'=>FALSE,'responseJSON'=>'','responseText'=>'','redirect'=> FALSE);
    /****************************************************************************/
    public function __construct(){

    }

    public function initGame(){

        if(!is_null($this->game) && is_object($this->game)):
            return TRUE;
        elseif (Input::has('game') && Input::get('game') > 0 ):
            $this->game = Game::where('id',Input::get('game'))->with('users')->first();
            $this->user = GameUser::where('game_id',Input::get('game'))->where('user_id',Auth::user()->id)->first();
            return TRUE;
        else:
            $this->game = null;
            return FALSE;
        endif;
    }
    /****************************************************************************/
    public static function returnRoutes() {

        $class = __CLASS__;
        Route::controller('password', 'RemindersController');

        Route::group(array('before'=>'login','prefix'=>''), function() use ($class) {
            Route::post('login/user', array('before'=>'csrf','as'=>'quick-auth','uses'=>$class.'@QuickAuth'));
            Route::post('register/user', array('before'=>'csrf','as'=>'quick-register','uses'=>$class.'@QuickRegister'));
        });

        Route::group(array('before'=>'user.auth','prefix' => $class::$name), function() use ($class) {
            Route::get('', array('as'=>'game','uses'=>$class.'@indexGame'));
            Route::get('demo', array('as'=>'game-demo','uses'=>$class.'@demoGame'));
        });

        Route::group(array('before'=>'user.auth','prefix' => $class::$name), function() use ($class) {
            Route::post('profile/password-save', array('as'=>'profile-password-save','uses'=>$class.'@ProfilePasswordSave'));

            Route::post('get-game', array('as'=>'get-game','uses'=>$class.'@getGame'));
            Route::post('over-game', array('as'=>'over-game','uses'=>$class.'@overGame'));
            Route::post('question/get-quiz', array('as'=>'get-quiz-question','uses'=>$class.'@getQuizQuestion'));
            Route::post('question/get-normal', array('as'=>'get-normal-question','uses'=>$class.'@getNormalQuestion'));
            Route::post('question/send-answer', array('as'=>'send-answer-question','uses'=>$class.'@sendAnswerQuestion'));
            Route::post('question/get-result', array('as'=>'get-result-question','uses'=>$class.'@getResultQuestion'));
            Route::post('question/get-users-results', array('as'=>'get-users-results-question','uses'=>$class.'@getUsersResultsQuestion'));
            Route::post('conquest/territory', array('as'=>'send-conquest-territory','uses'=>$class.'@sendConquestTerritory'));
            Route::post('conquest/capital', array('as'=>'send-conquest-capital','uses'=>$class.'@sendConquestCapital'));
        });
    }
    /****************************************************************************/
    public static function returnInfo() {

        return array(
            'name' => self::$name,
            'group' => self::$group,
            'title' => 'Сибирская корона',
            'visible' => 1,
        );
    }

    public static function returnMenu() {

        $menu_child[] = array(
            'title' => 'Квиз-вопросы',
            'link' => self::$name.'/questions/quiz',
            'class' => 'fa-circle',
        );
        $menu_child[] = array(
            'title' => 'Обычные вопросы',
            'link' => self::$name.'/questions/normal',
            'class' => 'fa-circle-o',
        );
        $menu_child[] = array(
            'title' => 'Бейджи',
            'link' => self::$name.'/'.BadgesController::$name,
            'class' => 'fa-trophy',
        );
        $menu[] = array(
            'title' => 'Игра',
            'link' => '#',
            'class' => 'fa-book',
            'system' => 1,
            'menu_child' => $menu_child,
            'permit' => 'view'
        );
        return $menu;
    }

    public static function returnActions() {

        return array(
            'view' => 'Просмотр',
            'create' => 'Создание',
            'edit' => 'Редактирование',
            'delete' => 'Удаление',
        );
    }
    /****************************************************************************/
    public function QuickAuth(){

        if (!Request::ajax()) return App::abort(404);
        $validator = Validator::make(Input::all(), array('email' => 'required|email', 'password' => 'required'));
        if ($validator->passes()):
//          Auth::loginUsingId(17);
//          $this->json_request['redirect'] = AuthAccount::getStartPage();
//          $this->json_request['status'] = TRUE;
            if (Auth::attempt(array('email' => Input::get('email'), 'password' => Input::get('password'),
                'active' => 1), FALSE)
            ):
                if (Auth::check()):
                    $this->json_request['redirect'] = AuthAccount::getStartPage();
                    $this->json_request['status'] = TRUE;
                endif;
            else:
                $this->json_request['responseText'] = 'Неверное имя пользователя или пароль';
            endif;
        endif;
        return Response::json($this->json_request, 200);
    }

    public function QuickRegister(){

        if(!Request::ajax()) return App::abort(404);
        $validator = Validator::make(Input::all(),array('email'=>'required|email'));
        if($validator->passes()):
            if(User::where('email',Input::get('email'))->exists() === FALSE):
                $user = new User;
                $user->group_id = Group::where('name','game')->pluck('id');
                $user->name = Input::get('name');
                $user->email = Input::get('email');
                $user->active = 1;
                $password = Str::random(12);
                $user->password = Hash::make($password);
                $user->save();
                Auth::login($user);
                Mail::send('emails.auth.signup', array('user' => $user,'password' => $password), function ($message) {
                    $message->from(Config::get('mail.from.address'),Config::get('mail.from.name'));
                    $message->to(Input::get('email'))->subject('Регистрация');
                });
                $this->json_request['status'] = TRUE;
                $this->json_request['redirect'] = URL::route('game');
            else:
                $this->json_request['responseText'] = 'E-mail уже зарегистрирован.';
            endif;
        else:
            $json_request['responseText'] = 'Неверно заполнены поля';
        endif;
        return Response::json($this->json_request,200);
    }
    /****************************************************************************/
    public function ProfilePasswordSave(){

        if (!Request::ajax()) return App::abort(404);
        $json_request = array('status'=>FALSE,'responseText'=>'','redirect'=> FALSE);
        if (Hash::check(Input::get('old_password'), Auth::user()->password)):
            $user = Auth::user();
            $user->password = Hash::make(Input::get('password'));
            $user->save();
            $json_request['status'] = TRUE;
        else:
            $json_request['responseText'] = 'Неверно указанный старый пароль';
        endif;
        return Response::json($json_request,200);
    }
    /****************************************************************************/
    /********************************* GAME *************************************/
    /****************************************************************************/
    public function indexGame(){

        $games = Game::where('status_over', 0)->with(array('users' => function ($query) {
            $query->where('user_id', Auth::user()->id);
        }))->get();
        if (count($games)):
            foreach ($games as $game):
                if ($game->users->count()):
                    $this->game = Game::where('id', $game->id)->with('users')->first();
                    break;
                endif;
            endforeach;
        endif;
        return View::make(Helper::acclayout('index'),array('game'=>$this->game));
    }

    public function demoGame(){

        $games = Game::where('status_over', 0)->with(array('users' => function ($query) {
            $query->where('user_id', Auth::user()->id);
        }))->get();
        if (count($games)):
            foreach ($games as $game):
                if ($game->users->count()):
                    $this->game = Game::where('id', $game->id)->with('users')->first();
                    break;
                endif;
            endforeach;
        endif;
        return View::make(Helper::acclayout('demo'),array('game'=>$this->game));
    }
    /********************************* JSON *************************************/
    public function getGame(){

        if(!Request::ajax()) return App::abort(404);

        if (!$this->initGame()):
            if(!$this->hasCreatedGame()):
                $this->createNewGame();
            else:
                $this->joinNewGame();
                $this->startGame();
                $this->createGameMap();
                $this->randomDistributionCapital();
                $this->randomStep();
            endif;
        endif;
        $this->setGameStatus();
        $this->createGameJSONResponse();
        return Response::json($this->json_request,200);
    }

    public function overGame(){

        if(!Request::ajax()) return App::abort(404);
        if ($this->initGame()):
            if (!empty($this->game->users)):
                foreach($this->game->users as $user):
                    if ($user->user_id == Auth::user()->id):
                        $this->finishGame(0);
                        $this->json_request['responseText'] = Auth::user()->name.' завершил игру.';
                        $this->json_request['status'] = TRUE;
                        break;
                    endif;
                endforeach;
            endif;
        endif;
        return Response::json($this->json_request,200);
    }

    public function getQuizQuestion(){

        if (!Request::ajax()) return App::abort(404);
        $validation = Validator::make(Input::all(), array('users' => ''));
        if ($validation->passes()):
            if ($this->initGame()):
                $this->changeGameStatus($this->game_statuses[2]);
                if ($this->validGameStage(0)):
                    $this->changeGameStage(1);
                endif;
                $this->nextStep(0);
                if (!GameUserQuestions::where('game_id', $this->game->id)->where('status', 0)->exists()):
                    $randomQuestion = $this->randomQuestion('quiz');
                    $this->createQuestion($randomQuestion->id,Input::get('users'));
                endif;
                $question = GameUserQuestions::where('game_id', $this->game->id)->where('user_id', Auth::user()->id)->where('status', 0)->with('question')->first();
                $this->createQuestionJSONResponse($question);
            endif;
        endif;
        return Response::json($this->json_request, 200);
    }

    public function getNormalQuestion(){

        if (!Request::ajax()) return App::abort(404);
        $validation = Validator::make(Input::all(), array('users' => ''));
        if ($validation->passes()):
            if ($this->initGame()):
                if ($this->validGameStage(2)):
                    if (!GameUserQuestions::where('game_id', $this->game->id)->where('status', 0)->exists()):
                        $this->createStepInSecondStage();
                        $randomQuestion = $this->randomQuestion('normal');
                        $this->createQuestion($randomQuestion->id,Input::get('users'));
                        $this->createDuel(Input::get('users'));
                        $this->nextStep(0);
                    endif;
                    $question = GameUserQuestions::where('game_id', $this->game->id)->where('user_id', Auth::user()->id)->where('status', 0)->with('question')->first();
                    $this->createQuestionJSONResponse($question);
                endif;
            endif;
        endif;
        return Response::json($this->json_request, 200);
    }

    public function sendAnswerQuestion(){

        $validation = Validator::make(Input::all(), array('question'=>'required','answer'=>'','time'=>'required'));
        if($validation->passes()):
            if ($this->initGame()):
                if($userGameQuestion = GameUserQuestions::where('game_id',$this->game->id)->where('id',Input::get('question'))->where('user_id',Auth::user()->id)->with('question')->first()):
                    $userGameQuestion->status = 1;
                    $userGameQuestion->answer = (int)Input::get('answer');
                    $userGameQuestion->seconds = (int)Input::get('time');
                    $userGameQuestion->save();
                    $userGameQuestion->touch();
                    if ($userGameQuestion->answer != 99999):
                        $this->json_request['responseText'] = "Спасибо. Ваш ответ принят.";
                    else:
                        $this->json_request['responseText'] = 'Вы не ответили на вопрос.';
                    endif;
                    $this->json_request['status'] = TRUE;
                endif;
            endif;
        endif;
        return Response::json($this->json_request, 200);
    }

    public function getResultQuestion(){

        if (!Request::ajax()) return App::abort(404);
        $validation = Validator::make(Input::all(), array('question' => 'required', 'type' => 'required', 'zone'=> ''));
        if ($validation->passes()):
            if ($this->initGame()):
                $number_participants = Config::get('game.number_participants');
                if($this->validGameStage(2)):
                    $number_participants = 2;
                endif;
                if (GameUserQuestions::where('game_id', $this->game->id)->where('status', 1)->count() == $number_participants):
                    $current_answer = FALSE;
                    foreach (GameUserQuestions::where('game_id', $this->game->id)->where('status', 1)->with('question')->get() as $userGameQuestion):
                        $this->game_answers['current_answer'] = $this->getCurrentAnswer($userGameQuestion);
                        $this->game_answers['answers_titles'][$userGameQuestion->user_id] = $userGameQuestion->answer;
                        $this->game_answers['answers_times'][$userGameQuestion->user_id] = $userGameQuestion->seconds;
                    endforeach;
                    if (Input::get('type') == 'quiz'):
                        $this->setQuizQuestionWinner();
                    elseif (Input::get('type') == 'normal'):
                        $this->setNormalQuestionWinner();
                    endif;
                    if ($this->game_winners === 'standoff'):
                        $this->resetQuestions();
                    elseif (count($this->game_winners) == $number_participants):
                        GameUser::where('game_id', $this->game->id)->update(array('status' => 0, 'make_steps' => 0, 'updated_at' => date('Y-m-d H:i:s')));
                        foreach ($this->game_winners as $user_id => $place):
                            GameUserQuestions::where('game_id', $this->game->id)->where('user_id', $user_id)->where('status', 1)->where('place', 0)->first()
                                ->update(array('status' => 2, 'place' => $place, 'updated_at' => date('Y-m-d H:i:s')));
                            $available_steps = $this->getAvailableSteps($user_id, $place);
                            GameUser::where('game_id', $this->game->id)->where('user_id', $user_id)->update(array('available_steps' => $available_steps));
                        endforeach;
                        if($this->validGameStage(2)):
                            $this->createDuel();
                            $this->nextStepInSecondStage();
                        endif;
                    endif;
                elseif ($userQuestion = GameUserQuestions::where('id', Input::get('question'))->where('game_id', $this->game->id)->where('status', 2)->first()):
                    $this->game_winners = GameUserQuestions::where('game_id', $this->game->id)->where('status', 2)->where('group_id', $userQuestion->group_id)->lists('place','user_id');
                elseif(GameUserQuestions::where('id', Input::get('question'))->where('game_id', $this->game->id)->where('status', 99)->exists()):
                    $this->game_winners = 'standoff';
                else:
                    $this->game_winners = 'retry';
                endif;
                $this->createQuestionResultJSONResponse();
                if (is_array($this->game_winners)):
                    asort($this->game_winners);
                    $winners = array_keys($this->game_winners);
                    $this->json_request['responseText'] = 'Первое место: ' . @$winners[0] . '! Второе место: ' . @$winners[1] . '! Третье место: ' . @$winners[2];
                endif;
            endif;
        endif;
        return Response::json($this->json_request, 200);
    }

    public function getUsersResultsQuestion(){

        if (!Request::ajax()) return App::abort(404);
        $validation = Validator::make(Input::all(), array('question' => 'required', 'type' => 'required'));
        if ($validation->passes()):
            if ($this->initGame()):
                $question_group = GameUserQuestions::where('game_id', $this->game->id)->where('id', Input::get('question'))->pluck('group_id');
                $question_id = GameUserQuestions::where('game_id', $this->game->id)->where('id', Input::get('question'))->pluck('question_id');
                if ($users_questions = GameUserQuestions::where('game_id', $this->game->id)->where('group_id',$question_group)->get()):
                    $current_answer = '';
                    $users_answers = array();
                    if ($answer_question = GameQuestions::where('id',$question_id)->where('type',Input::get('type'))->pluck('answers')):
                        $answer_question = json_decode($answer_question,TRUE);
                        if (Input::get('type') == 'quiz'):
                            $current_answer = isset($answer_question[0]['title']) ? $answer_question[0]['title']: '';
                            foreach($users_questions as $users_question):
                                $correctly = ($users_question->answer == $current_answer) ? TRUE : FALSE;
                                $users_answers[$users_question->user_id] = array('answer' => $users_question->answer,
                                    'seconds' => $users_question->seconds, 'place' => $users_question->place,
                                    'status' => $users_question->status, 'correctly' => $correctly);
                            endforeach;
                        elseif (Input::get('type') == 'normal'):
                            foreach($answer_question as $answer):
                                if ($answer['current'] == 1):
                                    $current_answer = $answer['title'];
                                endif;
                            endforeach;
                            foreach($users_questions as $users_question):
                                $answer = isset($answer_question[$users_question->answer]) ? $answer_question[$users_question->answer] : '';
                                $users_answers[$users_question->user_id] = array('answer' => $answer['title'],
                                    'seconds' => $users_question->seconds, 'place' => $users_question->place,
                                    'status' => $users_question->status,'correctly' => (int) $answer['current']);
                            endforeach;
                        endif;
                    endif;
                    $this->json_request['responseJSON'] = array('game_id' => $this->game->id, 'current_answer' => $current_answer, 'results' => $users_answers);
                    $this->json_request['status'] = TRUE;
                endif;
            endif;
        endif;
        return Response::json($this->json_request, 200);
    }

    public function sendConquestTerritory(){

        $validation = Validator::make(Input::all(), array('zone'=>'required'));
        if($validation->passes()):
            if ($this->initGame()):
                if($this->validGameStage(1)):
                    if ($this->changeGameUsersSteps()):
                        $this->conquestTerritory(Input::get('zone'));
                        $this->changeUserPoints(Auth::user()->id,200,$this->user);
                        if ($this->user->status == 1):
                            if ($this->user->available_steps == 2):
                                $user_id = GameUser::where('game_id',$this->game->id)->where('status',0)->where('available_steps',1)->pluck('user_id');
                                $this->nextStep($user_id);
                            else:
                                $this->nextStep(0);
                            endif;
                        endif;
                        if($this->isConqueredTerritories()):
                            $this->changeGameStage(2);
                            $this->nextStep();
                            $nextStep = $this->createTemplateStepInSecondStage();
                            $this->nextStep($nextStep);
                        endif;
                        $this->json_request['responseText'] = 'Вы заняли территорию.';
                        $this->json_request['status'] = TRUE;
                    endif;
                elseif($this->validGameStage(2)):
                    if ($this->changeGameUsersSteps()):
                        $points = $this->getTerritoryPoints(Input::get('zone'));
                        $this->changeUserPoints(Auth::user()->id,$points,$this->user);
                        $this->conquestTerritory(Input::get('zone'));
                        $users = GameUser::where('game_id', $this->game->id)->get();
                        $this->changeGameUsersStatus(2, $users);
                        $this->json_request['responseText'] = 'Вы заняли территорию.';
                        $this->json_request['status'] = TRUE;
                    endif;
                endif;
            endif;
        endif;
        return Response::json($this->json_request, 200);
    }

    public function sendConquestCapital(){

        $validation = Validator::make(Input::all(), array('zone'=>'required'));
        if($validation->passes()):
            if ($this->initGame()):
                if($this->validGameStage(2)):
                    if ($this->changeGameUsersSteps()):
                        $lives =  $this->conquestCapital(Input::get('zone'));
                        if ($lives == 1):
                            $this->changeUserPoints(Auth::user()->id, 1000, $this->user);
                            $this->nextStepInSecondStage();
                            $users = GameUser::where('game_id', $this->game->id)->get();
                            $this->changeGameUsersStatus(2, $users);
                            $this->json_request['responseText'] = 'Вы заняли столицу.';
                        elseif ($lives > 1):
                            $this->json_request['responseText'] = 'Продолжайте захват столицы';
                        endif;
                        $this->json_request['status'] = TRUE;
                    endif;
                endif;
            endif;
        endif;
        return Response::json($this->json_request, 200);
    }
    /****************************************************************************/
    /****************************************************************************/
    private function hasInitGame(){

        if (!is_null($this->game) && is_object($this->game)):
            return TRUE;
        else:
            return FALSE;
        endif;
    }

    private function hasCreatedGame(){

        foreach(Game::where('status_begin', 0)->where('status_over', 0)->with('users')->get() as $game):
            if (count($game->users) < 3):
                $this->game = $game;
                return TRUE;
            endif;
        endforeach;
        return FALSE;
    }

    private function joinNewGame(){

        if (GameUser::where('game_id', $this->game->id)->where('user_id', Auth::user()->id)->exists() === FALSE):
            $this->game->users[] = GameUser::create(array('game_id' => $this->game->id, 'user_id' => Auth::user()->id,
                'status' => 0, 'points' => 0, 'json_settings' => json_encode(array())));
        endif;
    }

    private function startGame(){

        if (isset($this->game->users) && count($this->game->users) == Config::get('game.number_participants')):
            $this->game->status_begin = 1;
            $this->game->date_begin = Carbon::now()->format('Y-m-d H:i:s');
            $this->game->save();
            $this->game->touch();
            $this->changeGameStatus($this->game_statuses[1]);
        endif;
    }

    private function finishGame($status_over = 1){

        $this->game->status = $this->game_statuses[3];
        $this->game->status_over = $status_over;
        $this->game->date_over = Carbon::now()->format('Y-m-d H:i:s');
        $this->game->save();
        $this->game->touch();
    }

    private function setGameStatus(){

        if (!is_null($this->game->status)):
            return TRUE;
        endif;
        if (!$this->game->status_begin):
            $this->game->status = $this->game_statuses[0];
        elseif ($this->game->status_begin):
            $this->game->status = $this->game_statuses[2];
        elseif ($this->game->status_over):
            $this->game->status = $this->game_statuses[3];
        endif;
        $this->game->save();
        $this->game->touch();
        return TRUE;
    }
    /****************************** CREATING ************************************/
    private function createNewGame(){

        $this->game = Game::create(array('status'=>$this->game_statuses[0],'stage'=>0,'started_id' => Auth::user()->id, 'winner_id' => 0, 'status_begin' => 0,
            'date_begin' => '000-00-00 00:00:00', 'status_over' => 0, 'date_over' => '000-00-00 00:00:00',
            'json_settings' => json_encode(array('next_step'=>0))));
        if ($this->game):
            self::joinNewGame();
        endif;
        return TRUE;
    }

    private function createGameMap(){

        if ($this->validGameStatus($this->game_statuses[1])):
            $map_places = array();
            for ($i = 0; $i < Config::get('game.number_places_on_map'); $i++):
                $map_places[] = new GameMap(array(
                    'game_id' => $this->game->id, 'user_id' => 0, 'zone' => $i + 1, 'capital' => '0',
                    'lives' => Config::get('game.map_empty_place_lives'), 'points' => 200, 'status' => 0,
                    'json_settings' => json_encode(array('color' => '')),
                ));
            endfor;
            if (count($map_places)):
                $this->game->map_places()->saveMany($map_places);
            endif;
        endif;
    }

    private function createQuestion($question_id, $users_ids = array()) {

        if ($this->validGameStatus($this->game_statuses[2])):
            $group_id = uniqid($this->game->id.'_');
            if (empty($users_ids)):
                foreach(GameUser::where('game_id', $this->game->id)->with('user')->lists('id','user_id') as $user_id => $user):
                    GameUserQuestions::create(array('group_id'=>$group_id,'game_id' => $this->game->id, 'user_id' => $user_id,
                        'question_id' => $question_id, 'status' => 0, 'place' => 0, 'answer' => 0, 'seconds' => 0));
                endforeach;
            elseif(count($users_ids)):
                foreach($users_ids as $user_id):
                    GameUserQuestions::create(array('group_id'=>$group_id,'game_id' => $this->game->id, 'user_id' => $user_id,
                        'question_id' => $question_id, 'status' => 0, 'place' => 0, 'answer' => 0, 'seconds' => 0));
                endforeach;
            endif;
        endif;
    }

    private function createDuel($users_ids = array()){

        $json_settings = json_decode($this->game->json_settings,TRUE);
        $json_settings['duel'] = is_array($users_ids) ? $users_ids : array();
        $this->game->json_settings = json_encode($json_settings);
        $this->game->save();
        $this->game->touch();
    }

    private function createTemplateStepInSecondStage(){

        $json_settings = json_decode($this->game->json_settings, TRUE);
        $json_settings['current_tour'] = 0;
        $json_settings['stage2_tours'] = array(array(),array(),array(),array());
        if ($users = GameUser::where('game_id', $this->game->id)->with('user')->lists('user_id')):
            foreach($json_settings['stage2_tours'] as $tour => $users_id):
                shuffle($users);
                foreach($users as $user_id):
                    $json_settings['stage2_tours'][$tour][$user_id] = FALSE;
                endforeach;
            endforeach;
        endif;
        $this->game->json_settings = json_encode($json_settings);
        $this->game->save();
        $this->game->touch();

        reset($json_settings['stage2_tours'][0]);
        return array_keys($json_settings['stage2_tours'][0])[0];
    }

    private function createStepInSecondStage(){

        $json_settings = json_decode($this->game->json_settings, TRUE);
        $current_tour = $json_settings['current_tour'];
        $stage2_tours = $json_settings['stage2_tours'];
        if (isset($stage2_tours[$current_tour][Auth::user()->id]) && $stage2_tours[$current_tour][Auth::user()->id] == FALSE):
            $stage2_tours[$current_tour][Auth::user()->id] = TRUE;
            $nextTour = TRUE;
            foreach($stage2_tours[$current_tour] as $user_id => $status):
                if ($status == FALSE):
                    $nextTour = FALSE;
                    break;
                endif;
            endforeach;
            if ($nextTour):
                $current_tour++;
            endif;
            $json_settings['current_tour'] = $current_tour;
            $json_settings['stage2_tours'] = $stage2_tours;
        endif;
        $this->game->json_settings = json_encode($json_settings);
        $this->game->save();
        $this->game->touch();
    }

    private function createGameJSONResponse(){

        if ($this->game):
            $users = $map = array();
            foreach (GameUser::where('game_id', $this->game->id)->with('user')->get() as $user_game):
                $users[] = array('id' => $user_game->user->id, 'name' => $user_game->user->name,
                    'email' => $user_game->user->email, 'photo' => $user_game->user->photo,
                    'color' => $user_game->color, 'points' => $user_game->points, 'place' => $user_game->place,
                    'status' => $user_game->status,
                    'available_steps' => $user_game->available_steps,'make_steps' => $user_game->make_steps,
                    'settings' => json_decode($user_game->json_settings, TRUE));
            endforeach;
            foreach (GameMap::where('game_id', $this->game->id)->orderBy('id')->get() as $map_place):
                $map[] = array('id' => $map_place->id, 'zone' => $map_place->zone, 'user_id' => $map_place->user_id,
                    'capital' => $map_place->capital, 'lives' => $map_place->lives, 'points' => $map_place->points,
                    'settings' => json_decode($map_place->json_settings, TRUE));
            endforeach;
            $this->json_request['responseJSON'] = array('game_id' => $this->game->id, 'game_stage' => $this->game->stage,
                'game_status' => $this->game->status, 'current_user' => Auth::user()->id, 'users' => $users,
                'map' => $map, 'settings' => json_decode($this->game->json_settings, TRUE));
            $this->json_request['status'] = TRUE;
            return TRUE;
        else:
            return FALSE;
        endif;
    }

    private function createQuestionJSONResponse($user_question){

        if ($this->validGameStatus($this->game_statuses[2]) && is_object($user_question)):
            $this->json_request['responseJSON'] = array('game_id' => $this->game->id,
                'game_status' => $this->game->status,'game_stage' => $this->game->stage,'current_user' => Auth::user()->id,
                'question' => array('id' => $user_question->id, 'text' => $user_question->question->question,
                'type' => $user_question->question->type,'answers'=>array())
            );
            if ($user_question->question->type == 'normal' && !empty($user_question->question->answers)):
                $answers = json_decode($user_question->question->answers);
                foreach($answers as $answer):
                    if (!empty($answer->title)):
                        $this->json_request['responseJSON']['question']['answers'][] = $answer->title;
                    endif;
                endforeach;
            endif;
            $this->json_request['status'] = TRUE;
        endif;
    }

    private function createQuestionResultJSONResponse(){

        if ($this->validGameStatus($this->game_statuses[2])):
            $this->json_request['responseJSON'] = array('game_id' => $this->game->id,
                'game_status' => $this->game->status, 'game_stage' => $this->game->stage,
                'current_user' => Auth::user()->id, 'result' => $this->game_winners);
            $this->json_request['status'] = TRUE;
        endif;
    }
    /******************************* RANDOM**************************************/
    private function randomDistributionCapital(){

        if ($this->validGameStatus($this->game_statuses[1])):
            if($map_places_list = GameMap::where('game_id',$this->game->id)->orderBy('zone')->orderBy('id')->get()):
                $map_places = $map_places_ids = $map_capital_ids = array();
                foreach ($map_places_list as $map_place):
                    $map_places_ids[$map_place->id] = $map_place->id;
                endforeach;
                foreach ($map_places_list as $map_place):
                    $map_places[$map_place->id] = $map_place;
                endforeach;
                foreach(GameUser::where('game_id',$this->game->id)->get() as $user_index => $user):
                    $capital = array_rand($map_places_ids);
                    $map_places_ids = $this->exclude_indexes($capital,$map_places,$map_places_ids);
                    $map_places[$capital]->user_id = $user->user_id;
                    $map_places[$capital]->points = 1000;
                    $map_places[$capital]->capital = 1;
                    $map_places[$capital]->lives = Config::get('game.map_capital_place_lives');
                    $settings = json_decode($map_places[$capital]->json_settings);
                    $settings->color = $this->randomColor($user_index);
                    $user->color = $settings->color;
                    $user->save();
                    $map_places[$capital]->json_settings = json_encode($settings);
                    $map_places[$capital]->save();
                endforeach;
            endif;
        endif;
    }

    private function randomStep(){

        if ($users = GameUser::where('game_id', $this->game->id)->with('user')->lists('id','user_id')):
            $user_id = array_rand($users);
            $this->nextStep($user_id);
        endif;
    }

    private function randomColor($color_index){

        $colors = Config::get('game.colors');
        return isset($colors[$color_index]) ? $colors[$color_index] : '';
    }

    private function randomQuestion($type = 'quiz'){

        if ($this->validGameStatus($this->game_statuses[2])):
            $exclude_ids = GameUserQuestions::where('game_id',$this->game->id)->groupBy('question_id')->orderBy('question_id')->lists('question_id');
            if (empty($exclude_ids)):
                $exclude_ids = array(0);
            endif;
            if ($questions = GameQuestions::where('type',$type)->whereNotIn('id',$exclude_ids)->lists('title','id')):
                $question_id = array_rand($questions);
                return GameQuestions::where('id',$question_id)->where('type',$type)->first();
            endif;
        endif;
    }
    /******************************** CHANGES ************************************/
    private function changeGameStatus($status){

        if ($this->game->status != $status):
            $this->game->status = $status;
            $this->game->save();
        endif;
    }

    private function changeGameStage($stage){

        if ($this->game->stage != $stage):
            $this->game->stage = $stage;
            $this->game->save();
        endif;
    }

    private function changeGameUsersSteps(){

        if ($this->validGameStatus($this->game_statuses[2])):
            if($this->user->status == 0):
                $diff_steps = (int)$this->user->available_steps - $this->user->make_steps;
                if ($diff_steps > 0):
                    $this->user->make_steps = $this->user->make_steps + 1;
                    $this->user->save();
                    $this->user->touch();
                endif;
                if($this->user->available_steps == $this->user->make_steps):
                    $this->changeGameUsersStatus(1);
                endif;
                return TRUE;
            endif;
        endif;
        return FALSE;
    }

    private function changeGameUsersStatus($status,$users = NULL){

        if (!is_null($users) && count($users) == 1):
            $users->status = $status;
            $users->save();
            $users->touch();
        elseif (!is_null($users) && count($users) > 1):
            foreach($users as $user):
                $user->status = $status;
                $user->save();
                $user->touch();
            endforeach;
        else:
            $this->user->status = $status;
            $this->user->save();
            $this->user->touch();
        endif;
    }

    private function changeUserPoints($user_id,$points,$user = NULL){

        if ($this->game->status_begin):
            if (is_null($user)):
                $user = GameUser::where('game_id',$this->game->id)->where('user_id',$user_id)->first();
            endif;
            $user->points = (int) $user->points + (int)$points;
            $user->save();
            $user->touch();
        endif;
    }
    /******************************* VALIDATION **********************************/
    private function validGameStatus($status){

        if ($this->game->status == $status):
            return TRUE;
        else:
            return FALSE;
        endif;
    }

    private function validGameStage($stage){

        if ($this->game->stage == $stage):
            return TRUE;
        else:
            return FALSE;
        endif;
    }
    /********************************* OTHER *************************************/
    private function exclude_indexes($capital,$map_places, $map_places_ids){

        $adjacent_places = Config::get('game.adjacent_places');
        $temp_map_places_ids = $map_places_ids;
        $map_places_ids = array();
        foreach ($temp_map_places_ids as $map_place_id):
            if ($map_place_id != $capital && !in_array($map_places[$map_place_id]->zone,$adjacent_places[$map_places[$capital]->zone])):
                $map_places_ids[$map_place_id] = $map_place_id;
            endif;
        endforeach;
        return $map_places_ids;
    }

    private function conquestTerritory($zone){

        if ($this->validGameStatus($this->game_statuses[2])):
            #if ($conquest = GameMap::where('game_id', $this->game->id)->where('user_id', '!=', Auth::user()->id)->where('zone', $zone)->where('capital', 0)->first()):
            if ($conquest = GameMap::where('game_id', $this->game->id)->where('user_id', '!=', Auth::user()->id)->where('zone', $zone)->first()):
                if ($this->validGameStage(2)):
                    $conquest->points += 200;
                endif;
                $settings = json_decode($conquest->json_settings);
                $settings->color = $this->user['color'];
                $conquest->json_settings = json_encode($settings);
                $conquest->user_id = Auth::user()->id;
                $conquest->save();
            endif;
        endif;
    }

    private function conquestCapital($zone){

        if ($this->validGameStatus($this->game_statuses[2]) && $this->validGameStatus(2)):
            if ($conquest = GameMap::where('game_id', $this->game->id)->where('user_id', '!=', Auth::user()->id)->where('zone', $zone)->where('capital', 1)->first()):
                if ($conquest->lives == 1):
                    $this->removeUserInGame($conquest->user_id);
                    foreach (GameMap::where('game_id', $this->game->id)->where('user_id', $conquest->user_id)->get() as $territory):
                        $settings = json_decode($territory->json_settings);
                        $settings->color = $this->user['color'];
                        $territory->json_settings = json_encode($settings);
                        $territory->user_id = Auth::user()->id;
                        $territory->capital = 0;
                        $territory->status = 0;
                        $territory->save();
                        $territory->touch();
                    endforeach;
                elseif ($conquest->lives > 1):
                    $conquest->lives = $conquest->lives - 1;
                    $conquest->save();
                    $conquest->touch();
                endif;
                return $conquest->lives;
            endif;
        endif;
        return FALSE;
    }

    private function isConqueredTerritories(){

        if ($this->validGameStatus($this->game_statuses[2]) && $this->validGameStage(1)):
            if(GameMap::where('game_id',$this->game->id)->where('user_id',0)->exists()):
                return FALSE;
            else:
                return TRUE;
            endif;
        endif;
    }

    private function getCurrentAnswer($userGameQuestion){

        $currentAnswer = FALSE;
        if (!empty($userGameQuestion->question) && !empty($userGameQuestion->question->answers)):
            $answers = json_decode($userGameQuestion->question->answers);
            foreach($answers as $index => $answer):
                if ($answer->current == 1):
                    $currentAnswer = $userGameQuestion->question->type == 'quiz' ? $answer->title : $index;
                    break;
                endif;
            endforeach;
        endif;
        return $currentAnswer;
    }

    private function setQuizQuestionWinner(){

        if ($this->game_answers['current_answer'] !== FALSE):
            $this->game_winners = array('first_place' => array(), 'second_place' => array(), 'third_place' => array());
            $this->getFirstPlace();
            if ($this->isStandoff('first_place')):
                $this->game_winners = 'standoff';
                return FALSE;
            endif;
            if ($this->validGameStage(1)):
                $this->getSecondPlace();
                if ($this->isStandoff('second_place')):
                    $this->game_winners = 'standoff';
                    return FALSE;
                endif;
                $this->getThirdPlace();
            elseif($this->validGameStage(2)):
                $duel = $this->getDuel();
                if ($this->game_winners['first_place'][0] == $duel['conqu']):
                    $this->game_winners['second_place'][] = $duel['def'];
                elseif($this->game_winners['first_place'][0] == $duel['def']):
                    $this->game_winners['second_place'][] = $duel['conqu'];
                endif;
            endif;
            $winner_places = array();
            $places = array('first_place' => 1, 'second_place' => 2, 'third_place' => 3);
            foreach ($this->game_winners as $place => $user_id):
                if (isset($user_id[0])):
                    $winner_places[@$user_id[0]] = $places[$place];
                endif;
            endforeach;
            if ($this->validGameStage(1)):
                $this->nextStep(@$this->game_winners['first_place'][0]);
            endif;
            $this->game_winners = $winner_places;
        endif;
    }

    private function setNormalQuestionWinner(){

        if ($this->game_answers['current_answer'] !== FALSE):
            $this->game_winners = $winners = array();
            foreach($this->game_answers['answers_titles'] as $user_id => $answers_title):
                $this->game_winners[$user_id] = 2;
                if($answers_title == $this->game_answers['current_answer']):
                    $winners[$user_id] = @$this->game_answers['answers_times'][$user_id];
                endif;
            endforeach;
            if(count($winners) == 1):
                $users_ids = array_keys($winners);
                $this->game_winners[$users_ids[0]] = 1;
            else:
                $this->game_winners = 'standoff';
            endif;
        endif;
    }

    private function nextStep($user_id = 0){

        $json_settings = json_decode($this->game->json_settings,TRUE);
        $json_settings['next_step'] = $user_id;
        $this->game->json_settings = json_encode($json_settings);
        $this->game->save();
        $this->game->touch();
    }

    private function nextStepInSecondStage(){

        $json_settings = json_decode($this->game->json_settings, TRUE);
        $current_tour = $json_settings['current_tour'];
        $stage2_tours = $json_settings['stage2_tours'];
        if (isset($stage2_tours[$current_tour])):
            if($current_tour < 3):
                foreach($stage2_tours[$current_tour] as $user_id => $status):
                    if ($status == FALSE):
                        $this->nextStep($user_id);
                        break;
                    endif;
                endforeach;
            elseif($current_tour == 3):
                $firstStep = TRUE;
                foreach($stage2_tours[$current_tour] as $user_id => $status):
                    if ($status == TRUE):
                        $firstStep = FALSE;
                        break;
                    endif;
                endforeach;
                if($firstStep):
                    if ($winner = $this->getWinnerByPoints()):
                        $this->nextStep($winner);
                    else:
                        $this->randomStep();
                    endif;
                else:
                    foreach($stage2_tours[$current_tour] as $user_id => $status):
                        if ($status == FALSE):
                            $this->nextStep($user_id);
                            break;
                        endif;
                    endforeach;
                endif;
            endif;
        elseif($current_tour > 3):
            $this->finishGame(1);
        endif;
    }

    private function getFirstPlace(){

        $diff_titles = array();
        $diff_seconds = $this->game_answers['answers_times'];
        foreach($this->game_answers['answers_titles'] as $user_id => $answers_title):
            $diff_titles[$user_id] = abs((int)$answers_title - (int)$this->game_answers['current_answer']);
        endforeach;
        $first_place = $winners = $winner = array();
        asort($diff_titles);
        asort($diff_seconds);
        foreach($diff_titles as $user_id => $diff_title):
            $winner = array('user_id'=>$user_id,'title'=>$diff_title,'second'=>$diff_seconds[$user_id]);
            break;
        endforeach;
        $winners[$winner['user_id']] = $winner;
        foreach($diff_titles as $user_id => $diff_title):
            if ($winner['title'] == $diff_title):
                if ($diff_seconds[$winner['user_id']] > $diff_seconds[$user_id]):
                    $winners[$winner['user_id']] = FALSE;
                    $winners[$user_id] = $winner = array('user_id'=>$user_id,'title'=>$diff_title,'second'=>$diff_seconds[$user_id]);
                elseif($diff_seconds[$winner['user_id']] == $diff_seconds[$user_id]):
                    $winners[$user_id] = $winner = array('user_id'=>$user_id,'title'=>$diff_title,'second'=>$diff_seconds[$user_id]);
                endif;
            endif;
        endforeach;
        foreach($winners as $user_id => $winner):
            if ($winner):
                $this->game_winners['first_place'][] = $user_id;
            endif;
        endforeach;
    }

    private function getSecondPlace(){

        $diff_titles = $diff_seconds = array();
        foreach($this->game_answers['answers_titles'] as $user_id => $answer_title):
            if ($user_id != $this->game_winners['first_place'][0]):
                $diff_titles[$user_id] = abs((int)$answer_title - (int)$this->game_answers['current_answer']);
            endif;
        endforeach;
        foreach($this->game_answers['answers_times'] as $user_id => $answer_second ):
            if ($user_id != $this->game_winners['first_place'][0]):
                $diff_seconds[$user_id] = $answer_second;
            endif;
        endforeach;
        asort($diff_titles);
        $users = array_keys($diff_titles);
        $titles = array_values($diff_titles);
        if (@$titles[0] < @$titles[1]):
            $this->game_winners['second_place'][] = @$users[0];
        elseif(@$titles[0] == @$titles[1]):
            asort($diff_seconds);
            $users = array_keys($diff_seconds);
            $seconds = array_values($diff_seconds);
            if (@$seconds[0] < @$seconds[1]):
                $this->game_winners['second_place'][] = @$users[0];
            elseif(@$seconds[0] == @$seconds[1]):
                $this->game_winners['second_place'] = $users;
            endif;
        else:
            $this->game_winners['second_place'] = array('Вася','Петя'); # генерация ничьи
        endif;
    }

    private function getThirdPlace(){

        $winners = array($this->game_winners['first_place'][0],$this->game_winners['second_place'][0]);
        foreach($this->game_answers['answers_titles'] as $user_id => $answer_title):
            if (!in_array($user_id,$winners)):
                $this->game_winners['third_place'][] = $user_id;
                break;
            endif;
        endforeach;
    }

    private function isStandoff($place){

        if(count($this->game_winners[$place]) == 1):
            return FALSE;
        else:
            return TRUE;
        endif;
    }

    private function resetQuestions(){

        GameUserQuestions::where('game_id',$this->game->id)->where('status',1)->update(array('status'=>99));
    }

    private function getWinnerByPoints(){

        if ($users_points = GameUser::where('game_id', $this->game->id)->lists('points','user_id')):
            arsort($users_points);
            $users = array_keys($users_points);
            $points = array_values($users_points);
            if ($points[0] > $points[1]):
                return $users[0];
            endif;
        endif;
        return FALSE;
    }

    private function getDuel(){

        $json_settings = json_decode($this->game->json_settings,TRUE);
        if (isset($json_settings['duel'])):
            return $json_settings['duel'];
        else:
            return FALSE;
        endif;
    }

    private function getAvailableSteps($user_id, $place){

        $available_steps = 0;
        if ($this->validGameStage(1)):
            $available_steps = abs($place - 3);
        elseif ($this->validGameStage(2)):
            $duel = $this->getDuel();
            if ($place == 1 && $duel['conqu'] == $user_id):
                $available_steps = 1;
            elseif($place == 1 && $duel['def'] == $user_id):
                $lives = 1;
                if(Input::has('zone') && Input::get('zone') > 0):
                    $lives = GameMap::where('game_id',$this->game->id)->where('zone',Input::get('zone'))->pluck('lives');
                endif;
                $this->changeUserPoints($duel['def'],100*$lives);
                if(TRUE):
                    $users = GameUser::where('game_id', $this->game->id)->get();
                    $this->changeGameUsersStatus(2, $users);
                endif;
            endif;
        endif;
        return $available_steps;
    }

    private function getTerritoryPoints($zone){

        if ($this->validGameStatus($this->game_statuses[2])):
            return GameMap::where('game_id', $this->game->id)->where('zone', $zone)->where('capital', 0)->pluck('points');
        endif;
    }

    private function removeUserInGame($userID){

        if($user = GameUser::where('game_id',$this->game->id)->where('user_id',$userID)->first()):
            $user->status = 99;
            $user->available_steps = 0;
            $user->make_steps = 0;
            $user->save();
            $user->touch();
        endif;
        $json_settings = json_decode($this->game->json_settings, TRUE);
        foreach($json_settings['stage2_tours'] as $user_id => $status):
            if($user_id == $userID):
                $json_settings['stage2_tours'][$user_id] = TRUE;
            endif;
        endforeach;
        $this->game->json_settings = json_encode($json_settings);
        $this->game->save();
        $this->game->touch();
    }
}