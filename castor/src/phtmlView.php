<?php

class View {
	static $elements = array();

	static function Urlencode($uri) {
		$uri = rawurlencode($uri);

		// Feel Free to add other problematic or reserved characters
		$uri = str_replace('%2F', rawurlencode('%2F'), $uri);

		return $uri;
	}

	static function Urldecode($uri) {
		// Feel Free to add other problematic or reserved characters
		$uri = str_replace('%252F', '%2F', $uri);
		return rawurldecode($uri);
	}

	static function Add($name, $element, $escape = false) {
		if($escape) {
			if(is_array($element)) {
				foreach($element as $index => $value) {
					$element[$index] = htmlspecialchars($value);
				}
			} else {
				$element = htmlspecialchars($element);
			}
		}
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