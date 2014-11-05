<?php

/*
 *
 * xsltDocument Main Class for Castor Projects
 *
 * - Create a Page to load Controller Classes.
 * - An XML-Config, with a default or individual page/action construct couldt loaded but is not mandatory.
 * - $this->arrPage contains Page Objects and gaves access to Local functions from Action Objects.
 * - $this->document contains main data like a basepath and a rootpage.
 * - $this->elements contains main constants from configuration and setElement() calls.
 * - $this->expand contains an array with the content of <expand> nodes in configurations, expand elements are compatible with $this->createDomElement(), #relates todo #015 
 * - Elements, will be given to action method, if them was set for document, page or action.
 * - Elements for Actions are individual and not available in other page actions.
 * 
 * An Array sitemap for itself, will be contructed and assigned in/of Application Class child objects.
 *
 * @supplementary notes
 *
 * realpath(dirname(__FILE__)."config/config.xml", is used, if param 1 loadConfig was not set.
 *
 * @todo
 *
 * #001 "include tag", for config xml data.
 * #003 Check for loaded config files and only overwrite if it is valid.
 * #004 Main constants like title will load, but does not have a qualification.
 * #006 Sort return-type xml and json, next to text/html and improve html5 document type declarations
 * #007 Load Config-Tag for XML-RootNode name, relates to @todo 001 and 003
 * #008 Test sensitive factory classes in object page, respective there shouldt be a place for non Application Object Childs in later versions.
 * #009 Implement modules respective db class loaders or elements and constants like an OBJ.
 *		Maybe a deflect relation for constant arrays, couldt blame a method with params out of constants.
 *
 *		- Surface Interfaces was developed at 25.07.2013
 *		- Operator Childs was developed at 27.07.2013
 *		- Adapter Childs was developed at ~08.2013
 *
 * #010 Maybe, the inclusion of "overwrite" Tag attributes, is a good idea for later versions. Detect #010 comments for more inspirations and ideas. 
 * #011 Maybe, use Document Objects. For Document Elements and Nodes couldt be a place in its own object "Document", for main constants and relations.
 * #012 Use DocumentTypes to valid xml configurations and/or revalidate created documents.
 * #013 Use <var> Elements for Surface and Operator directives, in a simple case a node <arr> is exorbitant.
 * #014 Load local Modules also intim only for one simple action, but estimate the benefits for speed and memory, relates todo #009
 * 		Read elements.php to find a reason for system Plugins
 * #016 Test Elements and expandNode for run() calls and createPage Rules.
 * #017 A simple Module for user/session management and database connection shouldt rule the primitive use of Castor 
 * #019 Take a finally decision for the use of saveHTML or saveXML
 *
 * @version
 *
 * 0.95 / 11.02.2014
 *
 * @author
 *
 * Nanno Gohr
 *
 */

// define SITE_PATH
define('SITE_PATH', realpath(dirname(__FILE__)));

require_once(SITE_PATH.'/src/application.php');
require_once(SITE_PATH.'/src/elements.php');
require_once(SITE_PATH.'/src/module.php');
require_once(SITE_PATH.'/src/surface.php');
require_once(SITE_PATH.'/src/operator.php');
require_once(SITE_PATH.'/src/adapter.php');

class xsltDocument {
	// PHP DOMDocument / implements an xml root node and will filled after loadPage or run
	private $domDocumentObj = false;

	// PHP DomElement / xml root element in $this->domDocumentObj
	private $root = false;

	// Array() / Constants set for document standards, relates todo #011
	private $document = array();

	// Array() / Elements construct
	private $elements = array();

	// Array() / Constructed pages, "Page Objects"
	private $arrPage = array();

	// Array() / Loaded file names for loadConfig, relates todo #003
	private $validConfig = array();
	
	private $error = false;
	private $surface = array();
	private $operator = array();
	private $adapter = array();

	public function __construct() {

	}

	public function load() {
		// Create DomDocument Object
		$this->domDocumentObj = new DOMDocument('1.0', 'UTF-8');
		$this->domDocumentObj->preserveWhiteSpace = true;
		$this->domDocumentObj->formatOutput = true;
		
		// Create Document Rootnode <root>
		// Relates to #007: Document root name, must not named as 'root' otherwise it couldt named as html, document or alternative
		$this->root = $this->domDocumentObj->createElement('root');
	}

	// If a page exists in array $this->arrPage, return true
	public function pageExists($name) {
		if(!array_key_exists($name, $this->arrPage))
			return false;

		return true;
	}

	// Deletes a page in array $this->arrPage, otherwise return false
	public function deletePage($name) {
		if(!array_key_exists($name, $this->arrPage))
			return false;

		unset($this->arrPage[$page]);

		return true;
	}

	// Create an obj Page, named $name, return Obj Page
	public function createPage($name) {
		if($this->pageExists($name))
			return false;

		$this->arrPage[$name] = new Page($name);

		return $this->arrPage[$name];
	}

	// Create an obj Action for page $page, named as $action
	public function createAction($page, $action) {
		// Method addAction() from Obj Page is used, to create a local action!
		return $this->arrPage[$page]->addAction($action);
	}

	// Set a class for a Action Obj, use setClass($action, $classname) from Obj Page
	// Classes, will loaded, if a method for an action, is a method in $classname
	public function setClass($page, $action, $classname) {
		return $this->arrPage[$page]->setClass($action, $classname);
	}

	// Set a Method for an Action, methods will run if they was called or page will load.
	public function setMethod($page, $action, $method) {
		return $this->arrPage[$page]->setMethod($action, $method);
	}

	// Set a stylesheet file for an Action, use setStylesheet($action, $file) from Obj Page
	public function setTemplate($page, $action, $file) {
		return $this->arrPage[$page]->setStylesheet($action, $file);
	}

	// Ads a file for a page, files will required for all actions in Obj Page
	public function addFile($page, $file) {
		return $this->arrPage[$page]->addFile($file);
	}

	// Load default config: Returns true if config $file was loaded correctly, returns false on nonconforming use, throws Exception on errors
	public function loadConfig($file = false) {
		if($file) {
			if(!file_exists($file))
				throw new Exception('Config File: '.$file.' not exists!');
		} else {
			if(!file_exists(SITE_PATH.'/config/config.xml'))
				throw new Exception('Default Config File: '.SITE_PATH.'/config/config.xml not exists!');
			else
				$file = SITE_PATH.'/config/config.xml';
		}

		// Loaded files in an array, relates todo #003
		if(array_key_exists($file, $this->validConfig))
			return false;

		// Read Config
		$domConfig = new DOMDocument('1.0', 'UTF-8');

		$domConfig->load($file);

		$nodeConfig = $domConfig->getElementsByTagName('config')->item(0);
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

			// Use Document Objects for Global Elements, relates todo #011
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

						// module::add($name, $classname, $file, $objOperator);
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

					// $objOperator->setElements($elements);
					
					// unset($elements);
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
		
						// module::add($name, $classname, $file, $objAdapter);
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
		$sitemapNode = $nodeConfig->getElementsByTagName('sitemap');
		if($sitemapNode) {
			$nodes = $sitemapNode->item(0)->getElementsByTagName('page');

			for($i = 0; $i < $nodes->length; $i++) {
				$pagenode = $nodes->item($i);
				$page = $pagenode->getAttribute('name');

				// A Page is allready loaded, with this Name, relates todo #003
				if($this->pageExists($page))
					throw new Exception("Page: ".$page." is not inimitable!");

				$objPage = new Page($page);

				$returnTyp = $pagenode->getAttribute('return');
				$objPage->setReturnTyp($returnTyp);

				// If configured, ever add a rootfile for a page
				if($this->getRootpath())
					$rootfile = $this->getRootpath().'/'.$this->getRootfile();
				else
					$rootfile = $this->getRootfile();

				$objPage->addFile($rootfile);

				// Add configured files for $objPage
				$filenode = $pagenode->getElementsByTagName('file');
				for($e = 0; $e < $filenode->length; $e++) {
					$item = $filenode->item($e);
					if($item->nodeValue) {
						if($this->getRootpath())
							$file = $this->getRootpath().'/'.$item->nodeValue;
						else
							$file = $item->nodeValue;

						$objPage->addFile($file);
					}

					$e++;
				}

				// If configured, ever set root elements for a page
				if($this->elements) {
					foreach($this->elements as $name => $arr) {
						$objPage->setElement($name, $arr);
					}
				}
				unset($arr);

				// If configured ever set a root class element
				$rootClassNode = $pagenode->getElementsByTagName('class');
				if($rootClassNode) {
					if($rootClassNode->length > 0) {
						$rootClassItem = $rootClassNode->item(0);

						if($rootClassItem->parentNode->nodeName == 'page') {
							if($rootClassItem->nodeValue != '') {
								$objPage->setRootClass($rootClassItem->nodeValue);
							}
						}
					}
				}

				// If configured ever set a root method element
				$rootMethodNode = $pagenode->getElementsByTagName('method');
				if($rootMethodNode) {
					if($rootMethodNode->length > 0) {
						$rootMethodItem = $rootMethodNode->item(0);
				
						if($rootMethodItem->parentNode->nodeName == 'page') {
							if($rootMethodItem->nodeValue != '') {
								$objPage->setRootMethod($rootMethodItem->nodeValue);
							}
						}
					}
				}

				// Set elements - Load Page Arrays /sitemap/page/arr::name
				$arrNodes = $pagenode->getElementsByTagName('arr');
				if($arrNodes) {
					for($f = 0; $f < $arrNodes->length; $f++) {
						$item = $arrNodes->item($f);

						// Of course $arrNodes includes page/action/arr nodes, only page elements are elements, action elements are locals, use Document Classes for Global Elements, relates todo #011
						if($item->parentNode->nodeName == 'page') {
							$arrname = $item->getAttribute('name');
							$variables = $item->getElementsByTagName('var');
							for($x = 0; $x < $variables->length; $x++) {
								$var = $variables->item($x);
								$name = $var->getAttribute('name');
								$value = $var->nodeValue;

								$arr[$name] = $value;
							}

							$objPage->setElement($arrname, $arr);
						}
					}
				}
				unset($arr);

				// Expand Nodes Page
				$expand = array();
				
				$expandNodes = $pagenode->getElementsByTagName('expand');
				if($expandNodes && $expandNodes->length > 0) {
					$j = 0;

					while($j < $expandNodes->length) {
						$var = $expandNodes->item($j);
						$name = $var->getAttribute('node');

						if($var->parentNode->nodeName == 'page') {
							$expand[$name] = array();
				
							$addNodes = $var->getElementsByTagName('add');
							if($addNodes && $addNodes->length > 0) {
								$length = 0;
								$expand[$name][$length] = array();
				
								for($g = 0; $g < $addNodes->length; $g++) {
									$varAddNode = $addNodes->item($g);
									$addNodeName = $varAddNode->getAttribute('name');
				
									$expand[$name][$length][$addNodeName] = $varAddNode->nodeValue;
								}
								$length = 0;
							} else {
								$expand[$name][0] = array();
							}
						}
				
						$j++;
					}
				}
				$objPage->setNodes($expand);

				// Add Constants for Page
				$constantNodes = $pagenode->getElementsByTagName('constant');
				if($constantNodes) {
					for($f = 0; $f < $constantNodes->length; $f++) {
						$item = $constantNodes->item($f);

						if($item->parentNode->nodeName == 'page') {
							$objPage->addConstant($item->getAttribute('name'), $item->nodeValue);
						}
					}
				}
				unset($arr);

				// Index action for Page /sitemap/page::index
				$index = $pagenode->getAttribute('index');
				if(!empty($index))
					$objPage->setIndex($index);

				// Title for Page /sitemap/page/title
				if($pagenode->getElementsByTagName('title')) {
					$title = $pagenode->getElementsByTagName('title')->item(0)->nodeValue;
					if(!empty($title))
						$objPage->setTitle($title);
				}

				// /sitemap/page/title, relates to todo #004
				// $this->page[$pagename]['title'] = $pagenode->getElementsByTagName('title')->item(0)->nodeValue;

				// Actions for page /sitemap/page/action
				$actionsNodes = $pagenode->getElementsByTagName('action');

				for($f = 0; $f < $actionsNodes->length; $f++) {
					$actionNode = $actionsNodes->item($f);
					$action = $actionNode->getAttribute('name');
					$actionTitle = $actionNode->getAttribute('title');

					$classname = false;
					$method = false;

					$classNode = $actionNode->getElementsByTagName('class');
					if($classNode && $classNode->length > 0) {
						if($classNode->item(0)->nodeValue != '') {
							$classname = $actionNode->getElementsByTagName('class')->item(0)->nodeValue;
						}
					}
					
					$methodNode = $actionNode->getElementsByTagName('method');
					if($methodNode && $methodNode->length > 0) {
						if($methodNode->item(0)->nodeValue != '') {
							$method = $actionNode->getElementsByTagName('method')->item(0)->nodeValue;
						}
					}

					$objPage->addAction($action, $classname, $method);
					$objAction = $objPage->getObjAction($action);

					$objAction->setTitle($actionTitle);
					$returnType = $actionNode->getAttribute('return');

					if($returnType && $returnType != '')
						$objAction->setReturnType($returnType);

					$stylesNode = $actionNode->getElementsByTagName('style')->item(0);
					if($stylesNode) {
						$objPage->setStylesheet($action, $stylesNode->nodeValue);
						$rendering = $stylesNode->getAttribute('rendering');
						$objAction->setRendering($rendering);
					}

					// Load Action Arrays
					$actionArrays = $actionNode->getElementsByTagName('arr');
					if($actionArrays) {
						for($c = 0; $c < $actionArrays->length; $c++) {
							$arrNode = $actionArrays->item($c);
							
							$arr = array();

							// Load only local Elements - Dirty Hack to suspend childnodes like an <arr> Node, relates todo #011
							if($arrNode->parentNode->nodeName == 'action') {							
								$arrname = $arrNode->getAttribute('name');
								$varNodes = $arrNode->getElementsByTagName('var');
								for($j = 0; $j < $varNodes->length; $j++) {
									$var = $varNodes->item($j);
									$name = $var->getAttribute('name');
									$value = $var->nodeValue;

									$arr[$name] = $value;
								}

								$objAction->setLocal($arrname, $arr);

								unset($arr);
							}
						}
					}

					// Add Constants for Action
					$constantNodes = $actionNode->getElementsByTagName('constant');
					if($constantNodes) {
						for($x = 0; $x < $constantNodes->length; $x++) {
							$var = $constantNodes->item($x);
								
							if($var->parentNode->nodeName == 'action') {
								$objAction->setConstant($var->getAttribute('name'), $var->nodeValue);
							}
						}
					}

					// Expand Nodes for Action
					$expand = array();

					$expandNodes = $actionNode->getElementsByTagName('expand');
					if($expandNodes && $expandNodes->length > 0) {
						$j = 0;
						while($j < $expandNodes->length) {
							$var = $expandNodes->item($j);
							$name = $var->getAttribute('node');

							if($var->parentNode->nodeName == 'action') {
								$expand[$name] = array();
	
								$addNodes = $var->getElementsByTagName('add');
								if($addNodes && $addNodes->length > 0) {
									$length = 0;
									$expand[$name][$length] = array();
	
									for($g = 0; $g < $addNodes->length; $g++) {
										$varAddNode = $addNodes->item($g);
										$addNodeName = $varAddNode->getAttribute('name');
		
										$expand[$name][$length][$addNodeName] = $varAddNode->nodeValue;								
									}
									$length = 0;
								} else {
									$expand[$name][0] = array();
								}
							}

							$j++;
						}
					}
					$objAction->setNodes($expand);

					unset($arr);

					// Apply cache
					/*
					$cacheNode = $actionNode->getElementsByTagName('cache');
					if($cacheNode->item(0)) {
						$objAction->applyCaching($cacheNode->item(0)->nodeValue);
					}
					*/

					// "overwrite" TAG relates #010 /config/page/action/overwrite
					/*
					$nodeOverwriteTag = $item->getElementsByTagName('overwrite');
					if($nodeOverwriteTag) {
						$e = 0;
						while($arrNode = $nodeOverwriteTag->item($e)) {
							$attributeTest = $arrNode->getAttribute('test');

							if(preg_match("/^(.+)\[(.+)\]\s*=\s*\'(.*)\'$/", $attributeTest, $match)) {
								// Overwrite style attributes
								if(preg_match("/".$match[3]."/i", $_SERVER[$match[2]])) {	
									if($match[1] == 'SERVER') {
										$rendering = $arrNode->getElementsByTagName('style')->item(0)->getAttribute('rendering');
										if($rendering) {
											$objPage->setRendering($action, $rendering);
										}
										$styleSheet = preg_replace('/\s/', '', $arrNode->nodeValue);
										if($arrNode->nodeValue) {
											$objPage->setStylesheet($action, $styleSheet);
										}
									}
								}
							}

							$e++;
						}
					}
					*/
				}

				$this->arrPage[$page] = $objPage;
			}

			// Find Root Action in configured pages
			if($this->getRootpage()) {
				$page = $this->getRootpage();
				if($this->pageExists($page)) {
					$objPage = $this->arrPage[$page];

					if($this->getRootaction()) {
						$action = $this->getRootaction();
						if(!$objPage->actionExists($action)) {
							throw new Exception('Action "'.$action.'" for Page "'.$page.'" is not defined!');
						}
					} elseif($objPage->getIndex()) {
						$action = $objPage->getIndex();
						if(!$objPage->actionExists($action)) {
							throw new Exception('Action "'.$action.'" for Page "'.$page.'" is not defined!');
						} else {
							$this->setRootaction($action);
						}
					} else {
						throw new Exception('Index Action for Page "'.$page.'" is not defined!');
					}
				} else {
					throw new Exception('Page "'.$page.'" is not defined!');
				}
			} else {
				throw new Exception('mandatory Element config/root is not defined!');
			}
		}

		// Document Root Index /config/document/root::index
		$attributeAction = $nodeRootPage->getAttribute('action');
		if($attributeAction)
			$this->setRootaction($attributeAction);

		// Loaded Files in an array, relates todo #003
		$this->validConfig[] = $file;

		return true;
	}

	private function expandNodes($objPage, $action) {
		// Expand Nodes for Page
		$expand = $objPage->getNodes();

		if($expand && count($expand) > 0) {
			foreach($expand as $index => $value) {
				if(is_array($value)) {
					foreach($value as $subIndex => $subValue) {
						$node = $this->domDocumentObj->createElement($index);
						elements::createElementRepresentation($subValue, $this->domDocumentObj, $node);

						$this->root->appendChild($node);
					}
				} else {
					$node = $this->domDocumentObj->createElement($index);
					$txt = $this->domDocumentObj->createTextNode($value);
					$node->appendChild($txt);
					$this->root->appendChild($node);
				}
			}
		}
		
		// Expand Nodes for Actions
		$expand = $objPage->getLocalNodes($action);
		if($expand && count($expand) > 0) {
			foreach($expand as $index => $value) {
				if(is_array($value)) {
					foreach($value as $subIndex => $subValue) {
						$node = $this->domDocumentObj->createElement($index);
						elements::createElementRepresentation($subValue, $this->domDocumentObj, $node);

						$this->root->appendChild($node);
					}
				} else {
					$node = $this->domDocumentObj->createElement($index);
					$txt = $this->domDocumentObj->createTextNode($value);
					$node->appendChild($txt);
					$this->root->appendChild($node);
				}
			}
		}
	}

	// Load a defined Page. Use param action to call an action directly. elements and sitemap, will be set in Application Classes.
	// Return Type Array, json, DomDocument and text/html is supported. Renderingtypes xml, server and client is valid.
	public function loadPage($page = false, $action = false) {
		$objPage = false;

		// Create DomDocument Object
		if(!$this->domDocumentObj) {
			$this->domDocumentObj = new DOMDocument('1.0', 'UTF-8');
			$this->domDocumentObj->preserveWhiteSpace = true;
			$this->domDocumentObj->formatOutput = true;
			
			// Create Document Rootnode <root>
			// Relates to #007: Document root name, must not named as 'root' otherwise it couldt named as html, document or alternative
			$this->root = $this->domDocumentObj->createElement('root');
		}

		if($page) {
			if(!$this->pageExists($page))
				throw new Exception('Page: "'.$page.'" is not defined...'); 
			else
				$objPage = $this->arrPage[$page];

			if($action) {
				if(!$objPage->actionExists($action))
					throw new Exception('Action "'.$action.'" for Page "'.$page.'" is not defined...'); 
			} else {
				// Find index
				$action = $objPage->getIndex();

				if(!$objPage->actionExists($action))
					throw new Exception('Action "'.$action.'" for Page "'.$page.'" is not defined...'); 
			}
		} else {
			// Find RootPage
			$page = $this->getRootpage();

			if(!$this->pageExists($page))
				throw new Exception('Page: "'.$page.'" is not defined...'); 
			else
				$objPage = $this->arrPage[$page];

			$action = $this->getRootaction();

			if(!$objPage->actionExists($action))
				throw new Exception('Action "'.$action.'" for Page "'.$page.'" is not defined...'); 
		}

		// Set Page Array for Sitemap Call
		$objPage->setSitemap($this->arrPage);

		$objPage->loadFiles();
		$returnType = $objPage->getReturnTyp($action);
		if(!$returnType)
			$returnType = $objPage->getReturnTyp();

		// relates #006 - Returntype json and xml is not an individual for text/html
		switch($returnType) {
			case 'DomDocument':
				/*
				$saveFile = false;
				if($objPage->localCacheApplied($action)) {
					if(session_id() == '' || !isset($_SESSION)) {
						session_start();
					}

					if(session_id() != '' && isset($_SESSION)) {
						$filename = "/tmp/".md5(base64_encode(session_id().$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'].$_SERVER['QUERY_STRING']));

						if($filename) {
							if(!$_POST) {
								if($objPage->getRendering($action) == "server") {
									if(!file_exists($filename)) {
										$saveFile = true;
									}
		
									if(!$saveFile) {
										$timeout = time() - 20;
										if($timeout < filemtime($filename)) {
											$handle = fopen($filename, "r");
											$contents = fread($handle, filesize($filename));
											fclose($handle);
	
											header('Content-Type: '.$objPage->getLocalMimeType($action).'; charset=UTF-8');
	
											echo $contents;
											
											return true;
										} else {
											$saveFile = true;
	
											unlink($filename);
										}
									}
								} else {
									throw new Exception("Cached Values are only valid for 'server' rendering!");
								}
							} else {
								unlink($filename);
		
								$saveFile = true;
							}
						}
					}
				}
				*/
				
				// Import the node, and all its children, to the document
				$returnvalue = $objPage->call($action);
				if($returnvalue) {
					foreach($returnvalue->childNodes as $sibling) {
						$node = $this->domDocumentObj->importNode($sibling, true);
						$this->root->appendChild($node);
					}
					
					$this->expandNodes($objPage, $action);
				}

				break;

			case 'Array':
				// Contruct DomDocument Elements from a given array, returned from $objPage->call($action);
				$returnvalue = $objPage->call($action);

				if($returnvalue && is_array($returnvalue)) {
					elements::createElementRepresentation($returnvalue, $this->domDocumentObj, $this->root);

					$this->expandNodes($objPage, $action);
				}

				break;

			case 'text/html':
				// Print Plain Text from $objPage->load();
				if(!$action) {
					foreach($objPage->load() as $index => $value)
						echo $value."\n";
				} else {
					echo $objPage->call($action);
				}

				return true;
			
				break;

			case 'json':
				if(!$action) {
					$send = array();
					foreach($objPage->load() as $index => $value) {
						$send[$index][] = $value;
					}
					echo json_encode($send);
				} else {
					echo json_encode($objPage->call($action));
				}

				return true;
			
				break;
		}

		// A redering-type isnt mandatory, if no stylesheet was given, relates #006
		switch($objPage->getRendering($action)) {
			case 'client':
				$stylesheet = $objPage->getStylesheet($action);

				if(!empty($stylesheet)) {
					$xslt = $this->domDocumentObj->createProcessingInstruction(
						'xml-stylesheet', 'type="text/xsl" href="'.$stylesheet.'"'
					);
					
					$this->domDocumentObj->appendChild($xslt);
					$this->domDocumentObj->appendChild($this->root);
				} else {
					return false;
				}

				$this->domDocumentObj->appendChild($this->root);

				$this->sendXml();

				break;

			case 'server':
				$this->domDocumentObj->appendChild($this->root);
				$stylesheet = $objPage->getStylesheet($action);

				/*
				if(session_id() != '' && isset($_SESSION)) {
					if($saveFile) {
						$output = $this->getHTML($stylesheet);
						
						if(!$handle = fopen($filename, "a")) {
							throw new Exception("Cannot open: ".$filename);
						}
					
						if(!fwrite($handle, $output)) {
							throw new Exception($filename." issnt writeable");
						}
	
						fclose($handle);

						header('Content-Type: '.$objPage->getLocalMimeType($action).'; charset=UTF-8');
						
						echo $output;
					} else {
						$this->sendHTML($stylesheet);
					}
				} else {
					$this->sendHTML($stylesheet);
				}
				*/

				$this->sendHTML($stylesheet);

				break;

			case 'html5':
				$this->domDocumentObj->appendChild($this->root);
				$stylesheet = $objPage->getStylesheet($action);
			
				$this->sendHTML5($stylesheet);
			
				break;

			// relates todo #006
			case 'xml':
				header('Content-type: text/xml; charset=UTF-8');

				$this->domDocumentObj->appendChild($this->root);
				$this->domDocumentObj->save('php://output');

				break;
			
			default:
				$stylesheet = $objPage->getStylesheet($action);
				
				if(!empty($stylesheet)) {
					$xslt = $this->domDocumentObj->createProcessingInstruction(
							'xml-stylesheet', 'type="text/xsl" href="'.$stylesheet.'"'
					);
						
					$this->domDocumentObj->appendChild($xslt);
					$this->domDocumentObj->appendChild($this->root);
				} else {
					return false;
				}
				
				$this->domDocumentObj->appendChild($this->root);
				
				$this->sendXml();
				
				break;
		}

		return true;
	}

	// Runs a random method from a loaded File with a class defined as $classname, respectively also other required files couldt have classes.
	public function run($classname, $method) {
		$page = rand();
		$objPage = new Page($page);

		$action = rand();
		$objPage->addAction($action, $classname, $method);

		return $objPage->call($action);
	}

	// Send root-element as XML with a processing-instruction for xsl Stylesheets.
	// $styleSheet must be relative to the document root of the server and shouldt describe a document for xml Rootnode <document>
	public function sendXml() {
		if(array_key_exists('HTTP_ACCEPT', $_SERVER)) {
			if(stristr($_SERVER["HTTP_ACCEPT"], "application/xhtml+xml")) {
				header("Content-type: application/xhtml+xml; charset=UTF-8");
			} else {
				header('Content-type: text/xml; charset=UTF-8');
			}
		} else {
			header('Content-type: text/xml; charset=UTF-8');
		}

		$this->domDocumentObj->save('php://output');

		return true;
	}

	// Send HTML Data while /root transformed to HTML with PHP Mod XsltProcessor.
	// $styleSheet must be an absolute path to a valid xsl stylesheet.
	public function sendHTML($styleSheet) {
		$xsl = new DomDocument();

		if(file_exists($styleSheet)) {
			$xsl->load($styleSheet);
		} else {
			throw new Exception('Couldt not load stylesheet from File: '.$styleSheet);
		}

		$xpr = new XsltProcessor();
		$xpr->importStylesheet($xsl);

		$output = $xpr->transformToDoc($this->domDocumentObj);

		header('Content-Type: text/html; charset=UTF-8');
		echo $output->saveHTML();

		return true;
	}
	
	public function getHTML($styleSheet) {
		$xsl = new DomDocument();
	
		if(file_exists($styleSheet)) {
			$xsl->load($styleSheet);
		} else {
			throw new Exception('Couldt not load stylesheet from File: '.$styleSheet);
		}
	
		$xpr = new XsltProcessor();
		$xpr->importStylesheet($xsl);
	
		$output = $xpr->transformToDoc($this->domDocumentObj);

		return $output->saveHTML();
	}

	public function sendHTML5($styleSheet) {
		$xsl = new DomDocument();
	
		if(file_exists($styleSheet)) {
			$xsl->load($styleSheet);
		} else {
			throw new Exception('Couldt not load stylesheet from File: '.$styleSheet);
		}
	
		$xpr = new XsltProcessor();
		$xpr->importStylesheet($xsl);
	
		$output = $xpr->transformToDoc($this->domDocumentObj);

		header('Content-Type: text/html; charset=UTF-8');
		echo "<!DOCTYPE HTML>\n";
		echo $output->saveHTML();
	
		return true;
	}

	// Set Root Path for Controller Files
	public function setRootpath($path) {
		$this->document['path'] = $path;

		return true;
	}

	// Get Root Path for Controller Files
	public function getRootpath() {
		return $this->document['path'];
	}

	// Set Root File, with controller classes
	public function setRootfile($file) {
		$this->document['file'] = $file;

		return true;
	}

	// Get Root File, with controller classes
	public function getRootfile() {
		return $this->document['file'];
	}

	// Set Main Page. Param 1 is Name for a constructed Page, Page couldt constructed after this
	public function setRootpage($name) {
		$this->document['page'] = $name;

		return true;
	}

	// Get Main Page
	public function getRootpage() {
		return $this->document['page'];
	}

	// Set Main Action Param 1 is Name for a constructed Action. Action relates to $this->document['page']
	public function setRootaction($name) {
		$this->document['action'] = $name;

		return true;
	}

	// Get Main Action
	public function getRootaction() {
		return $this->document['action'];
	}

	// Set individual element constants
	public function setElements($name, $constants, $page = false, $action = false) {
		$arr = array();

		if(is_array($constants)) {
			if($page) {
				if(pageExists($page)) {
					$objPage = $this->arrPage[$page];
					if($action) {
						if($objPage->actionExists($action)) {
							$objPage->setLocal($action, $name, $constants);
						} else {
							return false;
						}
					} else {
						foreach($objPage->getActions() as $index => $actionname) {
							$objPage->setLocal($actionname, $name, $constants);
						}
					}
				} else {
					return false;
				}
			} else {
				foreach($this->arrPage as $pagename => $objPage) {
					foreach($objPage->getActions() as $index => $actionname) {
						$objPage->setLocal($actionname, $name, $constants);
					}
				}
			}
		} else {
			return false;
		}
	}

	public function expandNode($index, $value) {
		$element = $this->domDocumentObj->createElement($index);
		$txt = $this->domDocumentObj->createTextNode($value);
		$element->appendChild($txt);
		$this->root->appendChild($element);
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
	
	public function loadSurface($name) {
		if(!array_key_exists($name, $this->surface)) {
			return false;
		}
	
		module::add($name, $this->surface[$name]['class'], $this->surface[$name]['file'], $objSurface);
		$objSurface->setElements($elements);
	
		return true;
	}
	
	public function loadOperator($name) {
		if(!array_key_exists($name, $this->operator)) {
			return false;
		}
	
		module::add($name, $this->operator[$name]['class'], $this->operator[$name]['file'], $objOperator);
		$objOperator->setElements($elements);
	
		return true;
	}
	
	public function loadAdapter($name) {
		if(!array_key_exists($name, $this->adapter)) {
			return false;
		}
	
		module::add($name, $this->adapter[$name]['class'], $this->adapter[$name]['file'], $objAdapter);
		$objAdapter->setElements($elements);
	
		return true;
	}
}
