<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateGameQuestionsTable extends Migration {

	public function up(){
		Schema::create('game_questions', function(Blueprint $table){
			$table->increments('id');
            $table->string('title',128)->nullable();
            $table->string('type',10)->nullable();
            $table->text('question')->nullable();
            $table->text('answers')->nullable();
			$table->timestamps();
		});
	}

	public function down(){
		Schema::drop('game_questions');
	}

}
