<?php

class GroupsTableSeeder extends Seeder{

	public function run(){
		
        Group::create(array(
			'name' => 'developer',
			'desc' => 'Разработчики',
			'dashboard' => 'admin'
		));

		Group::create(array(
			'name' => 'admin',
			'desc' => 'Администраторы',
			'dashboard' => 'admin'
		));
	}
}