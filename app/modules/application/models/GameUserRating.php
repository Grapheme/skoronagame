<?php

class GameUserRating extends \BaseModel {

    protected $table = 'game_users_rating';
    protected $guarded = array('id','_method','_token');
    protected $fillable = array('game_id','user_id','rating');
    public static $rules = array();

    public function user(){
        return $this->hasOne('User', 'id', 'user_id');
    }

    public function game(){
        return $this->belongsTo('Game', 'game_id');
    }
}