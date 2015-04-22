<?php

class ApplicationController extends BaseController {

    public static $name = 'application';
    public static $group = 'application';

    /****************************************************************************/
    public function __construct(){

    }
    /****************************************************************************/
    public static function returnRoutes($prefix = null) {

        $class = __CLASS__;
        Route::group(array('before'=>'login','prefix'=>''), function() use ($class) {
            Route::get('login/auto/user/{user_id}', array('before'=>'login','as'=>'auto-auth','uses'=>$class.'@AutoAuth'));
        });

        if (Auth::check() && Auth::user()->group_id == 3):
            Route::group(array('prefix' => self::$name), function() use ($class) {
                Route::get('', array('as'=>'game','uses'=>$class.'@indexGame'));
            });
        endif;
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
            'link' => self::$group.'/questions/quiz',
            'class' => 'fa-circle',
        );
        $menu_child[] = array(
            'title' => 'Вопросы с ответами',
            'link' => self::$group.'/questions/with-answers',
            'class' => 'fa-circle-o',
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
            'view'              => 'Доступ на просмотр',
            'create.questions'  => 'Создание вопросов',
            'edit.questions'    => 'Редактирование вопросов',
            'delete.questions'  => 'Удаление вопросов',
        );
    }
    /****************************************************************************/
    public function AutoAuth($user_id){

        if(Auth::check()):
            Auth::logout();
        endif;
        if ($user = User::where('id',$user_id)->where('active',1)->first()):
            Auth::login($user);
            return Redirect::to(AuthAccount::getStartPage());
        else:
            return Redirect::back();
        endif;

    }
    /****************************************************************************/

    /****************************************************************************/
    /********************************* GAME *************************************/
    /****************************************************************************/

    public function indexGame(){


    }

}