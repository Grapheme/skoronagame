<?php

class GameUserBadges extends \BaseModel {

    protected $table = 'game_users_badges';
    protected $guarded = array('id','_method','_token');
    protected $fillable = array('user_id','badge_id','game_id');
    public static $rules = array('user_id'=>'required','badge_id'=>'required','game_id'=>'required');

}