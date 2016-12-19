<?php

/*
 * Copyright (c) 2016, Nanno Gohr 
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
 */
 
/*
 *
* Document main class extends Castor
*
* - Create an array includes Page and Action objects to load Controller Classes.
* - Elements, will be given to action method, if them was set for document, page or action.
* - Elements for Actions are individual and not available in other page actions.
* 
* @author
* 
* Nanno Gohr
* 
* @version
* 
* 1.0 / 12.11.2014
*
*/

require_once(SITE_PATH."/src/page.php");

abstract class Document extends Castor {
	abstract function loadPage($pagename, $actionname);

	// If a page exists in array $this->arrPage, return true
	public function pageExists($name) {
		if(!array_key_exists($name, $this->sitemap))
			return false;

		return true;
	}

	public function createPage($pagename, $pagenode) {
		if(array_key_exists($pagename, $this->sitemap)) {
			return false;
		}

		$xpath = new DOMXpath($this->domDocumentObj);

		$index = $pagenode->getAttribute('index');
		if(!$index || $index == '') {
			throw new Exception('Attribute index for page '.$pagename.' not exists!');
		}

		$objPage = new Page($pagename, $index);
		$returnTyp = $pagenode->getAttribute('return');
		if($returnTyp && $returnTyp != '') {
			$objPage->setReturnTyp($returnTyp);
		}

		$titleNode = $xpath->query("title", $pagenode);
		if($titleNode) {
			$item = $titleNode->item(0);
			if($item) {
				$title = $item->nodeValue;
				if($title && $title != '')
					$objPage->setTitle($title);
			}
		}

		// Add Rootfile
		$objPage->addFile($this->getRootpath()."/".$this->getRootfile());

		$classNode = $xpath->query("class", $pagenode);
		if($classNode && $classNode->length > 0 && $classNode->item(0)->nodeValue) {
			$objPage->setClass($classNode->item(0)->nodeValue);
		}

		$methodNode = $xpath->query("method", $pagenode);
		if($methodNode && $methodNode->length > 0 && $methodNode->item(0)->nodeValue) {
			$objPage->setMethod($methodNode->item(0)->nodeValue);
		}

		// Add files for page
		$filesNode = $xpath->query("file", $pagenode);
		for($f = 0; $f < $filesNode->length; $f++) {
			if($filesNode && $filesNode->item($f)) {
				if($this->getRootpath())
					$file = $this->getRootpath().'/'.$filesNode->item($f)->nodeValue;
				else
					$file = $filesNode->item($f)->nodeValue;

				if(!file_exists($file)) {
					throw new Exception('File '.$file.' not exists!');
				}

				$objPage->addFile($file);
			}
		}

		// Load Modules for Page
		$loadNodes = $xpath->query("load", $pagenode);
		if($loadNodes->length > 0) {
			for($i = 0; $i < $loadNodes->length; $i++) {
				$loadNode = $loadNodes->item($i);
				$type = $loadNode->getAttribute('type');
				$name = $loadNode->nodeValue;
				switch($type) {
					case 'surface':
						$objPage->addSurface($name, $this->surface[$name]);
						break;
					case 'operator':
						$objPage->addOperator($name, $this->operator[$name]);
						break;
					case 'adapter':
						$objPage->addAdapter($name, $this->adapter[$name]);
						break;
				}
			}
		}

		// Add Rootelements
		if(count($this->elements) > 0) {
			foreach($this->elements as $index => $arr) {
				$objPage->setElement($index, $arr);
			}
		}

		// Overwrite elements
		$arrNodes = $xpath->query("arr", $pagenode);
		for($e = 0; $e < $arrNodes->length; $e++) {
			$arr = array();
		
			$item = $arrNodes->item($e);
			$arrname = $item->getAttribute('name');
			$variables = $item->getElementsByTagName('var');
			for($f = 0; $f < $variables->length; $f++) {
				$var = $variables->item($f);
				$name = $var->getAttribute('name');
				$value = $var->nodeValue;
		
				$arr[$name] = $value;
			}

			$objPage->setElement($arrname, $arr);

			unset($arr);
		}

		// Add Constants for Page
		$constantNodes = $xpath->query("constant", $pagenode);
		if($constantNodes) {
			for($f = 0; $f < $constantNodes->length; $f++) {
				$item = $constantNodes->item($f);
				$objPage->addConstant($item->getAttribute('name'), $item->nodeValue);
			}
		}

		// Expand nodes for Page
		$expand = array();
		
		$expandNodes = $xpath->query("expand", $pagenode);
		if($expandNodes && $expandNodes->length > 0) {
			$x = 0;
			for($j = 0; $j < $expandNodes->length; $j++) {
				$item = $expandNodes->item($j);
				$name = $item->getAttribute('node');
		
				$addNodes = $xpath->query("add", $item);
				if($addNodes && $addNodes->length > 0) {
					$expand[$name][$x] = array();

					for($g = 0; $g < $addNodes->length; $g++) {
						$varAddNode = $addNodes->item($g);
						$addNodeName = $varAddNode->getAttribute('name');
		
						$expand[$name][$x][$addNodeName] = $varAddNode->nodeValue;
					}

					$x++;
				} else {
					if($item->nodeValue)
						$expand[$name] = $item->nodeValue;
					else
						$expand[$name] = "";
				}
			}
		
			$objPage->setNodes($expand);
		}

		$this->sitemap[$pagename] = $objPage;

		return true;
	}

	public function createActions($pagename, $pagenode, $actionname = false) {
		if($this->pageExists($pagename))
			throw new Exception("Page: ".$pagename." is not inimitable!");
		if(!$this->createPage($pagename, $pagenode))
			throw new Exception("Fatal Error: Cannot create ".$pagename);

		$xpath = new DOMXpath($this->domDocumentObj);

		if($actionname) {
			$actionNodes = $xpath->query("action[@name='".$actionname."']", $pagenode);
		} else {
			$actionNodes = $xpath->query("action", $pagenode);
		}

		for($i = 0; $i < $actionNodes->length; $i++) {
			$actionNode = $actionNodes->item($i);
			if($actionNode) {
				$actionname = $actionNode->getAttribute('name');

				$classNode = $xpath->query("class", $actionNode);
				if($classNode && $classNode->length > 0 && $classNode->item(0)->nodeValue) {
					$class = $classNode->item(0)->nodeValue;
				} else {
					$class = $this->sitemap[$pagename]->getClass();
				}

				$methodNode = $xpath->query("method", $actionNode);
				if($methodNode && $methodNode->length > 0 && $methodNode->item(0)->nodeValue) {
					$method = $methodNode->item(0)->nodeValue;
				} else {
					$method = $this->sitemap[$pagename]->getMethod();
				}

				$this->sitemap[$pagename]->addAction($actionname, $class, $method);
				$fileNode = $xpath->query("file", $actionNode);
				if($fileNode) {
					$item = $fileNode->item(0);
					if($item && $item->nodeValue != '') {
						if($this->getRootpath())
							$file = $this->getRootpath().'/'.$item->nodeValue;
						else
							$file = $item->nodeValue;

						$this->sitemap[$pagename]->addFile($file, $actionname);
					}
				}

				$returnType = $actionNode->getAttribute('return');
				$this->sitemap[$pagename]->setReturnTyp($returnType, $actionname);
				$title = $actionNode->getAttribute('title');
				$this->sitemap[$pagename]->setActionTitle($title, $actionname);

				$styleNodes = $xpath->query("style", $actionNode);
				if($styleNodes && $styleNodes->item(0)) {
					$renderBy = $styleNodes->item(0)->getAttribute('renderby');
					$this->sitemap[$pagename]->setRendering($actionname, $renderBy);
					if($styleNodes->item(0)->nodeValue != '') {
						$this->sitemap[$pagename]->setStylesheet($actionname, $styleNodes->item(0)->nodeValue);
					}
				}

				// Load Modules for Actions
				$loadNodes = $xpath->query("load", $actionNode);
				if($loadNodes->length > 0) {
					for($y = 0; $y < $loadNodes->length; $y++) {
						$loadNode = $loadNodes->item($y);
						$type = $loadNode->getAttribute('type');
						$name = $loadNode->nodeValue;
						switch($type) {
							case 'surface':
								$this->sitemap[$pagename]->addLocalSurface($actionname, $name, $this->surface[$name]);
								// module::add($name, $this->surface[$name]['class'], $this->surface[$name]['file'], $objSurface);
								// $objSurface->setElements($this->surface[$name]['elements']);
								break;
							case 'operator':
								$this->sitemap[$pagename]->addLocalOperator($actionname, $name, $this->operator[$name]);
								// module::add($name, $this->operator[$name]['class'], $this->operator[$name]['file'], $objOperator);
								// $objOperator->setElements($this->operator[$name]['elements']);
								break;
							case 'adapter':
								$this->sitemap[$pagename]->addLocalAdapter($actionname, $name, $this->adapter[$name]);
								// module::add($name, $this->adapter[$name]['class'], $this->adapter[$name]['file'], $objOperator);
								// $objOperator->setElements($this->adapter[$name]['elements']);
								break;
						}
					}
				}

				// Add elements for action
				$arrNodes = $xpath->query("arr", $actionNode);
				for($e = 0; $e < $arrNodes->length; $e++) {
					$arr = array();

					$item = $arrNodes->item($e);
					$arrname = $item->getAttribute('name');
					$variables = $xpath->query("var", $item);
					for($f = 0; $f < $variables->length; $f++) {
						$var = $variables->item($f);
						$name = $var->getAttribute('name');
						$value = $var->nodeValue;

						$arr[$name] = $value;
					}

					$this->sitemap[$pagename]->setLocal($actionname, $arrname, $arr);

					unset($arr);
				}

				// Add Elements from Page
				$pageElements = $this->sitemap[$pagename]->getElements();
				foreach($pageElements as $index => $value) {
					$this->sitemap[$pagename]->setLocal($actionname, $index, $value);
				}

				// Add Constants for Action
				$constantNodes = $xpath->query("constant", $actionNode);
				if($constantNodes) {
					for($e = 0; $e < $constantNodes->length; $e++) {
						$var = $constantNodes->item($e);
						$name = $var->getAttribute('name');
						$value = $var->nodeValue;

						$this->sitemap[$pagename]->setLocalConstant($actionname, $name, $value);
					}
				}

				// Expand Nodes for Action
				$expand = array();
				
				$expandNodes = $xpath->query("expand", $actionNode);
				if($expandNodes && $expandNodes->length > 0) {
					$x = 0;
					for($j = 0; $j < $expandNodes->length; $j++) {
						$item = $expandNodes->item($j);
						$name = $item->getAttribute('node');
				
						$addNodes = $xpath->query("add", $item);
						if($addNodes && $addNodes->length > 0) {
							$expand[$name][$x] = array();
				
							for($g = 0; $g < $addNodes->length; $g++) {
								$varAddNode = $addNodes->item($g);
								$addNodeName = $varAddNode->getAttribute('name');
				
								$expand[$name][$x][$addNodeName] = $varAddNode->nodeValue;
							}
				
							$x++;
						} else {
							if($item->nodeValue)
								$expand[$name] = $item->nodeValue;
							else
								$expand[$name] = "";
						}
					}

					$this->sitemap[$pagename]->setLocalNodes($actionname, $expand);
				}
			} else {
				return false;
			}
		}

		return true;
	}

	public function getSitemap() {
		return $this->sitemap;
	}

	public function setElements($arrname, $arr) {
		foreach($this->sitemap as $index => $pageObj) {
			$pageObj->setElement($arrname, $arr);
		}
	}

	public function getStylesheet($page, $action) {
		if(!$page) {
			$page = $this->getRootpage();
		}
		
		if(!$action) {
			$action = $this->getRootaction();
		}

		return $this->sitemap[$page]->getStylesheet($action);
	}
	
	public function setStylesheet($page, $action, $file) {
		return $this->sitemap[$page]->setStylesheet($action, $file);
	}
}
