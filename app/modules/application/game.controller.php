<?php

class GameController extends BaseController {

    public static $name = 'game';
    public static $group = 'application';

    private $game;
    private $user;
    private $game_statuses = array('wait', 'start', 'ready', 'over');
    private $game_answers;
    private $game_winners = array();
    private $json_request = array('status' => FALSE, 'responseJSON' => '', 'responseText' => '', 'redirect' => FALSE);

    /****************************************************************************/
    public function __construct() {

    }

    public function initGame() {

        if (!is_null($this->game) && is_object($this->game)):
            return TRUE;
        elseif (Input::has('game') && Input::get('game') > 0):
            $this->game = Game::where('id', Input::get('game'))->with('users', 'users.user_social', 'users.session', 'map_places')->first();
            $this->user = GameUser::where('game_id', Input::get('game'))->where('user_id', Auth::user()->id)->first();
            return ($this->user) ? TRUE : FALSE;
        else:
            $this->game = 0;
            return FALSE;
        endif;
    }

    public function reInitGame() {

        if (isset($this->game->id)):
            $this->game = Game::where('id', $this->game->id)->with('users', 'users.user_social', 'users.session', 'map_places')->first();
            $this->user = GameUser::where('game_id', $this->game->id)->where('user_id', Auth::user()->id)->first();
        endif;
    }

    /****************************************************************************/
    public static function returnRoutes() {

        $class = __CLASS__;
        Route::controller('password', 'RemindersController');
        Route::group(array('before' => 'login', 'prefix' => ''), function () use ($class) {
            Route::post('login/user', array('before' => 'csrf', 'as' => 'quick-auth', 'uses' => $class . '@QuickAuth'));
            Route::post('register/user', array('before' => 'csrf', 'as' => 'quick-register',
                'uses' => $class . '@QuickRegister'));
        });
        Route::group(array('before' => 'user.auth.session', 'prefix' => $class::$name), function () use ($class) {
            Route::get('', array('as' => 'game', 'uses' => $class . '@indexGame'));
            Route::get('demo', array('as' => 'game-demo', 'uses' => $class . '@demoGame'));
        });
        Route::group(array('before' => 'user.auth', 'prefix' => $class::$name), function () use ($class) {
            Route::post('get-game', array('as' => 'get-game', 'uses' => $class . '@getGame'));
            Route::post('add-bots', array('as' => 'add-bots', 'uses' => $class . '@addBots'));
            Route::post('over-game', array('as' => 'over-game', 'uses' => $class . '@overGame'));
            Route::post('get-adjacent-zones', array('as' => 'get-adjacent-zones',
                'uses' => $class . '@getAdjacentZones'));
            Route::post('question/get-quiz', array('as' => 'get-quiz-question', 'uses' => $class . '@getQuizQuestion'));
            Route::post('question/get-normal', array('as' => 'get-normal-question',
                'uses' => $class . '@getNormalQuestion'));
            Route::post('question/get-result', array('as' => 'get-result-question',
                'uses' => $class . '@getResultQuestion'));
            Route::post('question/get-users-results', array('as' => 'get-users-results-question',
                'uses' => $class . '@getUsersResultsQuestion'));
            Route::any('disconnect_user', array('as' => 'disconnect_user_url',
                'uses' => $class . '@sendDisconnectUser'));
        });
        Route::group(array('before' => 'user.auth.session', 'prefix' => $class::$name), function () use ($class) {
            Route::post('profile/password-save', array('as' => 'profile-password-save',
                'uses' => $class . '@ProfilePasswordSave'));

            Route::post('question/send-answer', array('as' => 'send-answer-question',
                'uses' => $class . '@sendAnswerQuestion'));

            Route::post('conquest/territory', array('as' => 'send-conquest-territory',
                'uses' => $class . '@sendConquestTerritory'));
            Route::post('conquest/capital', array('as' => 'send-conquest-capital',
                'uses' => $class . '@sendConquestCapital'));
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
            'link' => self::$name . '/questions/quiz',
            'class' => 'fa-circle',
        );
        $menu_child[] = array(
            'title' => 'Обычные вопросы',
            'link' => self::$name . '/questions/normal',
            'class' => 'fa-circle-o',
        );
        $menu_child[] = array(
            'title' => 'Бейджи',
            'link' => self::$name . '/' . BadgesController::$name,
            'class' => 'fa-trophy',
        );
        $menu_child[] = array(
            'title' => 'Игроки',
            'link' => self::$name . '/' . GamerController::$name,
            'class' => 'fa-users',
        );
        $menu_child[] = array(
            'title' => 'Статистика',
            'link' => self::$name . '/statistic',
            'class' => 'fa-bar-chart',
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
    public function QuickAuth() {

        if (!Request::ajax()) return App::abort(404);
        $validator = Validator::make(Input::all(), array('email' => 'required|email', 'password' => 'required'));
        if ($validator->passes()):
//              Auth::loginUsingId(6);
//              $this->json_request['redirect'] = AuthAccount::getStartPage();
//              $this->json_request['status'] = TRUE;
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

    public function QuickRegister() {

        if (!Request::ajax()) return App::abort(404);
        $validator = Validator::make(Input::all(), array('email' => 'required|email'));
        if ($validator->passes()):
            if (User::where('email', Input::get('email'))->exists() === FALSE):
                $user = new User;
                $user->group_id = Group::where('name', 'game')->pluck('id');
                $user->name = Input::get('name');
                $user->email = Input::get('email');
                $user->active = 1;
                $password = Str::random(12);
                $user->password = Hash::make($password);
                $user->save();
                Auth::login($user);
                Mail::send('emails.auth.signup', array('user' => $user, 'password' => $password), function ($message) {
                    $message->from(Config::get('mail.from.address'), Config::get('mail.from.name'));
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
        return Response::json($this->json_request, 200);
    }

    /****************************************************************************/
    public function ProfilePasswordSave() {

        if (!Request::ajax()) return App::abort(404);
        $json_request = array('status' => FALSE, 'responseText' => '', 'redirect' => FALSE);
        $user = Auth::user();
        $user->password = Hash::make(Input::get('password'));
        $user->save();
        $json_request['responseText'] = 'Пароль изменен.';
        $json_request['status'] = TRUE;
        return Response::json($json_request, 200);
    }
    /****************************************************************************/
    /********************************* GAME *************************************/
    /****************************************************************************/
    public function indexGame() {

        $games = Game::where('status', '!=', $this->game_statuses[3])->with(array('users' => function ($query) {
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
        $fistDayMonth = Carbon::now()->startOfMonth();
        $month_rating = $this->getRating($fistDayMonth);
        $total_rating = $this->getRating();
        return View::make(Helper::acclayout('index'), array('game' => $this->game, 'month_rating' => $month_rating,
            'total_rating' => $total_rating));
    }

    public function demoGame() {

        if (File::exists(storage_path('logs/laravel.log'))):
            File::delete(storage_path('logs/laravel.log'));
        endif;

        $games = Game::where('status', '!=', $this->game_statuses[3])->with(array('users' => function ($query) {
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

        return View::make(Helper::acclayout('demo'), array('game' => $this->game));
    }

    /********************************* JSON *************************************/
    public function getGame() {

        if (!Request::ajax()) return App::abort(404);

        if (!$this->initGame()):
            if (!$this->hasCreatedGame()):
                $this->createNewGame();
            else:
                $this->joinNewGame();
                $this->startGame();
            endif;
        endif;

        $this->isBotNextStepStage2();
        $this->finishGameInFourTour();
        $this->droppingGameUsers();

        $this->createGameJSONResponse();
        return Response::json($this->json_request, 200);
    }

    public function overGame() {

        if (!Request::ajax()) return App::abort(404);
        if ($this->initGame()):
            if ($this->validGameStatus($this->game_statuses[3])):
                $this->setGameWinners();
                $this->json_request['status'] = TRUE;
            else:
                if (!empty($this->game->users)):
                    foreach ($this->game->users as $user):
                        if ($user->user_id == Auth::user()->id):
                            $this->finishGame(0);

                            Log::info('finishGame (0)', array('method' => 'isBotNextStepStage2',
                                'message' => 'Игра завершилась. Пользователь завершил игру.',
                                'current_user' => Auth::user()->id));

                            $this->reInitGame();
                            $this->json_request['responseText'] = Auth::user()->name . ' завершил игру.';
                            $this->json_request['status'] = TRUE;
                            break;
                        endif;
                    endforeach;
                endif;
            endif;
        endif;
        return Response::json($this->json_request, 200);
    }

    public function getQuizQuestion() {

        if (!Request::ajax()) return App::abort(404);
        $validation = Validator::make(Input::all(), array('users' => ''));
        if ($validation->passes()):
            if ($this->initGame()):
                $this->nextStep();

                Log::info('nextStep', array('method' => 'getQuizQuestion', 'message' => 'nextStep',
                    'nextStep' => $this->getNextStep(), 'current_user' => Auth::user()->id));

                if (!GameUserQuestions::where('game_id', $this->game->id)->where('status', 0)->exists()):
                    $this->changeGameStatus($this->game_statuses[2]);
                    $this->resetGameUsers();

                    Log::info('resetGameUsers', array('method' => 'getQuizQuestion',
                        'message' => 'Сброс параметров игроков',
                        'current_user' => Auth::user()->id, 'stage' => $this->game->stage));

                    $randomQuestion = $this->randomQuestion('quiz');
                    $this->createQuestion($randomQuestion->id, Input::get('users'));

                    Log::info('createQuestion', array('method' => 'getQuizQuestion',
                        'message' => 'Пользователь создал quiz вопрос', 'question' => $randomQuestion->id,
                        ' users' => Input::get('users'), 'current_user' => Auth::user()->id));

                endif;
                $question = GameUserQuestions::where('game_id', $this->game->id)->where('user_id', Auth::user()->id)->where('status', 0)->with('question')->first();
                $this->createQuestionJSONResponse($question);
            endif;
        endif;
        return Response::json($this->json_request, 200);
    }

    public function getNormalQuestion() {

        if (!Request::ajax()) return App::abort(404);
        $validation = Validator::make(Input::all(), array('users' => 'required', 'zone' => ''));
        if ($validation->passes()):
            if ($this->initGame()):
                if ($this->validGameStage(2)):

                    $createStepInSecondStage = TRUE;
                    $duel = $this->getDuel();
                    if (isset($duel['conqu']) && isset($duel['def'])):
                        $createStepInSecondStage = FALSE;
                    endif;

                    if (Input::get('users.conqu') == Auth::user()->id || Input::get('users.def') == Auth::user()->id):
                        $next_step = $this->getNextStep();
                        if ($next_step == Auth::user()->id && !GameUserQuestions::where('game_id', $this->game->id)->where('status', 0)->exists()):
                            $this->closeGameUsersQuestions();
                            $this->resetGameUsers();

                            Log::info('resetGameUsers', array('method' => 'getNormalQuestion',
                                'message' => 'Сброс параметров игроков',
                                'current_user' => Auth::user()->id, 'stage' => $this->game->stage));

                            if ($createStepInSecondStage === TRUE):
                                $this->createStepInSecondStage();

                                Log::info('createStepInSecondStage', array('method' => 'getNormalQuestion',
                                    'message' => 'Дуели не существует. Пользователь совершил ход',
                                    'next_step' => $this->getNextStep(),
                                    'current_user' => Auth::user()->id,
                                    'stage' => $this->game->stage));
                            else:

                                Log::info('createStepInSecondStage', array('method' => 'getNormalQuestion',
                                    'message' => 'Дуель существует. Пользователь не совершает ход',
                                    'next_step' => $this->getNextStep(),
                                    'duel' => $duel,
                                    'current_user' => Auth::user()->id,
                                    'stage' => $this->game->stage));

                            endif;

                            $this->setStepInSecondStageJSON();
                            $randomQuestion = $this->randomQuestion('normal');
                            $this->createQuestion($randomQuestion->id, Input::get('users'));

                            Log::info('createQuestion', array('method' => 'getNormalQuestion',
                                'message' => 'Пользователь создал normal вопрос', 'question' => $randomQuestion->id,
                                ' users' => Input::get('users'), 'current_user' => Auth::user()->id));

                            #$this->createDuel(Input::get('users'), Input::get('zone'));
                            $this->createDuel(Input::get('users'));

                            Log::info('createQuestion', array('method' => 'getNormalQuestion',
                                'message' => 'Создана дуель', 'duel' => $this->getDuel(),
                                'users' => Input::get('users'), 'current_user' => Auth::user()->id));

                            $this->nextStep();
                            Log::info('nextStep', array('method' => 'getNormalQuestion',
                                'message' => 'Обнуляем ход. Получаем нормальный вопрос',
                                'nextStep' => $this->getNextStep(), 'current_user' => Auth::user()->id));
                        endif;
                        $question = GameUserQuestions::where('game_id', $this->game->id)->where('user_id', Auth::user()->id)->where('status', 0)->with('question')->first();
                        $this->createQuestionJSONResponse($question);
                    endif;
                endif;
            endif;
        endif;
        return Response::json($this->json_request, 200);
    }

    public function sendAnswerQuestion() {

        $validation = Validator::make(Input::all(), array('question' => 'required', 'answer' => '',
            'time' => 'required'));
        if ($validation->passes()):
            if ($this->initGame()):
                if ($userGameQuestion = GameUserQuestions::where('game_id', $this->game->id)->where('id', Input::get('question'))->where('user_id', Auth::user()->id)->where('status', 0)->with('question')->first()):
                    $userGameQuestion->status = 1;
                    $userGameQuestion->answer = (int)Input::get('answer');
                    $userGameQuestion->seconds = (int)Input::get('time');
                    $userGameQuestion->save();
                    $userGameQuestion->touch();

                    Log::info('answer', array('method' => 'sendAnswerQuestion',
                        'message' => 'Пользователь дал ответ', 'question' => Input::get('question'),
                        'answer' => $userGameQuestion->answer,
                        'current_user' => Auth::user()->id));

                    $this->sendBotsAnswers($userGameQuestion);
                    $this->sendDroppedUserAnswers($userGameQuestion);
                    if ($userGameQuestion->answer != 99999):
                        $this->json_request['responseText'] = "GOOD";
                    else:
                        $this->json_request['responseText'] = 'BAD';
                    endif;
                    $this->json_request['status'] = TRUE;
                endif;
            endif;
        endif;
        return Response::json($this->json_request, 200);
    }

    public function getResultQuestion() {

        Log::info('BEGIN', array('method' => 'getResultQuestion',
            'message' => 'Начало скрипта определения результатов ответов', 'current_user' => Auth::user()->id,
            'input' => Input::all()));

        if (!Request::ajax()) return App::abort(404);
        $validation = Validator::make(Input::all(), array('question' => 'required', 'type' => 'required',
            'zone' => 'required|numeric'));
        if ($validation->passes()):
            if ($this->initGame()):
                $post = Input::all();
                $number_participants = Config::get('game.number_participants');
                $hasWinnersCalculate = TRUE;
                if ($this->validGameStage(2)):
                    $number_participants = 2;
                    if ($duel = $this->getDuel()):
                        if ($duel['conqu'] == Auth::user()->id):
                            $hasWinnersCalculate = TRUE;

                            Log::info('hasWinnersCalculate', array('method' => 'getResultQuestion',
                                'message' => 'Запрос от нападающего. Расчет победителей доступен',
                                'hasWinnersCalculate' => (int)$hasWinnersCalculate,
                                'current_user' => Auth::user()->id));

                        elseif ($duel['def'] == Auth::user()->id):
                            $hasWinnersCalculate = FALSE;

                            Log::info('hasWinnersCalculate', array('method' => 'getResultQuestion',
                                'message' => 'Запрос от защищающегося. Расчет победителей недоступен',
                                'hasWinnersCalculate' => (int)$hasWinnersCalculate,
                                'current_user' => Auth::user()->id));

                        endif;
                        if ($hasWinnersCalculate === FALSE && ($this->isBot($duel['conqu']) || $this->isBot($duel['def']))):
                            $hasWinnersCalculate = TRUE;

                            Log::info('hasWinnersCalculate', array('method' => 'getResultQuestion',
                                'message' => 'Запрос от защищающегося. В дуели есть боты. Расчет победителей доступен',
                                'hasWinnersCalculate' => (int)$hasWinnersCalculate,
                                'current_user' => Auth::user()->id));

                        endif;
                    endif;
                endif;

                if ($hasWinnersCalculate && GameUserQuestions::where('game_id', $this->game->id)->where('status', 1)->count() == $number_participants):

                    Log::info('number_participants', array('method' => 'getResultQuestion',
                        'message' => 'Все игроки дали ответы', 'number_participants' => $number_participants,
                        'current_user' => Auth::user()->id));

                    $current_answer = FALSE;
                    foreach (GameUserQuestions::where('game_id', $this->game->id)->where('status', 1)->with('question')->get() as $userGameQuestion):
                        $this->game_answers['current_answer'] = $this->getCurrentAnswer($userGameQuestion);
                        $this->game_answers['answers_titles'][$userGameQuestion->user_id] = $userGameQuestion->answer;
                        $this->game_answers['answers_times'][$userGameQuestion->user_id] = $userGameQuestion->seconds;
                    endforeach;

                    if ($this->game_answers['current_answer'] !== FALSE && !empty($this->game_answers['answers_titles']) && !empty($this->game_answers['answers_times'])):
                        if ($post['type'] == 'quiz'):
                            $this->setQuizQuestionWinner();
                            Log::info('setQuizQuestionWinner', array('method' => 'getResultQuestion',
                                'message' => 'Определились победители на квиз-вопрос.',
                                'winners' => $this->game_winners, 'current_user' => Auth::user()->id));

                        elseif ($post['type'] == 'normal'):
                            $this->setNormalQuestionWinner();

                            Log::info('setNormalQuestionWinner', array('method' => 'getResultQuestion',
                                'message' => 'Определились победители на нормальный вопрос.',
                                'winners' => $this->game_winners, 'current_user' => Auth::user()->id));

                        endif;
                        if ($this->game_winners === 'standoff'):
                            $this->resetQuestions();

                            Log::info('resetQuestions', array('method' => 'getResultQuestion',
                                'message' => 'Случилась ничья.',
                                'winners' => $this->game_winners, 'current_user' => Auth::user()->id));

                        elseif (!empty($this->game_winners) && is_array($this->game_winners) && count($this->game_winners) == $number_participants):
                            foreach ($this->game_winners as $user_id => $place):
                                $this->setGameUserQuestionPlace($user_id, $place);
                                $available_steps = $this->getAvailableSteps($user_id, $place);
                                if ($available_steps < 0):
                                    $this->gamerDefenceTerritory($post['zone']);
                                    $available_steps = 0;

                                    Log::info('gamerDefenceTerritory', array('method' => 'getResultQuestion',
                                        'message' => '2 этап. Дуель. Защищающийся победил',
                                        'user_id' => $user_id, 'steps' => $available_steps,
                                        'current_user' => Auth::user()->id));

                                endif;
                                $this->setGameUserAvailableSteps($user_id, $available_steps);

                                Log::info('setGameUserAvailableSteps', array('method' => 'getResultQuestion',
                                    'message' => 'Установили доступные шаги для пользователя',
                                    'user_id' => $user_id, 'steps' => $available_steps,
                                    'current_user' => Auth::user()->id));

                            endforeach;

                            if ($this->validGameStage(1)):
                                if ($this->validGameBots()):

                                    Log::info('isBotNextStepStage1', array('method' => 'getResultQuestion',
                                        'message' => 'Запуск скрипта шагов ботов на 1-м этапе.',
                                        'current_user' => Auth::user()->id, 'stage' => $this->game->stage));

                                    $this->isBotNextStepStage1();
                                endif;
                            elseif ($this->validGameStage(2)):

                                Log::info('ДАННЫЕ ОТ СЕРВЕРА на 2м этапе. середина', array('method' => 'getResultQuestion',
                                    'message' => 'INPUT', 'zone' => $post['zone'],
                                    'current_user' => Auth::user()->id, 'input' => Input::all()));

                                Log::info('ЗАХВАТ ТЕРРИТОРИИ', array('method' => 'getResultQuestion',
                                    'message' => 'ПОЛЬЗОВАТЕЛЬ ПЫТАЕТСЯ ЗАХВАТИТЬ ТЕРРИИТОРИЮ',
                                    'zone' => $post['zone'], 'current_user' => Auth::user()->id));

                                if ($this->validCapitalZone($post['zone'])):

                                    Log::info('validCapitalZone', array('method' => 'getResultQuestion',
                                        'message' => 'Пользователь напал на столицу', 'zone' => $post['zone'],
                                        'current_user' => Auth::user()->id));

                                    if ($duel = $this->getDuel()):

                                        Log::info('getDuel', array('method' => 'getResultQuestion',
                                            'message' => 'Получаем текущую дуель', 'duel' => $duel,
                                            'current_user' => Auth::user()->id));

                                        if (GameUser::where('game_id', $this->game->id)->where('user_id', $duel['conqu'])->where('available_steps', 1)->exists()):

                                            Log::info('available_steps 1', array('method' => 'getResultQuestion',
                                                'message' => 'Побелитель в дуели. Нападающий',
                                                'conqu' => $duel['conqu'], 'current_user' => Auth::user()->id));

                                            $this->nextStep($duel['conqu']);

                                            Log::info('nextStep', array('method' => 'getResultQuestion',
                                                'message' => 'Ходит', 'next_step' => $this->getNextStep(),
                                                'current_user' => Auth::user()->id));

                                        else:
                                            Log::info('available_steps 0', array('method' => 'getResultQuestion',
                                                'message' => 'Побелитель в дуели. Защищающийся', 'def' => $duel['def'],
                                                'current_user' => Auth::user()->id));
                                            $this->closeGameUsersQuestions();
                                            $this->resetGameUsers();

                                            Log::info('resetGameUsers', array('method' => 'getResultQuestion',
                                                'message' => 'Сброс параметров игроков',
                                                'current_user' => Auth::user()->id, 'stage' => $this->game->stage));

                                            $this->nextStepInSecondStage();
                                            Log::info('nextStepInSecondStage', array('method' => 'getResultQuestion',
                                                'message' => 'Защищающийся победил. Следующий ходит.',
                                                'next_step' => $this->getNextStep(),
                                                'current_user' => Auth::user()->id,
                                                'stage' => $this->game->stage));
                                        endif;
                                    endif;
                                else:

                                    Log::info('validCapitalZone', array('method' => 'getResultQuestion',
                                        'message' => 'Пользователь напал на обычную территорию',
                                        'zone' => $post['zone'], 'current_user' => Auth::user()->id));

                                    if ($duel = $this->getDuel()):
                                        if (GameUser::where('game_id', $this->game->id)->where('user_id', $duel['conqu'])->where('available_steps', 1)->exists()):

                                            Log::info('conqu.available_steps = 1', array('method' => 'getResultQuestion',
                                                'message' => 'Нападающий выиграл сражение.',
                                                'current_user' => Auth::user()->id,
                                                'stage' => $this->game->stage));
                                        endif;
                                    endif;

                                    $this->createDuel();
                                    $this->nextStepInSecondStage();

                                    Log::info('nextStepInSecondStage', array('method' => 'getResultQuestion',
                                        'message' => 'Переход хода следующему игроку. независимо от результата дуели',
                                        'next_step' => $this->getNextStep(),
                                        'current_user' => Auth::user()->id,
                                        'duel' => $this->getDuel(),
                                        'stage' => $this->game->stage));

                                endif;
                            endif;
                        else:
                            $this->game_winners = 'standoff';
                            $this->resetQuestions();

                            Log::info('resetQuestions', array('method' => 'getResultQuestion',
                                'message' => 'Случилась ничья.',
                                'winners' => $this->game_winners, 'current_user' => Auth::user()->id));

                        endif;
                    else:
                        $this->game_winners = 'retry';
                    endif;
                elseif ($userQuestion = GameUserQuestions::where('id', $post['question'])->where('game_id', $this->game->id)->where('status', 2)->first()):
                    $this->game_winners = GameUserQuestions::where('game_id', $this->game->id)->where('status', 2)->where('group_id', $userQuestion->group_id)->lists('place', 'user_id');
                elseif (GameUserQuestions::where('id', $post['question'])->where('game_id', $this->game->id)->where('status', 99)->exists()):
                    $this->game_winners = 'standoff';
                else:
                    $this->game_winners = 'retry';
                endif;
                $this->createQuestionResultJSONResponse();
                if (is_array($this->game_winners)):
                    asort($this->game_winners);
                    $winners = array_keys($this->game_winners);
                    $this->json_request['responseText'] = 'First place: ' . @$winners[0] . '. Second place: ' . @$winners[1] . '. Third place: ' . @$winners[2];

                    Log::info('game_winners', array('method' => 'getResultQuestion',
                        'message' => 'Опеределились победители.',
                        'places' => $this->json_request['responseText'], 'current_user' => Auth::user()->id,
                        'next_step' => $this->getNextStep(), 'stage' => $this->game->stage));

                endif;
            endif;
        endif;
        return Response::json($this->json_request, 200);
    }

    public function getUsersResultsQuestion() {

        if (!Request::ajax()) return App::abort(404);
        $validation = Validator::make(Input::all(), array('question' => 'required', 'type' => 'required'));
        if ($validation->passes()):
            if ($this->initGame()):
                $post = Input::all();
                $question_group = GameUserQuestions::where('game_id', $this->game->id)->where('id', $post['question'])->pluck('group_id');
                $question_id = GameUserQuestions::where('game_id', $this->game->id)->where('id', $post['question'])->pluck('question_id');
                if ($users_questions = GameUserQuestions::where('game_id', $this->game->id)->where('group_id', $question_group)->get()):
                    $current_answer = '';
                    $users_answers = array();
                    if ($answer_question = GameQuestions::where('id', $question_id)->where('type', $post['type'])->pluck('answers')):
                        $answer_question = json_decode($answer_question, TRUE);
                        if ($post['type'] == 'quiz'):
                            $current_answer = isset($answer_question[0]['title']) ? $answer_question[0]['title'] : '';
                            $current_answer_index = isset($answer_question[0]['title']) ? $answer_question[0]['title'] : 0;
                            foreach ($users_questions as $users_question):
                                $correctly = ($users_question->answer == $current_answer) ? TRUE : FALSE;
                                $users_answers[$users_question->user_id] = array('answer' => $users_question->answer,
                                    'current_answer_index' => $current_answer_index, 'correctly' => $correctly,
                                    'seconds' => $users_question->seconds, 'place' => $users_question->place,
                                    'status' => $users_question->status);
                            endforeach;
                        elseif ($post['type'] == 'normal'):
                            foreach ($answer_question as $index => $answer):
                                if ($answer['current'] == 1):
                                    $current_answer_index = $index;
                                endif;
                            endforeach;
                            foreach ($users_questions as $users_question):
                                $answer_title = isset($answer_question[$users_question->answer]['title']) ? $answer_question[$users_question->answer]['title'] : '';
                                $answer_current = isset($answer_question[$users_question->answer]['current']) ? $answer_question[$users_question->answer]['current'] : '';
                                $users_answers[$users_question->user_id] = array('answer' => $answer_title,
                                    'current_answer_index' => $current_answer_index,
                                    'correctly' => (int)$answer_current,
                                    'seconds' => $users_question->seconds, 'place' => $users_question->place,
                                    'status' => $users_question->status);
                            endforeach;
                        endif;
                    endif;
                    $this->json_request['responseJSON'] = array('game_id' => $this->game->id,
                        'current_answer' => $current_answer, 'results' => $users_answers);
                    $this->json_request['status'] = TRUE;
                endif;
            endif;
        endif;
        return Response::json($this->json_request, 200);
    }

    public function sendConquestTerritory() {

        $validation = Validator::make(Input::all(), array('zone' => 'required'));
        if ($validation->passes()):
            if ($this->initGame()):
                $zone_conquest = Input::get('zone');
                if ($this->validGameStage(1)):
                    if ($this->changeGameUsersSteps()):
                        if ($this->conquestTerritory($zone_conquest)):
                            $points = $this->getTerritoryPoints($zone_conquest);
                            $this->changeUserPoints(Auth::user()->id, $points, $this->user);
                            if ($this->user->status == 1):
                                if ($this->user->available_steps == 2):
                                    $user_id = GameUser::where('game_id', $this->game->id)->where('status', 0)->where('available_steps', 1)->pluck('user_id');
                                    $this->nextStep($user_id);

                                    Log::info('available_steps == 2', array('method' => 'sendConquestTerritory',
                                        'message' => 'Ход переходит игроку занявшему 2-е место',
                                        'nextStep' => $this->getNextStep(), 'current_user' => Auth::user()->id,
                                        'stage' => $this->game->stage));

                                    if ($this->isBot($user_id)):
                                        $this->botConquestTerritory($user_id);
                                        $this->nextStep();

                                        Log::info('botConquestTerritory', array('method' => 'sendConquestTerritory',
                                            'message' => 'Бот занявший 2-е место выбрал территории. Ход сбрасывается.',
                                            'nextStep' => $this->getNextStep(), 'current_user' => Auth::user()->id,
                                            'stage' => $this->game->stage));

                                    endif;
                                else:
                                    $this->nextStep();

                                    Log::info('available_steps != 2', array('method' => 'sendConquestTerritory',
                                        'message' => 'Ход сбрасывается. Походил игрок занявший 2-е место.',
                                        'nextStep' => $this->getNextStep(), 'current_user' => Auth::user()->id,
                                        'stage' => $this->game->stage));
                                endif;
                            endif;
                            if ($this->isConqueredTerritories()):

                                Log::info('isConqueredTerritories', array('method' => 'botConquestTerritory',
                                    'message' => 'Все территории захвачены',
                                    'current_user' => Auth::user()->id,
                                    'stage' => $this->game->stage));

                                $this->closeGameUsersQuestions();
                                $this->changeGameStage(2);

                                Log::info('changeGameStage', array('method' => 'botConquestTerritory',
                                    'message' => 'Игрок перевел игру на 2й этап',
                                    'current_user' => Auth::user()->id,
                                    'stage' => $this->game->stage));

                                $this->closeGameUsersQuestions();
                                $this->resetGameUsers();

                                Log::info('resetGameUsers', array('method' => 'getResultQuestion',
                                    'message' => 'Сброс параметров игроков',
                                    'current_user' => Auth::user()->id, 'stage' => $this->game->stage));

                                $nextStep = $this->createTemplateStepInSecondStage();
                                $this->nextStep($nextStep);

                                Log::info('createTemplateStepInSecondStage', array('method' => 'sendConquestTerritory',
                                    'message' => 'Пользователь создал шаблон шагов для 2-го этапа',
                                    'nextStep' => $this->getNextStep(), 'current_user' => Auth::user()->id,
                                    'stage' => $this->game->stage));

                                $this->setStepInSecondStageJSON();
                            endif;
                            $this->json_request['responseText'] = 'Вы заняли территорию.';
                            $this->json_request['status'] = TRUE;
                        endif;
                    else:

                        Log::info('changeGameUsersSteps. ERROR', array('method' => 'sendConquestTerritory',
                            'message' => 'Захват территории не удался. Нет доступных очков хода',
                            'zone_conquest' => $zone_conquest, 'current_user' => Auth::user()->id,
                            'stage' => $this->game->stage));

                    endif;
                elseif ($this->validGameStage(2)):
                    if ($this->changeGameUsersSteps()):
                        if ($this->conquestTerritory($zone_conquest)):
                            $points = $this->getTerritoryPoints($zone_conquest);
                            $this->changeUserPoints(Auth::user()->id, $points, $this->user);
                            $this->changeTerritoryPoints($zone_conquest, 200);
                            $this->changeGameUsersStatus(2);
                            $this->json_request['responseText'] = 'Вы заняли территорию.';
                            $this->json_request['status'] = TRUE;
                        endif;
                    else:

                        Log::info('changeGameUsersSteps. ERROR', array('method' => 'sendConquestTerritory',
                            'message' => 'Захват территории не удался. Нет доступных очков хода',
                            'zone_conquest' => $zone_conquest, 'current_user' => Auth::user()->id,
                            'stage' => $this->game->stage));
                    endif;
                endif;
            endif;
        endif;
        return Response::json($this->json_request, 200);
    }

    public function sendConquestCapital() {

        $validation = Validator::make(Input::all(), array('zone' => 'required'));
        if ($validation->passes()):
            if ($this->initGame()):
                if ($this->validGameStage(2)):
                    $zone_conqueror = Input::get('zone');
                    if ($this->changeGameUsersSteps()):

                        Log::info('changeGameUsersSteps', array('method' => 'sendConquestCapital',
                            'message' => 'У пользователя отобралось доступное очко хода',
                            'current_user' => Auth::user()->id));

                        Log::info('conquestCapital', array('method' => 'sendConquestCapital',
                            'message' => 'Запуск скрипта захвата столицы',
                            'current_user' => Auth::user()->id));

                        $capitalLives = $this->conquestCapital($zone_conqueror);
                        if ($capitalLives === 0):
                            $this->closeGameUsersQuestions();
                            $this->resetGameUsers();

                            Log::info('resetGameUsers. capitalLives === 0', array('method' => 'sendConquestCapital',
                                'message' => 'Сброс параметров игроков',
                                'current_user' => Auth::user()->id, 'stage' => $this->game->stage));

                            $this->createDuel();
                            $points = $this->getTerritoryPoints($zone_conqueror);
                            $this->changeUserPoints(Auth::user()->id, $points, $this->user);
                            $this->nextStepInSecondStage();

                            Log::info('nextStepInSecondStage', array('method' => 'sendConquestCapital',
                                'message' => 'Столица захвачена. Переход хода',
                                'nextStep' => $this->getNextStep(), 'current_user' => Auth::user()->id,
                                'duel' => $this->getDuel(),
                                'stage' => $this->game->stage));

                            $this->changeGameUsersStatus(2);

                            $this->json_request['conquest_result'] = 'success';
                            $this->json_request['responseText'] = 'Вы заняли столицу.';
                            $this->json_request['status'] = TRUE;

                            if ($this->isConqueredCapitals()):
                                $this->nextStep();

                                Log::info('isConqueredCapitals', array('method' => 'sendConquestCapital',
                                    'message' => 'nextStep',
                                    'nextStep' => $this->getNextStep(), 'current_user' => Auth::user()->id));

                                $this->finishGame(1);

                                Log::info('finishGame (1)', array('method' => 'sendConquestCapital',
                                    'message' => 'Игра завершилась. Осталась одна столица',
                                    'current_user' => Auth::user()->id));

                            endif;
                        elseif ($capitalLives > 0):
                            $this->closeGameUsersQuestions();
                            $this->resetGameUsers();

                            Log::info('resetGameUsers. capitalLives > 0', array('method' => 'sendConquestCapital',
                                'message' => 'Сброс параметров игроков',
                                'current_user' => Auth::user()->id, 'stage' => $this->game->stage));

                            Log::info('capitalLives > 0', array('method' => 'sendConquestCapital',
                                'message' => 'У столицы остались жизни',
                                'capitalLives' => $capitalLives,
                                'zone' => $zone_conqueror, 'current_user' => Auth::user()->id));

                            $this->nextStep($this->user->user_id);

                            Log::info('capitalLives > 0', array('method' => 'sendConquestCapital',
                                'message' => 'nextStep',
                                'nextStep' => $this->getNextStep(), 'current_user' => Auth::user()->id));

                            $this->json_request['conquest_result'] = 'retry';
                            $this->json_request['responseText'] = 'Продолжайте захват столицы';
                            $this->json_request['status'] = TRUE;
                        endif;
                    else:

                        Log::info('changeGameUsersSteps. ERROR', array('method' => 'sendConquestCapital',
                            'message' => 'Захват столицы пользователем не удался. Нет доступных очков хода',
                            'zone_conquest' => $zone_conqueror, 'current_user' => Auth::user()->id,
                            'stage' => $this->game->stage));
                    endif;
                endif;
            endif;
        endif;
        return Response::json($this->json_request, 200);
    }

    public function getAdjacentZones() {

        if ($this->initGame()):
            $adjacentZones = array();
            if ($adjacentZonesList = $this->getAdjacentPlaces()):
                if ($this->validGameStage(1)):
                    foreach ($adjacentZonesList as $adjacentZone):
                        if ($adjacentZone['user_id'] == 0 && $adjacentZone['capital'] == 0):
                            $adjacentZones[] = $adjacentZone['zone'];
                        endif;
                    endforeach;
                    if (empty($adjacentZones)):
                        $adjacentZones = GameMap::where('game_id', $this->game->id)->where('user_id', 0)->where('capital', 0)->lists('zone');
                    endif;
                elseif ($this->validGameStage(2)):
                    foreach ($adjacentZonesList as $adjacentZone):
                        $adjacentZones[] = $adjacentZone['zone'];
                    endforeach;
                endif;
            endif;
            $this->json_request['responseJSON'] = array('game_id' => $this->game->id, 'zones' => $adjacentZones);
            $this->json_request['status'] = TRUE;
        endif;
        return Response::json($this->json_request, 200);
    }

    public function sendDisconnectUser() {

        $validation = Validator::make(Input::all(), array('user' => 'numeric'));
        if ($validation->passes()):
            if ($this->initGame()):
                $this->disconnectUserInGame(Input::get('user'));
                $this->json_request['status'] = TRUE;
            endif;
        endif;
        return Response::json($this->json_request, 200);
    }

    /****************************************************************************/
    private function hasInitGame() {

        if (!is_null($this->game) && is_object($this->game)):
            return TRUE;
        else:
            return FALSE;
        endif;
    }

    private function hasCreatedGame() {

        $games = Game::where('status', $this->game_statuses[0])->where('stage', 0)
            ->where('status_begin', 0)->where('status_over', 0)->with('users')
            ->get();
        foreach ($games as $game):
            if (count($game->users) < 3):
                $this->game = $game;
                return TRUE;
            endif;
        endforeach;
        return FALSE;
    }

    private function joinNewGame() {

        if (GameUser::where('game_id', $this->game->id)->where('user_id', Auth::user()->id)->exists() === FALSE):
            $this->game->users[] = GameUser::create(array('game_id' => $this->game->id, 'user_id' => Auth::user()->id,
                'is_bot' => 0, 'status' => 0, 'points' => 0, 'json_settings' => json_encode(array())));

            Sessions::setUserLastActivity();

            Log::info('setUserLastActivity', array('method' => 'joinNewGame',
                'message' => 'Пользователь подключился к игре',
                'game_id' => $this->game->id, 'current_user' => Auth::user()->id));

        endif;
    }

    private function startGame() {

        if (isset($this->game->users) && count($this->game->users) == Config::get('game.number_participants')):
            $this->game->status_begin = 1;
            $this->game->date_begin = Carbon::now()->format('Y-m-d H:i:s');
            $this->game->save();
            $this->game->touch();

            $this->changeGameStatus($this->game_statuses[1]);
            $this->changeGameStage(1);
            $this->randomUsersColor();
            $this->reInitGame();
            $this->createGameMap();
            $this->randomDistributionCapital();
            $this->randomStep();
            $this->reInitGame();
        endif;
    }

    private function finishGame($status_over = 1) {

        if ($this->validGameStatus($this->game_statuses[3]) === FALSE):
            $this->game->status = $this->game_statuses[3];
            $this->game->status_over = $status_over;
            $this->game->date_over = Carbon::now()->format('Y-m-d H:i:s');
            $this->game->save();
            $this->game->touch();
        endif;
    }

    /************************** CREATING RESPONSES ******************************/
    private function createGameJSONResponse() {

        if ($this->game):
            $users = $map = array();
            if (count($this->game->users)):
                $users_ids = $this->getUsersIDs();
                $activeUsers = Sessions::getUserIDsLastActivity($users_ids);
                foreach ($this->game->users as $user_game):
                    $photo_link = '';
                    if (!empty($user_game->user_social) && isset($user_game->user_social->photo_big) && !empty($user_game->user_social->photo_big)):
                        $photo_link = $user_game->user_social->photo_big;
                    endif;
                    $users[] = array('id' => $user_game->user->id, 'name' => $user_game->user->name,
                        'email' => $user_game->user->email, 'photo' => $photo_link,
                        'color' => $user_game->color, 'points' => $user_game->points, 'place' => $user_game->place,
                        'status' => $user_game->status,
                        'available_steps' => $user_game->available_steps, 'make_steps' => $user_game->make_steps,
                        'active' => in_array($user_game->user->id, $activeUsers),
                        'settings' => json_decode($user_game->json_settings, TRUE));
                endforeach;
            endif;
            if (count($this->game->map_places)):
                foreach ($this->game->map_places as $map_place):
                    $map[] = array('id' => $map_place->id, 'zone' => $map_place->zone, 'user_id' => $map_place->user_id,
                        'capital' => $map_place->capital, 'lives' => $map_place->lives, 'points' => $map_place->points,
                        'settings' => json_decode($map_place->json_settings, TRUE));
                endforeach;
            endif;
            $this->json_request['responseJSON'] = array(
                'game_id' => $this->game->id,
                'game_stage' => $this->game->stage,
                'game_status' => $this->game->status,
                'game_owner' => $this->game->started_id,
                'current_user' => Auth::user()->id,
                'users' => $users,
                'map' => $map,
                'settings' => json_decode($this->game->json_settings, TRUE),
                'disconnect_user_timeout' => Config::get('game.disconnect_user_timeout', 30),
                'disconnect_user_url' => URL::route('disconnect_user_url'),
            );
            $this->json_request['status'] = TRUE;
            return TRUE;
        else:
            return FALSE;
        endif;
    }

    private function setStepInSecondStageJSON() {

        $json_settings = json_decode($this->game->json_settings, TRUE);
        if (isset($json_settings['stage2_tours'])):
            $stage2_tours_json = array();
            foreach ($json_settings['stage2_tours'] as $tour => $user_steps):
                $stage2_tours_steps = array();
                foreach ($user_steps as $user_id => $step):
                    $stage2_tours_steps[] = '{"' . $user_id . '":' . (int)$step . '}';
                endforeach;
                $stage2_tours_json[] = '[' . implode(',', $stage2_tours_steps) . ']';
            endforeach;
            $json_settings['stage2_tours_json'] = '[' . implode(',', $stage2_tours_json) . ']';
        else:
            $json_settings['stage2_tours_json'] = '[]';
        endif;
        $this->game->json_settings = json_encode($json_settings);
        $this->game->save();
        $this->game->touch();
    }

    private function createStepInSecondStage($userID = NULL) {

        if ($this->validGameStage(2)):
            $json_settings = json_decode($this->game->json_settings, TRUE);
            $current_tour = isset($json_settings['current_tour']) ? $json_settings['current_tour'] : FALSE;
            $stage2_tours = isset($json_settings['stage2_tours']) ? $json_settings['stage2_tours'] : FALSE;

            if ($current_tour === FALSE || $stage2_tours === FALSE):
                $this->nextStep();
                $this->finishGame(0);

                Log::info('finishGame (0)', array('method' => 'createStepInSecondStage',
                    'message' => 'Игра завершилась. Отсутствуют данные current_tour или stage2_tours',
                    'current_user' => Auth::user()->id));

                $this->reInitGame();
            endif;

            if (is_null($userID)):
                $userID = Auth::user()->id;
            endif;

            $nextTour = TRUE;
            foreach ($stage2_tours[$current_tour] as $user_id => $status):
                if ($status == FALSE):
                    $nextTour = FALSE;
                    break;
                endif;
            endforeach;

            if ($nextTour && $current_tour < 3):
                $current_tour++;

                Log::info('current_tour < 3', array('method' => 'createStepInSecondStage',
                    'message' => 'При фиксации хода наступил следующий тур', 'current_tour' => $current_tour,
                    'user' => $userID, 'stage' => $this->game->stage,
                    'current_user' => Auth::user()->id));
            else:

                Log::info('current_tour < 3', array('method' => 'createStepInSecondStage',
                    'message' => 'При фиксации хода НЕ наступил следующий тур', 'current_tour' => $current_tour,
                    'current_user' => Auth::user()->id));

            endif;
            if (isset($stage2_tours[$current_tour][$userID]) && $stage2_tours[$current_tour][$userID] == FALSE):
                $stage2_tours[$current_tour][$userID] = TRUE;

                Log::info('user_step == TRUE', array('method' => 'createStepInSecondStage',
                    'message' => 'Зафиксировался шаг игрока', 'current_tour' => $current_tour,
                    'user' => $userID, 'stage' => $this->game->stage,
                    'current_user' => Auth::user()->id));

            endif;
            $json_settings['current_tour'] = $current_tour;
            $json_settings['stage2_tours'] = $stage2_tours;
            $this->game->json_settings = json_encode($json_settings);
            $this->game->save();
            $this->game->touch();
        endif;
    }

    private function createQuestionJSONResponse($user_question) {

        if ($this->validGameStatus($this->game_statuses[2]) && is_object($user_question)):
            $this->json_request['responseJSON'] = array('game_id' => $this->game->id,
                'game_status' => $this->game->status, 'game_stage' => $this->game->stage,
                'current_user' => Auth::user()->id,
                'question' => array('id' => $user_question->id, 'text' => $user_question->question->question,
                    'type' => $user_question->question->type, 'answers' => array())
            );
            if ($user_question->question->type == 'normal' && !empty($user_question->question->answers)):
                $answers = json_decode($user_question->question->answers);
                foreach ($answers as $answer):
                    if (!empty($answer->title)):
                        $this->json_request['responseJSON']['question']['answers'][] = $answer->title;
                    endif;
                endforeach;
            endif;
            $this->json_request['status'] = TRUE;
        endif;
    }

    private function createQuestionResultJSONResponse() {

        if ($this->validGameStatus($this->game_statuses[2])):
            $this->json_request['responseJSON'] = array('game_id' => $this->game->id,
                'game_status' => $this->game->status, 'game_stage' => $this->game->stage,
                'current_user' => Auth::user()->id, 'result' => $this->game_winners);
            $this->json_request['status'] = TRUE;
        endif;
    }

    /****************************** CREATING ************************************/
    private function createNewGame() {

        $this->game = Game::create(array('status' => $this->game_statuses[0], 'stage' => 0,
            'started_id' => Auth::user()->id, 'winner_id' => 0, 'status_begin' => 0,
            'date_begin' => '000-00-00 00:00:00', 'status_over' => 0, 'date_over' => '000-00-00 00:00:00',
            'json_settings' => json_encode(array('next_step' => 0))));

        if (Config::get('app.debug') === TRUE):
            if (File::exists(storage_path('logs/laravel.log'))):
                File::delete(storage_path('logs/laravel.log'));
            endif;
        endif;

        Log::info('createNewGame', array('method' => 'createNewGame',
            'message' => 'Создана новая игра',
            'game_id' => $this->game->id, 'current_user' => Auth::user()->id));

        if ($this->game):
            self::joinNewGame();
            $this->reInitGame();
        endif;
        return TRUE;
    }

    private function createGameMap() {

        if ($this->validGameStatus($this->game_statuses[1])):
            $map_places = array();
            for ($i = 0; $i < Config::get('game.number_places_on_map'); $i++):
                $map_places[] = new GameMap(array(
                    'game_id' => $this->game->id, 'user_id' => 0, 'zone' => $i + 1, 'capital' => 0,
                    'lives' => Config::get('game.map_empty_place_lives'), 'points' => 200, 'status' => 0,
                    'json_settings' => json_encode(array('color' => ''))
                ));
            endfor;
            if (count($map_places)):
                $this->game->map_places()->saveMany($map_places);
            endif;
        endif;
    }

    private function createQuestion($question_id, $users_ids = array()) {

        if ($this->validGameStatus($this->game_statuses[2])):
            $group_id = uniqid($this->game->id . '_');
            if (empty($users_ids)):
                foreach (GameUser::where('game_id', $this->game->id)->with('user')->lists('id', 'user_id') as $user_id => $user):
                    GameUserQuestions::create(array('started_id' => Auth::user()->id, 'group_id' => $group_id,
                        'game_id' => $this->game->id,
                        'user_id' => $user_id,
                        'question_id' => $question_id, 'status' => 0, 'place' => 0, 'answer' => 0, 'seconds' => 0));
                endforeach;
            elseif (count($users_ids)):
                foreach ($users_ids as $user_id):
                    GameUserQuestions::create(array('started_id' => Auth::user()->id, 'group_id' => $group_id,
                        'game_id' => $this->game->id,
                        'user_id' => $user_id,
                        'question_id' => $question_id, 'status' => 0, 'place' => 0, 'answer' => 0, 'seconds' => 0));
                endforeach;
            endif;
        endif;
    }

    private function createDuel($users_ids = array(), $zone = NULL) {

        $json_settings = json_decode($this->game->json_settings, TRUE);
        $json_settings['duel'] = is_array($users_ids) ? $users_ids : array();

//        if(is_null($zone) && !isset($json_settings['conqu_zone'])):
//            $json_settings['conqu_zone'] = NULL;
//        elseif(!is_null($zone)):
//            $json_settings['conqu_zone'] = $zone;
//        endif;

        $this->game->json_settings = json_encode($json_settings);
        $this->game->save();
        $this->game->touch();
    }

    private function createTemplateStepInSecondStage() {

        $json_settings = json_decode($this->game->json_settings, TRUE);
        $json_settings['current_tour'] = 0;
        $json_settings['stage2_tours'] = array(array(), array(), array(), array());
        $json_settings['stage2_tours_json'] = '[]';
        if ($users = GameUser::where('game_id', $this->game->id)->lists('user_id')):
            shuffle($users);
            $user_ids = array();

            $step_values[1] = FALSE;
            $step_values[2] = FALSE;
            $step_values[3] = FALSE;

            foreach ($users as $index => $user_id):
                $user_ids[$index + 1] = $user_id;
                $json_settings['stage2_tours'][3][$user_id] = FALSE;
            endforeach;
            try {
                $users = GameUser::where('game_id', $this->game->id)->lists('status', 'user_id');
                foreach ($user_ids as $index => $user_id):
                    if (in_array($users[$user_id], array(99, 100))):
                        $step_values[$index] = TRUE;
                    endif;
                endforeach;
                // первый тур
                $json_settings['stage2_tours'][0][$user_ids[1]] = $step_values[1];
                $json_settings['stage2_tours'][0][$user_ids[2]] = $step_values[2];
                $json_settings['stage2_tours'][0][$user_ids[3]] = $step_values[3];
                // второй тур
                $json_settings['stage2_tours'][1][$user_ids[2]] = $step_values[2];
                $json_settings['stage2_tours'][1][$user_ids[3]] = $step_values[3];
                $json_settings['stage2_tours'][1][$user_ids[1]] = $step_values[1];
                // третий тур
                $json_settings['stage2_tours'][2][$user_ids[3]] = $step_values[3];
                $json_settings['stage2_tours'][2][$user_ids[1]] = $step_values[1];
                $json_settings['stage2_tours'][2][$user_ids[2]] = $step_values[2];
            } catch (Exception $e) {

            }
        endif;

        $this->game->json_settings = json_encode($json_settings);
        $this->game->save();
        $this->game->touch();

        reset($json_settings['stage2_tours'][0]);
        return array_keys($json_settings['stage2_tours'][0])[0];
    }

    private function updateTemplateStepInSecondStage($first_step) {

        $this->reInitGame();

        $json_settings = json_decode($this->game->json_settings, TRUE);
        $current_tour = isset($json_settings['current_tour']) ? $json_settings['current_tour'] : FALSE;
        $stage2_tours = isset($json_settings['stage2_tours']) ? $json_settings['stage2_tours'] : FALSE;

        if ($current_tour === FALSE || $stage2_tours === FALSE):
            $this->nextStep();
            $this->finishGame(0);

            Log::info('finishGame (0)', array('method' => 'updateTemplateStepInSecondStage',
                'message' => 'Игра завершилась. Отсутствуют данные current_tour или stage2_tours',
                'current_user' => Auth::user()->id));

            $this->reInitGame();
        endif;

        $first_step_value = GameUser::where('game_id', $this->game->id)->where('user_id', $first_step)->whereNotIn('status', array(99,
            100))->exists();
        $step_values[0] = FALSE;
        $step_values[1] = FALSE;

        $user_ids = array();
        foreach ($this->game->users as $index => $game_user):
            if ($game_user->user_id != $first_step):
                $user_ids[$index] = $game_user->user_id;
                if (in_array($game_user->status, array(99, 100))):
                    $step_values[$index] = TRUE;
                endif;
            endif;
        endforeach;
        try {
            // третий тур
            $json_settings['stage2_tours'][$current_tour][$first_step] = $first_step_value;
            $json_settings['stage2_tours'][$current_tour][$user_ids[0]] = $step_values[0];
            $json_settings['stage2_tours'][$current_tour][$user_ids[1]] = $step_values[1];
        } catch (Exception $e) {

            Log::info('try|catch', array('method' => 'updateTemplateStepInSecondStage',
                'message' => 'Возникла ошибка при назначении шагов в 3 туре',
                'current_tour' => $current_tour,
                'first_step' => $first_step,
                'user_ids' => $user_ids,
                'current_user' => Auth::user()->id));
        }
        $this->game->json_settings = json_encode($json_settings);
        $this->game->save();
        $this->game->touch();
    }

    /******************************* RANDOM**************************************/
    private function randomUsersColor() {

        if ($this->validGameStage(1)):
            foreach ($this->game->users as $user_index => $user):
                $random_color = $this->randomColor($user_index);
                $user->color = $random_color;
                $user->save();
                $user->touch();
            endforeach;
        endif;
    }

    private function randomDistributionCapital() {

        if ($this->validGameStage(1)):
            if ($map_places_list = GameMap::where('game_id', $this->game->id)->orderBy('zone')->orderBy('id')->get()):
                $map_places = $map_places_ids = $map_capital_ids = array();
                foreach ($map_places_list as $map_place):
                    $map_places_ids[$map_place->id] = $map_place->id;
                endforeach;
                foreach ($map_places_list as $map_place):
                    $map_places[$map_place->id] = $map_place;
                endforeach;
                foreach (GameUser::where('game_id', $this->game->id)->get() as $user_index => $user):
                    $capital = array_rand($map_places_ids);
                    $map_places_ids = $this->exclude_indexes($capital, $map_places, $map_places_ids);
                    $map_places[$capital]->user_id = $user->user_id;
                    $map_places[$capital]->points = 1000;
                    $map_places[$capital]->capital = 1;
                    $map_places[$capital]->lives = Config::get('game.map_capital_place_lives');
                    $settings = json_decode($map_places[$capital]->json_settings);
                    $settings->color = $user->color;
                    $map_places[$capital]->json_settings = json_encode($settings);
                    $map_places[$capital]->save();
                endforeach;
            endif;
        endif;
    }

    private function randomStep() {

        if ($users = GameUser::where('game_id', $this->game->id)->where('is_bot', 0)->with('user')->lists('id', 'user_id')):
            $user_id = array_rand($users);
            $this->nextStep($user_id);

            Log::info('nextStep', array('method' => 'randomStep', 'message' => 'nextStep',
                'nextStep' => $this->getNextStep(), 'current_user' => Auth::user()->id));

        endif;
    }

    private function randomColor($color_index) {

        $colors = Config::get('game.colors');
        return isset($colors[$color_index]) ? $colors[$color_index] : '';
    }

    private function randomQuestion($type_question = 'quiz') {

        if ($this->validGameStatus($this->game_statuses[2])):
            $exclude_ids = GameUserQuestions::where('game_id', $this->game->id)->groupBy('question_id')->orderBy('question_id')->lists('question_id');
            if (empty($exclude_ids)):
                $exclude_ids = array(0);
            endif;
            if ($questions = GameQuestions::where('type', $type_question)->whereNotIn('id', $exclude_ids)->lists('title', 'id')):
                $question_id = array_rand($questions);
                return GameQuestions::where('id', $question_id)->where('type', $type_question)->first();
            endif;
        endif;
    }

    /******************************** CHANGES ************************************/
    private function changeGameStatus($status) {

        if ($this->game->status != $status):
            $this->game->status = $status;
            $this->game->save();
        endif;
    }

    private function changeGameStage($stage) {

        if ($this->game->stage != $stage):
            $this->game->stage = $stage;
            $this->game->save();
        endif;
    }

    private function changeGameUsersSteps($user = NULL) {

        if (is_null($user)):
            $user = $this->user;
        endif;
        $status = FALSE;
        if ($this->validGameStatus($this->game_statuses[2])):
            if ($user->status == 0 && $user->available_steps > 0):
                if ($user->available_steps > $user->make_steps):
                    $user->make_steps = $user->make_steps + 1;
                    $user->save();
                    $status = TRUE;
                    if ($user->available_steps == $user->make_steps):
                        $user->status = 1;
                        $user->save();
                        $this->reInitGame();
                    endif;
                    $user->touch();
                endif;
            endif;
        endif;
        return $status;
    }

    private function changeGameUsersStatus($status, $user = NULL) {

        if(is_null($user)):
            foreach (GameUser::where('game_id', $this->game->id)->whereNotIn('status', array(99, 100))->get() as $user):
                $user->status = $status;
                $user->save();
                $user->touch();
            endforeach;
        elseif(is_numeric($user)):
            GameUser::where('game_id', $this->game->id)->where('user_id', $user)
                ->whereNotIn('status', array(99, 100))
                ->update(array('status' => $status));
        elseif(is_object($user)):
            if (isset($user->status) && !in_array($user->status, array(99, 100))):
                $user->status = $status;
                $user->save();
                $user->touch();
            endif;
        endif;
    }

    private function changeUserPoints($user_id, $points, $user = NULL) {

        if ($this->game->status_begin):
            if (is_null($user)):
                $user = GameUser::where('game_id', $this->game->id)->where('user_id', $user_id)->first();
            endif;
            $user->points = (int)$user->points + (int)$points;
            $user->save();
            $user->touch();
        endif;
    }

    private function changeUserRating($user_id, $rating) {

        if ($this->validGameStatus($this->game_statuses[3])):
            if (GameUserRating::where('game_id', $this->game->id)->where('user_id', $user_id)->exists() === FALSE):
                $userRating = new GameUserRating();
                $userRating->game_id = $this->game->id;
                $userRating->user_id = $user_id;
                $userRating->rating = $rating;
                $userRating->save();
                $userRating->touch();
            endif;
        endif;
    }

    private function changeTerritoryPoints($zone, $points) {

        if ($this->validGameStatus($this->game_statuses[2])):
            if ($territory = GameMap::where('game_id', $this->game->id)->where('zone', $zone)->first()):
                $territory->points = (int)$territory->points + (int)$points;
                $territory->save();
                $territory->touch();
                return $territory->points;
            endif;
        endif;
        return 0;
    }

    /******************************* VALIDATION **********************************/
    private function validGame(){

        if(!is_null($this->game) && isset($this->game->id) && !empty($this->game->users)):
            return TRUE;
        else:
            return FALSE;
        endif;
    }

    private function validGameStatus($status) {

        if ($this->initGame()):
            if ($this->game->status == $status):
                return TRUE;
            else:
                return FALSE;
            endif;
        endif;
    }

    private function validGameStage($stage) {

        if ($this->initGame()):
            if ($this->game->stage == $stage):
                return TRUE;
            else:
                return FALSE;
            endif;
        endif;
    }

    private function validAvailableSteps() {

        if (GameUser::where('game_id', $this->game->id)->whereRaw('(available_steps - make_steps) > 0')->exists()):
            return TRUE;
        else:
            return FALSE;
        endif;
    }

    private function validUsersStatus($status) {

        if (GameUser::where('game_id', $this->game->id)->where('status', $status)->exists()):
            return TRUE;
        else:
            return FALSE;
        endif;
    }

    private function validCapitalZone($zone) {

        if ($zone):
            if (count($this->game->map_places)):
                foreach ($this->game->map_places as $map_place):
                    if ($map_place->zone == $zone && $map_place->capital == 1):
                        return TRUE;
                    endif;
                endforeach;
            endif;
        endif;
        return FALSE;
    }

    /********************************** BOTS *************************************/
    public function addBots() {

        if (!Request::ajax()) return App::abort(404);
        if ($this->initGame()):
            $this->joinBotsInGame();
            $this->reInitGame();
            $this->startGame();
            $this->reInitGame();
            $this->json_request['responseText'] = 'Виртуальные пользователи добавлены';
            $this->json_request['status'] = TRUE;
        endif;
        return Response::json($this->json_request, 200);
    }

    private function joinBotsInGame() {

        $user_games_count = GameUser::where('game_id', $this->game->id)->count();
        $bots = array();
        $bots_ids = Config::get('game.bots_ids');
        if ($user_games_count == 1):
            $this->game->users[] = $bots[] = array('game_id' => $this->game->id, 'user_id' => $bots_ids[0],
                'is_bot' => 1,
                'status' => 0, 'available_steps' => 0, 'make_steps' => 0, 'color' => '', 'points' => 0,
                'place' => 0, 'json_settings' => json_encode(array()), 'created_at' => date('Y-m-d H:i:s'));
            $this->game->users[] = $bots[] = array('game_id' => $this->game->id, 'user_id' => $bots_ids[1],
                'is_bot' => 1,
                'status' => 0, 'available_steps' => 0, 'make_steps' => 0, 'color' => '', 'points' => 0,
                'place' => 0, 'json_settings' => json_encode(array()), 'created_at' => date('Y-m-d H:i:s'));
        elseif ($user_games_count == 2):
            $this->game->users[] = $bots[] = array('game_id' => $this->game->id, 'user_id' => $bots_ids[0],
                'is_bot' => 1,
                'status' => 0, 'available_steps' => 0, 'make_steps' => 0, 'color' => '', 'points' => 0,
                'place' => 0, 'json_settings' => json_encode(array()), 'created_at' => date('Y-m-d H:i:s'));
        endif;
        if (count($bots)):
            GameUser::insert($bots);
        endif;
    }

    private function validGameBots() {

        $hasBots = FALSE;
        if ($this->validGameStatus($this->game_statuses[2])):
            if (isset($this->game->users) && !empty($this->game->users)):
                foreach ($this->game->users as $game_user):
                    if ($this->isBot($game_user->user_id)):
                        $hasBots = TRUE;
                    endif;
                endforeach;
            endif;
        endif;
        return $hasBots;
    }

    private function botConquestTerritory($bot_id, $conqueror_zone = NULL) {

        $bot = GameUser::where('game_id', $this->game->id)->where('is_bot', 1)->where('user_id', $bot_id)->first();
        if ($this->changeGameUsersSteps($bot)):
            if ($adjacent_places = $this->getAdjacentPlaces($bot_id)):
                if ($this->validGameStage(1)):
                    $empty_territories = array();
                    foreach ($adjacent_places as $zone_id => $adjacent_place):
                        if ($adjacent_place['user_id'] == 0 && $adjacent_place['capital'] == 0):
                            $empty_territories[] = $adjacent_place['zone'];
                        endif;
                    endforeach;
                    if (!empty($empty_territories)):
                        $conquest_zone_index = array_rand($empty_territories);
                        $conquest_zone = $empty_territories[$conquest_zone_index];

                        if ($this->conquestTerritory($conquest_zone, $bot_id)):
                            $points = $this->getTerritoryPoints($conquest_zone);
                            $this->changeUserPoints($bot_id, $points, $bot);

                            Log::info('conquestTerritory', array('method' => 'botConquestTerritory',
                                'message' => 'Бот захватил смежную террирорию!',
                                'zone' => $conquest_zone, 'current_user' => $bot_id, 'stage' => $this->game->stage));

                        else:

                            Log::info('conquestTerritory. ERROR', array('method' => 'botConquestTerritory',
                                'message' => 'Бот не смог захватить смежную террирорию!',
                                'zone' => $conquest_zone, 'current_user' => $bot_id, 'stage' => $this->game->stage));

                        endif;
                    else:
                        if ($firstEmptyZone = GameMap::where('game_id', $this->game->id)->where('user_id', 0)->where('capital', 0)->first()):
                            if ($this->conquestTerritory($firstEmptyZone->zone, $bot_id)):
                                $points = $this->getTerritoryPoints($firstEmptyZone->zone);
                                $this->changeUserPoints($bot_id, $points, $bot);

                                Log::info('conquestTerritory', array('method' => 'botConquestTerritory',
                                    'message' => 'Бот захватил первую попавшуюсь террирорию!',
                                    'bot' => $bot_id,
                                    'zone' => $firstEmptyZone->zone, 'current_user' => Auth::user()->id,
                                    'stage' => $this->game->stage));

                            else:

                                Log::info('conquestTerritory. ERROR', array('method' => 'botConquestTerritory',
                                    'message' => 'Бот не смог захватить первую попавшуюся террирорию!',
                                    'zone' => $firstEmptyZone->zone, 'current_user' => $bot_id,
                                    'stage' => $this->game->stage));

                            endif;
                        endif;
                    endif;
                    return TRUE;
                elseif ($this->validGameStage(2) && !is_null($conqueror_zone)):
                    if ($this->conquestTerritory($conqueror_zone, $bot_id)):

                        Log::info('conquestTerritory.', array('method' => 'botConquestTerritory',
                            'message' => 'Бот захватил территорию', 'zone' => $conqueror_zone, 'bot' => $bot_id,
                            'stage' => $this->game->stage));

                        $points = $this->getTerritoryPoints($conqueror_zone);
                        $this->changeUserPoints($bot_id, $points, $bot);
                        $this->changeTerritoryPoints($conqueror_zone, 200);

                        $this->changeGameUsersStatus(2);
                        $this->reInitGame();
                        return TRUE;
                    endif;
                endif;
            endif;
        else:

            Log::info('changeGameUsersSteps. ERROR', array('method' => 'sendConquestCapital',
                'message' => 'Захват территории ботом не удался. Нет доступных очков хода',
                'zone_conquest' => $conqueror_zone, 'bot' => $bot_id, 'current_user' => Auth::user()->id,
                'stage' => $this->game->stage));
        endif;
        return FALSE;
    }

    private function botConquestCapital($bot_id, $conqueror_zone = NULL) {

        $bot = GameUser::where('game_id', $this->game->id)->where('is_bot', 1)->where('user_id', $bot_id)->first();
        if ($this->validGameStatus($this->game_statuses[2]) && $this->validGameStage(2)):
            if ($this->changeGameUsersSteps($bot)):
                if ($conquest = GameMap::where('game_id', $this->game->id)->where('user_id', '!=', $bot_id)->where('zone', $conqueror_zone)->where('capital', 1)->first()):
                    if ($conquest->lives == 1):

                        Log::info('conquest->lives == 1', array('method' => 'botConquestCapital',
                            'message' => 'Бот захватил столицу!',
                            'zone' => $conqueror_zone, 'current_user' => $bot_id, 'stage' => $this->game->stage,
                            'owner' => $conquest->user_id));

                        $this->disconnectUserInGame($conquest->user_id);
                        foreach (GameMap::where('game_id', $this->game->id)->where('user_id', $conquest->user_id)->get() as $territory):
                            if ($territory->capital == 1):
                                $territory->points = 200;
                            endif;
                            $settings = json_decode($territory->json_settings);
                            $settings->color = $bot['color'];
                            $territory->json_settings = json_encode($settings);
                            $territory->user_id = $bot_id;
                            $territory->capital = 0;
                            $territory->status = 0;
                            $territory->save();
                            $territory->touch();
                            return 0;
                        endforeach;
                    elseif ($conquest->lives > 1):
                        $conquest->lives = $conquest->lives - 1;
                        $conquest->save();
                        $conquest->touch();

                        Log::info('conquest->lives > 1', array('method' => 'botConquestCapital',
                            'message' => 'Бот уменьшил количество жизней у столицы!',
                            'zone' => $conqueror_zone, 'current_user' => $bot_id, 'stage' => $this->game->stage,
                            'owner' => $conquest->user_id, 'lives' => $conquest->lives));

                        return $conquest->lives;
                    endif;
                endif;
            else:

                Log::info('changeGameUsersSteps. ERROR', array('method' => 'sendConquestCapital',
                    'message' => 'Захват столицы ботом не удался. Нет доступных очков хода',
                    'zone_conquest' => $conqueror_zone, 'bot' => $bot_id, 'current_user' => Auth::user()->id,
                    'stage' => $this->game->stage));
            endif;
        endif;
        return FALSE;
    }

    private function isBot($user_id) {

        if (in_array($user_id, Config::get('game.bots_ids'))):
            return TRUE;
        else:
            return FALSE;
        endif;
    }

    private function sendBotsAnswers($userGameQuestion) {

        if ($bots_id = $this->getGameBotsIDs()):
            $current_answer = (int)$this->getCurrentAnswer($userGameQuestion);
            $question_id = isset($userGameQuestion->question_id) ? $userGameQuestion->question_id : 0;
            if ($question = GameQuestions::where('id', $question_id)->first()):
                $question_type = $question->type;
                if ($question_type == 'quiz'):
                    foreach ($bots_id as $bot_id):
                        $this->botAnswerQuizQuestion($bot_id, $current_answer, $userGameQuestion->group_id);
                    endforeach;
                elseif ($question_type == 'normal'):
                    foreach ($bots_id as $bot_id):
                        $this->botAnswerNormalQuestion($bot_id, $current_answer, $userGameQuestion->group_id);
                    endforeach;
                endif;
            endif;
        endif;
    }

    private function botAnswerQuizQuestion($bot_id, $current_answer, $question_group_id) {

        $min_value = $current_answer - round($current_answer * 0.4, 0);
        $max_value = $current_answer + round($current_answer * 0.4, 0);
        if ($botGameQuestion = GameUserQuestions::where('game_id', $this->game->id)->where('group_id', $question_group_id)->where('user_id', $bot_id)->where('status', 0)->first()):
            $botGameQuestion->status = 1;
            $botGameQuestion->answer = mt_rand($min_value, $max_value);
            $botGameQuestion->seconds = mt_rand(3, 7);
            $botGameQuestion->save();
            $botGameQuestion->touch();

            Log::info('botAnswerQuizQuestion', array('method' => 'sendBotsAnswers',
                'message' => 'Бот ответил на квиз вопрос', 'bot_id' => $bot_id,
                'current_answer' => $current_answer,
                'bot_answer' => $botGameQuestion->answer,
                'current_user' => Auth::user()->id,
                'question_type' => 'квиз',
                'stage' => $this->game->stage));
        endif;
    }

    private function botAnswerNormalQuestion($bot_id, $current_answer, $question_group_id) {

        $answer = (mt_rand() == 1) ? $current_answer : 99999;
        if ($botGameQuestion = GameUserQuestions::where('game_id', $this->game->id)->where('group_id', $question_group_id)->where('user_id', $bot_id)->where('status', 0)->first()):
            $botGameQuestion->status = 1;
            $botGameQuestion->answer = $answer;
            $botGameQuestion->seconds = mt_rand(3, 7);
            $botGameQuestion->save();
            $botGameQuestion->touch();

            Log::info('botAnswerNormalQuestion', array('method' => 'sendBotsAnswers',
                'message' => 'Бот ответил на нормальный вопрос', 'bot_id' => $bot_id,
                'current_answer' => $current_answer,
                'bot_answer' => $botGameQuestion->answer,
                'current_user' => Auth::user()->id,
                'question_type' => 'нормальный',
                'stage' => $this->game->stage));
        endif;
    }

    private function isBotNextStepStage1() {

        $winners_id = array_keys($this->game_winners);
        if ($this->isBot($winners_id[0])):

            Log::info('isBot', array('method' => 'isBotNextStepStage1',
                'message' => 'Бот занял 1-е место.', 'bot_id' => $winners_id[0],
                'current_user' => Auth::user()->id, 'stage' => $this->game->stage));

            $this->nextStep($winners_id[0]);

            Log::info('Сохраняем ход для бота. Бот занял 1-е место', array('method' => 'isBotNextStepStage1',
                'message' => 'nextStep',
                'nextStep' => $this->getNextStep(), 'current_user' => Auth::user()->id));

            $this->botConquestTerritory($winners_id[0]);
            $this->botConquestTerritory($winners_id[0]);
            $this->nextStep($winners_id[1]);

            Log::info('Переводим ход на следующего игрока. Бот отходил', array('method' => 'isBotNextStepStage1',
                'message' => 'nextStep',
                'nextStep' => $this->getNextStep(), 'current_user' => Auth::user()->id));

            if ($this->isBot($winners_id[1])):

                Log::info('isBot', array('method' => 'isBotNextStepStage1',
                    'message' => 'Бот занял 2-е место.', 'bot_id' => $winners_id[1],
                    'current_user' => Auth::user()->id, 'stage' => $this->game->stage));

                $this->nextStep($winners_id[1]);

                Log::info('nextStep 3', array('method' => 'isBotNextStepStage1', 'message' => 'nextStep',
                    'nextStep' => $this->getNextStep(), 'current_user' => Auth::user()->id));

                $this->botConquestTerritory($winners_id[1]);

                $this->changeGameUsersStatus(2);

                $this->nextStep();

                Log::info('nextStep', array('method' => 'isBotNextStepStage1',
                    'message' => 'Сброс хода. Победители боты. Они отходили',
                    'next_step' => $this->getNextStep(),
                    'current_user' => Auth::user()->id,
                    'stage' => $this->game->stage));
            endif;
            if ($this->isConqueredTerritories()):

                Log::info('isConqueredTerritories.', array('method' => 'botConquestTerritory',
                    'message' => 'Все территории захвачены', 'stage' => $this->game->stage));

                $this->nextStep();

                Log::info('nextStep', array('method' => 'isBotNextStepStage1',
                    'message' => 'Сброс хода. Все территории захвачены',
                    'current_user' => Auth::user()->id,
                    'stage' => $this->game->stage));

                $this->closeGameUsersQuestions();
                $this->changeGameStage(2);

                Log::info('changeGameStage', array('method' => 'botConquestTerritory',
                    'message' => 'Бот перевел игру на 2й этап',
                    'current_user' => Auth::user()->id,
                    'stage' => $this->game->stage));

                $this->resetGameUsers();

                Log::info('resetGameUsers', array('method' => 'isBotNextStepStage1',
                    'message' => 'Сброс параметров игроков',
                    'current_user' => Auth::user()->id, 'stage' => $this->game->stage));

                $nextStep = $this->createTemplateStepInSecondStage();
                $this->nextStep($nextStep);

                Log::info('createTemplateStepInSecondStage', array('method' => 'botConquestTerritory',
                    'message' => 'Бот создал шаблон шагов для 2-го этапа',
                    'nextStep' => $this->getNextStep(), 'current_user' => Auth::user()->id,
                    'stage' => $this->game->stage));
            endif;
        endif;
    }

    private function isBotNextStepStage2() {

        if ($this->validGameStatus($this->game_statuses[2]) && $this->validGameStage(2)):
            if ($this->validGameBots()):

                $botConqueror = $this->getNextStep();
                $duel = $this->getDuel();
                $botExecute = TRUE;

                if ($this->validAvailableSteps()):
                    $botExecute = FALSE;

                    Log::info('execute', array('method' => 'isBotNextStepStage2',
                        'message' => 'У игроков есть доступные ходы. Запуск бота невозможен.',
                        'bot' => $botConqueror, 'duel' => $duel,
                        'current_user' => Auth::user()->id,
                        'stage' => $this->game->stage));

                endif;

                if (!empty($duel) && $botConqueror != $duel['conqu']):
                    $botExecute = FALSE;

                    Log::info('execute', array('method' => 'isBotNextStepStage2',
                        'message' => 'Существует дуель где нападает не next_step бот. Запуск бота невозможен.',
                        'bot' => $botConqueror, 'duel' => $duel,
                        'current_user' => Auth::user()->id,
                        'stage' => $this->game->stage));

                endif;
                foreach ($this->game->users as $game_user):
                    if ($game_user->user_id == $botConqueror && $game_user->available_steps > 0):
                        $botExecute = TRUE;

                        Log::info('execute', array('method' => 'isBotNextStepStage2',
                            'message' => 'У бота есть доступные очки хода. Запуск бота.',
                            'bot' => $botConqueror, 'duel' => $duel,
                            'available_steps' => $game_user->available_steps,
                            'current_user' => Auth::user()->id,
                            'stage' => $this->game->stage));

                    endif;
                endforeach;

                if ($this->isBot($botConqueror) && $botExecute):

                    Log::info('isBot 1', array('method' => 'isBotNextStepStage2',
                        'message' => 'Ход предоставлен боту', 'bot' => $botConqueror,
                        'current_user' => Auth::user()->id,
                        'stage' => $this->game->stage));

                    $bot = GameUser::where('game_id', $this->game->id)->where('is_bot', 1)->where('user_id', $botConqueror)->first();
                    if ($bot->status > 0):

                        Log::info('bot->status > 0. ERROR', array('method' => 'isBotNextStepStage2',
                            'message' => 'Ходит бот, но у него статус НЕ 0', 'bot_id' => $botConqueror,
                            'current_user' => Auth::user()->id, 'stage' => $this->game->stage));

                        $this->closeGameUsersQuestions();
                        $this->resetGameUsers();

                        Log::info('resetGameUsers', array('method' => 'isBotNextStepStage2',
                            'message' => 'Сброс параметров игроков',
                            'current_user' => Auth::user()->id, 'stage' => $this->game->stage));

                        $this->createDuel();
                        $this->nextStepInSecondStage();

                        Log::info('nextStepInSecondStage. ERROR', array('method' => 'isBotNextStepStage2',
                            'message' => 'У бота статус 0. Передает ход.', 'next_stap' => $this->getNextStep(),
                            'duel' => $this->getDuel(),
                            'current_user' => Auth::user()->id, 'stage' => $this->game->stage));

                        return FALSE;
                    endif;
                    $this->closeGameUsersQuestions();
                    $this->resetGameUsers();

                    Log::info('resetGameUsers', array('method' => 'isBotNextStepStage2',
                        'message' => 'Сброс параметров игроков',
                        'current_user' => Auth::user()->id, 'stage' => $this->game->stage));

                    $this->createStepInSecondStage($botConqueror);
                    $this->setStepInSecondStageJSON();

                    Log::info('createStepInSecondStage', array('method' => 'isBotNextStepStage2',
                        'message' => 'Бот совершил ход', 'bot' => $botConqueror,
                        'current_user' => Auth::user()->id,
                        'stage' => $this->game->stage));

                    if ($adjacentZonesList = $this->getAdjacentPlaces($botConqueror)):
                        $adjacentZones = array();
                        foreach ($adjacentZonesList as $adjacentZone):
                            $adjacentZones[$adjacentZone['zone']] = $adjacentZone['user_id'];
                        endforeach;
                        $zones_numbers = array_keys($adjacentZones);
                        $zones_users = array_values($adjacentZones);
                        $conqueror_index = array_rand($zones_numbers);
                        if (isset($zones_users[$conqueror_index])):

                            Log::info('conqueror_index', array('method' => 'isBotNextStepStage2',
                                'message' => 'Получена зона для нападения', 'bot' => $botConqueror,
                                'zone' => $zones_users[$conqueror_index],
                                'current_user' => Auth::user()->id,
                                'stage' => $this->game->stage));

                            $gamerDefence = $zones_users[$conqueror_index];
                            $zoneConqueror = $zones_numbers[$conqueror_index];
                            $duel = array('conqu' => $botConqueror, 'def' => $gamerDefence);
                            $this->createDuel($duel);

                            Log::info('createDuel', array('method' => 'isBotNextStepStage2',
                                'message' => 'Бот создал дуель', 'bot' => $botConqueror,
                                'duel' => $duel,
                                'current_user' => Auth::user()->id,
                                'stage' => $this->game->stage));

                            if ($this->isBot($gamerDefence)):

                                Log::info('isBot 2', array('method' => 'isBotNextStepStage2',
                                    'message' => 'Обороняющийся тоже бот', 'bot' => $botConqueror,
                                    'duel' => $duel, 'gamerDefence' => $gamerDefence,
                                    'current_user' => Auth::user()->id,
                                    'stage' => $this->game->stage));

                                $botWinner = array_rand(array($botConqueror, $gamerDefence));

                                Log::info('botWinner', array('method' => 'isBotNextStepStage2',
                                    'message' => 'Выбрали победителя в дуели', 'bot' => $botConqueror,
                                    'duel' => $duel, 'botWinner' => $botWinner,
                                    'current_user' => Auth::user()->id,
                                    'stage' => $this->game->stage));

                                if ($botWinner === 0):

                                    Log::info('botWinner == 0', array('method' => 'isBotNextStepStage2',
                                        'message' => 'В дуели победил нападающий', 'bot' => $botConqueror,
                                        'duel' => $duel, 'botWinner' => $botWinner,
                                        'current_user' => Auth::user()->id,
                                        'stage' => $this->game->stage));

                                    if ($this->validCapitalZone($zoneConqueror)):

                                        Log::info('validCapitalZone', array('method' => 'isBotNextStepStage2',
                                            'message' => 'Бот атакует столицу', 'bot' => $botConqueror,
                                            'duel' => $duel, 'zoneConqueror' => $zoneConqueror,
                                            'current_user' => Auth::user()->id,
                                            'stage' => $this->game->stage));

                                        $lives = $this->getTerritoryLives($zoneConqueror);

                                        Log::info('validCapitalZone', array('method' => 'isBotNextStepStage2',
                                            'message' => 'Жизни столицы', 'bot' => $botConqueror,
                                            'duel' => $duel, 'zoneConqueror' => $zoneConqueror,
                                            'lives' => $lives, 'current_user' => Auth::user()->id,
                                            'stage' => $this->game->stage));

                                        for ($i = $lives; $i > 0; $i--):
                                            $botDuelWinner = array_rand(array($botConqueror, $gamerDefence));

                                            Log::info('botDuelWinner', array('method' => 'isBotNextStepStage2',
                                                'message' => 'Выбрали победителя в дуели при атаке на столицу',
                                                'bot' => $botConqueror,
                                                'duel' => $duel, 'botDuelWinner' => $botDuelWinner,
                                                'current_user' => Auth::user()->id,
                                                'stage' => $this->game->stage));

                                            if ($botDuelWinner === 0):
                                                $this->setGameUserAvailableSteps($botConqueror, 1);

                                                Log::info('botDuelWinner == 0', array('method' => 'isBotNextStepStage2',
                                                    'message' => 'Нападающий выиграл и предоствлено дали очко дейсвий',
                                                    'bot' => $botConqueror, 'botDuelWinner' => $botDuelWinner,
                                                    'current_user' => Auth::user()->id,
                                                    'stage' => $this->game->stage));

                                                if ($capitalLives = $this->botConquestCapital($botConqueror, $zoneConqueror)):

                                                    Log::info('botConquestCapital', array('method' => 'isBotNextStepStage2',
                                                        'message' => 'Нападающий ударил по столице',
                                                        'bot' => $botConqueror, 'capitalLives' => $capitalLives,
                                                        'current_user' => Auth::user()->id,
                                                        'stage' => $this->game->stage));

                                                    if ($capitalLives === 0):

                                                        Log::info('capitalLives === 0', array('method' => 'isBotNextStepStage2',
                                                            'message' => 'Нападающий уничтожил столицу',
                                                            'bot' => $botConqueror, 'capitalLives' => $capitalLives,
                                                            'current_user' => Auth::user()->id,
                                                            'stage' => $this->game->stage));

                                                        $this->closeGameUsersQuestions();
                                                        $this->resetGameUsers();

                                                        Log::info('resetGameUsers', array('method' => 'isBotNextStepStage2',
                                                            'message' => 'Сброс параметров игроков',
                                                            'current_user' => Auth::user()->id,
                                                            'stage' => $this->game->stage));

                                                        $this->createDuel();
                                                        $points = $this->getTerritoryPoints($zoneConqueror);
                                                        $this->changeUserPoints($botConqueror, $points, $bot);

                                                        $this->nextStepInSecondStage();

                                                        Log::info('nextStepInSecondStage', array('method' => 'isBotNextStepStage2',
                                                            'message' => 'Бот захватил столицу. Переход хода',
                                                            'nextStep' => $this->getNextStep(),
                                                            'current_user' => Auth::user()->id,
                                                            'duel' => $this->getDuel(),
                                                            'stage' => $this->game->stage));

                                                        $this->changeGameUsersStatus(2);
                                                        if ($this->isConqueredCapitals()):
                                                            $this->nextStep();
                                                            $this->finishGame(1);

                                                            Log::info('finishGame (1)', array('method' => 'isBotNextStepStage2',
                                                                'message' => 'Бот завершил игру. Осталась только одна столица',
                                                                'bot' => $botConqueror,
                                                                'current_user' => Auth::user()->id,
                                                                'stage' => $this->game->stage));

                                                            $this->reInitGame();
                                                        endif;
                                                        break;
                                                    endif;
                                                endif;

                                            else:

                                                Log::info('gamerDefenceTerritory', array('method' => 'isBotNextStepStage2',
                                                    'message' => 'Удара по столице непроизошло. Победил обороняющийся',
                                                    'bot' => $botConqueror, 'duel' => $duel,
                                                    'gamerDefence' => $gamerDefence,
                                                    'zoneConqueror' => $zoneConqueror,
                                                    'current_user' => Auth::user()->id,
                                                    'stage' => $this->game->stage));

                                                $this->gamerDefenceTerritory($zoneConqueror);
                                                $this->closeGameUsersQuestions();
                                                $this->resetGameUsers();

                                                Log::info('resetGameUsers', array('method' => 'isBotNextStepStage2',
                                                    'message' => 'Сброс параметров игроков',
                                                    'current_user' => Auth::user()->id, 'stage' => $this->game->stage));

                                                $this->nextStepInSecondStage();
                                                $this->createDuel();

                                                Log::info('nextStepInSecondStage', array('method' => 'isBotNextStepStage2',
                                                    'message' => 'Победил обороняющийся бот. Переход хода',
                                                    'nextStep' => $this->getNextStep(),
                                                    'current_user' => Auth::user()->id,
                                                    'duel' => $this->getDuel(),
                                                    'stage' => $this->game->stage));

                                                break;
                                            endif;
                                        endfor;
                                    else:

                                        Log::info('botConquestTerritory', array('method' => 'isBotNextStepStage2',
                                            'message' => 'Боту предоставляется очко хода и он захватывает обычную территорию',
                                            'botConqueror' => $botConqueror, 'zoneConqueror' => $zoneConqueror,
                                            'current_user' => Auth::user()->id,
                                            'stage' => $this->game->stage));

                                        $this->setGameUserAvailableSteps($botConqueror, 1);
                                        $this->botConquestTerritory($botConqueror, $zoneConqueror);
                                        $this->closeGameUsersQuestions();
                                        $this->resetGameUsers();

                                        Log::info('resetGameUsers', array('method' => 'isBotNextStepStage2',
                                            'message' => 'Сброс параметров игроков',
                                            'current_user' => Auth::user()->id, 'stage' => $this->game->stage));

                                        $this->createDuel();
                                        $this->nextStepInSecondStage();

                                        Log::info('nextStepInSecondStage', array('method' => 'isBotNextStepStage2',
                                            'message' => 'Победил нападающий бот. Переход хода',
                                            'nextStep' => $this->getNextStep(), 'current_user' => Auth::user()->id,
                                            'duel' => $this->getDuel(),
                                            'stage' => $this->game->stage));

                                    endif;
                                else:

                                    Log::info('gamerDefenceTerritory', array('method' => 'isBotNextStepStage2',
                                        'message' => 'Обороняющийся бот - победил', 'duel' => $duel,
                                        'zoneConqueror' => $zoneConqueror,
                                        'current_user' => Auth::user()->id,
                                        'stage' => $this->game->stage));

                                    $this->gamerDefenceTerritory($zoneConqueror);
                                    $this->closeGameUsersQuestions();
                                    $this->resetGameUsers();

                                    Log::info('resetGameUsers', array('method' => 'isBotNextStepStage2',
                                        'message' => 'Сброс параметров игроков',
                                        'current_user' => Auth::user()->id, 'stage' => $this->game->stage));

                                    $this->nextStepInSecondStage();

                                    Log::info('nextStepInSecondStage', array('method' => 'isBotNextStepStage2',
                                        'message' => 'Победил обороняющийся бот. Переход хода',
                                        'nextStep' => $this->getNextStep(), 'current_user' => Auth::user()->id,
                                        'stage' => $this->game->stage));
                                endif;
                            else:
                                $this->resetGameUsers();

                                Log::info('resetGameUsers', array('method' => 'isBotNextStepStage2',
                                    'message' => 'Сброс параметров игроков',
                                    'current_user' => Auth::user()->id, 'stage' => $this->game->stage));

                                $this->botCreateNormalQuestion($duel);
                                $this->nextStep();

                                Log::info('nextStep', array('method' => 'isBotNextStepStage2',
                                    'message' => 'Сброс хода. Был задан нормальный вопрос от бота',
                                    'nextStep' => $this->getNextStep(), 'current_user' => Auth::user()->id,
                                    'stage' => $this->game->stage));

                            endif;
                        endif;
                    endif;
                endif;
            endif;
        endif;
    }

    private function botCreateNormalQuestion($users_ids) {

        $randomQuestion = $this->randomQuestion('normal');
        $this->createQuestion($randomQuestion->id, $users_ids);

        Log::info('createQuestion', array('method' => 'botCreateNormalQuestion',
            'message' => 'БОТ создал нормальный вопрос', 'step' => $this->getNextStep(),
            'question' => $randomQuestion->id, 'users' => $users_ids,
            'current_user' => Auth::user()->id));
    }

    /******************************** CONQUEST ***********************************/
    private function conquestTerritory($zone, $user_id = NULL) {

        if (is_null($user_id)):
            $user_id = Auth::user()->id;
            $user = $this->user;
        else:
            $user = GameUser::where('game_id', $this->game->id)->where('user_id', $user_id)->first();
        endif;
        if ($this->validGameStatus($this->game_statuses[2])):
            if ($conquest = GameMap::where('game_id', $this->game->id)->where('user_id', '!=', $user_id)->where('zone', $zone)->where('capital', 0)->first()):
                $settings = json_decode($conquest->json_settings);
                $settings->color = $user['color'];
                $conquest->json_settings = json_encode($settings);
                $conquest->user_id = $user_id;
                $conquest->save();
                return TRUE;
            endif;
        endif;
        return FALSE;
    }

    private function conquestCapital($zone, $user_id = NULL) {

        if (is_null($user_id)):
            $user_id = Auth::user()->id;
            $user = $this->user;
        else:
            $user = GameUser::where('game_id', $this->game->id)->where('user_id', $user_id)->first();
        endif;
        if ($this->validGameStatus($this->game_statuses[2]) && $this->validGameStage(2)):
            if ($conquest = GameMap::where('game_id', $this->game->id)->where('user_id', '!=', $user_id)->where('zone', $zone)->where('capital', 1)->first()):
                if ($conquest->lives == 1):

                    Log::info('conquest->lives == 1', array('method' => 'conquestCapital',
                        'message' => 'Пользователь захватил столицу!',
                        'zone' => $zone, 'current_user' => $user_id, 'stage' => $this->game->stage,
                        'owner' => $conquest->user_id));

                    Log::info('disconnectUserInGame', array('method' => 'conquestCapital',
                        'message' => 'Запуск скрипта на исключение пользователя из игры',
                        'remove_user' => $conquest->user_id, 'current_user' => $user_id,
                        'stage' => $this->game->stage));

                    $this->disconnectUserInGame($conquest->user_id, 99, @$user->user_id, @$user->color);
                    foreach (GameMap::where('game_id', $this->game->id)->where('user_id', $conquest->user_id)->get() as $territory):
                        if ($territory->capital == 1):
                            $territory->points = 200;
                        endif;
                        $settings = json_decode($territory->json_settings);
                        $settings->color = $user['color'];
                        $territory->json_settings = json_encode($settings);
                        $territory->user_id = $user_id;
                        $territory->capital = 0;
                        $territory->status = 0;
                        $territory->save();
                        $territory->touch();
                    endforeach;
                    return 0;
                elseif ($conquest->lives > 1):
                    $conquest->lives = $conquest->lives - 1;
                    $conquest->save();
                    $conquest->touch();

                    Log::info('conquest->lives > 1', array('method' => 'conquestCapital',
                        'message' => 'Пользователь уменьшил количество жизней у столицы!',
                        'zone' => $zone, 'current_user' => $user_id, 'stage' => $this->game->stage,
                        'owner' => $conquest->user_id, 'lives' => $conquest->lives));

                    return $conquest->lives;
                endif;
            endif;
        endif;
        return FALSE;
    }

    private function isConqueredTerritories() {

        if ($this->validGameStatus($this->game_statuses[2]) && $this->validGameStage(1)):
            if (GameMap::where('game_id', $this->game->id)->where('user_id', 0)->exists()):
                return FALSE;
            else:
                return TRUE;
            endif;
        endif;
    }

    private function isConqueredCapitals() {

        if ($this->validGameStatus($this->game_statuses[2]) && $this->validGameStage(2)):
            if (GameMap::where('game_id', $this->game->id)->where('capital', 1)->count() == 1):
                return TRUE;
            else:
                return FALSE;
            endif;
        endif;
    }

    private function getDuel() {

        $json_settings = json_decode($this->game->json_settings, TRUE);
        if (isset($json_settings['duel']) && !empty($json_settings['duel'])):
            return $json_settings['duel'];
        else:
            return FALSE;
        endif;
    }

    private function gamerDefenceTerritory($zone = NULL) {

        $duel = $this->getDuel();
        $lives = 1;
        if ($zone && $zone > 0):
            $lives = GameMap::where('game_id', $this->game->id)->where('zone', $zone)->pluck('lives');
        endif;
        $this->changeUserPoints($duel['def'], 100 * $lives);
        $this->changeGameUsersStatus(2);
        $this->reInitGame();
    }

    /******************************** WINNERS ************************************/
    private function setQuizQuestionWinner() {

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
            elseif ($this->validGameStage(2)):
                $duel = $this->getDuel();
                if ($this->game_winners['first_place'][0] == $duel['conqu']):
                    $this->game_winners['second_place'][] = $duel['def'];
                elseif ($this->game_winners['first_place'][0] == $duel['def']):
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

    private function setNormalQuestionWinner() {

        if ($this->game_answers['current_answer'] !== FALSE):
            $this->game_winners = $winners = array();
            foreach ($this->game_answers['answers_titles'] as $user_id => $answers_title):
                $this->game_winners[$user_id] = 2;
                if ($answers_title == $this->game_answers['current_answer']):
                    $winners[$user_id] = @$this->game_answers['answers_times'][$user_id];
                endif;
            endforeach;
            if (count($winners) == 1):
                $users_ids = array_keys($winners);
                $this->game_winners[$users_ids[0]] = 1;
            else:
                $this->game_winners = 'standoff';
            endif;
        endif;
    }

    private function getFirstPlace() {

        $diff_titles = array();
        $diff_seconds = $this->game_answers['answers_times'];
        foreach ($this->game_answers['answers_titles'] as $user_id => $answers_title):
            $diff_titles[$user_id] = abs((int)$answers_title - (int)$this->game_answers['current_answer']);
        endforeach;
        $first_place = $winners = $winner = array();
        asort($diff_titles);
        asort($diff_seconds);
        foreach ($diff_titles as $user_id => $diff_title):
            $winner = array('user_id' => $user_id, 'title' => $diff_title, 'second' => $diff_seconds[$user_id]);
            break;
        endforeach;
        $winners[$winner['user_id']] = $winner;
        foreach ($diff_titles as $user_id => $diff_title):
            if ($winner['title'] == $diff_title):
                if ($diff_seconds[$winner['user_id']] > $diff_seconds[$user_id]):
                    $winners[$winner['user_id']] = FALSE;
                    $winners[$user_id] = $winner = array('user_id' => $user_id, 'title' => $diff_title,
                        'second' => $diff_seconds[$user_id]);
                elseif ($diff_seconds[$winner['user_id']] == $diff_seconds[$user_id]):
                    $winners[$user_id] = $winner = array('user_id' => $user_id, 'title' => $diff_title,
                        'second' => $diff_seconds[$user_id]);
                endif;
            endif;
        endforeach;
        foreach ($winners as $user_id => $winner):
            if ($winner):
                $this->game_winners['first_place'][] = $user_id;
            endif;
        endforeach;
    }

    private function getSecondPlace() {

        $diff_titles = $diff_seconds = array();
        foreach ($this->game_answers['answers_titles'] as $user_id => $answer_title):
            if ($user_id != $this->game_winners['first_place'][0]):
                $diff_titles[$user_id] = abs((int)$answer_title - (int)$this->game_answers['current_answer']);
            endif;
        endforeach;
        foreach ($this->game_answers['answers_times'] as $user_id => $answer_second):
            if ($user_id != $this->game_winners['first_place'][0]):
                $diff_seconds[$user_id] = $answer_second;
            endif;
        endforeach;
        asort($diff_titles);
        $users = array_keys($diff_titles);
        $titles = array_values($diff_titles);
        if (@$titles[0] < @$titles[1]):
            $this->game_winners['second_place'][] = @$users[0];
        elseif (@$titles[0] == @$titles[1]):
            asort($diff_seconds);
            $users = array_keys($diff_seconds);
            $seconds = array_values($diff_seconds);
            if (@$seconds[0] < @$seconds[1]):
                $this->game_winners['second_place'][] = @$users[0];
            elseif (@$seconds[0] == @$seconds[1]):
                $this->game_winners['second_place'] = $users;
            endif;
        else:
            $this->game_winners['second_place'] = array('Вася', 'Петя'); # генерация ничьи
        endif;
    }

    private function getThirdPlace() {

        $winners = array($this->game_winners['first_place'][0], $this->game_winners['second_place'][0]);
        foreach ($this->game_answers['answers_titles'] as $user_id => $answer_title):
            if (!in_array($user_id, $winners)):
                $this->game_winners['third_place'][] = $user_id;
                break;
            endif;
        endforeach;
    }

    private function isStandoff($place) {

        if (count($this->game_winners[$place]) == 1):
            return FALSE;
        else:
            return TRUE;
        endif;
    }

    private function resetQuestions() {

        GameUserQuestions::where('game_id', $this->game->id)->where('status', 1)->update(array('status' => 99));
    }

    private function resetGameUsers() {

        GameUser::where('game_id', $this->game->id)->whereNotIn('status', array(99, 100))->update(array('status' => 0,
            'available_steps' => 0, 'make_steps' => 0, 'updated_at' => date('Y-m-d H:i:s')));
    }

    private function getWinnerByPoints() {

        if ($users_points = GameUser::where('game_id', $this->game->id)->lists('points', 'user_id')):
            arsort($users_points);
            $users = array_keys($users_points);
            $points = array_values($users_points);
            if ($points[0] > $points[1]):
                return $users[0];
            endif;
        endif;
        return FALSE;
    }

    private function closeGameUsersQuestions() {

        GameUserQuestions::where('game_id', $this->game->id)->whereIn('status', array(0,
            1))->update(array('status' => 100));
    }

    private function setGameWinners() {

        if ($users_points = GameUser::where('game_id', $this->game->id)->lists('points', 'user_id')):
            arsort($users_points);
            $users = array_keys($users_points);
            $points = array_values($users_points);
            GameUser::where('game_id', $this->game->id)->update(array('place' => 0));
            $rating = array(0, 0, 0);
            if (count($points) < 3):
                return array();
            endif;
            if ($points[0] == $points[1] && $points[1] == $points[2]):
                // набрано одинаково очков
                GameUser::where('game_id', $this->game->id)->update(array('place' => 1));
                $rating = array(100, 100, 100);
            elseif ($points[0] == $points[1] && $points[1] > $points[2]):
                // 1 и 2 набрали одинаково
                GameUser::where('game_id', $this->game->id)->whereIn('user_id', array($users[0],
                    $users[1]))->update(array('place' => 1));
                GameUser::where('game_id', $this->game->id)->where('user_id', $users[2])->update(array('place' => 2));
                $rating = array(100, 100, 50);
            elseif ($points[0] > $points[1] && $points[1] == $points[2]):
                // 1 набрал больше, 2 и 3 - одинаково
                GameUser::where('game_id', $this->game->id)->where('user_id', $users[0])->update(array('place' => 1));
                GameUser::where('game_id', $this->game->id)->whereIn('user_id', array($users[1],
                    $users[2]))->update(array('place' => 2));
                $rating = array(100, 50, 50);
            elseif ($points[0] > $points[1] && $points[1] > $points[2]):
                // 1 набрал больше, 2 набрал больше 3
                GameUser::where('game_id', $this->game->id)->where('user_id', $users[0])->update(array('place' => 1));
                GameUser::where('game_id', $this->game->id)->where('user_id', $users[1])->update(array('place' => 2));
                GameUser::where('game_id', $this->game->id)->where('user_id', $users[2])->update(array('place' => 3));
                $rating = array(100, 50, 0);
            endif;
            foreach ($users as $index => $user_id):
                $this->changeUserRating($user_id, $rating[$index]);
            endforeach;
        endif;
        return TRUE;
    }

    private function setGameUserAvailableSteps($user_id, $available_steps) {

        GameUser::where('game_id', $this->game->id)->where('user_id', $user_id)->update(array('status' => 0,
            'available_steps' => $available_steps, 'make_steps' => 0,
            'updated_at' => date('Y-m-d H:i:s')));
    }

    private function setGameUserQuestionPlace($user_id, $place) {

        GameUserQuestions::where('game_id', $this->game->id)->where('user_id', $user_id)->where('status', 1)->where('place', 0)
            ->update(array('status' => 2, 'place' => $place, 'updated_at' => date('Y-m-d H:i:s')));
    }

    /****************************** REMOVED USERS *********************************/
    private function disconnectUserInGame($remove_user_id = NULL, $set_status = 100, $set_new_owner = -1, $set_color = 'black') {

        if($this->validGame() === FALSE):
            return FALSE;
        endif;

        if (is_null($remove_user_id)):
            $remove_user_id = Auth::user()->id;
        endif;
        if (is_null($set_new_owner)):
            $set_new_owner = -1;
        endif;

        if ($user = GameUser::where('game_id', $this->game->id)->where('user_id', $remove_user_id)->where('is_bot', 0)->whereNotIn('status', array(99, 100))->first()):
            $user->status = $set_status;
            $user->available_steps = 0;
            $user->make_steps = 0;
            $user->save();
            $user->touch();

            Log::info('user.status', array('method' => 'disconnectUserInGame',
                'message' => 'Пользователю выставился статус ' . $set_status,
                'user' => $remove_user_id, 'status' => $user->status, 'stage' => $this->game->stage));

            foreach (GameMap::where('game_id', $this->game->id)->where('user_id', $remove_user_id)->get() as $zone):
                $zone->user_id = $set_new_owner;
                if ($set_status == 100):
                    $zone->points = 0;
                    $zone->lives = 0;
                    $zone->status = $set_status;
                elseif ($set_status != 100 && $zone->capital == 1):
                    $zone->points = 200;
                    $zone->lives = 1;
                endif;
                $zone->capital = 0;
                $zone->json_settings = '{"color":"' . $set_color . '"}';
                $zone->save();
                $zone->touch();
            endforeach;

            Log::info('stage2_tours', array('method' => 'disconnectUserInGame',
                'message' => 'Новый владелец ' . $set_new_owner,
                'zones_color' => $set_color,
                'user' => $remove_user_id, 'stage' => $this->game->stage));

            if ($this->validGameStage(2)):
                $json_settings = json_decode($this->game->json_settings, TRUE);
                if (isset($json_settings['stage2_tours']) && !empty($json_settings['stage2_tours'])):
                    foreach ($json_settings['stage2_tours'] as $tour => $users_steps):
                        foreach ($users_steps as $user_id => $status):
                            if ($user_id == $remove_user_id):
                                $json_settings['stage2_tours'][$tour][$user_id] = TRUE;
                            endif;
                        endforeach;
                    endforeach;

                    Log::info('stage2_tours', array('method' => 'disconnectUserInGame',
                        'message' => 'Шаги пользователя помечанные как TRUE',
                        'user' => $remove_user_id, 'stage2_tours' => $json_settings['stage2_tours'],
                        'stage' => $this->game->stage));

                    $this->game->json_settings = json_encode($json_settings);
                    $this->game->save();
                    $this->game->touch();

                    $current_user_step = $this->getNextStep();
                    if($current_user_step == $remove_user_id):
                        $this->nextStepInSecondStage();

                        Log::info('nextStepInSecondStage', array('method' => 'disconnectUserInGame',
                            'message' => 'Текущий ход преналдежит исключаемому игроку. Производим переход хода',
                            'user' => $remove_user_id,
                            'nextStep' => $this->getNextStep(),
                            'current_user' => Auth::user()->id,
                            'stage'=>$this->game->stage));

                    endif;
                endif;
            endif;
            return TRUE;
        else:

            Log::info('not validDropUser', array('method' => 'disconnectUserInGame',
                'message' => 'Исключить игрока не удалось. Или он бот, или был исключен из игры раньше',
                'user' => $remove_user_id,
                'stage' => $this->game->stage));

            return FALSE;
        endif;
    }

    private function dropUser($user_game, $remove_in_game = FALSE) {

        if ($this->isBot($user_game->user_id)):
            return FALSE;
        endif;

        if ($remove_in_game === TRUE):
            if ((time() - $user_game->session->last_activity) > Config::get('game.remove_user_timeout_in_game_wait', 15)):
                GameUser::where('game_id', $this->game->id)->where('user_id', $user_game->user_id)->where('is_bot', 0)->delete();

                Log::info('remove_in_game == TRUE', array('method' => 'dropUser',
                    'message' => 'Обнаружился неактивный игрок. Он был удален из игры.',
                    'user_id' => $user_game->user_id,
                    'current_user' => Auth::user()->id));

                $this->reInitGame();
            endif;
        elseif (empty($user_game->session) || !isset($user_game->session->last_activity)):

            Log::info('empty(user_game->session)', array('method' => 'dropUser',
                'message' => 'У пользователя отсутствует данные сессии',
                'user_id' => $user_game->user_id,
                'current_user' => Auth::user()->id));

            $this->transferCurrentStep($user_game);
            $this->disconnectUserInGame($user_game->user_id);
            $this->reInitGame();
        elseif ((time() - $user_game->session->last_activity) > Config::get('game.disconnect_user_timeout', 30)):

            Log::info('time() - last_activity', array('method' => 'dropUser',
                'message' => 'У пользователя истекло время на действия',
                'user_id' => $user_game->user_id,
                'current_user' => Auth::user()->id));

            $this->transferCurrentStep($user_game);
            $this->disconnectUserInGame($user_game->user_id);
            $this->reInitGame();
        endif;
        return TRUE;
    }

    private function droppingGameUsers() {

        $deadUsersCount = $inActiveUsersCount = 0;
        if ($this->validGameStatus($this->game_statuses[0])):
            foreach ($this->game->users as $user_game):
                $this->dropUser($user_game, TRUE);
            endforeach;
        elseif ($this->validGameStatus($this->game_statuses[1]) || $this->validGameStatus($this->game_statuses[2])):
            foreach ($this->game->users as $user_game):
                if ($user_game->status == 100) :
                    $deadUsersCount++;
                elseif ($user_game->status == 99):
                    $inActiveUsersCount++;
                else:
                    $this->dropUser($user_game);
                endif;
            endforeach;
            if ($deadUsersCount >= 2):
                $this->nextStep();
                $this->finishGame(0);

                Log::info('finishGame (1)', array('method' => 'validDropGameUsers',
                    'message' => 'Игра завершилась. Отвалились 2 или более игроков',
                    'current_user' => Auth::user()->id));

                $this->reInitGame();
            endif;

            if ($inActiveUsersCount >= 2):
                $this->nextStep();
                $this->finishGame(1);

                Log::info('finishGame (1)', array('method' => 'validDropGameUsers',
                    'message' => 'Игра завершилась. 2 или более игроков были повержаны в процессе игры и получили 99 статус',
                    'current_user' => Auth::user()->id));

                $this->reInitGame();
            endif;

        endif;
    }

    private function sendDroppedUserAnswers($userGameQuestion) {

        foreach ($this->game->users as $user):
            if (in_array($user->status, array(99, 100))):
                if ($gameQuestion = GameUserQuestions::where('game_id', $this->game->id)->where('group_id', $userGameQuestion->group_id)->where('user_id', $user->id)->first()):
                    $gameQuestion->status = 1;
                    $gameQuestion->answer = 99999;
                    $gameQuestion->seconds = 10;
                    $gameQuestion->save();
                    $gameQuestion->touch();

                    Log::info('droppedUserAnswers', array('method' => 'sendDroppedUserAnswers',
                        'message' => 'Выбывшиц игрок ответил на вопрос', 'user_id' => $user->id,
                        'current_answer' => 'Не известно',
                        'user_answer' => 99999,
                        'current_user' => Auth::user()->id,
                        'stage' => $this->game->stage));

                endif;
            endif;
        endforeach;
    }

    /********************************* OTHER *************************************/
    private function exclude_indexes($capital, $map_places, $map_places_ids) {

        $adjacent_places = Config::get('game.adjacent_places');
        $temp_map_places_ids = $map_places_ids;
        $map_places_ids = array();
        foreach ($temp_map_places_ids as $map_place_id):
            if ($map_place_id != $capital && !in_array($map_places[$map_place_id]->zone, $adjacent_places[$map_places[$capital]->zone])):
                $map_places_ids[$map_place_id] = $map_place_id;
            endif;
        endforeach;
        return $map_places_ids;
    }

    private function getCurrentAnswer($userGameQuestion) {

        $currentAnswer = FALSE;
        if (!empty($userGameQuestion->question) && !empty($userGameQuestion->question->answers)):
            $answers = json_decode($userGameQuestion->question->answers);
            foreach ($answers as $index => $answer):
                if ($answer->current == 1):
                    $currentAnswer = $userGameQuestion->question->type == 'quiz' ? $answer->title : $index;
                    break;
                endif;
            endforeach;
        endif;
        return $currentAnswer;
    }

    private function nextStep($user_id = 0) {

        $json_settings = json_decode($this->game->json_settings, TRUE);
        $json_settings['next_step'] = $user_id;
        $this->game->json_settings = json_encode($json_settings);
        $this->game->save();
        $this->game->touch();
    }

    private function getNextStep() {

        $json_settings = json_decode($this->game->json_settings, TRUE);
        return isset($json_settings['next_step']) ? $json_settings['next_step'] : 0;
    }

    private function nextStepInSecondStage() {

        if ($this->initGame() && $this->validGameStage(2)):
            $json_settings = json_decode($this->game->json_settings, TRUE);
            $current_tour = isset($json_settings['current_tour']) ? $json_settings['current_tour'] : FALSE;
            $stage2_tours = isset($json_settings['stage2_tours']) ? $json_settings['stage2_tours'] : FALSE;

            if ($current_tour === FALSE || $stage2_tours === FALSE):
                $this->nextStep();
                $this->finishGame(0);

                Log::info('finishGame (0)', array('method' => 'createStepInSecondStage',
                    'message' => 'Игра завершилась. Отсутствуют данные current_tour или stage2_tours',
                    'current_user' => Auth::user()->id));

                $this->reInitGame();
            endif;

            $nextTour = TRUE;
            foreach ($stage2_tours[$current_tour] as $user_id => $status):
                if ($status == FALSE):
                    $nextTour = FALSE;
                    break;
                endif;
            endforeach;

            if ($nextTour && $current_tour < 3):
                $current_tour++;

                $json_settings['current_tour'] = $current_tour;
                $this->game->json_settings = json_encode($json_settings);
                $this->game->save();
                $this->game->touch();

                Log::info('current_tour < 3', array('method' => 'nextStepInSecondStage',
                    'message' => 'При переходе хода наступил следующий тур', 'current_tour' => $current_tour,
                    'current_user' => Auth::user()->id));
            else:

                Log::info('current_tour < 3', array('method' => 'nextStepInSecondStage',
                    'message' => 'При переходе хода НЕ наступил следующий тур', 'current_tour' => $current_tour,
                    'current_user' => Auth::user()->id));

            endif;

            if (isset($stage2_tours[$current_tour])):
                if ($current_tour < 3):
                    foreach ($stage2_tours[$current_tour] as $user_id => $status):
                        if ($status == FALSE):
                            $this->nextStep($user_id);
                            break;
                        endif;
                    endforeach;
                elseif ($current_tour == 3):

                    Log::info('current_tour == 3', array('method' => 'nextStepInSecondStage',
                        'message' => 'Наступил 3-й тур',
                        'current_tour' => $current_tour, 'stage' => $this->game->stage));

                    $this->nextStep();
                    $firstStep = TRUE;
                    foreach ($stage2_tours[$current_tour] as $user_id => $status):
                        if ($status == TRUE):
                            $firstStep = FALSE;
                            break;
                        endif;
                    endforeach;
                    if ($firstStep):

                        Log::info('firstStep', array('method' => 'nextStepInSecondStage',
                            'message' => 'Первое вхождение в 3-й тур',
                            'stage' => $this->game->stage));

                        if ($winner = $this->getWinnerByPoints()):
                            Log::info('getWinnerByPoints', array('method' => 'nextStepInSecondStage',
                                'message' => 'Определился победитель по 3м турам',
                                'winner' => $winner,
                                'stage' => $this->game->stage));

                            $this->updateTemplateStepInSecondStage($winner);
                            $this->nextStep($winner);
                        else:
                            $this->randomStep();

                            Log::info('randomStep', array('method' => 'nextStepInSecondStage',
                                'message' => 'Победитель по 3м турам не определился. Выбираем случайного',
                                'winner' => $winner,
                                'stage' => $this->game->stage));

                            $this->updateTemplateStepInSecondStage($this->getNextStep());
                        endif;
                    else:
                        foreach ($stage2_tours[$current_tour] as $user_id => $status):
                            if ($status == FALSE):
                                $this->nextStep($user_id);
                                break;
                            endif;
                        endforeach;
                    endif;
                endif;
            endif;
        endif;
    }

    private function getAvailableSteps($user_id, $place) {

        $available_steps = 0;
        if ($this->validGameStage(1)):
            $available_steps = abs($place - 3);
        elseif ($this->validGameStage(2)):
            $duel = $this->getDuel();
            if ($place == 1 && $duel['conqu'] == $user_id):
                $available_steps = 1;
            elseif ($place == 1 && $duel['def'] == $user_id):
                $available_steps = -1;
            endif;
        endif;
        return $available_steps;
    }

    private function getTerritoryPoints($zone) {

        if ($this->validGameStatus($this->game_statuses[2])):
            foreach ($this->game->map_places as $map):
                if ($map->zone == $zone):
                    return $map->points;
                endif;
            endforeach;
        endif;
        return FALSE;
    }

    private function getTerritoryLives($zone) {

        if ($this->validGameStatus($this->game_statuses[2])):
            foreach ($this->game->map_places as $map):
                if ($map->zone == $zone):
                    return $map->lives;
                endif;
            endforeach;
        endif;
        return FALSE;
    }

    private function getGameBotsIDs() {

        $botsIDs = array();
        if ($this->validGameStatus($this->game_statuses[2])):
            if (isset($this->game->users) && !empty($this->game->users)):
                foreach ($this->game->users as $game_user):
                    if ($this->isBot($game_user->user_id)):
                        $botsIDs[] = $game_user->user_id;
                    endif;
                endforeach;
            endif;
        endif;
        return $botsIDs;
    }

    private function getAdjacentPlaces($user_id = NULL) {

        if (is_null($user_id)):
            $user_id = Auth::user()->id;
        endif;
        if ($this->validGameStatus($this->game_statuses[2])):
            $adjacent_places = Config::get('game.adjacent_places');
            $game_zones = $territories = array();
            foreach ($this->game->map_places as $zone):
                $game_zones[$zone->zone] = $zone;
            endforeach;
            if (empty($game_zones)):
                return FALSE;
            endif;
            foreach ($game_zones as $zone):
                if ($zone->user_id == $user_id):
                    if (isset($adjacent_places[$zone->zone]) && !empty($adjacent_places[$zone->zone])):
                        foreach ($adjacent_places[$zone->zone] as $adjacent_place):
                            if (isset($game_zones[$adjacent_place]) && $game_zones[$adjacent_place]->user_id != $user_id):
                                $territories[$game_zones[$adjacent_place]->zone] = $game_zones[$adjacent_place]->toArray();
                            endif;
                        endforeach;
                    endif;
                endif;
            endforeach;
            return $territories;
        endif;
        return FALSE;
    }

    private function finishGameInFourTour() {

        if ($this->validGameStage(2)):
            $json_settings = json_decode($this->game->json_settings, TRUE);
            if (isset($json_settings['current_tour']) && isset($json_settings['stage2_tours'])):
                $current_tour = $json_settings['current_tour'];
                $stage2_tours = $json_settings['stage2_tours'];
                if ($current_tour == 3):
                    $all_steps = TRUE;
                    foreach ($stage2_tours[$current_tour] as $user_id => $status):
                        if ($status == FALSE):
                            $all_steps = FALSE;
                            break;
                        endif;
                    endforeach;
                    if ($all_steps && !$this->validAvailableSteps() && $this->validUsersStatus(2)):
                        $this->nextStep();
                        $this->finishGame(1);

                        Log::info('finishGame (1)', array('method' => 'finishGameInFourTour',
                            'message' => 'Игра завершилась. Пользователи прошли все 4 этапа игры',
                            'current_user' => Auth::user()->id));

                        $this->reInitGame();
                    endif;
                endif;
            endif;
        endif;
    }

    private function getRating($dateBegin = FALSE) {

        if ($dateBegin):
            $rating_list = GameUserRating::where('rating', '>', 0)->where('created_at', '>=', $dateBegin)->with('game.users', 'user')->orderBy('rating', 'DESC')->orderBy('updated_at', 'DESC')->get();
        else:
            $rating_list = GameUserRating::where('rating', '>', 0)->with('game.users', 'user')->orderBy('rating', 'DESC')->orderBy('updated_at', 'DESC')->get();
        endif;
        $rating = array();
        foreach ($rating_list as $user_rating):
            $rating[$user_rating->user_id]['user_id'] = $user_rating->user_id;
            $rating[$user_rating->user_id]['user_name'] = isset($user_rating->user->name) ? $user_rating->user->name : 'No name';
            $rating[$user_rating->user_id]['wins'] = 0;
            $rating[$user_rating->user_id]['rating'] = 0;
        endforeach;
        foreach ($rating_list as $user_rating):
            $rating[$user_rating->user_id]['rating'] += $user_rating->rating;
            if (isset($user_rating->game->users) && !empty($user_rating->game->users)):
                foreach ($user_rating->game->users as $game_user):
                    if ($game_user->user_id == $user_rating->user_id && $game_user->place == 1):
                        $rating[$user_rating->user_id]['wins']++;
                    endif;
                endforeach;
            endif;
        endforeach;
        $sort_array = array();
        $sort_rating = array();
        foreach ($rating as $user_id => $user_rating):
            $sort_array[$user_id] = $user_rating['rating'];
        endforeach;
        arsort($sort_array);
        foreach ($sort_array as $user_id => $rating_value):
            $sort_rating[$user_id] = $rating[$user_id];
        endforeach;
        usort($sort_rating, function ($one, $two) {
            if ($one['rating'] == $two['rating']) return 0;
            return ($one['rating'] < $two['rating']) ? 1 : -1;
            if (strtotime($one['wins']) == strtotime($two['wins'])) return 0;
            return (strtotime($one['wins']) < strtotime($two['wins'])) ? 1 : -1;
        });
        $rating = $sort_rating;
        return $rating;
    }

    private function getUsersIDs() {

        $users_ids = array();
        foreach ($this->game->users as $user):
            $users_ids[] = $user->id;
        endforeach;
        return $users_ids;
    }

    private function transferCurrentStep($user_game){

        if ($this->validGameStage(1)):
            if ($user_game->available_steps == 2 && $user_game->status == 0):
                GameUser::where('game_id', $this->game->id)->where('user_id', $user_game->user_id)
                    ->where('available_steps', 2)->where('status', 0)
                    ->update(array('status' => 1, 'make_steps' => 2));

                Log::info('available_steps == 2', array('method' => 'transferCurrentStep',
                    'message' => 'У исключаемого пользователя есть 2 доступных очка хода. Делаем что он их использовал',
                    'user_id' => $user_game->user_id,
                    'current_user' => Auth::user()->id,
                    'stage'=>$this->game->stage));

                if($user_id = GameUser::where('game_id', $this->game->id)->where('status', 0)->where('available_steps', 1)->pluck('user_id')):
                    $this->nextStep($user_id);

                    Log::info('nextStep', array('method' => 'transferCurrentStep',
                        'message' => 'Переход хода предоставляется следующему победителю. У него 1 очко хода',
                        'nextStep' => $this->getNextStep(),
                        'current_user' => Auth::user()->id,
                        'stage'=>$this->game->stage));

                    if ($this->isBot($user_id)):

                        Log::info('isBot', array('method' => 'dropUser',
                            'message' => 'Переход хода предоставляется боту победителю. У него 1 очко хода',
                            'bot_id' => $user_id,
                            'current_user' => Auth::user()->id,
                            'stage'=>$this->game->stage));

                        $this->botConquestTerritory($user_id);
                        $this->nextStep();

                        Log::info('nextStep', array('method' => 'dropUser',
                            'message' => 'Сброс перехода хода.',
                            'current_user' => Auth::user()->id,
                            'stage'=>$this->game->stage));

                    endif;
                else:
                    $this->nextStep();

                    Log::info('nextStep', array('method' => 'transferCurrentStep',
                        'message' => 'Переход хода не предоставляется следующему победителю. Игрока с  1 очком хода не найдено',
                        'nextStep' => $this->getNextStep(),
                        'current_user' => Auth::user()->id,
                        'stage'=>$this->game->stage));

                endif;
            elseif ($user_game->available_steps == 1 && $user_game->status == 0):

                GameUser::where('game_id', $this->game->id)->where('user_id', $user_game->user_id)
                    ->where('available_steps', 1)->where('status', 0)
                    ->update(array('status' => 1, 'make_steps' => 1));

                Log::info('available_steps == 1', array('method' => 'transferCurrentStep',
                    'message' => 'У исключаемого пользователя есть 1 доступное очко хода. Делаем что он их использовал',
                    'user_id' => $user_game->user_id,
                    'current_user' => Auth::user()->id,
                    'stage'=>$this->game->stage));

                if (GameUser::where('game_id', $this->game->id)->where('status', 1)->where('available_steps', 2)->exists()):

                    Log::info('exists available_steps == 2', array('method' => 'transferCurrentStep',
                        'message' => 'Нашли игрока, у которого available_steps = 2 (победителя предыдущего шага). Сбрасываем ход.',
                        'current_user' => Auth::user()->id,
                        'stage'=>$this->game->stage));

                    $this->nextStep();
                endif;
            endif;
        elseif($this->validGameStage(2)):
            GameUserQuestions::where('game_id', $this->game->id)->where('user_id', $user_game->id)->where('status', 0)
                ->update(array('status' => 1, 'answer' => 99999, 'seconds' => 10));

            Log::info('validGameStage == 2', array('method' => 'transferCurrentStep',
                'message' => 'Отвечаем на все неотвеченные вопросы исключаемого пользователя',
                'current_user' => Auth::user()->id,
                'stage'=>$this->game->stage));

        endif;
    }
}