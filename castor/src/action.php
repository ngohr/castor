<?php

/*
 *
 * Xslt Action Class
 *
 * - Class to open Methods of an instance of child Application Objects.
 * - Acts as factory for castor websites and bypass Controllers to set individual properties for childs of Application Objects.
 * - Data Models, will returned and performed in associatet objects.
 * - A format for Data is defined by setRendering and setReturnType.
 * - Page and Action objects are named. An Action Object relates to a Page and is named as $this->name.
 *
 * @todo
 *
 * #001 An Instance of a Controller couldt call a base loader, load Constants and Elements directly in called Classes.
 * 		Respective an abstract static method "init" couldt load elements and constants and replaces a __construct function for Application childs.
 * 
 * @version
 *
 * 0.91 / 26.09.2013
 *
 * @author
 *
 * Nanno Gohr
 *
 */

class Action {
	private $name = false;
	private $classname = false;
	private $method = false;

	private $pagename = false;
	private $stylesheet = false;
	private $renderingTyp = false;
	private $returnTyp = false;
	private $locals = array();
	private $elements = array();
	private $constants = array();
	private $sitemap = false;
	private $title = "";
	private $cacheApplied = false;
	private $mimeType = false;
	private $file = false;
	private $arrSurface = array();
	private $arrOperator = array();
	private $arrAdapter = array();

	private $expand = array();

	public function __construct($name, $classname = false, $method = false) {
		$this->setName($name);
		$this->setClass($classname);
		$this->setMethod($method);
	}

	public function setPagename($value) {
		$this->pagename = $value;

		return true;
	}
	
	public function getPagename() {
		return $this->pagename;
	}

	public function setName($value) {
		$this->name = $value;

		return true;
	}
	
	public function getName() {
		return $this->name;
	}
	
	public function setTitle($name) {
		$this->title = $name;
	}
	
	public function getTitle() {
		return $this->title;
	}

	public function setFile($value) {
		$this->file = $value;
	}

	public function getFile() {
		return $this->file;
	}

	public function setClass($name) {
		$this->classname = $name;

		return true;
	}
	
	public function getClass() {
		return $this->classname;
	}

	public function getMethod() {
		return $this->method;
	}

	public function setMethod($name) {
		$this->method = $name;

		return true;
	}

	public function setStylesheet($file) {
		$this->stylesheet = $file;

		return true;
	}

	public function getStylesheet() {
		return $this->stylesheet;
	}

	public function setRendering($typ) {
		$this->renderingTyp = $typ;
	}

	public function getRendering() {
		return $this->renderingTyp;
	}

	public function setReturnType($typ) {
		$this->returnTyp = $typ;
	}

	public function getReturnType() {
		return $this->returnTyp;
	}

	public function setLocal($name, $arr) {
		if($name && $name != '') {
			if(array_key_exists($name, $this->locals) && $this->locals[$name]) {
				foreach($arr as $index => $value) {
					$this->locals[$name][$index] = $value;
				}
			} else {
				$this->locals[$name] = $arr;
			}

			return true;
		} else {
			return false;
		}
	}

	public function getLocal($name) {
		if(array_key_exists($name, $this->locals))
			return $this->locals[$name];
		else
			return false;
	}

	public function getLocals() {
		return $this->locals;
	}

	public function addSurface($name, $obj) {
		$this->arrSurface[$name] = $obj;
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
	
	public function getOperator($name = false) {
		if(!$name)
			return $this->arrOperator;
		else
			return $this->arrOperator[$name];
	}
	
	public function addAdapter($name, $obj) {
		$this->arrAdapter[$name] = $obj;
	}
	
	public function getAdapter($name = false) {
		if(!$name)
			return $this->arrAdapter;
		else
			return $this->arrAdapter[$name];
	}

	public function setNodes($arr) {
		$this->expand = $arr;
	}

	public function getNodes() {
		return $this->expand;
	}

	public function setElement($name, $arr) {
		if(array_key_exists($name, $this->elements) && $this->elements[$name]) {
			foreach($arr as $index => $value) {
				$this->elements[$name][$index] = $value;
			}
		} else {
			$this->elements[$name] = $arr;
		}
	}

	public function getElement($name) {
		if(array_key_exists($name, $this->elements))
			return $this->elements[$name];
		else
			return false;
	}

	public function getElements() {
		return $this->elements;
	}

	public function setSitemap($arr) {
		$this->sitemap = $arr;
	}

	public function getSitemap() {
		return $this->sitemap;
	}

	public function setConstant($name, $value) {
		$this->constants[$name] = $value;
	}
	
	public function getConstants() {
		return $this->constants;
	}
	
	public function applyCaching($mimeType) {
		if($mimeType) {
			$this->cacheApplied = true;
			$this->mimeType = $mimeType;
			
			return true;
		}
		
		return false;
	}
	
	public function cacheApplied() {
		if($this->cacheApplied && $this->mimeType)
			return true;
		
		return false;
	}
	
	public function getMimeType() {
		return $this->mimeType;
	}

	public function run() {
		// Load class
		if(!class_exists($this->classname)) {
			throw new Exception('Controller Class: '.$this->classname.' is not defined...'); 
		} else {
			$controller = new $this->classname();
		}

		/*
		 * Set constants for class Application childs
		 *
		 * run() shouldt call methods to make Application->getElements available in _constructors or init methods, relates #001
		 *
		 */
		if(is_subclass_of($controller, 'Application')) {
			$controller->setElements($this->getElements());
			
			// Locals will overwrite elements in Application Classes
			$locals = $this->getLocals();
			foreach($locals as $name => $arr) {
				$controller->overwriteElement($name, $arr);
			}

			$controller->setTitle($this->getTitle());
			$controller->setSitemap($this->getSitemap());
			$controller->setPagename($this->getPagename());
			$controller->setActionname($this->getName());
		}

		// Run method
		$action = $this->getMethod();
		if(method_exists($controller, $action)) {
			return $controller->$action();
		} else {
			throw new Exception('Method: "'.$action.'" not exists in Class: "'.$this->classname.'"');
		}
	}
}