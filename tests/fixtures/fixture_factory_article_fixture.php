<?php
App::import('Model', array('AppModel'));
class Article extends AppModel {
	var $name = 'Article';
	var $useTable = 'articles';
	var $belongsTo = array(
		'User'
	);
}

class FixtureFactoryArticleFixture extends CakeTestFixture {
	var $name = 'Article';
	var $table = 'articles';
	var $fields = array(
		'id'      => array('type' => 'integer', 'key' => 'primary'),
		'title'   => array('type' => 'string', 'null' => false),
		'user_id' => array('type' => 'integer', 'null' => true)
	);
}
?>