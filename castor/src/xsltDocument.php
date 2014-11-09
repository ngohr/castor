<?php

require_once(SITE_PATH."/src/Document.php");

class xsltDocument extends Document {
	// PHP DOMDocument / implements an xml root node and will filled after loadPage or run
	private $domDocumentObj = false;
	private $root;

	// If a page exists in array $this->arrPage, return true
	public function pageExists($name) {
		if(!array_key_exists($name, $this->sitemap))
			return false;
	
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
				$objPage = $this->sitemap[$page];
	
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
				$objPage = $this->sitemap[$page];
	
			$action = $this->getRootaction();
	
			if(!$objPage->actionExists($action))
				throw new Exception('Action "'.$action.'" for Page "'.$page.'" is not defined...');
		}

		// Set Page Array for Sitemap Call
		$objPage->setSitemap($this->sitemap);

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
						
					$expand = $objPage->getLocalNodes($action);
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
}