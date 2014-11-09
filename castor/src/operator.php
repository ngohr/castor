<?php

/*
 *
 * Xslt class Operator
 *
 * - class Operator is a data model for loaded instances of data, typically data will load from mysql-db-tables.
 * - Operators are named, they couldt contain a set of named data. Data is stored in an associative array $this->operator [$this->idx]
 * - Operators and all typicall xsltDocument modules must implement a method public function load($instance = false)
 *   At all, an Operator child Object, is an abstraction of document page and action elements for model products.
 *   Only with the known constants of a loaded page, also pagenames and Elements given for $instance = ChildOfApplication, are module concept for xsltDocument satisfying.
 * - Use class Operator to getElements for Page and Objects and use them in an initial load of data, a production env. are db login data get From Operator(instance) or connect to a db...
 * - After adding of extern data you couldt use data with a concurrent name or a new instance of the module to store more data.
 * - Use $this->choose to set a pointer for the named data, use "check" methods to valid data against given data from constants, page elements or extern data.#
 * 
 * @todos
 * 
 * #001 Data Models shouldt be a difently clean collection of abstract classes and it must be an experimental features for years of testing, relates module.php #001
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

abstract class Operator extends Application {
	public $error = false;
	private $operator = array();
	private $idx = "";
	private $dbConnection = false;

	private $memcacheObj;
	
	private $timeout = 60;

	abstract function init();
	
	public function __construct() {
		$this->memcacheObj = new Memcache;
		
		$this->memcacheObj->connect("localhost", 11211);
		$this->memcacheObj->addServer("localhost", 11211);
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
	
		$this->setSitemap($instance->getSitemapAttribute());
	
		if(!$this->init()) {
			$this->setError('Init Operator failed');
	
			return false;
		}
	
		return $this;
	}

	public function add($name) {
		if($name) {
			$this->idx = $name;
			if(!$this->memcacheObj->get($this->idx)) {
				trigger_error("reload keys");
				$this->operator[$this->idx] = array();
				$this->memcacheObj->set($this->idx, $this->operator[$this->idx], 0, $this->timeout);
				
				return true;
			} else {
				trigger_error("have keys");
				$this->choose($name);
			}

			return true;	
		}
	
		return false;
	}

	public function setTimeout($value) {
		$this->timeout = $value;
	}

	public function setMysqliResource(&$resource) {
		$this->dbConnection = $resource;
	}

	public function getMysqliResource() {
		if($this->dbConnection) {
			return $this->dbConnection;
		} else {
			$this->setError('Mysqli Ressource is not set!');
		}
	}

	public function overwrite($name) {
		if($name) {
			$this->idx = $name;
			$this->operator[$this->idx] = array();
			$this->memcacheObj->set($this->idx, $this->operator[$this->idx]);

			return true;	
		}
		
		return false;
	}

	public function choose($name) {
		if($name) {
			$this->idx = $name;
			if($this->operator[$this->idx] = $this->memcacheObj->get($name)) {
				return true;
			}
		}

		return false;
	}
	
	public function set($name, $value) {
		if(!array_key_exists($name, $this->operator[$this->idx])) {
			$this->operator[$this->idx][$name] = array();
		}

		$this->operator[$this->idx][$name] = $value;
		
		$this->memcacheObj->replace($this->idx, $this->operator[$this->idx]);

		return true;
	}
	
	public function extend($name, $idx, $value) {
		if(!array_key_exists($name, $this->operator[$this->idx])) {
			$this->operator[$this->idx][$name] = array();
		}
		
		$this->operator[$this->idx][$name][$idx] = $value;

		$this->memcacheObj->replace($this->idx, $this->operator[$this->idx]);

		return true;
	}

	public function get() {
		return $this->operator[$this->idx];
	}

	public function getData($name, $field = false) {
		if(array_key_exists($name, $this->operator[$this->idx])) {
			if(!$field) {
				return $this->operator[$this->idx][$name];
			} else {
				if(array_key_exists($field, $this->operator[$this->idx][$name])) {
					return $this->operator[$this->idx][$name][$field];
				} else {
					return false;
				}
			}
		} else {
			return false;
		}
	}

	public function getDataOf($index, $name) {
		if(!array_key_exists($index, $this->operator)) {
			return false;
		}

		if(array_key_exists($name, $this->operator[$index])) {
			return $this->operator[$index][$name];
		} else {
			return false;
		}
	}

	public function getIdx() {
		return $this->idx;
	}

	public function check($name, $value) {
		if(array_key_exists($name, $this->operator[$this->idx])) {
			if($this->operator[$this->idx][$name] === $value) {
				return true;
			}
		}
		
		return false;
	}

	public function checkArray($name, $field, $value) {
		if(array_key_exists($field, $this->operator[$this->idx][$name])) {
			if($this->operator[$this->idx][$name][$field] === $value) {
				return true;
			} else {
				return false;
			}
		}

		return false;
	}

	public function checkElement($name, $field) {
		$elements = $this->getElements($name);
		if(!$elements) {
			$this->setError('No Elements!');

			return false;
		}

		if(!array_key_exists($name, $this->operator[$this->idx])) {
			return false;
		}
		if(!array_key_exists($field, $this->operator[$this->idx][$name])) {
			return false;
		}
		if($this->operator[$this->idx][$name][$field] === $elements[$field]) {
			return true;
		} else {
			return false;
		}

		return false;
	}

	public function setError($value) {
		$this->error = $value;

		return true;
	}

	public function getError() {
		return $this->error;
	}


	public function loadMysqliData($name, $table, $idx, $constriction = false) {
		$subquery = "";

		$table = $this->dbConnection->real_escape_string($table);

		if($constriction) {
			$i = 0;
			foreach($constriction as $ident => $pass)  {
				if($i == 0)
					$subquery .= $this->dbConnection->real_escape_string($ident)." = '".$this->dbConnection->real_escape_string($pass)."'";
				else
					$subquery .= " AND ".$this->dbConnection->real_escape_string($ident)." = '".$this->dbConnection->real_escape_string($pass)."'";
				$i++;
			}
		}
		$sql = "SELECT * FROM ".$table." WHERE ".$subquery;
		$result = $this->dbConnection->query($sql);
		if(!$result) {
			$this->setError($this->dbConnection->error);
	
			return false;
		}

		$arr = array();
		if($result->num_rows > 0) {
			while($row = $result->fetch_assoc()) {
				$elements = array();
				foreach($row as $index => $value) {
					$elements[$index] = $value;
				}
				$arr[$row[$idx]] = $elements;
			}
	
			$this->set($name, $arr);
		} else {
			$this->set($name, array());
		}

		return true;
	}

	public function loadMysqliRow($name, $table, $constriction = false) {
		$subquery = "";

		$table = $this->dbConnection->real_escape_string($table);

		if($constriction) {
			$i = 0;
			foreach($constriction as $ident => $pass)  {
				if($i == 0)
					$subquery .= $this->dbConnection->real_escape_string($ident)." = '".$this->dbConnection->real_escape_string($pass)."'";
				else
					$subquery .= " AND ".$this->dbConnection->real_escape_string($ident)." = '".$this->dbConnection->real_escape_string($pass)."'";
				$i++;
			}
		}
		$sql = "SELECT * FROM ".$table." WHERE ".$subquery." LIMIT 1";
		$result = $this->dbConnection->query($sql);
		if(!$result) {
			$this->setError($this->dbConnection->error);
	
			return false;
		} else {
			if($result->num_rows > 0) {
				$row = $result->fetch_assoc();
				$this->set($name, $row);
			} else {
				$this->set($name, array());
			}
		}

		return true;
	}

	public function loadMysqliField($name, $idx, $table, $field, $constriction = false) {
		$subquery = "";

		$table = $this->dbConnection->real_escape_string($table);

		if($constriction) {
			$i = 0;
			foreach($constriction as $ident => $pass)  {
				if($i == 0)
					$subquery .= $this->dbConnection->real_escape_string($ident)." = '".$this->dbConnection->real_escape_string($pass)."'";
				else
					$subquery .= " AND ".$this->dbConnection->real_escape_string($ident)." = '".$this->dbConnection->real_escape_string($pass)."'";
				$i++;
			}
		}
		$sql = "SELECT ".$field." FROM ".$table." WHERE ".$subquery." LIMIT 1";
		$result = $this->dbConnection->query($sql);
		if(!$result) {
			$this->setError($this->dbConnection->error);

			return false;
		} else {
			if($result->num_rows > 0) {
				$row = $result->fetch_row();
				return $this->extend($name, $idx, $row[0]);
			} else {
				return false;
			}
		}

		return false;
	}
}
