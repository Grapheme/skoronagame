<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateGameUsersQuestionsTable extends Migration {

	public function up(){
		Schema::create('game_users_questions', function(Blueprint $table){
			$table->increments('id');
            $table->string('group_id',25)->nullable();
            $table->integer('game_id')->default(0)->nullable()->unsigned();
            $table->integer('user_id')->default(0)->nullable()->unsigned();
            $table->integer('question_id')->default(0)->nullable()->unsigned();
            $table->boolean('status')->default(0)->nullable()->unsigned();
            $table->tinyInteger('place')->default(0)->nullable()->unsigned();
            $table->integer('answer')->default(0)->nullable()->unsigned();
            $table->integer('seconds')->default(0)->nullable()->unsigned();
			$table->timestamps();
		});
	}

    public function down(){
		Schema::drop('game_users_questions');
	}

}
