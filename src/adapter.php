<?php

abstract class Adapter extends Application {
	public $error = false;

	abstract function init();

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