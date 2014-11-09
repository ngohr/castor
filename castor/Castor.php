<?php

define('SITE_PATH', realpath(dirname(__FILE__)));

require_once(SITE_PATH."/src/Application.php");
require_once(SITE_PATH."/src/module.php");
require_once(SITE_PATH."/src/surface.php");
require_once(SITE_PATH."/src/operator.php");
require_once(SITE_PATH."/src/adapter.php");
require_once(SITE_PATH."/src/xsltDocument.php");

abstract class Castor {
	private $domDocumentObj;

	public $document;
	public $sitemap = array();
	private $operator = array();
	private $adapter = array();

	public $sitemapNode;

	public $rootpath;
	public $rootfile;
	public $rootpage;
	public $rootaction;
	public $elements = array();

	private $surface = array();

	abstract function createAction($pagename, $actionname, $templateObj);

	public function __construct($file) {
		// Create DomDocument Object
		$this->domDocumentObj = new DOMDocument('1.0', 'UTF-8');
		$this->domDocumentObj->preserveWhiteSpace = true;
		$this->domDocumentObj->formatOutput = true;

		$this->domDocumentObj->load($file);

		$nodeConfig = $this->domDocumentObj->getElementsByTagName('config')->item(0);
		if(!$nodeConfig)
			throw new Exception('Read Config: Root Node config not exists!');

		$nodeDocument = $nodeConfig->getElementsByTagName('document')->item(0);
		if(!$nodeDocument)
			throw new Exception('Read Config: Node config/document not exists!');

		// Document Root Path /config/document/path
		$nodePath = $nodeDocument->getElementsByTagName('path')->item(0);
		if($nodePath)
			$this->setRootpath($nodePath->nodeValue);

		// Document Root File /config/document/file
		$nodeFile = $nodeDocument->getElementsByTagName('file')->item(0);
		if($nodeFile)
			$this->setRootfile($nodeFile->nodeValue);

		// Document Root Page /config/document/root
		$nodeRootPage = $nodeDocument->getElementsByTagName('root')->item(0);
		if($nodeRootPage) {
			$this->setRootpage($nodeRootPage->nodeValue);

			// Document Root Index /config/document/root::index
			$attributeAction = $nodeRootPage->getAttribute('action');
			if($attributeAction)
				$this->setRootaction($attributeAction);
		} else {
			throw new Exception('Read Config: Node config/document/root not exists!');
		}

		// Global Elements - Load Arrays defined in /config/document/arr::name
		$arrNodes = $nodeDocument->getElementsByTagName('arr');
		for($i = 0; $i < $arrNodes->length; $i++) {
			$arr = array();

			$item = $arrNodes->item($i);
			if($item->parentNode->nodeName == 'document') {
				$arrname = $item->getAttribute('name');
				$variables = $item->getElementsByTagName('var');
				for($f = 0; $f < $variables->length; $f++) {
					$var = $variables->item($f);
					$name = $var->getAttribute('name');
					$value = $var->nodeValue;

					$arr[$name] = $value;
				}
			}

			if(count($arr) > 0) {
				if(array_key_exists($arrname, $this->elements) && $this->elements[$arrname]) {
					foreach($arr as $index => $value) {
						$this->elements[$arrname][$index] = $value;
					}
				} else {
					$this->elements[$arrname] = $arr;
				}
			}

			unset($arr);
		}
		
		// Load Surface´s for document Elements
		$nodes = $nodeDocument->getElementsByTagName('surface');
		if($nodes) {
			for($i = 0; $i < $nodes->length; $i++) {
				$surfacenode = $nodes->item($i);
		
				if($surfacenode->parentNode->nodeName == 'document') {
					$surfaceName = $surfacenode->getAttribute('name');
					if(!$surfaceName)
						throw new Exception('Missing name for Surface!');
		
					$this->surface[$surfaceName] = array();
		
					$filesNode = $surfacenode->getElementsByTagName('file');
					if(!$filesNode)
						throw new Exception('Missing file for Surface '.$name.'!');
		
					if($filesNode->item(0)) {
						if(!file_exists($filesNode->item(0)->nodeValue)) {
							throw new Exception('File '.$filesNode->item(0)->nodeValue.' not exists!');
						}
						$file = $filesNode->item(0)->nodeValue;
		
						$this->surface[$surfaceName]['file'] = $file;
		
						$classNode = $surfacenode->getElementsByTagName('class');
						$classname = $classNode->item(0)->nodeValue;
		
						$this->surface[$surfaceName]['class'] = $classname;
		
						// module::add($surfaceName, $classname, $file, $objSurface);
					}
		
					// Load Arrays for surface nodes...
					$this->surface[$surfaceName]['elements'] = array();
					$arrNodes = $surfacenode->getElementsByTagName('arr');
					for($e = 0; $e < $arrNodes->length; $e++) {
						$arr = array();
		
						$item = $arrNodes->item($e);
						if($item->parentNode->nodeName == 'surface') {
							$arrname = $item->getAttribute('name');
							$varNodes = $item->getElementsByTagName('var');
							for($f = 0; $f < $varNodes->length; $f++) {
								$var = $varNodes->item($f);
								$name = $var->getAttribute('name');
								$value = $var->nodeValue;
		
								$arr[$name] = $value;
							}
							$this->surface[$surfaceName]['elements'][$arrname] = $arr;
								
							unset($arr);
						}
		
							
					}
		
					// $objSurface->setElements($elements);
					// unset($elements);
				}
			}
		}

		// Load Operator´s for document Elements
		$nodes = $nodeDocument->getElementsByTagName('operator');
		if($nodes) {
			for($i = 0; $i < $nodes->length; $i++) {
				$operatornode = $nodes->item($i);
		
				if($operatornode->parentNode->nodeName == 'document') {
					$operatorName = $operatornode->getAttribute('name');
					if(!$operatorName)
						throw new Exception('Missing name for Operator Module!');
						
					$this->operator[$operatorName] = array();
		
					$filesNode = $operatornode->getElementsByTagName('file');
					if(!$filesNode)
						throw new Exception('Missing file for Operator Module '.$operatorName.'!');
		
					if($filesNode->item(0)) {
						if(!file_exists($filesNode->item(0)->nodeValue)) {
							throw new Exception('File '.$filesNode->item(0)->nodeValue.' not exists!');
						}
						$file = $filesNode->item(0)->nodeValue;
		
						$this->operator[$operatorName]['file'] = $file;
		
						$classNode = $operatornode->getElementsByTagName('class');
						$this->operator[$operatorName]['class'] = $classNode->item(0)->nodeValue;
					}
		
					// Load Arrays for operator nodes...
					$this->operator[$operatorName]['elements'] = array();
					$arrNodes = $operatornode->getElementsByTagName('arr');
					for($e = 0; $e < $arrNodes->length; $e++) {
						$arr = array();
		
						$item = $arrNodes->item($e);
		
						if($item->parentNode->nodeName == 'operator') {
							$arrname = $item->getAttribute('name');
							$varNodes = $item->getElementsByTagName('var');
							for($f = 0; $f < $varNodes->length; $f++) {
								$var = $varNodes->item($f);
								$name = $var->getAttribute('name');
								$value = $var->nodeValue;
									
								$arr[$name] = $value;
							}
							$this->operator[$operatorName]['elements'][$arrname] = $arr;
								
							unset($arr);
						}
					}
				}
			}
		}
		
		// Load Adapter´s for document Elements
		$nodes = $nodeDocument->getElementsByTagName('adapter');
		if($nodes) {
			for($i = 0; $i < $nodes->length; $i++) {
				$adapternode = $nodes->item($i);
		
				if($adapternode->parentNode->nodeName == 'document') {
					$adapterName = $adapternode->getAttribute('name');
					if(!$adapterName)
						throw new Exception('Missing name for Adapter Module!');
						
					$this->adapter[$adapterName] = array();
		
					$filesNode = $adapternode->getElementsByTagName('file');
					if(!$filesNode)
						throw new Exception('Missing file for Adapter Module '.$adapterName.'!');
		
					if($filesNode->item(0)) {
						if(!file_exists($filesNode->item(0)->nodeValue)) {
							throw new Exception('File '.$files->item(0)->nodeValue.' not exists!');
						}
						$file = $filesNode->item(0)->nodeValue;
		
						$this->adapter[$adapterName]['file'] = $file;
		
						$classNode = $adapternode->getElementsByTagName('class');
						$classname = $classNode->item(0)->nodeValue;
						$this->adapter[$adapterName]['class'] = $classname;
					}
		
					// Load Arrays for operator nodes...
					$this->adapter[$adapterName]['elements'] = array();
					$arrNodes = $adapternode->getElementsByTagName('arr');
					for($e = 0; $e < $arrNodes->length; $e++) {
						$arr = array();
		
						$item = $arrNodes->item($e);
		
						if($item->parentNode->nodeName == 'adapter') {
							$arrname = $item->getAttribute('name');
							$varNodes = $item->getElementsByTagName('var');
							for($f = 0; $f < $varNodes->length; $f++) {
								$var = $varNodes->item($f);
								$name = $var->getAttribute('name');
								$value = $var->nodeValue;
									
								$arr[$name] = $value;
							}
							$this->adapter[$adapterName]['elements'][$arrname] = $arr;
		
							unset($arr);
						}
					}
				}
			}
		}

		// Load Sitemap /config/sitemap/page
		$this->sitemapNode = $nodeConfig->getElementsByTagName('sitemap');
		if($this->sitemapNode) {
			$nodes = $this->sitemapNode->item(0)->getElementsByTagName('page');
			for($i = 0; $i < $nodes->length; $i++) {
				$pagenode = $nodes->item($i);
				$page = $pagenode->getAttribute('name');
				if(array_key_exists($page, $this->sitemap)) {
					throw new Exception("Page: ".$page." is not inimitable!");
				}

				// Actions for page /sitemap/page/action
				$actionsNodes = $pagenode->getElementsByTagName('action');
				if($actionsNodes) {
					for($f = 0; $f < $actionsNodes->length; $f++) {
						$actionNode = $actionsNodes->item($f);
						$action = $actionNode->getAttribute('name');

						$this->load($page, $action);
					}
				}
			}
		}

		if(count($this->elements) > 0) {
			foreach($this->elements as $index => $arr) {
				$this->setElements($index, $arr);
			}
		}
		
		$this->setFiles($this->getRootpath()."/".$this->getRootfile());
		$this->loadModules();
	}

	public function load($page, $action) {
		// Create DomDocument Object
		$domDocumentObj = new DOMDocument('1.0', 'UTF-8');
		$domDocumentObj->preserveWhiteSpace = true;
		$domDocumentObj->formatOutput = true;

		$rootNode = $domDocumentObj->createElement('root');

		$castorNode = $domDocumentObj->createElement('castor');

		$pageNode = $domDocumentObj->createElement('pagename');
		$txt = $domDocumentObj->createTextNode($page);
		$pageNode->appendChild($txt);
		$castorNode->appendChild($pageNode);

		$actionNode = $domDocumentObj->createElement('actionname');
		$txt = $domDocumentObj->createTextNode($action);
		$actionNode->appendChild($txt);
		$castorNode->appendChild($actionNode);

		$rootNode->appendChild($castorNode);
		if($this->sitemapNode->item(0)) {
			$node = $domDocumentObj->importNode($this->sitemapNode->item(0), true);
			$rootNode->appendChild($node);
		}
		$domDocumentObj->appendChild($rootNode);

		$objCustomizer = new DomDocument();

		if(!file_exists(SITE_PATH."/templates/document.xsl"))
			throw new Exception ('System Template: document.xsl not exists!');
		else
			$objCustomizer->load(SITE_PATH."/templates/document.xsl");

		$xpr = new XsltProcessor();
		$xpr->importStylesheet($objCustomizer);

		$template = $xpr->transformToDoc($domDocumentObj);

		if(!$this->createAction($page, $action, $template)) {
			throw new Exception("createAction(".$page.", ".$action."); failed...");
		}
	}

	public function setRootpath($value) {
		$this->rootpath = $value;

		return true;
	}

	public function getRootpath() {
		return $this->rootpath;
	}

	public function setRootfile($value) {
		$this->rootfile = $value;
	
		return true;
	}
	
	public function getRootfile() {
		return $this->rootfile;
	}

	public function setRootpage($value) {
		$this->rootpage = $value;
	
		return true;
	}
	
	public function getRootpage() {
		return $this->rootpage;
	}

	public function setRootaction($value) {
		$this->rootaction = $value;
	
		return true;
	}
	
	public function getRootaction() {
		return $this->rootaction;
	}

	public function loadModules() {
		foreach($this->surface as $name => $arr) {
			module::add($name, $arr['class'], $arr['file'], $objSurface);
			$objSurface->setElements($arr['elements']);
		}
		foreach($this->operator as $name => $arr) {
			module::add($name, $arr['class'], $arr['file'], $objOperator);
			$objOperator->setElements($arr['elements']);
		}
		foreach($this->adapter as $name => $arr) {
			module::add($name, $arr['class'], $arr['file'], $objAdapter);
			$objAdapter->setElements($arr['elements']);
		}
	
		return true;
	}
}