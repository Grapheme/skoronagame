<?php

class GameUserQuestions extends \BaseModel {

    protected $table = 'game_users_questions';
    protected $guarded = array('id','_method','_token');
    protected $fillable = array('started_id','group_id','game_id','user_id','question_id','status','place','answer','seconds');
    public static $rules = array();

    public function user(){
        return $this->hasOne('User', 'id', 'user_id');
    }

    public function game(){
        return $this->belongsTo('Game', 'game_id');
    }

    public function question(){
        return $this->belongsTo('GameQuestions', 'question_id');
    }
}