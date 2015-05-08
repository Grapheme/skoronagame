<?php

class GameController extends BaseController {

    public static $name = 'game';
    public static $group = 'application';

    private $game;
    private $user;
    private $game_statuses = array('wait','start','ready','over');
    private $game_answers;
    private $game_winners = array('first_place' => array(), 'second_place' => array(), 'third_place' => array());
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
        });

        Route::group(array('before'=>'user.auth','prefix' => $class::$name), function() use ($class) {
            Route::post('profile/password-save', array('as'=>'profile-password-save','uses'=>$class.'@ProfilePasswordSave'));

            Route::post('get-game', array('as'=>'get-game','uses'=>$class.'@getGame'));
            Route::post('question/get-quiz', array('as'=>'get-quiz-question','uses'=>$class.'@getQuizQuestion'));
            Route::post('question/send-answer', array('as'=>'send-answer-question','uses'=>$class.'@sendAnswerQuestion'));
            Route::post('question/get-result', array('as'=>'get-result-question','uses'=>$class.'@getResultQuestion'));
            Route::post('conquest/territory', array('as'=>'send-conquest-territory','uses'=>$class.'@sendConquestTerritory'));
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

        if(!Request::ajax()) return App::abort(404);
        $validator = Validator::make(Input::all(),array('email'=>'required|email','password'=>'required'));
        if($validator->passes()):
            if(Auth::attempt(array('email'=>Input::get('email'),'password'=>Input::get('password'),'active'=>1), FALSE)):
                if(Auth::check()):
                    $this->json_request['redirect'] = AuthAccount::getStartPage();
                    $this->json_request['status'] = TRUE;
                endif;
            else:
                $this->json_request['responseText'] = 'Неверное имя пользователя или пароль';
            endif;
        endif;
        return Response::json($this->json_request,200);
    }

    public function QuickRegister(){

        if(!Request::ajax()) return App::abort(404);
        $validator = Validator::make(Input::all(),array('email'=>'required|email'));
        if($validator->passes()):
            if(User::where('email',Input::get('email'))->exists() === FALSE):
                $user = new User;
                $user->group_id = Group::where('name','game')->pluck('id');
                $user->name = 'Игрок';
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

    #$this->game = Game::find(1);
    #$this->randomStep();
    #exit;

    public function indexGame(){

        if(!self::initGame()):
            if ($game_id = GameUser::where('user_id',Auth::user()->id)->where('status',1)->pluck('game_id')):
                $this->game = Game::where('id',$game_id)->with('users')->first();
            endif;
        endif;
        return View::make(Helper::acclayout('index'),array('game'=>$this->game));
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

    public function getQuizQuestion(){

        if (!Request::ajax()) return App::abort(404);
        if ($this->initGame()):
            $this->changeGameStatus($this->game_statuses[2]);
            $this->changeGameStage(1);
            $this->nextStep(0);
            if (!GameUserQuestions::where('game_id', $this->game->id)->where('status', 0)->exists()):
                $randomQuestion = $this->randomQuestion('quiz');
                $this->createQuestion($randomQuestion->id);
            endif;
            $question = GameUserQuestions::where('game_id', $this->game->id)->where('user_id', Auth::user()->id)->where('status', 0)->with('question')->first();
            $this->createQuestionJSONResponse($question);
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
                    if (!empty($userGameQuestion->answer)):
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
        $validation = Validator::make(Input::all(), array('question' => 'required'));
        if ($validation->passes()):
            if ($this->initGame()):
                if (GameUserQuestions::where('game_id', $this->game->id)->where('status', 1)->count() == Config::get('game.number_participants')):
                    $current_answer = FALSE;
                    foreach (GameUserQuestions::where('game_id', $this->game->id)->where('status', 1)->with('question')->get() as $userGameQuestion):
                        $this->game_answers['current_answer'] = $this->getCurrentAnswer($userGameQuestion);
                        $this->game_answers['answers_titles'][$userGameQuestion->user_id] = $userGameQuestion->answer;
                        $this->game_answers['answers_times'][$userGameQuestion->user_id] = $userGameQuestion->seconds;
                    endforeach;
                    $this->setQuestionWinner();
                    if ($this->game_winners === 'standoff'):
                        $this->resetQuestions();
                    elseif (count($this->game_winners) == Config::get('game.number_participants')):
                        foreach ($this->game_winners as $user_id => $place):
                            GameUserQuestions::where('game_id', $this->game->id)->where('user_id', $user_id)->where('status', 1)->where('place', 0)->first()
                                ->update(array('status' => 2, 'place' => $place, 'updated_at' => date('Y-m-d H:i:s')));
                            GameUser::where('game_id', $this->game->id)->where('user_id', $user_id)
                                ->update(array('status'=>0,'available_steps'=>abs($place-3),'make_steps'=>0, 'updated_at' => date('Y-m-d H:i:s')));
                        endforeach;
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

    public function sendConquestTerritory(){

        $validation = Validator::make(Input::all(), array('zone'=>'required'));
        if($validation->passes()):
            if ($this->initGame()):
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
                    endif;
                    $this->json_request['responseText'] = 'Вы заняли территорию.';
                    $this->json_request['status'] = TRUE;
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

        $this->game->users[] = GameUser::create(array('game_id' => $this->game->id, 'user_id' => Auth::user()->id, 'status' => 0, 'points' => 0,
            'json_settings' => json_encode(array())));
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

    private function overGame(){

        $this->game->status_over = 1;
        $this->game->date_over = Carbon::now()->format('Y-m-d H:i:s');
        $this->game->save();
        $this->game->touch();
        $this->json_request['game_status'] = $this->game->status[3];
        GameUser::where('game_id',$this->game->id)->update(array('status'=>0));
    }

    private function setWinners(){

        $winners = GameUser::where('game_id',$this->game->id)->orderBy('points','DESC')->lists('id');
        if (count($winners) > 1):
            # несколько победителей
        else:
            #один победитель
        endif;
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
                    'game_id' => $this->game->id, 'user_id' => 0,'zone'=>$i+1,'capital' => '0',
                    'lives' => Config::get('game.map_empty_place_lives'), 'status' => '',
                    'json_settings' => json_encode(array('color'=>'')),
                ));
            endfor;
            if (count($map_places)):
                $this->game->map_places()->saveMany($map_places);
            endif;
        endif;
    }

    private function createQuestion($question_id){

        if ($this->validGameStatus($this->game_statuses[2])):
            $group_id = uniqid($this->game->id.'_');
            foreach(GameUser::where('game_id', $this->game->id)->with('user')->lists('id','user_id') as $user_id => $user):
                GameUserQuestions::create(array('group_id'=>$group_id,'game_id' => $this->game->id, 'user_id' => $user_id,
                    'question_id' => $question_id, 'status' => 0, 'place' => 0, 'answer' => '', 'seconds' => 0));
            endforeach;
        endif;
    }

    private function createGameJSONResponse(){

        if ($this->game):
            $users = $map = array();
            foreach (GameUser::where('game_id', $this->game->id)->with('user')->get() as $user_game):
                $users[] = array('id' => $user_game->user->id, 'name' => $user_game->user->name,
                    'email' => $user_game->user->email, 'photo' => $user_game->user->photo,
                    'color' => $user_game->color, 'points' => $user_game->points, 'place' => $user_game->place,
                    'status' => $user_game->status, 'stage' => $user_game->stage,
                    'available_steps' => $user_game->available_steps,'make_steps' => $user_game->make_steps,
                    'settings' => json_decode($user_game->json_settings, TRUE));
            endforeach;
            foreach (GameMap::where('game_id', $this->game->id)->orderBy('id')->get() as $map_place):
                $map[] = array('id' => $map_place->id, 'zone' => $map_place->zone, 'user_id' => $map_place->user_id,
                    'capital' => $map_place->capital, 'lives' => $map_place->lives,'settings'=>json_decode($map_place->json_settings,TRUE));
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
                'question' => array('id' => $user_question->id, 'text' => $user_question->question->question, 'type' => $user_question->question->type));
            $this->json_request['status'] = TRUE;
        endif;
    }

    private function createQuestionResultJSONResponse(){

        if ($this->validGameStatus($this->game_statuses[2])):
            $this->json_request['responseJSON'] = array('game_id' => $this->game->id,'game_status' => $this->game->status, 'game_stage' => $this->game->stage,
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
                    $map_places[$capital]->capital = 1;
                    $map_places[$capital]->lives = Config::get('game.map_capital_place_lives');
                    $settings = json_decode($map_places[$capital]->json_settings);
                    $settings->color = $this->randomColor($user_index);
                    $user->color = $settings->color;
                    $user->save();
                    $map_places[$capital]->json_settings = json_encode($settings);
                    $map_places[$capital]->save();
                    $this->changeUserPoints(5,1000,$user);
                endforeach;
            endif;
        endif;
    }

    private function randomStep(){

        if ($this->validGameStatus($this->game_statuses[1])):
            if ($users = GameUser::where('game_id', $this->game->id)->with('user')->lists('id','user_id')):
                $user_id = array_rand($users);
                #$this->nextStep($user_id);
                $this->nextStep(3);
            endif;
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

        if ($this->validGameStatus($this->game_statuses[2]) && $this->validGameStage(1)):
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

        if ($this->validGameStatus($this->game_statuses[2]) && $this->validGameStage(1)):
            if($conquest =  GameMap::where('game_id',$this->game->id)->where('user_id',0)->where('zone',$zone)->where('capital',0)->first()):
                $settings = json_decode($conquest->json_settings);
                $settings->color = $this->user['color'];
                $conquest->json_settings = json_encode($settings);
                $conquest->user_id = Auth::user()->id;
                $conquest->save();
            endif;
        endif;
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
            foreach($answers as $answer):
                if ($answer->current == 1):
                    $currentAnswer = $answer->title;
                    break;
                endif;
            endforeach;
        endif;
        return $currentAnswer;
    }

    private function setQuestionWinner(){

        if ($this->game_answers['current_answer']):
            $this->getFirstPlace();
            if ($this->isStandoff('first_place')):
                $this->game_winners = 'standoff';
                return FALSE;
            endif;
            $this->getSecondPlace();
            if ($this->isStandoff('second_place')):
                $this->game_winners = 'standoff';
                return FALSE;
            endif;
            $this->getThirdPlace();
            $winner_places = array();
            $places = array('first_place' => 1, 'second_place' => 2, 'third_place' => 3);
            foreach ($this->game_winners as $place => $user_id):
                $winner_places[@$user_id[0]] = $places[$place];
            endforeach;
            $this->nextStep(@$this->game_winners['first_place'][0]);
            $this->game_winners = $winner_places;
        endif;
    }

    private function nextStep($user_id = ''){

        $json_settings = json_decode($this->game->json_settings,TRUE);
        $json_settings['next_step'] = $user_id;
        $this->game->json_settings = json_encode($json_settings);
        $this->game->save();
        $this->game->touch();
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
}