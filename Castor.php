<?php

/*
 * Copyright (c) 2016, Nanno Gohr
 *
 * All rights reserved.
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 * Redistributions of source code must retain the above copyright notice,
 * this list of conditions and the following disclaimer.
 * Redistributions in binary form must reproduce the above copyright
 * notice, this list of conditions and the following disclaimer in the
 * documentation and/or other materials provided with the distribution.
 * Neither the name of  nor the names of its contributors may be used to
 * endorse or promote products derived from this software without specific
 * prior written permission.

 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * The Castor main class
 *
 * - Documents wouldt extend Castor to construct the given sitemap
 * - The given sitemap from config files will be deconstructed
 * - The filtered node will given to childs and a method named createActions
 * - $this->sitemap contains an array with Page and Action objects prepared by Document classes
 * - Tags from config/document will extracted here and only the sitemap/page tags will prepared for document classes
 * - Also Castor modules will prepared here and loaded with $this->loadModules();
 * - Elements in document or module nodes will prepared here and given to the sitemap before Document calls loadPage()
 *
 * @todos
 *
 * #001 loadModules() couldt called manually or module tags couldt located in page or action namespaces
 * #002 An include tag shouldt extend the config
 * #003 Test sensitive factory classes in object page, respective there shouldt be a place for non Application Object Childs in later versions.
 * #004 Implement modules/plugins respective db class loaders or elements and constants like an OBJ.
 *		Maybe a deflect relation for constant arrays, couldt blame a method with params out of constants.
 *
 *		- Surface Interfaces was developed at 25.07.2013
 *		- Operator Childs was developed at 27.07.2013
 *		- Adapter Pattern was developed at ~May 2014
 *		- A first plugin was written at ~Aug 2016
 *
 * #005 Use DocumentTypes to valid xml configurations and/or revalidate created documents.
 * #006 Use <var> Elements for Surface and Operator directives, in a simple case a node <arr> is exorbitant.
 * #007 Check for loaded config files and only overwrite if it is valid.
 *
 */
define('SITE_PATH', realpath(dirname(__FILE__)));

require_once(SITE_PATH."/src/application.php");
require_once(SITE_PATH."/src/elements.php");
require_once(SITE_PATH."/src/module.php");
require_once(SITE_PATH."/src/hooks.php");
require_once(SITE_PATH."/src/surface.php");
require_once(SITE_PATH."/src/operator.php");
require_once(SITE_PATH."/src/adapter.php");
require_once(SITE_PATH."/src/xsltDocument.php");
require_once(SITE_PATH."/src/phtmlDocument.php");

abstract class Castor {
	public $domDocumentObj;

	public $document;
	public $sitemap = array();
	public $operator = array();
	public $adapter = array();
	public $surface = array();

	public $sitemapNode;

	public $rootpath;
	public $rootfile;
	public $rootpage;
	public $rootaction;

	// document / arr nodes
	public $elements = array();

	abstract function createActions($pagename, $templateObj);

	public function __construct($file, $pagename = false, $actionname = false, $notCreate = false) {
		// Create DomDocument Object
		$this->domDocumentObj = new DOMDocument('1.0', 'UTF-8');
		$this->domDocumentObj->preserveWhiteSpace = true;
		$this->domDocumentObj->formatOutput = true;

		if($file) {
			if(!file_exists($file))
				throw new Exception('Config File: '.$file.' not exists!');
		} else {
			if(!file_exists(SITE_PATH.'/config/config.xml'))
				throw new Exception('Default Config File: '.SITE_PATH.'/config/config.xml not exists!');
			else
				$file = SITE_PATH.'/config/config.xml';
		}

		$this->domDocumentObj->load($file);

		$xpath = new DOMXpath($this->domDocumentObj);
		$nodeDocument = $xpath->query("document");
		if($nodeDocument->length > 1) {
			throw new Exception("Read Config: Node document is not inimitable!");
		} elseif(!$nodeDocument || $nodeDocument->length <= 0) {
			throw new Exception('Read Config: Node config/document not exists!');
		}
		$elementDocument = $nodeDocument->item(0);

		// Document Root Path /config/document/path
		$nodePath = $xpath->query("path", $elementDocument);
		if($nodePath->length > 1) {
			throw new Exception("Read Config: document/path is not inimitable!");
		}
		$elementPath = $nodePath->item(0);
		if($elementPath && $elementPath->nodeValue != '')
			$this->setRootpath($elementPath->nodeValue);

		// Document Root File /config/document/file
		$nodeFile = $xpath->query("file", $elementDocument);
		if($nodeFile->length > 1) {
			throw new Exception("Read Config: document/file is not inimitable!");
		}
		$elementFile = $nodeFile->item(0);
		if($elementFile && $elementFile->nodeValue != '')
			$this->setRootfile($elementFile->nodeValue);

		// Document Root Page /config/document/root
		$nodeRoot = $xpath->query("root", $elementDocument);
		if($nodeRoot->length > 1) {
			throw new Exception("Read Config: document/root is not inimitable!");
		}
		$elementRoot = $nodeRoot->item(0);
		if($elementRoot && $elementRoot->nodeValue != '') {
			$this->setRootpage($elementRoot->nodeValue);

			// Document Root Index /config/document/root[@index != '']
			$attributeAction = $elementRoot->getAttribute('action');
			if($attributeAction)
				$this->setRootaction($attributeAction);
		}

		// Global Elements - Load Arrays defined in /config/document/arr::name
		$arrNodes = $xpath->query("arr", $elementDocument);
		if($arrNodes) {
			for($i = 0; $i < $arrNodes->length; $i++) {
				$arr = array();

				$item = $arrNodes->item($i);
				if($item) {
					$arrname = $item->getAttribute('name');
					$variables = $xpath->query("var", $item);
					for($f = 0; $f < $variables->length; $f++) {
						$var = $variables->item($f);
						if($var) {
							$name = $var->getAttribute('name');
							if($name && $name != '') {
								$value = $var->nodeValue;

								$arr[$name] = $value;
							}

							unset($name);
						}
					}

					if(count($arr) > 0) {
						$this->elements[$arrname] = $arr;
					}
				}

				unset($arrname);
				unset($arr);
			}
		}

		// Load Surface´s for document Elements
		$surfaceNodes = $xpath->query('surface', $elementDocument);
		if($surfaceNodes && $surfaceNodes->length > 0) {
			for($i = 0; $i < $surfaceNodes->length; $i++) {
				$node = $surfaceNodes->item($i);
				$surfaceName = $node->getAttribute('name');
				if(!$surfaceName || $surfaceName == '')
					throw new Exception('Missing name for Surface!');

				$this->surface[$surfaceName] = array();

				$filesNode = $xpath->query("file", $node);
				if(!$filesNode || $filesNode->length <= 0) {
					throw new Exception('Missing file for Surface '.$surfaceName.'!');
				}

				$fileElement = $filesNode->item(0);
				if($fileElement->nodeValue != '') {
					if(!file_exists($fileElement->nodeValue)) {
						throw new Exception('File '.$fileElement->nodeValue.' not exists!');
					}

					$this->surface[$surfaceName]['file'] = $fileElement->nodeValue;
				} else {
					throw new Exception('Missing file for Surface!');
				}

				$classNode = $xpath->query("class", $node);
				if(!$classNode || $classNode->length <= 0) {
					throw new Exception('Missing class for Surface '.$surfaceName.'!');
				} else {
					$class = $classNode->item(0)->nodeValue;
					$this->surface[$surfaceName]['class'] = $class;
				}

				// Load Arrays for surface nodes...
				$this->surface[$surfaceName]['elements'] = array();
				$arrNodes = $xpath->query("arr", $node);
				if($arrNodes && $arrNodes->length > 0) {
					for($e = 0; $e < $arrNodes->length; $e++) {
						$arr = array();

						$item = $arrNodes->item($e);
						$arrname = $item->getAttribute('name');
						$varNodes = $xpath->query("var", $item);
						if($varNodes && $varNodes->length > 0) {
							for($f = 0; $f < $varNodes->length; $f++) {
								$var = $varNodes->item($f);
								$name = $var->getAttribute('name');
								$value = $var->nodeValue;

								$arr[$name] = $value;
							}
						}
						$this->surface[$surfaceName]['elements'][$arrname] = $arr;

						unset($arr);
					}
				}

				// Load Dependencys
				$this->surface[$surfaceName]['dependency'] = array();
				$dependencyNodes = $xpath->query("dependency", $node);
				if($dependencyNodes && $dependencyNodes->length > 0) {
					$this->surface['dependency'] = array();
					for($e = 0; $e < $dependencyNodes->length; $e++) {
						$item = $dependencyNodes->item($e);
						$name = $item->nodeValue;
						$type = $item->getAttribute('type');
						if(!array_key_exists($type, $this->surface[$surfaceName]['dependency']))
							$this->surface[$surfaceName]['dependency'][$type] = array();
						$this->surface[$surfaceName]['dependency'][$type][] = $name;
					}
				}
			}
		}

		// Load Operator´s for document Elements
		$operatorNodes = $xpath->query("operator", $elementDocument);
		if($operatorNodes && $operatorNodes->length > 0) {
			for($i = 0; $i < $operatorNodes->length; $i++) {
				$node = $operatorNodes->item($i);
				$operatorName = $node->getAttribute('name');
				if(!$operatorName || $operatorName == '')
					throw new Exception('Missing name for Operator!');

				$this->operator[$operatorName] = array();

				$filesNode = $xpath->query("file", $node);
				if(!$filesNode || $filesNode->length <= 0) {
					throw new Exception('Missing file for Operator '.$operatorName.'!');
				}

				$fileElement = $filesNode->item(0);
				if($fileElement->nodeValue != '') {
					if(!file_exists($fileElement->nodeValue)) {
						throw new Exception('File '.$fileElement->nodeValue.' not exists!');
					}

					$this->operator[$operatorName]['file'] = $fileElement->nodeValue;
				} else {
					throw new Exception('Missing file for Operator!');
				}

				$classNode = $xpath->query("class", $node);
				if(!$classNode || $classNode->length <= 0) {
					throw new Exception('Missing class for Surface '.$operatorName.'!');
				} else {
					$class = $classNode->item(0)->nodeValue;
					$this->operator[$operatorName]['class'] = $class;
				}

				// Load Arrays for operator nodes...
				$this->operator[$operatorName]['elements'] = array();
				$arrNodes = $xpath->query("arr", $node);
				if($arrNodes && $arrNodes->length > 0) {
					for($e = 0; $e < $arrNodes->length; $e++) {
						$arr = array();

						$item = $arrNodes->item($e);
						$arrname = $item->getAttribute('name');
						$varNodes = $xpath->query("var", $item);
						if($varNodes && $varNodes->length > 0) {
							for($f = 0; $f < $varNodes->length; $f++) {
								$var = $varNodes->item($f);
								$name = $var->getAttribute('name');
								$value = $var->nodeValue;

								$arr[$name] = $value;
							}
						}
						$this->operator[$operatorName]['elements'][$arrname] = $arr;

						unset($arr);
					}
				}

				// Load Dependencys
				$this->operator[$operatorName]['dependency'] = array();
				$dependencyNodes = $xpath->query("dependency", $node);
				if($dependencyNodes && $dependencyNodes->length > 0) {
					$this->operator['dependency'] = array();
					for($e = 0; $e < $dependencyNodes->length; $e++) {
						$item = $dependencyNodes->item($e);
						$name = $item->nodeValue;
						$type = $item->getAttribute('type');
						if(!array_key_exists($type, $this->operator[$operatorName]['dependency']))
							$this->operator[$operatorName]['dependency'][$type] = array();
						$this->operator[$operatorName]['dependency'][$type][] = $name;
					}
				}
			}
		}

		// Load Adapter´s for document Elements
		$adapterNodes = $xpath->query("adapter", $elementDocument);
		if($adapterNodes && $adapterNodes->length > 0) {
			for($i = 0; $i < $adapterNodes->length; $i++) {
				$node = $adapterNodes->item($i);
				$adapterName = $node->getAttribute('name');
				if(!$adapterName || $adapterName == '')
					throw new Exception('Missing name for Adapter!');

				$this->adapter[$adapterName] = array();

				$filesNode = $xpath->query("file", $node);
				if(!$filesNode || $filesNode->length <= 0) {
					throw new Exception('Missing file for Adapter '.$adapterName.'!');
				}

				$fileElement = $filesNode->item(0);
				if($fileElement->nodeValue != '') {
					if(!file_exists($fileElement->nodeValue)) {
						throw new Exception('File '.$fileElement->nodeValue.' not exists!');
					}

					$this->adapter[$adapterName]['file'] = $fileElement->nodeValue;
				} else {
					throw new Exception('Missing file for Adapter!');
				}

				$classNode = $xpath->query("class", $node);
				if(!$classNode || $classNode->length <= 0) {
					throw new Exception('Missing class for Surface '.$adapterName.'!');
				} else {
					$class = $classNode->item(0)->nodeValue;
					$this->adapter[$adapterName]['class'] = $class;
				}

				// Load Arrays for adapter nodes...
				$this->adapter[$adapterName]['elements'] = array();
				$arrNodes = $xpath->query("arr", $node);
				if($arrNodes && $arrNodes->length > 0) {
					for($e = 0; $e < $arrNodes->length; $e++) {
						$arr = array();

						$item = $arrNodes->item($e);
						$arrname = $item->getAttribute('name');
						$varNodes = $xpath->query("var", $item);
						if($varNodes && $varNodes->length > 0) {
							for($f = 0; $f < $varNodes->length; $f++) {
								$var = $varNodes->item($f);
								$name = $var->getAttribute('name');
								$value = $var->nodeValue;

								$arr[$name] = $value;
							}
						}
						$this->adapter[$adapterName]['elements'][$arrname] = $arr;

						unset($arr);
					}
				}

				// Load Dependencys
				$this->adapter[$adapterName]['dependency'] = array();
				$dependencyNodes = $xpath->query("dependency", $node);
				if($dependencyNodes && $dependencyNodes->length > 0) {
					$this->adapter['dependency'] = array();
					for($e = 0; $e < $dependencyNodes->length; $e++) {
						$item = $dependencyNodes->item($e);
						$name = $item->nodeValue;
						$type = $item->getAttribute('type');
						if(!array_key_exists($type, $this->adapter[$adapterName]['dependency']))
							$this->adapter[$adapterName]['dependency'][$type] = array();
						$this->adapter[$adapterName]['dependency'][$type][] = $name;
					}
				}
			}
		}

		// Load Modules for DocumentElements
		$loadNodes = $xpath->query("load", $elementDocument);
		if($loadNodes->length > 0) {
			for($i = 0; $i < $loadNodes->length; $i++) {
				$loadNode = $loadNodes->item($i);
				$type = $loadNode->getAttribute('type');
				$name = $loadNode->nodeValue;
				switch($type) {
					case 'surface':
						module::add($name, $this->surface[$name]['class'], $this->surface[$name]['file'], $objSurface);
						$objSurface->setElements($this->surface[$name]['elements']);
					break;
					case 'operator':
						module::add($name, $this->operator[$name]['class'], $this->operator[$name]['file'], $objOperator);
						$objOperator->setElements($this->operator[$name]['elements']);
					break;
					case 'adapter':
						module::add($name, $this->adapter[$name]['class'], $this->adapter[$name]['file'], $objOperator);
						$objOperator->setElements($this->adapter[$name]['elements']);
					break;
				}
			}
		}

		$this->sitemapNode = $xpath->query("sitemap");
		if($this->sitemapNode->length > 1) {
			throw new Exception("Read Config: Node sitemap is not inimitable!");
		} elseif(!$this->sitemapNode || $this->sitemapNode->length <= 0) {
			throw new Exception('Read Config: Node config/sitemap not exists!');
		}
		$elementSitemap = $this->sitemapNode->item(0);

		$pageNodes = $xpath->query("page", $elementSitemap);
		for($i = 0; $i < $pageNodes->length; $i++) {
			$pagenode = $pageNodes->item($i);
			$page = $pagenode->getAttribute('name');
			if($page && $page != '') {
				if($pagename && $actionname) {
					if($page == $pagename) {
						if(!$this->createActions($page, $pagenode, $actionname)) {
							throw new Exception("createActions(".$page.", DomElement); failed...");
						}
					} else {
						if(!$notCreate) {
							if(!$this->createPage($page, $pagenode)) {
								throw new Exception("createActions(".$page.", DomElement); failed...");
							}
						}
					}
				} elseif($pagename && !$actionname) {
					if($page == $pagename) {
						if(!$this->createActions($page, $pagenode)) {
							throw new Exception("createActions(".$page.", DomElement); failed...");
						}
					} else {
						if(!$notCreate) {
							if(!$this->createPage($page, $pagenode)) {
								throw new Exception("createActions(".$page.", DomElement); failed...");
							}
						}
					}
				} else {
					if(!$this->createActions($page, $pagenode)) {
						throw new Exception("createActions(".$page.", DomElement); failed...");
					}
				}
			}
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

	public function pageExists($name) {
		if(array_key_exists($name, $this->sitemap)) {
			return true;
		}

		return false;
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