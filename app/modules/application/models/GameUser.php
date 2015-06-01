<?php

class GameUser extends \BaseModel {

    protected $table = 'game_users';
    protected $guarded = array('id','_method','_token');
    protected $fillable = array('game_id','user_id','is_bot','status','available_steps','make_steps','color','points','place','json_settings');
    public static $rules = array();

    public function user(){
        return $this->hasOne('User', 'id', 'user_id');
    }

    public function user_social(){
        return $this->hasOne('Ulogin', 'user_id', 'user_id');
    }

    public function game(){
        return $this->belongsTo('Game', 'game_id');
    }

    public function session(){
        return $this->belongsTo('Sessions', 'user_id', 'user_id');
    }
}