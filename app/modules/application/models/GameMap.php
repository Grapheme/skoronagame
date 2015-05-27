<?php

class GameMap extends \BaseModel {

    protected $table = 'game_mapfields';
    protected $guarded = array('id','_method','_token');
    protected $fillable = array('game_id','user_id','zone','capital','lives','status','points','json_settings');
    public static $rules = array();

    public function user(){
        return $this->hasOne('User', 'id', 'user_id');
    }

    public function game(){
        return $this->belongsTo('Game', 'game_id');
    }
}