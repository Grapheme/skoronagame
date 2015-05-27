<?php

class GameQuestions extends \BaseModel {

    protected $table = 'game_questions';
    protected $guarded = array('id','_method','_token');
    protected $fillable = array('title','type','question','answers');
    public static $rules = array('title'=>'required','type'=>'required','question'=>'required','answers'=>'required');

}