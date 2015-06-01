<?php

class Sessions extends \BaseModel {

    protected $guarded = array();
    protected $table = 'sessions';
    public $timestamps = FALSE;

    public static function setUserLastActivity(){

        if($session = self::find(Session::getId())):
            $session->user_id = Auth::user()->id;
            $session->last_activity = time();
            $session->save();
        endif;
    }

    public static function destroyUserSession(){

        if($session = self::find(Session::getId())):
            $session->user_id = NULL;
            $session->save();
        endif;
    }

    public static function getUsersLastActivity(){

        $users = array();
        if($sessions = self::where('user_id','!=','null')->where('last_activity','>=',Config::get('game.time_activity'))->with('user')->get()):
            foreach($sessions as $session):
                $users[] = $session->user ? $session->user : NULL;
            endforeach;
        endif;
        return $users;
    }

    public static function getUserIDsLastActivity($user_ids = NULL){

        $users = array();
        $user_ids = (array)$user_ids;
        if($sessions = self::where('user_id','!=','null')->whereIn('user_id', $user_ids)->where('last_activity','>=',Config::get('game.time_activity'))->with('user')->get()):
            foreach($sessions as $session):
                if(!empty($session->user)):
                    $users[] = $session->user->id;
                endif;
            endforeach;
        endif;
        return $users;
    }

    public function user(){

        return $this->belongsTo('User','user_id','id');
    }
}