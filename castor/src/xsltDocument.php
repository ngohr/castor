<?php

/*
 *
 * xsltDocument Class extends Document 
 *
 * - Objects of type Array or DomDocument will rendered as a xslt output or prepared for a client rendering with xsl include
 * - Expand nodes form config expand/add nodes
 * - $this->root extends $this->domDocumentObj with a root element and includes the main values from controller classes
 * 
 * @todos
 * 
 * #001 A root tag shouldt not named ever as 'root' and shouldt configured and named from class Castor
 * #002 Returntype json and xml is not an individual for text/html, take a finnaly decission for saveXML or saveHTML ways...
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
			// Relates @todo #001: Document root name, must not named as 'root' otherwise it couldt named as html, document or alternative
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

		$objPage->loadFiles($action);
		$returnType = $objPage->getReturnTyp($action);
		if(!$returnType)
			$returnType = $objPage->getReturnTyp();

		// relates #002 - Returntype json and xml is not an individual for text/html
		switch($returnType) {
			case 'DomDocument':
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
				ob_start();

				if(!$action) {
					foreach($objPage->load() as $index => $value)
						echo $value."\n";
				} else {
					echo $objPage->call($action);
				}
	
				return true;

				ob_flush();

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

				$this->sendHTML($stylesheet);
	
				break;
	
			case 'html5':
				$this->domDocumentObj->appendChild($this->root);
				$stylesheet = $objPage->getStylesheet($action);
					
				$this->sendHTML5($stylesheet);
					
				break;
	
			// relates todo #002
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

	public function expandNode($index, $value) {
		// Create DomDocument Object
		if(!$this->domDocumentObj) {
			$this->domDocumentObj = new DOMDocument('1.0', 'UTF-8');
			$this->domDocumentObj->preserveWhiteSpace = true;
			$this->domDocumentObj->formatOutput = true;
		
			// Create Document Rootnode <root>
			// Relates to #007: Document root name, must not named as 'root' otherwise it couldt named as html, document or alternative
			$this->root = $this->domDocumentObj->createElement('root');
		}

		$element = $this->domDocumentObj->createElement($index);
		$txt = $this->domDocumentObj->createTextNode($value);
		$element->appendChild($txt);
		$this->root->appendChild($element);
	}
}