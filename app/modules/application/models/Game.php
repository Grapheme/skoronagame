<?php

class Game extends \BaseModel {

    protected $table = 'game_games';
    protected $guarded = array('id','_method','_token');
    protected $fillable = array('status','stage','started_id','status_begin','date_begin','status_over','date_over','json_settings');
    public static $rules = array();

    public function started_user(){
        return $this->hasOne('User', 'id', 'user_id');
    }

    public function users(){
        return $this->hasMany('GameUser', 'game_id', 'id');
    }

    public function map_places(){

        return $this->hasMany('GameMap', 'game_id', 'id');
    }
}