<?php

/*
 * castors module Class
 *
 * - class module provides a static singleton pattern to add constructed Modules once -> The <load> Tag defined in configuration files.
 * - Typical castor modules provides a relation to a given connection or a data instance
 * - Modules are named from config constants, but they typically provide a key to get multiple data loaded from the instance.
 * - ELements from definitions are added while the page loaded all modules with a load tag for the action
 * - Also, modules will be loaded by getting a named object and abstract a given collection of named data models.
 *
 * [NOTE] module::get does not add a new instance but overwrites the loaded elements with elements from $instance.
 *        typical if(isnull) singleton patterns must be implemented in the getter functions of the module.
 *        constructors of the custom modules are called on page load deconstructors after page load.
 *        The onReady Hook calls after all elements and values from instance was set and init() was called. It takes parameters from get()
 *
 * @todos
 *
 * #001 Data Models shouldt be a difently clean collection of abstract classes and it must be an experimental features for years of testing.
 *
 * @version
 *
 * 0.9 / 04.05.2016
 *
 * @author
 *
 * Nanno Gohr
 *
 */

class module {
	static $arrModules = array();

	// Only files for defined modules with a load tag in the page or document will included once...
	static function addFile($filename) {
		if(!file_exists($filename)) {
			return false;
		} else {
			require_once($filename);
		}

		return true;
	}

	// Only the first module of given load tags will added to $arrModules, elements will set only if the module will provided by get()
	static function add($name, $classname, $file, &$obj) {
		if(!self::addFile($file)) {
			throw new Exception('File '.$file.' not found!');
		}

		if(!array_key_exists($name, self::$arrModules) || !self::$arrModules[$name])
			self::$arrModules[$name] = new $classname;

		$obj = self::$arrModules[$name];

		return true;
	}

	// Get a reference of the defined module and set all elements of the definition and given instance
	static function &get($name, $instance) {
		$args = func_num_args();
		if($args < 2) {
			throw new Exception("Not enough arguments for module::get");
		}

		$arrArgs = func_get_args();

		$params = array();
		for($i = 2; $i < count($arrArgs); $i++) {
			$params[] = $arrArgs[$i];
		}

		if(!array_key_exists($name, self::$arrModules)) {
			throw new Exception('Module '.$name.' not found!');
		}

		self::$arrModules[$name]->load($instance);

		if(method_exists(self::$arrModules[$name], "onReady"))
			call_user_func_array(array(self::$arrModules[$name], "onReady") , $params);

		return self::$arrModules[$name];
	}

	// If a module exists
	static function exists($name) {
		if(array_key_exists($name, self::$arrModules))
			return true;

		return false;
	}

	static function help($name) {
		if(!self::exists($name))
			throw new Exception("Unknown module: '".$name."'");
		if(isset(self::$arrModules[$name]->helpText)) {
			echo self::$arrModules[$name]->helpText."\n\n";
		}
		if(isset(self::$arrModules[$name]->helpParameters) && is_array(self::$arrModules[$name]->helpParameters)) {
			echo "Parameters for '".$name."':\n\n";
			foreach(self::$arrModules[$name]->helpParameters as $arr => $val) {
				echo("'".$arr."' => ");
				print_r($val);
			}
		}
		echo "\n";
		if(isset(self::$arrModules[$name]->helpMethods) && is_array(self::$arrModules[$name]->helpMethods)) {
			echo "Methods for '".$name."':\n\n";
			foreach(self::$arrModules[$name]->helpMethods as $method => $helpText) {
				echo "'".$method."' => ".$helpText."\n";
			}
		}
	}
}
