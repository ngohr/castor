<?php

/*
 * class Surface, a castor Module abstraction with a method load, called from module::get()
 * 
 * - A Surface is a primitive data model, that copy elements, constants and pagenames from $instance to local data procced by $this->init() in final constructs.
 * - A Surface instead of an Operator, do not valid external data.
 * - A Surface must implement a method to connect a node, especially sockets or object instances
 * - A method close, shouldt shutdown the connections, a pointer for factory classes shouldt be unuseable after calling close...
 * - A method reset, shouldt be available to reset a surface, respective a connection to null and start it again...
 * 
 * @todos
 * 
 * #001 Data Models shouldt be a difently clean collection of abstract classes and it must be an experimental features for years of testing, relates module.php #001
 * a
 * @version
 *
 * 0.4 / 27.09.2013
 *
 * @author
 *
 * Nanno Gohr
 * 
 */
abstract class Surface extends Application {
	public $error = false;

	abstract function init();
	abstract function &connect();
	abstract function close();
	abstract function reset();

	public function setError($value) {
		$this->error = $value;

		return true;
	}

	public function getError() {
		return $this->error;
	}

	public function load($instance = false) {
		if($instance) {
			$elements = $instance->getElements();
			if($elements) {
				foreach($elements as $index => $value) {
					$this->addElement($index, $value);
				}
			}
			$pageName = $instance->getPagename();
			if($pageName)
				$this->setPagename($pageName);
			$actionName = $instance->getActionname();
			if($actionName)
				$this->setActionname($actionName);
		}

		if(!$this->init()) {
			throw new Exception($this->getError());
	
			return false;
		}

		return $this;
	}
}