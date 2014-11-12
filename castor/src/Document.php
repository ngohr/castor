<?php

/*
 *
* Document main class extends Castor
*
* - Create an array includes Page and Action objects to load Controller Classes.
* - The given sitemap from castor objects will be deconstructed with a xsl-template and elements wouldt be extracted
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

	public function createPage($pagename, $pagenode) {
		if(!array_key_exists($pagename, $this->sitemap)) {
			$objPage = new Page($pagename);
			$objPage->setName($pagename);

			$index = $pagenode->getAttribute('index');
			$objPage->setIndex($index);
			$returnTyp = $pagenode->getAttribute('return');
			$objPage->setReturnTyp($returnTyp);
			$titleNode = $pagenode->getElementsByTagName('title');
			$title = $titleNode->item(0)->nodeValue;
			$objPage->setTitle($title);

			// Add Rootfile
			$objPage->addFile($this->getRootpath()."/".$this->getRootfile());

			// Add files for page
			$filesNode = $pagenode->getElementsByTagName('file');
			for($f = 0; $f < $filesNode->length; $f++) {
				if($filesNode && $filesNode->item($f)) {
					if(!file_exists($this->getRootpath()."/".$filesNode->item($f)->nodeValue)) {
						throw new Exception('File '.$this->getRootpath()."/".$filesNode->item($f)->nodeValue.' not exists!');
					}

					if($this->getRootpath())
						$file = $this->getRootpath().'/'.$filesNode->item($f)->nodeValue;
					else
						$file = $filesNode->item($f)->nodeValue;

					$objPage->addFile($file);
				}
			}

			// Add Rootelements
			if(count($this->elements) > 0) {
				foreach($this->elements as $index => $arr) {
					$objPage->setElement($index, $arr);
				}
			}

			// Add elements
			$arrNodes = $pagenode->getElementsByTagName('arr');
			for($e = 0; $e < $arrNodes->length; $e++) {
				$arr = array();
			
				$item = $arrNodes->item($e);
				if($item->parentNode->nodeName == 'page') {
					$arrname = $item->getAttribute('name');
					$variables = $item->getElementsByTagName('var');
					for($f = 0; $f < $variables->length; $f++) {
						$var = $variables->item($f);
						$name = $var->getAttribute('name');
						$value = $var->nodeValue;
				
						$arr[$name] = $value;
					}
	
					$objPage->setElement($arrname, $arr);
				}
			
				unset($arr);
			}

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

			$this->sitemap[$pagename] = $objPage;

			return true;
		}

		return false;
	}

	public function createActions($pagename, $template) {
		if(!array_key_exists($pagename, $this->sitemap)) {
			$this->createPage($pagename, $template->getElementsByTagName('page')->item(0));
		}

		$actionNodes = $template->getElementsByTagName('action');

		for($i = 0; $i < $actionNodes->length; $i++) {
			$actionNode = $actionNodes->item($i);
			if($actionNode) {
				$actionname = $actionNode->getAttribute('name');
				$classNode = $actionNode->getElementsByTagName('class');
				$class = $classNode->item(0)->nodeValue;
				$methodNode = $actionNode->getElementsByTagName('method');
				$method = $methodNode->item(0)->nodeValue;

				$this->sitemap[$pagename]->addAction($actionname, $class, $method);
				$fileNode = $actionNode->getElementsByTagName('file');
				for($e = 0; $e < $fileNode->length; $e++) {
					$item = $fileNode->item($e);
					if($item->nodeValue) {
						if($this->getRootpath())
							$file = $this->getRootpath().'/'.$item->nodeValue;
						else
							$file = $item->nodeValue;
				
						$this->sitemap[$pagename]->addLocalFile($file, $actionname);
					}

					$e++;
				}

				$returnType = $actionNode->getAttribute('return');
				$this->sitemap[$pagename]->setReturnTyp($returnType, $actionname);
				$title = $actionNode->getAttribute('title');
				$this->sitemap[$pagename]->setActionTitle($title, $actionname);

				$styleNode = $actionNode->getElementsByTagName('style');
				if($styleNode) {
					$renderBy = $styleNode->item(0)->getAttribute('renderby');
					if($styleNode->item(0)->nodeValue != '') {
						$this->sitemap[$pagename]->setStylesheet($actionname, $styleNode->item(0)->nodeValue);
					}
				}
				
				$this->sitemap[$pagename]->setRendering($actionname, $renderBy);

				// Add elements for action
				$arrNodes = $actionNode->getElementsByTagName('arr');
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

					$this->sitemap[$pagename]->setLocal($actionname, $arrname, $arr);

					unset($arr);
				}

				// Add Constants for Action
				$constantNodes = $actionNode->getElementsByTagName('constant');
				if($constantNodes) {
					for($e = 0; $e < $constantNodes->length; $e++) {
						$var = $constantNodes->item($e);
				
						$this->sitemap[$pagename]->setLocalConstant($actionname, $var->getAttribute('name'), $var->nodeValue);
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
				
						$j++;
					}

					$this->sitemap[$pagename]->setLocalNodes($actionname, $expand);
				}
			}
		}

		return true;
	}

	public function setElements($arrname, $arr) {
		foreach($this->sitemap as $index => $pageObj) {
			$pageObj->setElement($arrname, $arr);
		}
	}
}
