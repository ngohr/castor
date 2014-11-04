<?php

/*
 * castors module Class
 * 
 * - class module provides a static singleton pattern to save constructed Modules, defined in configuration files.
 * - Typical castor modules provides a relation to a given connection or a data instance.
 * - Modules are named from config constants, but they typically provide a key to get multiple data loaded from the instance.
 *   Also, modules will be loaded by getting a named object and abstract a given collection of named data models.
 *   
 * @todos
 * 
 * #001 Data Models shouldt be a difently clean collection of abstract classes and it must be an experimental features for years of testing.
 * 
 * @version
 *
 * 0.4 / 27.09.2013
 *
 * @author
 *
 * Nanno Gohr
 * 
 */

class module {
	static $arrModules = array();

	static function addFile($filename) {
		if(!file_exists($filename)) {
			return false;
		} else {
			require_once($filename);
		}

		return true;
	}

	static function add($name, $classname, $file, &$obj) {
		if(!self::addFile($file))
			throw new Exception('File '.$file.' not found!');

		if(!array_key_exists($name, self::$arrModules) || !self::$arrModules[$name])
			self::$arrModules[$name] = new $classname;

		$obj = self::$arrModules[$name];

		return true;
	}

	static function get($name, $instance) {
		return self::$arrModules[$name]->load($instance);
	}
}	
