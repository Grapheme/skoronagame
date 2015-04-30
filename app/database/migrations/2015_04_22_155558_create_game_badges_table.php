<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateGameBadgesTable extends Migration {

	public function up(){
		Schema::create('game_badges', function(Blueprint $table){
			$table->increments('id');
            $table->string('name',100)->nullable();
            $table->string('photo',100)->nullable();
			$table->timestamps();
		});
	}

	public function down(){
		Schema::drop('game_badges');
	}

}
