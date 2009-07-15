<?php
class FactoryNotDefinedException extends Exception { }
class RecordNotSavedException extends Exception { }

class FFactory {

	static $_instance = array();

	var $models = array();

	var $sequences = array();

	static function getInstance() {
		if (!self::$_instance) {
			self::$_instance = new self;
		}
		return self::$_instance;
	}

	static function clean() {
		$_this = self::getInstance();
		$_this->models    = array();
		$_this->sequences = array();
	}

	static function define($alias, $attributes = array(), $model = null) {
		$_this = self::getInstance();
		if (empty($model)) {
			$model = Inflector::classify($alias);
		}
		$_this->models[$alias] = array('attributes' => $attributes, 'model' => $model);
	}

	static function sequence($seq, $value, $start = 1) {
		$_this = self::getInstance();
		$_this->sequences[$seq] = array('n' => $start, 'value' => $value);
	}

	function next($seq) {
		$_this = self::getInstance();
		if (!empty($_this->sequences[$seq])) {
			return str_replace('{n}', $_this->sequences[$seq]['n']++, $_this->sequences[$seq]['value']);
		}
		return null;
	}

	static function build($alias, $attributes = array()) {
		$_this = self::getInstance();
		if (!empty($_this->models[$alias])) {
			$data = array_merge($_this->models[$alias]['attributes'], $attributes);
			foreach ($data as &$value) {
				if (is_array($value)) {
					switch ($value[0]) {
						case 'sequence':
						$value = $_this->next($value[1]);
						break;
						case 'assoc':
						if (empty($value[2])) {
							$value[2] = 'id';
						}
						$value = $_this->assoc($value[1], $value[2]);
						break;
						default:
						$value = null;
					}
				}
			}
			return $data;
		} else {
			throw new FactoryNotDefinedException("Factory [$alias] is not defined.");
		}
		return null;
	}

	static function create($alias, $attributes = array(), $throwException = true) {
		$_this = self::getInstance();
		$data = self::build($alias, $attributes);
		if (!empty($data)) {
			$model = $_this->load($_this->models[$alias]['model']);
			$model->create($data);
			if ($model->save()) {
				return $model->read();
			} elseif ($throwException) {
				throw new RecordNotSavedException("Could not save record with [$alias] factory.");
			}
		}
		return null;
	}

	function assoc($alias, $attribute = 'id') {
		$_this = self::getInstance();
		$associated = FFactory::create($alias);
		return $associated[$_this->load($_this->models[$alias]['model'])->alias][$attribute];
	}

	function load($model) {
		$model =& ClassRegistry::init($model);
		return $model;
	}
}
?>