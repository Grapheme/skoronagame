<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateGameUsersTable extends Migration {

	public function up(){
		Schema::create('game_users', function(Blueprint $table){
			$table->increments('id');
            $table->integer('game_id')->default(0)->nullable()->unsigned();
            $table->integer('user_id')->default(0)->nullable()->unsigned();
            $table->boolean('is_bot')->default(0)->nullable()->unsigned();
            $table->tinyInteger('status')->default(0)->nullable()->unsigned();
            $table->tinyInteger('available_steps')->default(0)->nullable()->unsigned();
            $table->tinyInteger('make_steps')->default(0)->nullable()->unsigned();
            $table->string('color')->nullable();
            $table->integer('points')->default(0)->nullable()->unsigned();
            $table->tinyInteger('place')->default(0)->nullable()->unsigned();
            $table->string('json_settings',255)->nullable();
			$table->timestamps();
		});
	}

	public function down(){
		Schema::drop('game_users');
	}
}
