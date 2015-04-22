<?php

class TablesSeeder extends Seeder{

	public function run(){

		Setting::create(array(
			'id' => 1,
			'name' => 'language',
			'value' => 'ru',
		));
	}
}