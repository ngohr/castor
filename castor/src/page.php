<?php

/*
 *
 * Xslt Page Class
 *
 * - Construct entities for Action objects.
 * - Acts as factory for castor Main actions and bypass methods to set individual properties.
 * - Page and Action objects are named. Merged names can call Action->run directly.
 * 
 * A file, a classname and a method must be set before named Action directives couldt load.
 * Class Page does not render data from methods called, it acts as container for prepared collection of sites.
 * 
 * @version
 *
 * 0.93 / 27.09.2013
 *
 * @author
 *
 * Nanno Gohr
 *
 */

require_once('action.php');

class Page {
	private $name = false;
	private $index = false;
	private $title = false;
	private $returnTyp = false;

	private $arrFile = array();
	private $arrAction = array();
	private $arrModule = array();
	private $elements = array();
	private $sitemap = array();
	private $constants = array();
	private $arrSurface = array();
	private $expand = array();
	private $cachedir = false;
	private $classname = false;
	private $method = false;

	public function __construct($page) {
		$this->setName($page);
	}

	public function setName($value) {
		$this->name = $value;

		return true;
	}

	public function getName() {
		return $this->name;
	}

	public function actionExists($name) {
		if(!array_key_exists($name, $this->arrAction))
			return false;
		else
			return true;
	}

	public function addConstant($name, $value) {
		$this->constants[$name] = $value;
	}

	public function getConstants() {
		return $this->constants;
	}

	public function addFile($file) {
		if($file) {
			if(!file_exists($file))
				throw new Exception('File: "'.$file.'" not exists!');
			else
				$this->arrFile[] = $file;
		} else {
			return false;
		}

		return true;
	}

	public function addAction($name, $classname = false, $method = flase) {
		if(!$classname) {
			if(!$this->classname) {
				throw new Exception("Couldt not add action without classname!");
			} else {
				$classname = $this->getRootClass();
			}
		}

		if(!$method) {
			if(!$this->method) {
				throw new Exception("Couldt not add action without methodname!");
			} else {
				$method = $this->getRootMethod();
			}
		}

		if($this->actionExists($name)) {
			throw new Exception("Action: ".$name." is not inimitable!");
		}

		$this->arrAction[$name] = new Action($name, $classname, $method);
		$this->arrAction[$name]->setPagename($this->getName());
		$this->locals[$name] = array();

		return true;
	}

	public function getActions() {
		foreach($this->arrAction as $index => $obj)
			$names[] = $index;

		return $names;
	}

	public function getObjAction($name) {
		if(array_key_exists($name, $this->arrAction))
			return $this->arrAction[$name];
		else
			return false;
	}

	public function setElement($name, $arr) {
		if(array_key_exists($name, $this->elements) && $this->elements[$name]) {
			foreach($arr as $index => $value) {
				$this->elements[$name][$index] = $value;
			}
		} else {
			$this->elements[$name] = $arr;
		}

		return true;
	}

	public function getElement($name) {
		return $this->elements[$name];
	}

	public function getElements() {
		return $this->elements;
	}

	public function setLocal($action, $name, $arr) {
		if($this->actionExists($action)) {
			// Local Elements will overwrite elements at load page
			$this->arrAction[$action]->setLocal($name, $arr);
		} else {
			return false;
		}

		return true;
	}

	public function getLocal($action, $name) {
		if($this->actionExists($action)) {
			return $this->arrAction[$action]->getLocal($name);
		}

		return false;
	}

	public function setLocalConstant($action, $name, $value) {
		$this->arrAction[$action]->setConstant($name, $value);
	}
	
	public function setRootClass($name) {
		$this->classname = $name;
		
		return true;
	}
	
	public function getRootClass() {
		return $this->classname;
	}
	
	public function setRootMethod($name) {
		$this->method = $name;
		
		return true;
	}
	
	public function getRootMethod() {
		return $this->method;
	}
	
	public function setNodes($arr) {
		$this->expand = $arr;
		
		return true;
	}

	public function getNodes() {
		return $this->expand;
	}

	public function setLocalNodes($action, $arr) {
		if($this->actionExists($action)) {
			$this->arrAction[$action]->setNodes($arr);
		} else {
			return false;
		}
	}

	public function getLocalNodes($action) {
		if($this->actionExists($action)) {
			return $this->arrAction[$action]->getNodes();
		}
	
		return false;
	}

	public function setActionTitle($title, $action) {
		$this->arrAction[$action]->setTitle($title);
	}

	public function setIndex($value) {
		$this->index = $value;
	}

	public function getIndex() {
		return $this->index;
	}

	public function setTitle($value) {
		$this->title = $value;
	}

	public function getTitle() {
		return $this->title;
	}

	public function setReturnTyp($value, $action = false) {
		if(!$action)
			$this->returnTyp = $value;
		else
			$this->arrAction[$action]->setReturnType($value);
	}

	public function getReturnTyp($action = false) {
		if(!$action)
			return $this->returnTyp;
		else
			return $this->arrAction[$action]->getReturnType();
	}

	public function setRendering($action, $value) {
		if(!$action) {
			foreach($this->arrAction as $action => $obj)
				$obj->setRendering($value);
		} else {
			$this->arrAction[$action]->setRendering($value);
		}
	}

	public function getRendering($action) {
		return $this->arrAction[$action]->getRendering();
	}

	public function setStylesheet($action, $file) {
		$this->arrAction[$action]->setStylesheet($file);
	}

	public function getStylesheet($action) {
		return $this->arrAction[$action]->getStylesheet();
	}
	
	public function getCachefile($action) {
		return $this->arrAction[$action]->getCachefile();
	}

	public function setSitemap($arrPage) {
		$this->sitemap = $arrPage;
	}

	public function getSitemap() {
		return $this->sitemap;
	}

	public function setMethod($action, $method) {
		return $this->arrAction[$action]->setMethod($method);
	}

	public function setClass($action, $classname) {
		return $this->arrAction[$action]->setClass($classname);
	}

	public function addSurface($name, $obj) {
		$this->arrSurface[$name] = $obj;
	}

	public function loadFiles() {
		foreach($this->arrFile as $index => $file) {
			if(!file_exists($file)) {
				throw new Exception('File: '.$file.' for Page "'.$page.'" not exists!');
			} else {
				require_once($file);
			}
		}
	}

	public function setLocals($action, &$objAction) {
		if($this->elements) {
			foreach($this->elements as $name => $value) {
				$objAction->setElement($name, $value);
			}
		}
		
		if(count($this->locals[$action]) > 0) {
			foreach($this->locals[$action] as $name => $value) {
				$objAction->setElement($name, $value);
			}
		}
	}

	// Run all actions in $this->arrAction, setElements, setPagename and return Array($objAction->run())
	public function load() {
		foreach($this->arrAction as $action => $objAction) {
			// Add Page Vars to $objAction
			$this->setLocals($action, $objAction);

			$objAction->setPagename($this->getName());

			if($this->getSitemap())
				$objAction->setSitemap($this->getSitemap());

			$arr[$objAction->getName()] = $objAction->run();
		}

		return $arr;
	}

	public function localCacheApplied($action) {
		return $this->arrAction[$action]->cacheApplied();
	}

	public function getLocalMimeType($action) {
		return $this->arrAction[$action]->getMimeType();
	}

	// Load a defined Action, setElements, setPagename and returns $objAction->run()
	public function call($action) {
		$objAction = $this->arrAction[$action];

		// Add Page Vars to $objAction
		if($this->elements) {
			foreach($this->elements as $name => $value) {
				$objAction->setElement($name, $value);
			}
		}
		if(count($this->locals[$action]) > 0) {
			foreach($this->locals[$action] as $name => $value) {
				$objAction->setElement($name, $value);
			}
		}

		$objAction->setPagename($this->getName());

		$objAction->setSitemap($this->getSitemap());

		if(method_exists($objAction, 'run'))
			return $objAction->run();
		else
			return false;
	}
}