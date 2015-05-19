<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateGameMapfieldsTable extends Migration {

	public function up(){
		Schema::create('game_mapfields', function(Blueprint $table){
			$table->increments('id');
            $table->integer('game_id')->default(0)->nullable()->unsigned();
            $table->integer('user_id')->default(0)->nullable()->unsigned();
            $table->tinyInteger('zone')->default(0)->nullable()->unsigned();
            $table->boolean('capital')->default(0)->nullable()->unsigned();
            $table->integer('lives')->default(1)->nullable()->unsigned();
            $table->integer('points')->default(0)->nullable()->unsigned();
            $table->tinyInteger('status')->default(0)->nullable()->unsigned();
            $table->string('json_settings',255)->nullable();
			$table->timestamps();
		});
	}

    public function down(){
		Schema::drop('game_mapfields');
	}

}
