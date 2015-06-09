<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateGameGamesTable extends Migration {

	public function up(){
		Schema::create('game_games', function(Blueprint $table){
			$table->increments('id');
            $table->string('status',10)->nullable();
            $table->tinyInteger('stage')->default(0)->nullable()->unsigned();
            $table->integer('started_id')->default(0)->nullable()->unsigned();
            $table->boolean('status_begin')->default(0)->nullable()->unsigned();
            $table->timestamp('date_begin')->nullable();
            $table->boolean('status_over')->default(0)->nullable()->unsigned();
            $table->timestamp('date_over')->nullable();
            $table->text('json_settings')->nullable();
            $table->boolean('sync')->default(0)->nullable()->unsigned();
			$table->timestamps();
		});
	}

	public function down(){
		Schema::drop('game_games');
	}
}
