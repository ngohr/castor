<?php

/*
 *
 * Application Class
 *
 * - Defines methods to handle Elements, constants and localNodes defined by a page loader.
 * - Elements arent restrictively designed for castor element collections. Class Application in all is an independent data container.
 * 
 * - Controller Classes shouldt inherit class Application
 *
 * @todo
 *
 * #001 Include Mods is not developed, relates main.php #009.
 * #002 __construct does nothing, implement __clone constructor or otherwise, relates to action.php #001.
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
require_once('page.php');

// Include mods, relates to todo #001
// require(SITE_PATH.'mods/xmlForm.php');

class Application {
	private $elements = false;
	private $sitemap = false;
	private $error = false;

	public $pagename = false;
	public $actionname = false;
	public $arrSurface = false;

	public function __construct() {
		// Construct Main Constants / params or otherwhise, relates todo #002
	}

	public function setPagename($value) {
		$this->pagename = $value;

		return true;
	}
	
	public function getPagename() {
		return $this->pagename;
	}

	public function setActionname($value) {
		$this->actionname = $value;

		return true;
	}
	
	public function getActionname() {
		return $this->actionname;
	}
	
	public function getObjPage($name) {
		if(array_key_exists($name, $this->sitemap))
			return $this->sitemap[$name];
		else
			return false;
	}

	// If sitemap was set, getSitemap and getSitemapArray couldt construct sitemap/page elements as model
	// Thats for factory classes, websites repective PHP-Files and Obj Page
	public function setSitemap($arr) {
		$this->sitemap = $arr;
	}

	// Construct sitemap/page elements as model, returns DomElement
	public function getSitemap($computeActions = true) {
		$domDocumentObj = new DOMDocument('1.0', 'UTF-8');
		$domDocumentObj->preserveWhiteSpace = true;
		$domDocumentObj->formatOutput = true;
		
		if(!$this->sitemap || count($this->sitemap) <= 0) {
			return false;
		}

		$sitemapElement = $domDocumentObj->createElement('sitemap');
		if($sitemapElement) {
			foreach($this->sitemap as $name => $obj) {
				$elementPage = $domDocumentObj->createElement('page');
				if($name == $this->getPagename())
					$elementPage->setAttribute('active', 'true');

				$elementPagename = $domDocumentObj->createElement('name');
				$text = $domDocumentObj->createTextNode($name);
				$elementPagename->appendChild($text);

				$elementPage->appendChild($elementPagename);

				if($obj->getTitle() && $obj->getTitle() != "") {
					$elementTitle = $domDocumentObj->createElement('title');
					$text = $domDocumentObj->createTextNode($obj->getTitle());
					$elementTitle->appendChild($text);
					$elementPage->appendChild($elementTitle);
				}

				$elementIndex = $domDocumentObj->createElement('index');
				$text = $domDocumentObj->createTextNode($obj->getIndex());
				$elementIndex->appendChild($text);
				$elementPage->appendChild($elementIndex);

				if($constants = $obj->getConstants()) {
					foreach($constants as $constantName => $constantValue) {
						$elementConstant = $domDocumentObj->createElement($constantName);
						$text = $domDocumentObj->createTextNode($constantValue);
						$elementConstant->appendChild($text);
						$elementPage->appendChild($elementConstant);
					}
				}
				
				if($computeActions) {
					$arrActions = $obj->getActions();
					foreach($arrActions as $action => $name) {
						$objAction = $obj->getObjAction($name);
	
						$elementAction = $domDocumentObj->createElement('action');
	
						$elementActionname = $domDocumentObj->createElement('name');
						$text = $domDocumentObj->createTextNode($name);
						$elementActionname->appendChild($text);
						
						$elementAction->appendChild($elementActionname);

						if($objAction->getTitle() && $objAction->getTitle() != "") {
							$elementActiontitle = $domDocumentObj->createElement('title');
							$text = $domDocumentObj->createTextNode($objAction->getTitle());
							$elementActiontitle->appendChild($text);
							
							$elementAction->appendChild($elementActiontitle);
						}
	
						if($constants = $objAction->getConstants()) {
							foreach($constants as $constantName => $valueConstant) {
								$element = $domDocumentObj->createElement($constantName);
								$txt = $domDocumentObj->createTextNode($valueConstant);
								$element->appendChild($txt);
								$elementAction->appendChild($element);
							}
						}
	
						$elementPage->appendChild($elementAction);
					}
				}

				$sitemapElement->appendChild($elementPage);
			}
		}

		return $sitemapElement;
	}

	// Construct sitemap/page elements as model, returns Array
	public function getSitemapArray($computeActions = true) {
		if(!$this->sitemap)
			return false;

		$i = 0;
		foreach($this->sitemap as $pagename => $obj) {
			$arr = array('name' => $pagename, 'title' => $obj->getTitle(), 'index' => $obj->getIndex());
			if($constants = $obj->getConstants()) {
				foreach($constants as $constantName => $constantValue) {
					$arr[$constantName] = $constantValue;
				}
			}
			$arrSitemap['page'][$i] = $arr;

			if($computeActions) {
				$arrSitemap['page'][$i]['action'] = array();
				$arrActions = $obj->getActions();
	
				foreach($arrActions as $idx => $name) {
					$objAction = $obj->getObjAction($name);
					$arrSitemap['page'][$i]['action'][$idx] = array('name' => $name, 'title' => $objAction->getTitle());
					if($constants = $objAction->getConstants()) {
						foreach($constants as $constantName => $constantValue) {
							$arrSitemap['page'][$i]['action'][$idx][$constantName] = $constantValue;
						}
					}
				}
			}
			
			$i++;
		}

		return $arrSitemap;
	}
	
	public function getSitemapAttribute() {
		return $this->sitemap;
	}

	// Elements, couldt set and get for page and action constants.
	// Thats for factory classes, websites repective PHP-Files and Obj Page
	public function setElements($arr) {
		$this->elements = $arr;
	}

	public function addElement($arrname, $value) {
		if(!$this->elements) {
			$this->elements = array();
		}
		if(!array_key_exists($arrname, $this->elements)) {
			$this->elements[$arrname] = array();
		}

		$this->elements[$arrname] = $value;
	}

	public function overwriteElement($name, $arr) {
		$this->elements[$name] = $arr;
	}

	// Get an element, returns Array Elements if $name is null or false
	// Elements usually are Arrays from factory classes, websites repective PHP-Files and Obj Page
	public function getElements($name = false) {
		if(!$this->elements)
			return false;

		if($name) {
			if(!$this->elements || !array_key_exists($name, $this->elements))
				return false;
	
			return $this->elements[$name];
		} else {
			return $this->elements;
		}
	}

	// Get all elements set, returns DomElement
	public function getDomElement($name) {
		if(!isset($this->elements[$name]))
			return false;

		$objDom = new DOMDocument('1.0', 'UTF-8');
		$element = $objDom->createElement($name);
		foreach($this->elements[$name] as $var => $value) {
			$variable = $objDom->createElement($var);
			$text = $objDom->createTextNode($value);
			$variable->appendChild($text);
			$element->appendChild($variable);
		}

		return $element;
	}
	
	public function setError($value) {
		$this->error = $value;
		
		return $this->error;
	}
	
	public function getError() {
		return $this->error();
	}
}
