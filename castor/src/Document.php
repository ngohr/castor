<?php

require_once(SITE_PATH."/src/Application.php");
require_once(SITE_PATH."/src/page.php");

abstract class Document extends Castor {
	abstract function loadPage($pagename, $actionname);

	public function createAction($pagename, $actionname, $template) {
		if(!array_key_exists($pagename, $this->sitemap)) {
			$this->sitemap[$pagename] = new Page($pagename);
		}

		$fileNode = $template->getElementsByTagName('file');
		for($e = 0; $e < $fileNode->length; $e++) {
			$item = $fileNode->item($e);
			if($item->nodeValue) {
				if($this->getRootpath())
					$file = $this->getRootpath().'/'.$item->nodeValue;
				else
					$file = $item->nodeValue;
		
				$this->sitemap[$pagename]->addFile($file);
			}
		
			$e++;
		}

		$class = false;
		$classNode = $template->getElementsByTagName('class');
		if($classNode) {
			if($classNode->length > 0) {
				$classItem = $classNode->item(0);

				if($classItem->nodeValue != '') {
					$class = $classItem->nodeValue;
				}
			}
		}

		if(!$class)
			throw new Exception('No class defined...');

		$method = false;
		$methodNode = $template->getElementsByTagName('method');
		if($methodNode) {
			if($methodNode->length > 0) {
				$methodItem = $methodNode->item(0);
		
				if($methodItem->nodeValue != '') {
					$method = $methodItem->nodeValue;
				}
			}
		}
		
		if(!$method)
			throw new Exception('No method defined...');

		$return = false;
		$returnNode = $template->getElementsByTagName('return');
		if($returnNode) {
			if($returnNode->length > 0) {
				$returnItem = $returnNode->item(0);
		
				if($returnItem->nodeValue != '') {
					$return = $returnItem->nodeValue;
				}
			}
		}
		
		if(!$return)
			throw new Exception('No returntyp defined...');

		$this->sitemap[$pagename]->addAction($actionname, $class, $method);

		$style = false;
		$rendering = false;
		$styleNode = $template->getElementsByTagName('style');
		if($styleNode) {
			if($styleNode->length > 0) {
				$styleItem = $styleNode->item(0);
		
				if($styleItem->nodeValue != '') {
					$style = $styleItem->nodeValue;
					$rendering = $styleItem->getAttribute('renderby');
				}
			}
		}

		if($style) {
			$this->sitemap[$pagename]->setStylesheet($actionname, $style);
			if($rendering)
				$this->sitemap[$pagename]->setRendering($actionname, $rendering);
		}

		$this->sitemap[$pagename]->setReturnTyp($return, $actionname);

		$arrNodes = $template->getElementsByTagName('arr');
		for($i = 0; $i < $arrNodes->length; $i++) {
			$arr = array();
		
			$item = $arrNodes->item($i);
			$arrname = $item->getAttribute('name');
			$variables = $item->getElementsByTagName('var');
			for($f = 0; $f < $variables->length; $f++) {
				$var = $variables->item($f);
				$name = $var->getAttribute('name');
				$value = $var->nodeValue;
	
				$arr[$name] = $value;
			}

			$this->sitemap[$pagename]->setElement($arrname, $arr);

			unset($arr);
		}

		return true;
	}

	public function setElements($arrname, $arr) {
		foreach($this->sitemap as $index => $pageObj) {
			$pageObj->setElement($arrname, $arr);
		}
	}

	public function setFiles($file) {
		foreach($this->sitemap as $index => $pageObj) {
			$pageObj->addFile($file);
		}
	}
}
