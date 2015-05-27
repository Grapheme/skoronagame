<?php

class BadgesController extends BaseController {

    public static $name = 'badges';
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
            Route::resource('game/badges', $class,
                array(
                    'except' => array('show'),
                    'names' => array(
                        'index' => 'badges.index',
                        'create' => 'badges.create',
                        'store' => 'badges.store',
                        'edit' => 'badges.edit',
                        'update' => 'badges.update',
                        'destroy' => 'badges.destroy'
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

        $badges = GameBadges::orderBy('name')->get();
        return View::make($this->module['tpl'].'badges_index', compact('badges'));
    }

    public function create(){

        return View::make($this->module['tpl'].'badge_create');
    }

    public function store(){

        $validator = Validator::make(Input::all(),GameBadges::$rules);
        if ($validator->passes()):
            GameBadges::create(array('name'=>Input::get('name')));
            $json_request['responseText'] = "Бейдж добавлен";
            $json_request['redirect'] = URL::route('badges.index');
            $json_request['status'] = TRUE;
        else:
            $json_request['responseText'] = 'Неверно заполнены поля';
            $json_request['responseErrorText'] = implode($validator->messages()->all(),'<br />');
        endif;
        return Response::json($json_request, 200);
    }

    public function edit($badge_id){

        if($badge = GameBadges::where('id',$badge_id)->first()):
            return View::make($this->module['tpl'].'badge_edit',compact('badge'));
        else:
            App::abort(404);
        endif;
    }

    public function update($badge_id){

        $validator = Validator::make(Input::all(),GameBadges::$rules);
        if ($validator->passes()):
            $badge = GameBadges::where('id',$badge_id)->firstOrFail();
            $badge->name = Input::get('name');
            $badge->save();
            $badge->touch();

            $json_request['responseText'] = "Бейдж сохранен";
            $json_request['redirect'] = URL::route('badges.index');
            $json_request['status'] = TRUE;
        else:
            $json_request['responseText'] = 'Неверно заполнены поля';
            $json_request['responseErrorText'] = implode($validator->messages()->all(),'<br />');
        endif;
        return Response::json($json_request, 200);
    }

    public function destroy($badge_id){

        if (GameBadges::where('id',$badge_id)->delete()):
            $json_request['responseText'] = "Бейдж удален.";
            $json_request['status'] = TRUE;
        else:
            App::abort(404);
        endif;
    }
}