<?php
App::Import('Vendor', 'FixtureFactory.fixture_factory');
Mock::generate('FFactory');

class FixtureFactoryTestCase extends CakeTestCase {

	var $fixtures = array('plugin.fixture_factory.fixture_factory_user', 'plugin.fixture_factory.fixture_factory_article');

	function startTest() {
		ClassRegistry::init('User');
		ClassRegistry::init('MockUser');
	}

	function &_mockModel($model) {
		$mockClass = "Mock$model";
		$mock =& ClassRegistry::init($mockClass);
		ClassRegistry::getInstance()->removeObject($model);
		ClassRegistry::getInstance()->addObject($model, $mock);
		return $mock;
	}

	function endTest() {
		FFactory::clean();
		ClassRegistry::getInstance()->flush();
	}

	// =========================
	// = Building and Creating =
	// =========================

	function testBuildingRecordDataWithEmptyDefaults() {
		FFactory::define('User');

		$result = FFactory::build('User');
		$expected = array();

		$this->assertEqual($result, $expected);
	}

	function testBuildingRecordDataWithNotEmptyDefaults() {
		$defaults = array(
			'active' => 1,
			'gender' => 'm'
		);
		FFactory::define('User', $defaults);

		$result = FFactory::build('User');
		$expected = $defaults;

		$this->assertEqual($result, $expected);
	}

	function testBuildMergesDefaultsWithPassedAttributes() {
		$defaults = array(
			'active' => 1,
			'gender' => 'm'
		);
		FFactory::define('User', $defaults);

		$result = FFactory::build('User', array(
			'login' => 'joe'
		));
		$expected = array(
			'active' => 1,
			'gender' => 'm',
			'login' => 'joe'
		);

		$this->assertEqual($result, $expected);

		$result = FFactory::build('User', array(
			'gender' => 'f'
		));
		$expected = array(
			'active' => 1,
			'gender' => 'f'
		);

		$this->assertEqual($result, $expected);
	}

	function testCreatingRecordWithBuiltData() {
		$user =& $this->_mockModel('User');

		$defaults = array(
			'active' => 1,
			'gender' => 'm'
		);
		FFactory::define('User', $defaults);

		$user->expectOnce('create', array($defaults));
		$user->setReturnValue('save', true);

		FFactory::create('User');
	}

	function testCreateSavesRecord() {
		$user =& $this->_mockModel('User');

		FFactory::define('User', array(
			'active' => 1,
			'gender' => 'm'
		));

		$user->expectOnce('save');
		$user->setReturnValue('save', true);

		FFactory::create('User');
	}

	function testCreateReturnsRecordReadFromDbAfterSave() {
		FFactory::define('User', array(
			'id' => 5,
			'active' => 1,
			'gender' => 'm'
		));

		$result = FFactory::create('User');

		$expected = ClassRegistry::init('User')->read(null, 5);

		$this->assertEqual($result, $expected);
	}

	function testCreateSavesRecordWithBuiltRecordData() {    
		$defaults = array(
			'active' => 1,
			'gender' => 'm'
		);
		FFactory::define('User', $defaults);

		$user = FFactory::create('User');

		$this->assertEqual(Set::extract($user, 'User.active'), 1);
		$this->assertEqual(Set::extract($user, 'User.gender'), 'm');
	}

	function testCreateDoesNotTryToCreateOrSaveRecordWithEmptyRecordData() {
		$user =& $this->_mockModel('User');

		FFactory::define('User');

		$user->expectNever('create');
		$user->expectNever('save');

		FFactory::create('User');
	}

	// =============
	// = Sequences =
	// =============

	function testSequenceReturnsSequentialValues() {
		FFactory::sequence('test', 'value {n}');

		$this->assertEqual(FFactory::next('test'), 'value 1');
		$this->assertEqual(FFactory::next('test'), 'value 2');
	}

	function testSequenceReturnsCorrectValuesIfStartedAtChosenIndex() {
		FFactory::sequence('test', 'value {n}', 11);
		$ff = FFactory::getInstance();

		$this->assertEqual($ff->next('test'), 'value 11');
		$this->assertEqual($ff->next('test'), 'value 12');
	}

	function testBuildUsesSequences() {
		FFactory::sequence('user_login', 'user{n}');

		$defaults = array(
			'login' => array('sequence', 'user_login'),
			'active' => 1,
			'gender' => 'm'
		);
		FFactory::define('User', $defaults);

		$result = FFactory::build('User');
		$expected = array(
			'login' => 'user1',
			'active' => 1,
			'gender' => 'm'
		);
		$this->assertEqual($result, $expected);

		$result = FFactory::build('User');
		$expected = array(
			'login' => 'user2',
			'active' => 1,
			'gender' => 'm'
		);
		$this->assertEqual($result, $expected);
	}

	// ================
	// = Associations =
	// ================

	function testBuildUsesAssocs() {
		$userDefaults = array(
			'id' => 1,
			'login' => 'joe'
		);
		FFactory::define('User', $userDefaults);

		$articleDefaults = array(
			'title' => 'lorem ipsum',
			'user_id' => array('assoc', 'User')
		);
		FFactory::define('Article', $articleDefaults);

		$result = FFactory::build('Article');
		$expected = array(
			'title' => 'lorem ipsum',
			'user_id' => 1
		);

		$this->assertEqual($result, $expected);
	}

	function testAttributeOfAssociatedRecordIsCorrectlyFetchedForAliasedModels() {
		ClassRegistry::init('User')->alias = 'Superuser';

		$userDefaults = array(
			'id' => 1,
			'login' => 'joe'
		);
		FFactory::define('User', $userDefaults);

		$articleDefaults = array(
			'title' => 'lorem ipsum',
			'user_id' => array('assoc', 'User')
		);
		FFactory::define('Article', $articleDefaults);

		$result = FFactory::build('Article');
		$expected = array(
			'title' => 'lorem ipsum',
			'user_id' => 1
		);

		$this->assertEqual($result, $expected);
	}

	function testAssociatedRecordsAreCreated() {
		$result = ClassRegistry::init('User')->findById(1);
		$expected = null;
		$this->assertEqual($result, $expected);

		$userDefaults = array(
			'id' => 1,
			'login' => 'joe'
		);
		FFactory::define('User', $userDefaults);

		$articleDefaults = array(
			'title' => 'lorem ipsum',
			'user_id' => array('assoc', 'User')
		);
		FFactory::define('Article', $articleDefaults);

		FFactory::build('Article');

		$result = ClassRegistry::init('User')->findById(1);
		$this->assertTrue(is_array($result));
	}

	// =====================
	// = Aliased factories =
	// =====================

	function testAliasedFactoryCreatesRecordsOfBaseModel() {
		$user =& $this->_mockModel('User');

		FFactory::define('InactiveUser', array(
			'active' => 0,
			'gender' => 'm'
		), 'User');

		$user->expectOnce('save');
		$user->setReturnValue('save', true);

		FFactory::create('InactiveUser');
	}

	// =============
	// = Exceptions =
	// =============

	function testBuildThrowsExceptionIfFactoryIsNotDefined() {
		$this->expectException('FactoryNotDefinedException');
		FFactory::build('Undefined');
	}

	function testCreateThrowsExceptionIfRecordSaveIsUnsuccessfull() {
		$user =& $this->_mockModel('User');
		$user->setReturnValue('save', false);
		$this->expectException('RecordNotSavedException');

		FFactory::define('User');

		FFactory::create('User', array('login' => 'joe'));
	}
}
?>