<?php

class GamerController extends BaseController {

    public static $name = 'gamers';
    public static $group = 'application';

    /****************************************************************************/
    public function __construct(){

        $this->module = array(
            'tpl' => static::returnTpl('admin'),
        );
        View::share('module', $this->module);
    }
    /****************************************************************************/
    public static function returnRoutes($prefix = null) {

        $class = __CLASS__;
        Route::group(array('before' => 'admin.auth', 'prefix' => 'admin'), function () use ($class) {
            Route::resource('game/gamers', $class,
                array(
                    'except' => array('show','create','store','edit','update'),
                    'names' => array(
                        'index' => 'gamer.index',
                        'destroy' => 'gamer.destroy'
                    )
                )
            );
            Route::get('game/statistic', array('as'=>'games-statistic','uses'=>$class.'@statistic'));
        });
    }
    /****************************************************************************/
    public static function returnInfo() {}

    public static function returnMenu() {}

    public static function returnActions() {}
    /****************************************************************************/

    public function index(){

        if($gamers = Accounts::orderBy('created_at','DESC')->where('group_id',3)->with('games','rating')->paginate(50)):
            foreach($gamers as $index => $gamer):
                $gamers[$index]->user_total_games = $gamer->games->count();
                $gamers[$index]->user_rating = 0;
                $gamers[$index]->user_total_rating = 0;
                $gamers[$index]->user_winners = 0;
                $gamers[$index]->user_total_winners = 0;

                if($gamer->games->count()):
                    foreach($gamer->games as $game):
                        if($game->place == 1):
                            $gamers[$index]->user_total_winners++;
                        endif;
                        if($game->updated_at->timestamp >= $game->updated_at->firstOfMonth()->timestamp):
                            if($game->place == 1):
                                $gamers[$index]->user_winners++;
                            endif;
                        endif;
                    endforeach;
                endif;
                if($gamer->rating->count()):
                    foreach($gamer->rating as $rating):
                        $gamers[$index]->user_total_rating += $rating->rating;
                        if($rating->updated_at->timestamp >= $rating->updated_at->firstOfMonth()->timestamp):
                            $gamers[$index]->user_rating += $rating->rating;
                        endif;
                    endforeach;
                endif;
            endforeach;
        endif;
        return View::make($this->module['tpl'].'gamers_index', compact('gamers'));
    }

    public function destroy($user_id){

        if (GameUser::where('user_id',$user_id)->delete()):
            GameUserRating::where('user_id',$user_id)->delete();
            GameUserQuestions::where('user_id',$user_id)->delete();
            GameUserBadges::where('user_id',$user_id)->delete();
            $json_request['responseText'] = "Игрок удален.";
            $json_request['status'] = TRUE;
        else:
            App::abort(404);
        endif;
    }

    public function statistic(){

        $req_count = Accounts::orderBy('active',1)->where('group_id',3)->count();
        $games_time = 0;
        $games = Game::where('status','over')->where('status_begin',1)->where('status_over',1)->get();
        if($games->count()):
            $games_time_def = 0;
            foreach($games as $game):
                if($game->date_over != '0000-00-00 00:00:00'):
                    $games_time_def += Carbon::createFromTimestamp(strtotime($game->date_over))->diffInMinutes(Carbon::createFromTimestamp(strtotime($game->date_begin)));
                endif;
            endforeach;
            $games_time = round($games_time_def/$games->count(),0);
        endif;
        $game_medium_time = $games_time;

        $question_time = 0;
        if($users_question_count = GameUserQuestions::where('status',2)->select(DB::raw('count(*) as user_count'))->pluck('user_count')):
            $users_seconds_sum = GameUserQuestions::where('status',2)->sum('seconds');
            $question_time = round($users_seconds_sum/$users_question_count,0);
        endif;
        $question_medium_time = $question_time;
        return View::make($this->module['tpl'].'gamers_statistic', compact('req_count','game_medium_time','question_medium_time'));
    }
}