<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateUloginTable extends Migration {

	public function up(){
		Schema::create('ulogin', function(Blueprint $table){
            $table->increments('id');
            $table->integer('user_id')->default(0)->nullable()->unsigned();
            $table->string('network', 255)->nullable();
            $table->string('identity', 255)->nullable()->unique();
            $table->string('email', 128)->nullable();
            $table->string('first_name', 128)->nullable();
            $table->string('last_name', 128)->nullable();
            $table->string('nickname', 128)->nullable();
            $table->string('country', 64)->nullable();
            $table->string('city', 64)->nullable();
            $table->string('photo', 512)->nullable();
            $table->string('photo_big', 512)->nullable();
            $table->string('bdate', 10)->nullable();
            $table->tinyInteger('sex')->default(0)->nullable()->unsigned();
            $table->string('profile', 512)->nullable();
            $table->string('uid', 32)->nullable();
            $table->string('access_token', 512)->nullable();
            $table->string('token_secret', 512)->nullable();
            $table->boolean('verified_email')->default(0)->nullable()->unsigned();
            $table->timestamps();
		});
	}

    public function down(){
		Schema::drop('ulogin');
	}
}
