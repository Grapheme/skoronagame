<?php

class GameController extends BaseController {

    public static $name = 'game';
    public static $group = 'application';

    private $game;
    private $user;
    private $leader;
    private $game_statuses = array('wait', 'start', 'ready', 'over');
    private $game_answers;
    private $game_winners = array();
    private $json_request = array('status' => FALSE, 'responseJSON' => '', 'responseText' => '', 'redirect' => FALSE);

    /****************************************************************************/
    public function __construct() {

    }

    /****************************************************************************/
    public static function returnRoutes() {

        $class = __CLASS__;
        Route::controller('password', 'RemindersController');
        Route::group(array('before' => 'login', 'prefix' => ''), function () use ($class) {
            Route::get('login/{user_id}', array('uses' => $class . '@supperQuickAuth'));
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
    public function supperQuickAuth($user_id) {

        Auth::loginUsingId($user_id);
        return Redirect::to(AuthAccount::getStartPage());
    }

    public function QuickAuth() {

        if (!Request::ajax()) return App::abort(404);
        $validator = Validator::make(Input::all(), array('email' => 'required|email', 'password' => 'required'));
        if ($validator->passes()):
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
                    $this->game = Game::where('id', $game->id)->with('users', 'users.user_social', 'users.session', 'map_places')->first();
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

        $games = Game::where('status', '!=', $this->game_statuses[3])->with(array('users' => function ($query) {
            $query->where('user_id', Auth::user()->id);
        }))->get();
        if (count($games)):
            foreach ($games as $game):
                if ($game->users->count()):
                    $this->game = Game::where('id', $game->id)->with('users', 'users.user_social', 'users.session', 'map_places')->first();
                    break;
                endif;
            endforeach;
        endif;
        return View::make(Helper::acclayout('demo'), array('game' => $this->game));
    }

    /****************************************************************************/
    private function initGame() {

        if (!is_null($this->game) && is_object($this->game)):
            return TRUE;
        elseif (Input::has('game') && Input::get('game') > 0):
            $this->game = Game::where('id', Input::get('game'))->with('users', 'users.user_social', 'users.session', 'map_places')->first();
            $this->user = GameUser::where('game_id', Input::get('game'))->where('user_id', Auth::user()->id)->first();
            $this->leader = GameUser::where('game_id', Input::get('game'))->where('leader', 1)->first();
            if (Config::get('game.new_game_log') === TRUE):
                $fileLogName = 'game-log-' . $this->game->id . '.log';
                Log::useFiles(storage_path() . '/logs/' . $fileLogName);
            endif;
            return ($this->user) ? TRUE : FALSE;
        else:
            $this->game = 0;
            return FALSE;
        endif;
    }

    private function reInitGame() {

        if (isset($this->game->id)):
            $this->game = Game::where('id', $this->game->id)->with('users', 'users.user_social', 'users.session', 'map_places')->first();
            $this->user = GameUser::where('game_id', $this->game->id)->where('user_id', Auth::user()->id)->first();
        endif;
    }

    private function setLog($current_method, $method_before = '', $message = '', $data = array()) {

        $make_log = FALSE;
        $make_log_stage = Config::get('game.make_log_stage');

        if ($make_log_stage == 0):
            $make_log = TRUE;
        elseif ($this->game->stage == $make_log_stage):
            $make_log = TRUE;
        endif;
        if (Config::get('game.make_log') && $make_log):
            Log::info($method_before,
                array(
                    'method' => $current_method,
                    'message' => $message,
                    'data' => $data,
                    'current_user' => Auth::user()->id,
                    'current_step' => $this->getNextStep(),
                    'game_stage' => $this->game->stage,
                    'game_tour' => $this->getCurrentTourInSecondStage(),
                    'game_id' => $this->game->id
                )
            );
        endif;
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
            $newGamer = GameUser::create(array('game_id' => $this->game->id, 'user_id' => Auth::user()->id,
                'leader' => 0, 'is_bot' => 0, 'status' => 0,
                'available_steps' => 0, 'make_steps' => 0, 'color' => NULL,
                'points' => 0, 'json_settings' => json_encode(array())));
            $this->game->users[] = $newGamer;
            $this->leader = $this->nextGameLeader($newGamer->user_id);
            Sessions::setUserLastActivity();
            $this->setLog('joinNewGame', 'setUserLastActivity', 'Пользователь подключился к игре и стал новым лидером');
            if (!empty($this->game->map_places)):
                GameMap::where('game_id', $this->game->id)->delete();
            endif;
        endif;
    }

    private function startGame() {

        if (isset($this->game->users) && count($this->game->users) == Config::get('game.number_participants')):
            $this->game->status_begin = 1;
            $this->game->date_begin = Carbon::now()->format('Y-m-d H:i:s');
            $this->game->save();
            $this->game->touch();

            $this->randomUsersColor();
            $this->reInitGame();
            $this->createGameMap();
            $this->randomDistributionCapital();
            $this->randomStep();
            $this->reInitGame();

            $this->changeGameStatus($this->game_statuses[1]);
            $this->changeGameStage(1);
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

    /********************************* JSON *************************************/
    public function getGame() {

        if (!Request::ajax()) return App::abort(404);
        if (!$this->initGame()):
            if (!$this->hasCreatedGame()):
                $this->createNewGame();
            else:
                $this->joinNewGame();
                $this->reInitGame();
                $this->droppingNewGameUsers();
                $this->reInitGame();
                $this->startGame();
                $this->reInitGame();
            endif;
        endif;
        if ($this->validGameLeader()):
            $this->isBotNextStepStage2();
            $this->finishGameInFourTour();
            $this->droppingGameUsers();
        endif;
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

                            $this->setLog('overGame', 'finishGame (0)', 'Игра завершилась. Пользователь завершил игру');

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

                $this->setLog('getQuizQuestion', 'nextStep', 'Запрос квиз вопрса. Сброс хода');

                if (!GameUserQuestions::where('game_id', $this->game->id)->where('status', 0)->exists()):
                    $this->changeGameStatus($this->game_statuses[2]);
                    $this->resetGameUsers();

                    $this->setLog('getQuizQuestion', 'resetGameUsers', 'Сброс параметров игроков');

                    $randomQuestion = $this->randomQuestion('quiz');
                    $this->createQuestion($randomQuestion->id, Input::get('users'));

                    $this->setLog('getQuizQuestion', 'createQuestion', 'Пользователь создал квиз-вопрос', array('question' => $randomQuestion->id,
                        ' users' => Input::get('users')));

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

                            $this->setLog('getNormalQuestion', 'resetGameUsers', 'Сброс параметров игроков');

                            if ($createStepInSecondStage === TRUE):
                                $this->createStepInSecondStage();

                                $this->setLog('getNormalQuestion', 'createStepInSecondStage', 'Дуели не существует. Пользователь совершил ход');

                            else:

                                $this->setLog('getNormalQuestion', 'createStepInSecondStage', 'Дуель существует. Пользователь не совершает ход', array('duel' => $duel));

                            endif;

                            $this->setStepInSecondStageJSON();
                            $randomQuestion = $this->randomQuestion('normal');
                            $this->createQuestion($randomQuestion->id, Input::get('users'));

                            $this->setLog('getNormalQuestion', 'createQuestion', 'Пользователь создал нормальный вопрос', array('question' => $randomQuestion->id,
                                'users' => Input::get('users')));

                            $this->createDuel(Input::get('users'), Input::get('zone'));

                            $this->setLog('getNormalQuestion', 'createQuestion', 'Пользователь создал дуель и установил зону для нападения', array('duel' => $this->getDuel(),
                                'zone' => Input::get('zone'), 'users' => Input::get('users')));

                            $this->nextStep();

                            $this->setLog('getNormalQuestion', 'nextStep', 'Обнуляем ход. Получаем нормальный вопрос');

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

                    $this->setLog('sendAnswerQuestion', 'answer', 'Пользователь дал ответ', array('question' => Input::get('question'),
                        'answer' => $userGameQuestion->answer));

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

        if (!Request::ajax()) return App::abort(404);
        $validation = Validator::make(Input::all(), array('question' => 'required', 'type' => 'required',
            'zone' => ''));
        if ($validation->passes()):
            if ($this->initGame()):
                $post = Input::all();
                $number_participants = $this->getNumberParticipants();
                $hasWinnersCalculate = $this->validWinnersCalculate($post);
                if ($hasWinnersCalculate && GameUserQuestions::where('game_id', $this->game->id)->where('status', 1)->count() == $number_participants):
                    $this->setLog('getResultQuestion', 'number_participants', 'Все игроки дали ответы', array('number_participants' => $number_participants));
                    $current_answer = FALSE;
                    foreach (GameUserQuestions::where('game_id', $this->game->id)->where('status', 1)->with('question')->get() as $userGameQuestion):
                        $this->game_answers['current_answer'] = $this->getCurrentAnswer($userGameQuestion);
                        $this->game_answers['answers_titles'][$userGameQuestion->user_id] = $userGameQuestion->answer;
                        $this->game_answers['answers_times'][$userGameQuestion->user_id] = $userGameQuestion->seconds;
                    endforeach;
                    if ($this->game_answers['current_answer'] !== FALSE && !empty($this->game_answers['answers_titles']) && !empty($this->game_answers['answers_times'])):
                        if ($post['type'] == 'quiz'):
                            $this->setQuizQuestionWinner();
                            $this->setLog('getResultQuestion', 'setQuizQuestionWinner', 'Определились победители на квиз-вопрос.', array('winners' => $this->game_winners));
                        elseif ($post['type'] == 'normal'):
                            $this->setNormalQuestionWinner();
                            $this->setLog('getResultQuestion', 'setNormalQuestionWinner', 'Определились победители на нормальный вопрос.', array('winners' => $this->game_winners));
                        endif;
                        if ($this->game_winners === 'standoff'):
                            $this->resetQuestions();
                            $this->setLog('getResultQuestion', 'resetQuestions', 'Случилась ничья');
                        elseif (!empty($this->game_winners) && is_array($this->game_winners) && count($this->game_winners) == $number_participants):
                            foreach ($this->game_winners as $user_id => $place):
                                $this->setGameUserQuestionPlace($user_id, $place);
                                $available_steps = $this->getAvailableSteps($user_id, $place);
                                if ($available_steps < 0):
                                    $this->gamerDefenceTerritory($post['zone']);
                                    $this->closeGameUsersQuestions();
                                    $this->resetGameUsers();
                                    $this->createDuel();
                                    $available_steps = 0;
                                    $this->setLog('getResultQuestion', 'gamerDefenceTerritory', 'В дуели победил защищающийся', array('user_id' => $user_id,
                                        'steps' => $available_steps));
                                    $this->nextStepInSecondStage();
                                    $this->setLog('getResultQuestion', 'nextStepInSecondStage', 'В дуели победил защищающийся. Переход хода');
                                    $this->setStepInSecondStageJSON();
                                endif;
                                $this->setGameUserAvailableSteps($user_id, $available_steps);
                                $this->setLog('getResultQuestion', 'setGameUserAvailableSteps', 'Установили доступные шаги для пользователя', array('user_id' => $user_id,
                                    'steps' => $available_steps));
                            endforeach;
                            if ($this->validGameStage(1)):
                                if ($this->validGameBots()):
                                    $this->setLog('getResultQuestion', 'isBotNextStepStage1', 'Запуск скрипта шагов ботов на 1-м этапе');
                                    $this->isBotNextStepStage1();
                                endif;
                            elseif ($this->validGameStage(2)):
                                $this->setLog('getResultQuestion', 'ЗАХВАТ ТЕРРИТОРИИ', 'ИГРОК ПЫТАЕТСЯ ЗАХВАТИТЬ ТЕРРИИТОРИЮ', array('zone' => $post['zone']));
                                if ($duel = $this->getDuel()):
                                    $this->setLog('getResultQuestion', 'getDuel', 'Получаем текущую дуель', array('duel' => $this->getDuel(),
                                        'zone' => $this->getConquestZone()));
                                    if (GameUser::where('game_id', $this->game->id)->where('user_id', $duel['conqu'])->where('available_steps', 1)->exists()):
                                        $this->setLog('getResultQuestion', 'available_steps 1', 'Побелитель в дуели. Нападающий', array('conqu' => $duel['conqu']));
                                        $this->nextStep($duel['conqu']);
                                        $this->setLog('getResultQuestion', 'nextStep', 'Переход хода побидителю');
                                        if ($this->validCapitalZone($post['zone'])):
                                            $this->setLog('getResultQuestion', 'validCapitalZone', 'Побидитель напал на столицу', array('zone' => $post['zone']));
                                        else:
                                            $this->setLog('getResultQuestion', 'validCapitalZone', 'Побидитель напал на обычную территорию', array('zone' => $post['zone']));
                                        endif;
                                    else:
                                        $this->setLog('getResultQuestion', 'available_steps 0', 'Побелитель в дуели. Защищающийся', array('def' => $duel['def']));
                                        $this->closeGameUsersQuestions();
                                        $this->resetGameUsers();
                                        $this->setLog('getResultQuestion', 'resetGameUsers', 'Сброс параметров игроков');
                                        $this->createDuel();
                                        $this->nextStepInSecondStage();
                                        $this->setLog('getResultQuestion', 'nextStepInSecondStage', 'Защищающийся победил. Следующий ходит');
                                        $this->setStepInSecondStageJSON();
                                    endif;
                                endif;
                            endif;
                        else:
                            $this->game_winners = 'standoff';
                            $this->resetQuestions();
                            $this->setLog('getResultQuestion', 'resetQuestions', 'Случилась ничья');
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
                    $this->setLog('getResultQuestion', 'game_winners', 'Опеределились победители', array('places' => $this->json_request['responseText']));
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
                                $users_answers[$users_question->user_id] = array(
                                    'type' => $post['type'],
                                    'answer' => $users_question->answer,
                                    'user_answer_index' => $users_question->answer,
                                    'current_answer_index' => $current_answer_index,
                                    'correctly' => $correctly,
                                    'seconds' => $users_question->seconds,
                                    'place' => $users_question->place,
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
                                $users_answers[$users_question->user_id] = array(
                                    'type' => $post['type'],
                                    'answer' => $answer_title,
                                    'user_answer_index' => $users_question->answer,
                                    'current_answer_index' => $current_answer_index,
                                    'correctly' => (int)$answer_current,
                                    'seconds' => $users_question->seconds,
                                    'place' => $users_question->place,
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

                            $this->setLog('sendConquestTerritory', 'conquestTerritory', 'Игрок захватил смежную террирорию!', array('zone' => $zone_conquest,
                                'gamer' => $this->user->id));

                            $points = $this->getTerritoryPoints($zone_conquest);
                            $this->changeUserPoints(Auth::user()->id, $points, $this->user);
                            if ($this->user->status == 1):
                                if ($this->user->available_steps == 2):
                                    $user_id = GameUser::where('game_id', $this->game->id)->where('status', 0)->where('available_steps', 1)->pluck('user_id');
                                    $this->nextStep($user_id);

                                    $this->setLog('sendConquestTerritory', 'available_steps == 2', 'Ход переходит игроку занявшему 2-е место');

                                    if ($this->isBot($user_id)):
                                        $this->botConquestTerritory($user_id);
                                        $this->nextStep();

                                        $this->setLog('sendConquestTerritory', 'botConquestTerritory', 'Бот занявший 2-е место выбрал территории. Ход сбрасывается.');

                                    endif;
                                else:
                                    $this->nextStep();

                                    $this->setLog('sendConquestTerritory', 'available_steps != 2', 'Ход сбрасывается. Походил игрок занявший 2-е место.');

                                endif;
                            endif;
                            if ($this->isConqueredTerritories()):

                                $this->setLog('sendConquestTerritory', 'isConqueredTerritories', 'Все территории захвачены');

                                $this->closeGameUsersQuestions();
                                $this->changeGameStage(2);

                                $this->setLog('sendConquestTerritory', 'changeGameStage', 'Игрок перевел игру на 2й этап');

                                $this->closeGameUsersQuestions();
                                $this->resetGameUsers();

                                $this->setLog('sendConquestTerritory', 'resetGameUsers', 'Сброс параметров игроков');

                                $nextStep = $this->createTemplateStepInSecondStage();
                                $this->nextStep($nextStep);

                                $this->setLog('sendConquestTerritory', 'createTemplateStepInSecondStage', 'Пользователь создал шаблон шагов для 2-го этапа');

                                $this->setStepInSecondStageJSON();
                            endif;
                            $this->json_request['responseText'] = 'Вы заняли территорию.';
                            $this->json_request['status'] = TRUE;
                        endif;
                    else:

                        $this->setLog('sendConquestTerritory', 'changeGameUsersSteps. ERROR', 'Захват территории не удался. Нет доступных очков хода', array('zone_conquest' => $zone_conquest,
                            'available_steps' => $this->user->available_steps,
                            'make_steps' => $this->user->make_steps));

                    endif;
                elseif ($this->validGameStage(2)):
                    if ($this->changeGameUsersSteps()):
                        if ($this->conquestTerritory($zone_conquest)):
                            $this->closeGameUsersQuestions();
                            $this->resetGameUsers();
                            $this->createDuel();

                            $this->setLog('sendConquestTerritory', 'conquestTerritory', 'Пользователь захватил территорию', array('zone_conquest' => $zone_conquest));

                            $points = $this->getTerritoryPoints($zone_conquest);
                            $this->changeUserPoints(Auth::user()->id, $points, $this->user);
                            $this->changeTerritoryPoints($zone_conquest, 200);
                            $this->nextStepInSecondStage();

                            $this->setLog('sendConquestTerritory', 'nextStepInSecondStage', 'Территория захвачена. Переход хода');

                            $this->setStepInSecondStageJSON();
                            $this->changeGameUsersStatus(2);

                            $this->setLog('sendConquestTerritory', 'changeGameUsersStatus(2)', 'Все пользователям статус 2');

                            $this->json_request['responseText'] = 'Вы заняли территорию.';
                            $this->json_request['status'] = TRUE;
                        endif;
                    else:

                        $this->setLog('sendConquestTerritory', 'changeGameUsersSteps. ERROR', 'Захват территории не удался. Нет доступных очков хода', array('zone_conquest' => $zone_conquest,
                            'user' => $this->user));

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

                        $this->setLog('sendConquestCapital', 'changeGameUsersSteps', 'У пользователя отобралось доступное очко хода');
                        $this->setLog('sendConquestCapital', 'conquestCapital', 'Запуск скрипта захвата столицы');

                        $capitalLives = $this->conquestCapital($zone_conqueror);
                        if ($capitalLives === 0):
                            $this->closeGameUsersQuestions();
                            $this->resetGameUsers();

                            $this->setLog('sendConquestCapital', 'resetGameUsers. capitalLives === 0', 'Сброс параметров игроков');

                            $this->createDuel();
                            $this->changeUserPoints(Auth::user()->id, 1000);
                            $this->nextStepInSecondStage();

                            $this->setLog('sendConquestCapital', 'nextStepInSecondStage', 'Столица захвачена. Переход хода');

                            $this->setStepInSecondStageJSON();
                            $this->changeGameUsersStatus(2);

                            $this->setLog('sendConquestCapital', 'changeGameUsersStatus(2)', 'Все пользователям статус 2');

                            $this->json_request['conquest_result'] = 'success';
                            $this->json_request['responseText'] = 'Вы заняли столицу.';
                            $this->json_request['status'] = TRUE;

                            if ($this->isConqueredCapitals()):
                                $this->nextStep();
                                $this->finishGame(1);

                                $this->setLog('sendConquestCapital', 'isConqueredCapitals. finishGame (1)', 'Игра завершилась. Осталась одна столица');
                            endif;
                        elseif ($capitalLives > 0):
                            $this->closeGameUsersQuestions();
                            $this->resetGameUsers();

                            $this->setLog('sendConquestCapital', 'resetGameUsers. capitalLives > 0', 'Сброс параметров игроков');
                            $this->setLog('sendConquestCapital', 'capitalLives > 0', 'У столицы остались жизни', array('capitalLives' => $capitalLives,
                                'zone' => $zone_conqueror));

                            $this->nextStep($this->user->user_id);

                            $this->setLog('sendConquestCapital', 'capitalLives > 0', 'Оставляем ход нападающему на столицу');

                            $this->json_request['conquest_result'] = 'retry';
                            $this->json_request['responseText'] = 'Продолжайте захват столицы';
                            $this->json_request['status'] = TRUE;
                        endif;
                    else:

                        $this->setLog('sendConquestCapital', 'changeGameUsersSteps. ERROR', 'Захват столицы пользователем не удался. Нет доступных очков хода', array('zone_conquest' => $zone_conqueror));

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

    /******************************* STEPS *************************************/
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
        $this->reInitGame();
    }

    private function createStepInSecondStage($userID = NULL) {

        if ($this->validGameStage(2)):
            $json_settings = json_decode($this->game->json_settings, TRUE);
            $current_tour = isset($json_settings['current_tour']) ? $json_settings['current_tour'] : FALSE;
            $stage2_tours = isset($json_settings['stage2_tours']) ? $json_settings['stage2_tours'] : FALSE;
            if (is_null($userID)):
                $userID = Auth::user()->id;
            endif;
            $current_tour = $this->nextTourInSecondStage($current_tour, $stage2_tours);
            if (isset($stage2_tours[$current_tour][$userID]) && $stage2_tours[$current_tour][$userID] == FALSE):
                $stage2_tours[$current_tour][$userID] = TRUE;
                $this->setLog('createStepInSecondStage', 'user_step == TRUE', 'Зафиксировался шаг игрока', array('current_tour' => $current_tour,
                    'user' => $userID));
            endif;
            $json_settings['stage2_tours'] = $stage2_tours;
            $this->game->json_settings = json_encode($json_settings);
            $this->game->save();
            $this->game->touch();
            $this->reInitGame();

            $this->nextTourInSecondStage($current_tour, $stage2_tours);
        endif;
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
            endforeach;
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

            $json_settings['stage2_tours'][3] = array();
        endif;

        $this->game->json_settings = json_encode($json_settings);
        $this->game->save();
        $this->game->touch();
        $this->reInitGame();
        reset($json_settings['stage2_tours'][0]);
        return array_keys($json_settings['stage2_tours'][0])[0];
    }

    private function updateTemplateStepInSecondStage($first_step) {

        $step_values[$first_step] = FALSE;
        if (GameUser::where('game_id', $this->game->id)->where('user_id', $first_step)->whereIn('status', array(99,100))->exists()):
            $step_values[0] = TRUE;
        endif;
        $user_ids = GameUser::where('game_id', $this->game->id)->where('user_id', '!=', $first_step)->lists('status', 'user_id');
        foreach ($user_ids as $user_id => $status):
            $step_values[$user_id] = FALSE;
            if (in_array($status, array(99, 100))):
                $step_values[$user_id] = TRUE;
            endif;
        endforeach;
        $current_tour = $this->getCurrentTourInSecondStage();
        $json_settings = json_decode($this->game->json_settings, TRUE);
        foreach($step_values as $user_id => $status):
            $json_settings['stage2_tours'][$current_tour][$user_id] = $status;
        endforeach;
        $this->setLog('updateTemplateStepInSecondStage', '', 'Произошло обновление шагов в 3 туре',array('stage2_tours'=>$json_settings, 'current_tour' => $current_tour));
        $this->game->json_settings = json_encode($json_settings);
        $this->game->save();
        $this->game->touch();
        $this->reInitGame();
    }

    private function nextStep($user_id = 0) {

        $json_settings = json_decode($this->game->json_settings, TRUE);
        $json_settings['next_step'] = $user_id;
        $this->game->json_settings = json_encode($json_settings);
        $this->game->save();
        $this->game->touch();
        $this->reInitGame();
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
            if ($this->validCurrentTourInSecondStage(4)):
                $this->setLog('nextStepInSecondStage', 'validCurrentTourInSecondStage', 'Переход хода отменено. Текущий номер тура - 5');
                return FALSE;
            endif;
            $current_tour = $this->nextTourInSecondStage($current_tour, $stage2_tours);
            if (isset($stage2_tours[$current_tour])):
                foreach ($stage2_tours[$current_tour] as $user_id => $status):
                    if ($status == FALSE):
                        $this->nextStep($user_id);
                        break;
                    endif;
                endforeach;
            else:
                $this->setLog('nextStepInSecondStage', 'stage2_tours[current_tour]. ERROR', 'Не существует текущего этапа', array('current_tour' => $current_tour));
            endif;
        endif;
    }

    private function transferCurrentStep($user_game) {

        if ($this->validGameStage(1)):
            if ($user_game->available_steps == 2 && $user_game->status == 0):
                GameUser::where('game_id', $this->game->id)->where('user_id', $user_game->user_id)
                    ->where('available_steps', 2)->where('status', 0)
                    ->update(array('status' => 1, 'make_steps' => 2));

                $this->reInitGame();

                $this->setLog('transferCurrentStep', 'available_steps == 2', 'У исключаемого пользователя есть 2 доступных очка хода. Делаем что он их использовал', array('user' => $user_game->user_id));

                if ($user_id = GameUser::where('game_id', $this->game->id)->where('status', 0)->where('available_steps', 1)->pluck('user_id')):
                    $this->nextStep($user_id);

                    $this->setLog('transferCurrentStep', 'nextStep', 'Переход хода предоставляется следующему победителю. У него 1 очко хода');

                    if ($this->isBot($user_id)):

                        $this->setLog('transferCurrentStep', 'isBot', 'Переход хода предоставляется боту победителю. У него 1 очко хода', array('bot' => $user_id));

                        $this->botConquestTerritory($user_id);
                        $this->nextStep();

                        $this->setLog('transferCurrentStep', 'nextStep', 'Сброс перехода хода');

                    endif;
                else:
                    $this->nextStep();

                    $this->setLog('transferCurrentStep', 'nextStep', 'Переход хода не предоставляется следующему победителю. Игрока с  1 очком хода не найдено');

                endif;
            elseif ($user_game->available_steps == 1 && $user_game->status == 0):

                GameUser::where('game_id', $this->game->id)->where('user_id', $user_game->user_id)
                    ->where('available_steps', 1)->where('status', 0)
                    ->update(array('status' => 1, 'make_steps' => 1));
                $this->reInitGame();

                $this->setLog('transferCurrentStep', 'available_steps == 1', 'У исключаемого пользователя есть 1 доступное очко хода. Делаем что он их использовал', array('user_id' => $user_game->user_id));

                if (GameUser::where('game_id', $this->game->id)->where('status', 1)->where('available_steps', 2)->exists()):

                    $this->setLog('transferCurrentStep', 'exists available_steps == 2', 'Нашли игрока, у которого available_steps = 2 (победителя предыдущего шага). Сбрасываем ход');

                    $this->nextStep();
                endif;
            endif;

        elseif ($this->validGameStage(2)):
            GameUserQuestions::where('game_id', $this->game->id)->where('user_id', $user_game->id)->where('status', 0)
                ->update(array('status' => 1, 'answer' => 99999, 'seconds' => 10));

            $this->setLog('transferCurrentStep', 'validGameStage == 2', 'Отвечаем на все неотвеченные вопросы исключаемого пользователя');

        endif;
    }

    private function nextTourInSecondStage($current_tour, $stage2_tours) {

        if ($current_tour === FALSE || $stage2_tours === FALSE):
            $this->nextStep();
            $this->finishGame(0);
            $this->setLog('nextTourInSecondStage', 'finishGame (0)', 'Игра завершилась. Отсутствуют данные current_tour или stage2_tours');
            $this->reInitGame();
        endif;
        if ($current_tour < 4):
            $nextTour = TRUE;
            foreach ($stage2_tours[$current_tour] as $user_id => $status):
                if ($status == FALSE):
                    $nextTour = FALSE;
                    break;
                endif;
            endforeach;
            $old_tour = $current_tour;
            if ($nextTour):
                $current_tour++;
                $json_settings = json_decode($this->game->json_settings, TRUE);
                $json_settings['current_tour'] = $current_tour;
                $this->game->json_settings = json_encode($json_settings);
                $this->game->save();
                $this->game->touch();
                $this->reInitGame();
                $this->setLog('nextTourInSecondStage', 'current_tour++', 'Наступил следующий тур', array('current_tour' => $current_tour));

                if ($old_tour == 2 && $current_tour == 3):
                    $firstStep = TRUE;
                    foreach ($stage2_tours[$current_tour] as $user_id => $status):
                        if ($status == TRUE):
                            $firstStep = FALSE;
                            break;
                        endif;
                    endforeach;
                    if ($firstStep):
                        $this->setLog('nextTourInSecondStage', 'firstStep', 'Первое вхождение в 3-й тур');
                        if ($winner = $this->getWinnerByPoints()):
                            $this->setLog('createStepInSecondStage', 'getWinnerByPoints', 'Определился победитель по 3м турам', array('winner' => $winner));
                            $this->updateTemplateStepInSecondStage($winner);
                            $this->setStepInSecondStageJSON();
                            $this->setLog('updateTemplateStepInSecondStage', 'setStepInSecondStageJSON', 'Шаги пользователя на 3 туре',array('json_settings'=>$this->game->json_settings, 'current_tour' => $current_tour));

                            $this->nextStep($winner);
                        else:
                            $this->randomStep();
                            $this->setLog('nextTourInSecondStage', 'randomStep', 'Победитель по 3м турам не определился. Выбираем случайного', array('winner' => $winner));
                            $this->updateTemplateStepInSecondStage($this->getNextStep());
                            $this->setStepInSecondStageJSON();
                        endif;
                    endif;
                elseif($old_tour == 3 && $current_tour == 4):
                    $this->setLog('createStepInSecondStage', '', 'Первое вхождение в 4-й тур');

                    $this->setLog('updateTemplateStepInSecondStage', 'setStepInSecondStageJSON', 'Шаги пользователя на 4 туре',array('json_settings'=>$this->game->json_settings, 'current_tour' => $current_tour));
                endif;
            endif;
        endif;
        return $current_tour;
    }

    private function finishGameInFourTour() {

        if ($this->validGameStage(2)):
            $json_settings = json_decode($this->game->json_settings, TRUE);
            if (isset($json_settings['current_tour']) && isset($json_settings['stage2_tours'])):
                if ($json_settings['current_tour'] == 4):
                    if (!$this->validAvailableSteps() && $this->validUsersStatus(2)):
                        $this->nextStep();
                        $this->finishGame(1);
                        $this->setLog('finishGameInFourTour', 'finishGame (1)', 'Игра завершилась. Пользователи прошли все 4 этапа игры');
                        $this->reInitGame();
                    endif;
                endif;
            endif;
        endif;
    }
    /************************** CREATING RESPONSES ******************************/
    private function createGameJSONResponse() {

        if ($this->game):
            $users = $map = array();
            $users_ids = $this->getUsersIDs();
            $activeUsers = Sessions::getUserIDsLastActivity($users_ids);
            foreach (GameUser::where('game_id', $this->game->id)->with('user','user_social')->get() as $user_game):
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
            foreach (GameMap::where('game_id', $this->game->id)->get() as $map_place):
                $map[] = array('id' => $map_place->id, 'zone' => $map_place->zone, 'user_id' => $map_place->user_id,
                    'capital' => $map_place->capital, 'lives' => $map_place->lives, 'points' => $map_place->points,
                    'settings' => json_decode($map_place->json_settings, TRUE));
            endforeach;
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
                'current_user' => Auth::user()->id, 'result' => $this->game_winners,
                '2bots' => (int)Config::get('game.2bots'));
            $this->json_request['status'] = TRUE;
        endif;
    }

    /****************************** CREATING ************************************/
    private function createNewGame() {

        $this->game = Game::create(array('status' => $this->game_statuses[0], 'stage' => 0,
            'started_id' => Auth::user()->id, 'winner_id' => 0, 'status_begin' => 0,
            'date_begin' => '000-00-00 00:00:00', 'status_over' => 0, 'date_over' => '000-00-00 00:00:00',
            'json_settings' => json_encode(array('next_step' => 0))));

        if (Config::get('game.make_log')):
            $fileLogName = 'laravel.log';
            if (Config::get('game.new_game_log') === TRUE):
                $fileLogName = 'game-log-' . $this->game->id . '.log';
            endif;
            if (Config::get('game.rebuild_log')):
                if (File::exists(storage_path('logs/' . $fileLogName))):
                    File::delete(storage_path('logs/' . $fileLogName));
                endif;
            endif;
            Log::useFiles(storage_path() . '/logs/' . $fileLogName);
        endif;
        $this->setLog('createNewGame', 'createNewGame', 'Создана новая игра');

        if ($this->game):
            self::joinNewGame();
            $this->reInitGame();
            $this->droppingNewGameUsers();
            $this->reInitGame();
        endif;
        return TRUE;
    }

    private function createGameMap() {

        if ($this->initGame()):
            $map_places = array();
            GameMap::where('game_id', $this->game->id)->delete();
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

    private function createDuel($users_ids = NULL, $zone = NULL) {

        $json_settings = json_decode($this->game->json_settings, TRUE);
        $json_settings['duel'] = is_array($users_ids) ? $users_ids : array();
        if (is_null($users_ids) && is_null($zone)):
            $json_settings['conqu_zone'] = NULL;
        elseif (!is_null($users_ids) && is_null($zone) && !isset($json_settings['conqu_zone'])):
            $json_settings['conqu_zone'] = NULL;
        elseif (!is_null($users_ids) && is_null($zone) && isset($json_settings['conqu_zone'])):
            # оставляем значение как есть
        elseif (!is_null($zone)):
            $json_settings['conqu_zone'] = $zone;
        endif;
        $this->game->json_settings = json_encode($json_settings);
        $this->game->save();
        $this->game->touch();
        $this->reInitGame();
    }

    /******************************* RANDOM**************************************/
    private function randomUsersColor() {

        if ($this->initGame()):
            foreach ($this->game->users as $user_index => $user):
                $random_color = $this->randomColor($user_index);
                $user->color = $random_color;
                $user->save();
                $user->touch();
                $this->game->users[$user_index]['color'] = $random_color;
            endforeach;
        endif;
    }

    private function randomDistributionCapital() {

        if ($this->initGame()):
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

            $this->setLog('randomStep', 'randomStep', 'Случайный ходит');

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

                        $this->setLog('changeGameUsersSteps', 'available_steps == make_steps', 'Игрок потратил доступные очки действий', array('available_steps' => $user->available_steps,
                            'make_steps' => $user->make_steps, 'user_id' => $user->id));

                    endif;
                    $user->touch();
                else:
                    $this->setLog('changeGameUsersSteps', 'available_steps > make_steps. ERROR', 'У доступных действий меньше или равно сделанных', array('user_status' => $user->status,
                        'available_steps' => $user->available_steps, 'make_steps' => $user->make_steps));
                endif;
            else:
                $this->setLog('changeGameUsersSteps', 'status == 0 && available_steps > 0. ERROR', 'У игрока не статус 0 или нет доступных действий', array('user_status' => $user->status,
                    'available_steps' => $user->available_steps, 'make_steps' => $user->make_steps));
            endif;
        endif;
        return $status;
    }

    private function changeGameUsersStatus($status, $user = NULL) {

        if (is_null($user)):
            foreach (GameUser::where('game_id', $this->game->id)->whereNotIn('status', array(99, 100))->get() as $user):
                $user->status = $status;
                $user->save();
                $user->touch();
            endforeach;
        elseif (is_numeric($user)):
            GameUser::where('game_id', $this->game->id)->where('user_id', $user)
                ->whereNotIn('status', array(99, 100))
                ->update(array('status' => $status));
        elseif (is_object($user)):
            if (isset($user->status) && !in_array($user->status, array(99, 100))):
                $user->status = $status;
                $user->save();
                $user->touch();
            endif;
        endif;
        $this->reInitGame();
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
    private function validGame() {

        if (!is_null($this->game) && isset($this->game->id) && !empty($this->game->users)):
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

    private function validCurrentTourInSecondStage($tour = 0) {

        $current_tour = $this->getCurrentTourInSecondStage();
        if ($current_tour && $current_tour == $tour):
            return TRUE;
        else:
            return FALSE;
        endif;
    }

    private function validGameLeader($user_id = NULL) {

        if (is_null($user_id)):
            $user_id = Auth::user()->id;
        endif;
        if (GameUser::where('game_id', $this->game->id)->where('user_id', $user_id)->where('leader', 1)->where('is_bot', 0)->exists()):
            return TRUE;
        else:
            return FALSE;
        endif;
    }

    private function validWinnersCalculate($post) {

        $hasWinnersCalculate = FALSE;
        if ($this->validGameStage(1)):
            $hasWinnersCalculate = $this->validGameLeader();
        elseif ($this->validGameStage(2)):
            if ($duel = $this->getDuel()):
                if (!isset($post['zone']) || $post['zone'] == 0):
                    if ($conquest_zone = $this->getConquestZone()):
                        $post['zone'] = $conquest_zone;
                        $this->setLog('getResultQuestion', 'getConquestZone', 'Зона получена из БД', array('zone' => $post['zone']));
                    endif;
                endif;
                if ($duel['conqu'] == Auth::user()->id):
                    $hasWinnersCalculate = TRUE;
                    $this->setLog('getResultQuestion', 'hasWinnersCalculate', 'Запрос от нападающего. Расчет победителей доступен');
                elseif ($duel['def'] == Auth::user()->id):
                    $hasWinnersCalculate = FALSE;
                    $this->setLog('getResultQuestion', 'hasWinnersCalculate', 'Запрос от защищающегося. Расчет победителей недоступен');
                endif;
                if ($hasWinnersCalculate === FALSE && ($this->isBot($duel['conqu']) || $this->isBot($duel['def']))):
                    $hasWinnersCalculate = TRUE;
                    $this->setLog('getResultQuestion', 'hasWinnersCalculate', 'Запрос от защищающегося. В дуели есть боты. Расчет победителей доступен');
                endif;
            endif;
        endif;
        return $hasWinnersCalculate;

    }

    /********************************** BOTS *************************************/
    private function joinBotsInGame() {

        $user_games_count = GameUser::where('game_id', $this->game->id)->count();
        $bots = array();
        $bots_ids = Config::get('game.bots_ids');
        if ($user_games_count == 1):
            $this->game->users[] = $bots[] = array('game_id' => $this->game->id, 'user_id' => $bots_ids[0],
                'is_bot' => 1,
                'status' => 0, 'available_steps' => 0, 'make_steps' => 0, 'color' => NULL, 'points' => 0,
                'place' => 0, 'json_settings' => json_encode(array()), 'created_at' => date('Y-m-d H:i:s'));
            $this->game->users[] = $bots[] = array('game_id' => $this->game->id, 'user_id' => $bots_ids[1],
                'is_bot' => 1,
                'status' => 0, 'available_steps' => 0, 'make_steps' => 0, 'color' => NULL, 'points' => 0,
                'place' => 0, 'json_settings' => json_encode(array()), 'created_at' => date('Y-m-d H:i:s'));
        elseif ($user_games_count == 2):
            $this->game->users[] = $bots[] = array('game_id' => $this->game->id, 'user_id' => $bots_ids[0],
                'is_bot' => 1,
                'status' => 0, 'available_steps' => 0, 'make_steps' => 0, 'color' => '', 'points' => 0,
                'place' => 0, 'json_settings' => json_encode(array()), 'created_at' => date('Y-m-d H:i:s'));
        endif;
        if (count($bots)):
            GameUser::insert($bots);

            $this->setLog('joinBotsInGame', 'insertBots', 'Игра с ботами');

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

                            $this->setLog('botConquestTerritory', 'conquestTerritory', 'Бот захватил смежную террирорию!', array('zone' => $conquest_zone,
                                'bot' => $bot_id));

                        else:

                            $this->setLog('botConquestTerritory', 'conquestTerritory. ERROR', 'Бот не смог захватить смежную террирорию!', array('zone' => $conquest_zone,
                                'bot' => $bot_id));

                        endif;
                    else:
                        if ($firstEmptyZone = GameMap::where('game_id', $this->game->id)->where('user_id', 0)->where('capital', 0)->first()):
                            if ($this->conquestTerritory($firstEmptyZone->zone, $bot_id)):
                                $points = $this->getTerritoryPoints($firstEmptyZone->zone);
                                $this->changeUserPoints($bot_id, $points, $bot);

                                $this->setLog('botConquestTerritory', 'conquestTerritory', 'Бот захватил первую попавшуюсь террирорию!', array('zone' => $firstEmptyZone->zone,
                                    'bot' => $bot_id));

                            else:

                                $this->setLog('botConquestTerritory', 'conquestTerritory. ERROR', 'Бот не смог захватить первую попавшуюся террирорию!', array('zone' => $firstEmptyZone->zone,
                                    'bot' => $bot_id));

                            endif;
                        endif;
                    endif;
                    return TRUE;
                elseif ($this->validGameStage(2) && !is_null($conqueror_zone)):
                    if ($this->conquestTerritory($conqueror_zone, $bot_id)):

                        $this->setLog('botConquestTerritory', 'conquestTerritory', 'Бот захватил территорию', array('zone' => $conqueror_zone,
                            'bot' => $bot_id));

                        $points = $this->getTerritoryPoints($conqueror_zone);
                        $this->changeUserPoints($bot_id, $points, $bot);
                        $this->changeTerritoryPoints($conqueror_zone, 200);

                        $this->changeGameUsersStatus(2);

                        $this->setLog('botConquestTerritory', 'changeGameUsersStatus(2)', 'Все пользователям статус 2');
                        $this->reInitGame();
                        return TRUE;
                    endif;
                endif;
            endif;
        else:

            $this->setLog('botConquestTerritory', 'changeGameUsersSteps. ERROR', 'Захват территории ботом не удался. Нет доступных очков хода', array('zone' => $conqueror_zone,
                'bot' => $bot_id));

        endif;
        return FALSE;
    }

    private function botConquestCapital($bot_id, $conqueror_zone) {

        $bot = GameUser::where('game_id', $this->game->id)->where('is_bot', 1)->where('user_id', $bot_id)->first();
        if ($this->validGameStatus($this->game_statuses[2]) && $this->validGameStage(2)):
            if ($this->changeGameUsersSteps($bot)):
                if ($conquest = GameMap::where('game_id', $this->game->id)->where('user_id', '!=', $bot_id)->where('zone', $conqueror_zone)->where('capital', 1)->first()):
                    if ($conquest->lives == 1):

                        $this->setLog('botConquestCapital', 'conquest->lives == 1', 'Бот захватил столицу!', array('zone' => $conqueror_zone,
                            'bot' => $bot_id));

                        $this->disconnectUserInGame($conquest->user_id, 99, @$bot->user_id, @$bot->color);
                        $this->changeUserPoints($bot_id, 1000, $bot);
                        return 0;
                    elseif ($conquest->lives > 1):
                        $conquest->lives = $conquest->lives - 1;
                        $conquest->save();
                        $conquest->touch();

                        $this->setLog('botConquestCapital', 'conquest->lives > 1', 'Бот уменьшил количество жизней у столицы!', array('zone' => $conqueror_zone,
                            'bot' => $bot_id, 'owner' => $conquest->user_id, 'lives' => $conquest->lives));

                        return $conquest->lives;
                    endif;
                endif;
            else:

                $this->setLog('botConquestCapital', 'changeGameUsersSteps. ERROR', 'Захват столицы ботом не удался. Нет доступных очков хода', array('zone_conquest' => $conqueror_zone,
                    'bot' => $bot_id));

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

        $percent = 0.4;
        if ($current_answer >= 100 && $current_answer <= 1000):
            $percent = 0.2;
        elseif ($current_answer > 1000 && $current_answer <= 1800):
            $percent = 0.1;
        elseif ($current_answer > 1800 && $current_answer <= 2000):
            $percent = 0.05;
        elseif ($current_answer > 2000):
            $percent = 0.01;
        endif;

        $min_value = $current_answer - round($current_answer * $percent, 0);
        $max_value = $current_answer + round($current_answer * $percent, 0);
        if ($botGameQuestion = GameUserQuestions::where('game_id', $this->game->id)->where('group_id', $question_group_id)->where('user_id', $bot_id)->where('status', 0)->first()):
            $botGameQuestion->status = 1;
            $botGameQuestion->answer = mt_rand($min_value, $max_value);
            $botGameQuestion->seconds = mt_rand(3, 7);
            $botGameQuestion->save();
            $botGameQuestion->touch();

            $this->setLog('botAnswerQuizQuestion', 'botAnswerQuizQuestion', 'Бот ответил на квиз вопрос', array('current_answer' => $current_answer,
                'bot' => $bot_id, 'bot_answer' => $botGameQuestion->answer));

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

            $this->setLog('botAnswerNormalQuestion', 'botAnswerNormalQuestion', 'Бот ответил на нормальный вопрос', array('current_answer' => $current_answer,
                'bot' => $bot_id, 'bot_answer' => $botGameQuestion->answer, 'bot_seconds' => $botGameQuestion->seconds,
                'question' => $botGameQuestion->id));

        endif;
    }

    private function isBotNextStepStage1() {

        if ($this->validGameLeader() === FALSE):
            return FALSE;
        endif;

        $winners_id = array_keys($this->game_winners);
        if ($this->isBot($winners_id[0])):

            $this->setLog('isBotNextStepStage1', 'isBot', 'Бот занял 1-е место', array('bot_id' => $winners_id[0]));

            $this->nextStep($winners_id[0]);

            $this->setLog('isBotNextStepStage1', 'nextStep', 'Сохраняем ход для бота. Бот занял 1-е место');

            $this->botConquestTerritory($winners_id[0]);
            $this->botConquestTerritory($winners_id[0]);
            $this->nextStep($winners_id[1]);

            $this->setLog('isBotNextStepStage1', 'nextStep', 'Переводим ход на следующего игрока. Бот отходил');

            if ($this->isBot($winners_id[1])):

                Config::set('game.2bots', 1);

                $this->setLog('isBotNextStepStage1', '2bots', 'Победили 2 бота');

                $this->setLog('isBotNextStepStage1', 'isBot', 'Бот занял 2-е место', array('bot' => $winners_id[1]));

                $this->nextStep($winners_id[1]);

                $this->setLog('isBotNextStepStage1', 'nextStep 3', 'Переход хода на следующего подедителя бота', array('bot' => $winners_id[1]));

                $this->botConquestTerritory($winners_id[1]);

                $this->changeGameUsersStatus(2);

                $this->setLog('isBotNextStepStage1', 'changeGameUsersStatus(2)', 'Все пользователям статус 2');

                $this->nextStep();

                $this->setLog('isBotNextStepStage1', 'nextStep', 'Сброс хода. Победители боты. Они отходили');

            endif;
            if ($this->isConqueredTerritories()):

                $this->setLog('isBotNextStepStage1', 'isConqueredTerritories', 'Все территории захвачены');

                $this->nextStep();

                $this->setLog('isBotNextStepStage1', 'nextStep', 'Сброс хода. Все территории захвачены');

                $this->closeGameUsersQuestions();
                $this->changeGameStage(2);

                $this->setLog('isBotNextStepStage1', 'changeGameStage', 'Бот перевел игру на 2й этап');

                $this->resetGameUsers();

                $this->setLog('isBotNextStepStage1', 'resetGameUsers', 'Сброс параметров игроков');

                $nextStep = $this->createTemplateStepInSecondStage();
                $this->nextStep($nextStep);

                $this->setLog('isBotNextStepStage1', 'createTemplateStepInSecondStage', 'Бот создал шаблон шагов для 2-го этапа');

                $this->setStepInSecondStageJSON();
            endif;
        endif;
    }

    private function getBotExecuteStep($bot) {

        $duel = $this->getDuel();
        $botExecute = FALSE;

        if (!empty($duel) && $duel['conqu'] == $bot->user_id):
            $botExecute = TRUE;

            $this->setLog('getBotExecuteStep', 'execute', 'Существует дуель где нападает бот. Запуск бота', array('bot' => $bot->user_id,
                'duel' => $duel, 'available_steps' => $bot->available_steps, 'make_steps' => $bot->make_steps));

            $this->nextStep();

            $this->setLog('getBotExecuteStep', 'nextStep', 'Существует дуель где нападает бот. Сбрасываем ходы', array('bot' => $bot->user_id,
                'duel' => $duel));

        endif;
        if ($botExecute === FALSE && $bot->available_steps > 0):
            $botExecute = TRUE;

            $this->setLog('getBotExecuteStep', 'execute', 'У бота есть доступные очки хода. Запуск бота.', array('bot' => $bot->user_id,
                'available_steps' => $bot->available_steps));

            $this->nextStep();

            $this->setLog('getBotExecuteStep', 'nextStep', 'У бота есть доступные очки хода. Сбрасываем ходы', array('bot' => $bot->user_id));
        elseif (empty($duel)):
            $botExecute = TRUE;
        endif;
        return $botExecute;
    }

    private function battleBotsCapital($zoneConqueror) {

        $duel = $this->getDuel();

        $botConqueror = $duel['conqu'];
        $gamerDefence = $duel['def'];

        $lives = $this->getTerritoryLives($zoneConqueror);

        $this->setLog('battleBotsCapital', 'validCapitalZone', 'Жизни столицы', array('bot' => $botConqueror,
            'duel' => $duel, 'zoneConqueror' => $zoneConqueror, 'lives' => $lives));

        for ($i = $lives; $i > 0; $i--):
            $botDuelWinner = array_rand(array($botConqueror, $gamerDefence));

            $this->setLog('battleBotsCapital', 'botDuelWinner', 'Выбрали победителя в дуели при атаке на столицу', array('bot' => $botConqueror,
                'duel' => $duel, 'botDuelWinner' => $botDuelWinner));

            if ($botDuelWinner === 0):
                $this->setGameUserAvailableSteps($botConqueror, 1);

                $this->setLog('battleBotsCapital', 'botDuelWinner == 0', 'Нападающий выиграл и ему предоставлено одно очко дейсвий', array('bot' => $botConqueror,
                    'duel' => $duel, 'botDuelWinner' => $botDuelWinner));

                if ($capitalLives = $this->botConquestCapital($botConqueror, $zoneConqueror)):

                    $this->setLog('battleBotsCapital', 'botConquestCapital', 'Нападающий бот ударил по столице', array('bot' => $botConqueror,
                        'capitalLives' => $capitalLives));

                    if ($capitalLives === 0):

                        $this->setLog('battleBotsCapital', 'capitalLives === 0', 'Нападающий бот уничтожил столицу', array('bot' => $botConqueror,
                            'capitalLives' => $capitalLives));

                        $this->botDestroyCapital($botConqueror, $zoneConqueror);
                        $this->changeUserPoints($botConqueror, 1000);
                        break;
                    endif;
                endif;

            else:

                $this->setLog('battleBotsCapital', 'gamerDefenceTerritory', 'Удара по столице непроизошло. Победил обороняющийся', array('bot' => $botConqueror,
                    'duel' => $duel, 'gamerDefence' => $gamerDefence, 'zoneConqueror' => $zoneConqueror));

                $this->gamerDefenceTerritory($zoneConqueror);
                $this->closeGameUsersQuestions();
                $this->resetGameUsers();

                $this->setLog('battleBotsCapital', 'resetGameUsers', 'Сброс параметров игроков');

                $this->nextStepInSecondStage();
                $this->setStepInSecondStageJSON();
                $this->createDuel();

                $this->setLog('battleBotsCapital', 'nextStepInSecondStage', 'Победил обороняющийся бот. Переход хода', array('duel' => $this->getDuel()));

                break;
            endif;
        endfor;
    }

    private function botFightingAnotherGamer() {

        $duel = $this->getDuel();
        $zoneConqueror = $this->getConquestZone();

        $botConqueror = $duel['conqu'];
        $gamerDefence = $duel['def'];

        if ($this->isBot($gamerDefence)):

            $this->setLog('botFightingAnotherGamer', 'isBot(gamerDefence)', 'Обороняющийся тоже бот', array('bot' => $botConqueror,
                'duel' => $duel, 'gamerDefence' => $gamerDefence));

            $botWinner = array_rand(array($botConqueror, $gamerDefence));

            $this->setLog('botFightingAnotherGamer', 'botWinner', 'Выбрали победителя в дуели', array('bot' => $botConqueror,
                'duel' => $duel, 'gamerDefence' => $gamerDefence, 'botWinner' => $botWinner));

            if ($botWinner === 0):

                $this->setLog('botFightingAnotherGamer', 'botWinner == 0', 'В дуели победил нападающий', array('bot' => $botConqueror,
                    'duel' => $duel, 'gamerDefence' => $gamerDefence));

                if ($this->validCapitalZone($zoneConqueror)):

                    $this->setLog('botFightingAnotherGamer', 'validCapitalZone', 'Бот атакует столицу', array('bot' => $botConqueror,
                        'duel' => $duel, 'gamerDefence' => $gamerDefence));

                    $this->battleBotsCapital($zoneConqueror);
                else:

                    $this->setLog('botFightingAnotherGamer', 'botConquestTerritory', 'Боту предоставляется очко хода и он захватывает обычную территорию', array('botConqueror' => $botConqueror,
                        'zoneConqueror' => $zoneConqueror));

                    $this->setGameUserAvailableSteps($botConqueror, 1);
                    $this->botConquestTerritory($botConqueror, $zoneConqueror);
                    $this->closeGameUsersQuestions();
                    $this->resetGameUsers();

                    $this->setLog('botFightingAnotherGamer', 'resetGameUsers', 'Сброс параметров игроков');

                    $this->createDuel();
                    $this->nextStepInSecondStage();

                    $this->setLog('botFightingAnotherGamer', 'nextStepInSecondStage', 'Победил нападающий бот. Переход хода', array('duel' => $this->getDuel()));

                    $this->setStepInSecondStageJSON();
                endif;
            else:

                $this->setLog('botFightingAnotherGamer', 'gamerDefenceTerritory', 'В дуели победил обороняющийся', array('duel' => $this->getDuel(),
                    'zoneConqueror' => $zoneConqueror));

                $this->gamerDefenceTerritory($zoneConqueror);
                $this->closeGameUsersQuestions();
                $this->createDuel();
                $this->resetGameUsers();

                $this->setLog('botFightingAnotherGamer', 'resetGameUsers', 'Сброс параметров игроков');

                $this->nextStepInSecondStage();

                $this->setLog('botFightingAnotherGamer', 'nextStepInSecondStage', 'Победил обороняющийся бот. Переход хода');

                $this->setStepInSecondStageJSON();
            endif;
        else:

            $this->setLog('botFightingAnotherGamer', 'isBot === FALSE', 'Дуель игрока и бота.');

            $this->resetGameUsers();

            $this->setLog('botFightingAnotherGamer', 'resetGameUsers', 'Сброс параметров игроков');

            $this->botCreateNormalQuestion($duel);
            $this->nextStep();

            $this->setLog('botFightingAnotherGamer', 'nextStep', 'Сброс хода. Был задан нормальный вопрос от бота');

        endif;
    }

    private function botDestroyCapital($botConqueror, $zoneConqueror) {

        $this->closeGameUsersQuestions();
        $this->resetGameUsers();

        $this->setLog('botDestroyCapital', 'resetGameUsers. capitalLives === 0', 'Сброс параметров игроков');

        $this->createDuel();
        $this->nextStepInSecondStage();

        $this->setLog('botDestroyCapital', 'nextStepInSecondStage', 'Бот захватил столицу. Переход хода', array('duel' => $this->getDuel()));

        $this->setStepInSecondStageJSON();
        $this->changeGameUsersStatus(2);

        $this->setLog('botDestroyCapital', 'changeGameUsersStatus(2)', 'Все пользователям статус 2');

        if ($this->isConqueredCapitals()):
            $this->nextStep();
            $this->finishGame(1);

            $this->setLog('botDestroyCapital', 'finishGame (1)', 'Бот завершил игру. Осталась только одна столица', array('bot' => $botConqueror));

            $this->reInitGame();
        endif;
    }

    private function isBotNextStepStage2() {

        if ($this->validGame() && $this->validGameStatus($this->game_statuses[2]) && $this->validGameStage(2)):
            if ($this->validGameBots()):
                if (!$botConqueror = $this->getNextStep()):
                    return FALSE;
                endif;
                if ($this->isBot($botConqueror) === FALSE):
                    return FALSE;
                endif;
                $bot = GameUser::where('game_id', $this->game->id)->where('is_bot', 1)->where('user_id', $botConqueror)->first();
                if ($this->getBotExecuteStep($bot)):
                    $this->setLog('isBotNextStepStage2', 'getBotExecuteStep', 'Ход предоставлен боту', array('bot' => $botConqueror));
                    $this->createStepInSecondStage($botConqueror);
                    $this->setStepInSecondStageJSON();
                    $this->setLog('isBotNextStepStage2', 'createStepInSecondStage', 'Бот совершил ход', array('bot' => $botConqueror));
                    $this->nextStep();
                    $this->setLog('isBotNextStepStage2', 'nextStep', 'Бот совершил ход. Сбрасываем ход');
                    $zoneConqueror = $this->getConquestZone();
                    $duel = $this->getDuel();
                    $gamerDefence = FALSE;
                    if ($zoneConqueror === FALSE || $duel === FALSE):
                        $this->closeGameUsersQuestions();
                        $this->resetGameUsers();
                        $this->setLog('isBotNextStepStage2', 'resetGameUsers', 'Нет дуели. Сброс параметров игроков для начала нового тура');
                        if ($adjacentZonesList = $this->getAdjacentPlaces($botConqueror)):
                            $adjacentZones = array();
                            foreach ($adjacentZonesList as $adjacentZone):
                                $adjacentZones[$adjacentZone['zone']] = $adjacentZone['user_id'];
                            endforeach;
                            $zones_numbers = array_keys($adjacentZones);
                            $zones_users = array_values($adjacentZones);
                            $conqueror_index = array_rand($zones_numbers);
                            if (isset($zones_users[$conqueror_index])):
                                $gamerDefence = $zones_users[$conqueror_index];
                                $zoneConqueror = $zones_numbers[$conqueror_index];
                                $duel = array('conqu' => $botConqueror, 'def' => $gamerDefence);
                                $this->createDuel($duel, $zoneConqueror);
                                $this->setLog('isBotNextStepStage2', 'createDuel', 'Бот создал новую дуель и назначил зону нападения', array('bot' => $botConqueror,
                                    'duel' => $this->getDuel(), 'zone' => $this->getConquestZone()));
                                $this->botFightingAnotherGamer();
                            endif;
                        else:
                            $this->setLog('isBotNextStepStage2', 'getAdjacentPlaces. ERROR', 'Отсутствует дуель и зона нападения. Ошибка при получении зон для нападения');
                        endif;
                    else:
                        $gamerDefence = $duel['def'];
                        $this->setLog('isBotNextStepStage2', 'zoneConqueror gamerDefence', 'Новая дуель не нужна. Дуель уже сущесвует', array('bot' => $botConqueror,
                            'gamerDefence' => $gamerDefence, 'zone' => $zoneConqueror));
                        if ($bot->available_steps > $bot->make_steps):
                            if ($this->validCapitalZone($zoneConqueror)):
                                $this->setLog('isBotNextStepStage2', 'validCapitalZone. available_steps', 'У бота есть очки для нападения. Он нападает на столицу игрока', array('botConqueror' => $botConqueror,
                                    'zoneConqueror' => $zoneConqueror));
                                if ($capitalLives = $this->botConquestCapital($botConqueror, $zoneConqueror)):
                                    $this->setLog('isBotNextStepStage2', 'botConquestCapital', 'Нападающий бот ударил по столице', array('bot' => $botConqueror,
                                        'capitalLives' => $capitalLives));
                                    if ($capitalLives === 0):
                                        $this->setLog('isBotNextStepStage2', 'capitalLives === 0', 'Нападающий бот уничтожил столицу', array('bot' => $botConqueror,
                                            'capitalLives' => $capitalLives));
                                        $this->botDestroyCapital($botConqueror, $zoneConqueror);
                                        $this->changeUserPoints($botConqueror, 1000);
                                    elseif ($capitalLives > 0):
                                        $this->closeGameUsersQuestions();
                                        $this->resetGameUsers();
                                        $this->setLog('isBotNextStepStage2', 'resetGameUsers. capitalLives > 0', 'Сброс параметров игроков');
                                        $this->setLog('isBotNextStepStage2', 'capitalLives > 0', 'У столицы остались жизни', array('capitalLives' => $capitalLives,
                                            'zone' => $zoneConqueror));
                                        $this->botCreateNormalQuestion($duel);
                                        $this->nextStep();
                                        $this->setLog('isBotNextStepStage2', 'nextStep.capitalLives > 0', 'Сброс хода. Был задан нормальный вопрос от бота');
                                    endif;
                                else:
                                    $this->setLog('isBotNextStepStage2', 'botConquestCapital. ERROR', 'Боту не удалось ударить по столице', array('zone' => $zoneConqueror));
                                endif;
                            else:
                                $this->setLog('isBotNextStepStage2', '!validCapitalZone. available_steps', 'У бота есть очки для нападения. Он нападает на обычную территорию игрока', array('botConqueror' => $botConqueror,
                                    'zoneConqueror' => $zoneConqueror));
                                $this->botConquestTerritory($botConqueror, $zoneConqueror);
                                $this->closeGameUsersQuestions();
                                $this->resetGameUsers();
                                $this->setLog('botFightingAnotherGamer', 'resetGameUsers', 'Сброс параметров игроков');
                                $this->createDuel();
                                $this->nextStepInSecondStage();
                                $this->setLog('botFightingAnotherGamer', 'nextStepInSecondStage', 'Победил нападающий бот. Переход хода', array('duel' => $this->getDuel()));
                                $this->setStepInSecondStageJSON();
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

        $this->setLog('isBotNextStepStage2', 'createQuestion', 'БОТ создал нормальный вопрос', array('question' => $randomQuestion->id,
            'users' => $users_ids));

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

                    $this->setLog('conquestCapital', 'conquest->lives == 1', 'Пользователь захватил столицу!', array('zone' => $zone,
                        'owner' => $conquest->user_id));

                    $this->setLog('conquestCapital', 'disconnectUserInGame', 'Запуск скрипта на исключение пользователя из игры', array('remove_user' => $conquest->user_id));

                    $this->disconnectUserInGame($conquest->user_id, 99, @$user->user_id, @$user->color);
                    $this->changeUserPoints($user_id, 1000);
                    return 0;
                elseif ($conquest->lives > 1):
                    $conquest->lives = $conquest->lives - 1;
                    $conquest->save();
                    $conquest->touch();

                    $this->setLog('conquestCapital', 'conquest->lives > 1', 'Пользователь уменьшил количество жизней у столицы!', array('zone' => $zone,
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

    private function getConquestZone() {

        $json_settings = json_decode($this->game->json_settings, TRUE);
        if (isset($json_settings['conqu_zone']) && !is_null($json_settings['conqu_zone']) && $json_settings['conqu_zone'] > 0):
            return $json_settings['conqu_zone'];
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

        $this->setLog('gamerDefenceTerritory', 'changeUserPoints', 'Игроку добавлены ички', array('user' => $duel['def'],
            'points' => 100 * $lives));

        $this->changeGameUsersStatus(2);

        $this->setLog('gamerDefenceTerritory', 'changeGameUsersStatus(2)', 'Все пользователям статус 2');

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

                $this->setLog('setQuizQuestionWinner', 'validGameStage(2)', 'Занятые места', array('game_winners' => $this->game_winners));

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
            else:
                $this->setLog('setQuizQuestionWinner', 'validGameStage(2)', 'Определения победителей', array('winner_places' => $winner_places));
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

        if ($this->validGameStage(2) && $this->validCurrentTourInSecondStage(4)):

            $this->setLog('resetGameUsers', 'validCurrentTourInSecondStage', 'Обнуление пользователей отменено. Текущий номер тура - 5');

            return FALSE;
        endif;

        GameUser::where('game_id', $this->game->id)->whereNotIn('status', array(99, 100))->update(array('status' => 0,
            'available_steps' => 0, 'make_steps' => 0, 'updated_at' => date('Y-m-d H:i:s')));
        $this->reInitGame();
    }

    private function getWinnerByPoints() {

        if ($users_points = GameUser::where('game_id', $this->game->id)->whereNotIn('status',array(99, 100))->lists('points', 'user_id')):
            arsort($users_points);
            $users = array_keys($users_points);
            $points = array_values($users_points);
            if (@$points[0] > @$points[1]):
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
            $this->reInitGame();
            foreach ($users as $index => $user_id):
                if ($this->isBot($user_id) === FALSE):
                    $this->changeUserRating($user_id, $rating[$index]);
                endif;
            endforeach;
        endif;
        return TRUE;
    }

    private function setGameUserAvailableSteps($user_id, $available_steps) {

        GameUser::where('game_id', $this->game->id)->where('user_id', $user_id)->update(array('status' => 0,
            'available_steps' => $available_steps, 'make_steps' => 0,
            'updated_at' => date('Y-m-d H:i:s')));
        $this->reInitGame();
    }

    private function setGameUserQuestionPlace($user_id, $place) {

        GameUserQuestions::where('game_id', $this->game->id)->where('user_id', $user_id)->where('status', 1)->where('place', 0)
            ->update(array('status' => 2, 'place' => $place, 'updated_at' => date('Y-m-d H:i:s')));
    }

    /****************************** REMOVED USERS *********************************/
    private function disconnectUserInGame($remove_user_id = NULL, $set_status = 100, $set_new_owner = -1, $set_color = 'black') {

        if ($this->validGame() === FALSE):
            return FALSE;
        endif;
        if (is_null($remove_user_id)):
            $remove_user_id = Auth::user()->id;
        endif;
        if (is_null($set_new_owner)):
            $set_new_owner = -1;
        endif;
        if ($user = GameUser::where('game_id', $this->game->id)->where('user_id', $remove_user_id)->whereNotIn('status', array(99,
            100))->first()
        ):
            $user->status = $set_status;
            $user->available_steps = 0;
            $user->make_steps = 0;
            $user->save();
            $user->touch();
            $this->setLog('disconnectUserInGame', 'user.status', 'Пользователю выставился статус ' . $set_status, array('user' => $remove_user_id,
                'status' => $user->status));
            if ($this->validGameLeader($remove_user_id)):
                $this->nextGameLeader();
            endif;
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
            $this->setLog('disconnectUserInGame', 'stage2_tours', 'Новый владелец ' . $set_new_owner, array('zones_color' => $set_color,
                'user' => $remove_user_id));
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
                    $this->setLog('disconnectUserInGame', 'stage2_tours', 'Шаги пользователя помечанные как TRUE', array('stage2_tours' => $json_settings['stage2_tours'],
                        'user' => $remove_user_id));
                    $this->game->json_settings = json_encode($json_settings);
                    $this->game->save();
                    $this->game->touch();
                    $this->reInitGame();
                    $current_user_step = $this->getNextStep();
                    if ($current_user_step == $remove_user_id):
                        $this->nextStepInSecondStage();
                        $this->setLog('disconnectUserInGame', 'nextStepInSecondStage', 'Текущий ход преналдежит исключаемому игроку. Производим переход хода', array('user' => $remove_user_id));
                        $this->setStepInSecondStageJSON();
                    endif;
                endif;
            endif;
            return TRUE;
        else:
            $this->setLog('disconnectUserInGame', 'not validDropUser', 'Исключить игрока не удалось. Или он бот, или был исключен из игры раньше', array('user' => $remove_user_id));
            return FALSE;
        endif;
    }

    private function dropUser($user_game, $remove_in_game = FALSE) {

        if ($this->isBot($user_game->user_id)):
            return FALSE;
        endif;
        $next_step = $this->getNextStep();
        if ($remove_in_game === TRUE):
            if ((time() - $user_game->session->last_activity) > Config::get('game.remove_user_timeout_in_game_wait', 15)):
                GameUser::where('game_id', $this->game->id)->where('user_id', $user_game->user_id)->where('is_bot', 0)->delete();
                $this->setLog('dropUser', 'remove_in_game == TRUE', 'Обнаружился неактивный игрок. Он был удален из игры', array('user' => $user_game->user_id));
                $this->reInitGame();
            endif;
        endif;
        if ($user_game->user_id == $next_step):
            if (empty($user_game->session) || !isset($user_game->session->last_activity)):
                $this->setLog('dropUser', 'empty(user_game->session)', 'У пользователя отсутствует данные сессии', array('user' => $user_game->user_id));
                $this->transferCurrentStep($user_game);
                $this->disconnectUserInGame($user_game->user_id);
                $this->reInitGame();
            elseif ((time() - $user_game->session->last_activity) > Config::get('game.disconnect_user_timeout', 30)):
                $this->setLog('dropUser', 'time() - last_activity', 'У пользователя истекло время на действия', array('user' => $user_game->user_id));
                $this->transferCurrentStep($user_game);
                $this->disconnectUserInGame($user_game->user_id);
                $this->reInitGame();
            endif;
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
                $this->setLog('droppingGameUsers', 'finishGame (1)', 'Игра завершилась. Отвалились 2 или более игроков');
                $this->reInitGame();
            elseif (($deadUsersCount == 1 || $inActiveUsersCount == 1) && $this->validGameBots()):
                $bots_count = $this->getGameCountBots(TRUE);
                if ($bots_count == 2):
                    $this->nextStep();
                    $this->finishGame(1);
                    $this->setLog('droppingGameUsers', 'finishGame (1)', 'Игра завершилась. В игре остались только боты');
                    $this->reInitGame();
                endif;
            endif;
            if ($inActiveUsersCount >= 2):
                $this->nextStep();
                $this->finishGame(1);

                $this->setLog('droppingGameUsers', 'finishGame (1)', 'Игра завершилась. 2 или более игроков были повержаны в процессе игры и получили 99 статус');

                $this->reInitGame();
            endif;
        endif;
    }

    private function droppingNewGameUsers() {

        if ($this->initGame()):
            foreach ($this->game->users as $user_game):
                if ($this->isBot($user_game->user_id) === FALSE):
                    if (empty($user_game->session) || !isset($user_game->session->last_activity)):
                        GameUser::where('game_id', $this->game->id)->where('user_id', $user_game->user_id)->where('is_bot', 0)->delete();
                        $this->setLog('droppingNewGameUsers', 'empty(user_game->session)', 'У пользователя отсутствует данные сессии. Удален из игры.', array('user' => $user_game->user_id));
                        $this->reInitGame();
                    elseif ((time() - $user_game->session->last_activity) > Config::get('game.disconnect_user_timeout', 60)):
                        GameUser::where('game_id', $this->game->id)->where('user_id', $user_game->user_id)->where('is_bot', 0)->delete();
                        $this->setLog('droppingNewGameUsers', 'time() - last_activity', 'У пользователя истекло время на действия. Удален из игры.', array('user' => $user_game->user_id));
                        $this->reInitGame();
                    endif;
                endif;
            endforeach;
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

                    $this->setLog('sendDroppedUserAnswers', 'droppedUserAnswers', 'Выбывший игрок ответил на вопрос', array('user_id' => $user->id,
                        'current_answer' => 'Не известно', 'user_answer' => 99999));

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

    private function getGameCountBots($active = FALSE) {

        $bots_count = 0;
        if ($this->validGameStatus($this->game_statuses[2])):
            if (isset($this->game->users) && !empty($this->game->users)):
                foreach ($this->game->users as $game_user):
                    if ($this->isBot($game_user->user_id)):
                        if ($active):
                            if (!in_array($game_user->status, array(99, 100))):
                                $bots_count++;
                            endif;
                        else:
                            $bots_count++;
                        endif;
                    endif;
                endforeach;
            endif;
        endif;
        return $bots_count;
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

    private function getCurrentTourInSecondStage() {

        if ($this->validGameStage(2)):
            $json_settings = json_decode($this->game->json_settings, TRUE);
            if (isset($json_settings['current_tour'])):
                return $json_settings['current_tour'];
            endif;
        endif;
        return FALSE;
    }

    private function nextGameLeader($user_id = NULL) {

        GameUser::where('game_id', $this->game->id)->update(array('leader' => 0));
        $newLeader = NULL;
        if (is_null($user_id)):
            $newLeader = GameUser::where('game_id', $this->game->id)->where('is_bot', 0)
                ->whereNotIn('status', array(99, 100))->first();
        else:
            $newLeader = GameUser::where('game_id', $this->game->id)->where('user_id', $user_id)->where('is_bot', 0)
                ->whereNotIn('status', array(99, 100))->first();
        endif;
        if ($newLeader):
            $newLeader->leader = 1;
            $newLeader->save();
            $this->leader = $newLeader;
        else:
            $this->nextStep();
            $this->finishGame(1);
            $this->setLog('nextGameLeader', 'finishGame (1)', 'Невозможно определить лидера игры.');
            $this->reInitGame();
            return FALSE;
        endif;
    }

    private function getNumberParticipants() {

        $number_participants = Config::get('game.number_participants');
        if ($this->validGameStage(2)):
            $number_participants = 2;
        endif;
        return $number_participants;
    }
}