<?php

class View {
	static $elements = array();

	static function Add($value) {
		self::$elements[$index] = $value;
	}
	
	static function Get($index) {
		if(array_key_exists(index, self::$elements))
			self::$elements[$index];
		else
			return false;
	}
	
}