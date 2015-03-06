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
	private $name;
	private $index;

	private $title = false;
	private $returnTyp = false;

	private $arrFile = array();
	private $arrAction = array();
	private $arrModule = array();
	private $elements = array();
	private $sitemap = array();
	private $constants = array();
	private $arrSurface = array();
	private $arrOperator = array();
	private $arrAdapter = array();
	private $expand = array();
	private $cachedir = false;
	private $classname = false;
	private $method = false;

	public function __construct($page, $index) {
		$this->setName($page);
		$this->setIndex($index);
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

	public function addFile($file, $action = false) {
		if($action) {
			$this->arrAction[$action]->setFile($file);
		} else {
			if($file) {
				if(!file_exists($file))
					throw new Exception('File: "'.$file.'" not exists!');
				else
					$this->arrFile[] = $file;
			} else {
				return false;
			}
		}

		return true;
	}

	public function addAction($name, $classname = false, $method = flase) {
		if(!$classname) {
			if(!$this->classname) {
				throw new Exception("Couldt not add action without classname!");
			} else {
				$classname = $this->getClass();
			}
		}

		if(!$method) {
			if(!$this->method) {
				throw new Exception("Couldt not add action without methodname!");
			} else {
				$method = $this->getMethod();
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
		$names = array();

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
			$this->arrAction[$action]->setElement($name, $arr);
		} else {
			return false;
		}

		return true;
	}

	public function getLocal($action, $name) {
		if($this->actionExists($action)) {
			return $this->arrAction[$action]->getElement($name);
		}

		return false;
	}

	public function getLocals($action) {
		if($this->actionExists($action)) {
			return $this->arrAction[$action]->getLocals();
		}

		return false;
	}

	public function setLocalConstant($action, $name, $value) {
		$this->arrAction[$action]->setConstant($name, $value);
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

	public function setMethod($name, $action = false) {
		if($action)
			return $this->arrAction[$action]->setMethod($name);
		else
			return $this->method = $name;
	}


	public function getMethod($action = false) {
		if($action)
			return $this->arrAction[$action]->getMethod($classname);
		else
			return $this->method;
	}

	public function setClass($name, $action = false) {
		if($action)
			return $this->arrAction[$action]->setClass($name);
		else
			return $this->classname = $name;
	}

	public function getClass($action = false) {
		if($action)
			return $this->arrAction[$action]->setClass($classname);
		else
			return $this->classname;
	}

	public function addSurface($name, $obj) {
		$this->arrSurface[$name] = $obj;
	}

	public function addLocalSurface($actionname, $name, $obj) {
		$this->arrAction[$actionname]->addSurface($name, $obj);
	}

	public function getSurface($name = false) {
		if(!$name)
			return $this->arrSurface;
		else
			return $this->arrSurface[$name];
	}

	public function addOperator($name, $obj) {
		$this->arrOperator[$name] = $obj;
	}

	public function addLocalOperator($actionname, $name, $obj) {
		$this->arrAction[$actionname]->addOperator($name, $obj);
	}

	public function getOperator($name = false) {
		if(!$name)
			return $this->arrOperator;
		else
			return $this->arrOperator[$name];
	}

	public function addAdapter($name, $obj) {
		$this->arrAdapter[$name] = $obj;
	}

	public function addLocalAdapter($actionname, $name, $obj) {
		$this->arrAction[$actionname]->addAdapter($name, $obj);
	}

	public function loadAddons($actionname = false) {
			$arrSurfaces = $this->getSurface();
		foreach($arrSurfaces as $name => $obj) {
			module::add($name, $obj['class'], $obj['file'], $objSurface);
			$objSurface->setElements($obj['elements']);
		}

		if($actionname) {
			$arrSurfaces = $this->arrAction[$actionname]->getSurface();
			foreach($arrSurfaces as $name => $obj) {
				module::add($name, $obj['class'], $obj['file'], $objSurface);
				$objSurface->setElements($obj['elements']);
			}
		}

			$arrOperator = $this->getOperator();
		foreach($arrOperator as $name => $obj) {
			module::add($name, $obj['class'], $obj['file'], $objOperator);
			$objOperator->setElements($obj['elements']);
		}
		
		if($actionname) {
			$arrOperator = $this->arrAction[$actionname]->getOperator();
			foreach($arrOperator as $name => $obj) {
				module::add($name, $obj['class'], $obj['file'], $objOperator);
				$objOperator->setElements($obj['elements']);
			}
		}

		$arrAdapter = $this->getAdapter();
		foreach($arrAdapter as $name => $obj) {
			module::add($name, $obj['class'], $obj['file'], $objAdapter);
			$objAdapter->setElements($obj['elements']);
		}
		
		if($actionname) {
			$arrAdapter = $this->arrAction[$actionname]->getAdapter();
			foreach($arrAdapter as $name => $obj) {
				module::add($name, $obj['class'], $obj['file'], $objAdapter);
				$objAdapter->setElements($obj['elements']);
			}
		}
	}

	public function getAdapter($name = false) {
		if(!$name)
			return $this->arrAdapter;
		else
			return $this->arrAdapter[$name];
	}

	public function loadFiles($action = false) {
		if($action) {
			if($file = $this->arrAction[$action]->getFile()) {
				if(!file_exists($file)) {
					throw new Exception('File: '.$file.' for Page "'.$page.'" not exists!');
				} else {
					require_once($file);

					return true;
				}
			}
		}

		foreach($this->arrFile as $index => $file) {
			if(!file_exists($file)) {
				throw new Exception('File: '.$file.' for Page "'.$page.'" not exists!');
			} else {
				require_once($file);
			}
		}

		return true;
	}

	// Run all actions in $this->arrAction, setElements, setPagename and return Array($objAction->run())
	public function load() {
		foreach($this->arrAction as $action => $objAction) {
			$objAction->setPagename($this->getName());

			if($this->getSitemap())
				$objAction->setSitemap($this->getSitemap());

			$arr[$objAction->getName()] = $objAction->run();
		}

		return $arr;
	}

	// Load a defined Action, setElements, setPagename and returns $objAction->run()
	public function call($action) {
		$objAction = $this->arrAction[$action];

		$objAction->setPagename($this->getName());

		if($this->getSitemap())
			$objAction->setSitemap($this->getSitemap());

		return $objAction->run();
	}
}