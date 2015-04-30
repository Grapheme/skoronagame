<?php

class UserTableSeeder extends Seeder{

	public function run(){
		
		#DB::table('users')->truncate();

		User::create(array(
            'group_id'=>1,
			'name'=>'Разработчик',
			'surname'=>'',
			'email'=>'developer@skorona.ru',
			'active'=>1,
			'password'=>Hash::make('grapheme1234'),
			'photo'=>'',
			'thumbnail'=>'',
			'temporary_code'=>'',
			'code_life'=>0,
			'remember_token' => 'Ycr4p62EPv3x3UWabeo3NpiSdJmI7hT3E460C5eTuiFKp1Vbjg6WL2M2bmPv',
		));

		User::create(array(
            'group_id'=>2,
			'name'=>'Администратор',
			'surname'=>'',
			'email'=>'admin@skorona.ru',
			'active'=>1,
			'password'=>Hash::make('grapheme1234'),
			'photo'=>'',
			'thumbnail'=>'',
			'temporary_code'=>'',
			'code_life'=>0,
		));



	}
}