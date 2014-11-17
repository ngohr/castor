<?php

class View {
	static $elements = array();

	static function Add($name, $element) {
		if(!array_key_exists($name, self::$elements)) {
			self::$elements[$name] = $element;

			return true;
		}

		return false;
	}

	static function Get($name = false, $index = false) {
		if(!$name) {
			return self::$elements;
		}

		if(array_key_exists($name, self::$elements)) {
			if(!$index) {
				return self::$elements[$name];
			} else {
				return self::$elements[$name][$index];
			}
		}

		return false;
	}
}