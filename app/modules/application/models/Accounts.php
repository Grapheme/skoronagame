<?php

class Accounts extends BaseModel {

	protected $table = 'users';

	protected $guarded = array();

	protected $hidden = array('password');

    public function games(){

        return $this->hasMany('GameUser','user_id','id');
    }

    public function badges(){

        return $this->hasMany('GameUserBadges','user_id','id');
    }

    public function rating(){

        return $this->hasMany('GameUserRating','user_id','id');
    }
}