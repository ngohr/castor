<?php 

class hooks {
	static $arrHooks = array('returnType' => array());

	// Only files for defined modules with a load tag in the page or document will included once...
	static function addHook($type, $name, $callback) {
		if(!array_key_exists($type, self::$arrHooks)) {
			throw new Exception("Callback type ".$type." unknown\n");
		}

		self::$arrHooks[$type][$name] = array();

		if(!function_exists($callback)) {
			throw new Exception("Callback function ".$callback." not exists\n");
		}

		self::$arrHooks[$type][$name]['callback'] = $callback;
	}

	static function getHooks($type) {
		if(array_key_exists($type, self::$arrHooks))
			return self::$arrHooks[$type];
		else
			return false;
	}
}