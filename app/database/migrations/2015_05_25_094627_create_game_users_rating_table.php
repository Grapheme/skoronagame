<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateGameUsersRatingTable extends Migration {

	public function up(){
		Schema::create('game_users_rating', function(Blueprint $table){
			$table->increments('id');
            $table->integer('game_id')->default(0)->nullable()->unsigned();
            $table->integer('user_id')->default(0)->nullable()->unsigned();
            $table->integer('rating')->default(0)->nullable()->unsigned();
			$table->timestamps();
		});
	}

	public function down(){
		Schema::drop('game_users_rating');
	}

}
