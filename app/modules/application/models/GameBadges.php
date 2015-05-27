<?php

class GameBadges extends \BaseModel {

    protected $table = 'game_badges';
    protected $guarded = array('id','_method','_token');
    protected $fillable = array('name');
    public static $rules = array('name'=>'required');

}