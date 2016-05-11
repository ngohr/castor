<?php

abstract class Adapter extends Application {
	abstract function init();

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

		$this->setSitemap($instance->getSitemapAttribute());

		if(!$this->init()) {
			throw new Exception($this->getError());
		}

		return true;
	}
}