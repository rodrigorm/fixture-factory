<?php
App::import('Model', array('AppModel'));
class User extends AppModel {
	var $name = 'User';
	var $useTable = 'users';
	var $hasMany = array(
		'Article'
	);
}
Mock::generate('User');

class FixturefactoryUserFixture extends CakeTestFixture {
	var $name = 'User';
	var $table = 'users';
	var $fields = array(
		'id'      => array('type' => 'integer', 'key' => 'primary'),
		'login'   => array('type' => 'string', 'null' => false),
		'active'  => array('type' => 'boolean', 'null' => true),
		'gender'  => array('type' => 'string', 'length' => 1, 'null' => true)
	);
}
?>