<?php

/*
 *
 * xsltDocument Class extends Document 
 *
 * - Objects of type Array or DomDocument will rendered as a xslt output or prepared for a client rendering with xsl include
 * - Expand nodes form config expand/add nodes
 * - $this->root extends $this->domDocumentPage with a root element and includes the main values from controller classes
 * 
 * @todos
 * 
 * #001 A root tag shouldt not named ever as 'root' and shouldt configured and named from class Castor
 * #003 implement compress output for return 'file' actions
 * 
 * @author
 * 
 * Nanno Gohr
 * 
 * @version
 * 
 * 1.0 / 24.11.2014
 * 
 */

require_once(SITE_PATH."/src/Document.php");

class xsltDocument extends Document {
	// PHP DOMDocument / implements an xml root node that will filled after loadPage() or run()
	private $domDocumentPage = false;
	private $root;

	private function expandNodes($objPage, $action) {
		// Expand Nodes for Page
		$expand = $objPage->getNodes();
		if($expand && count($expand) > 0) {
			foreach($expand as $index => $value) {
				if(is_array($value)) {
					foreach($value as $subIndex => $subValue) {
						$node = $this->domDocumentPage->createElement($index);
						elements::createElementRepresentation($subValue, $this->domDocumentPage, $node);

						$this->root->appendChild($node);
					}
				} else {
					$node = $this->domDocumentPage->createElement($index);
					$txt = $this->domDocumentPage->createTextNode($value);
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
						$node = $this->domDocumentPage->createElement($index);
						elements::createElementRepresentation($subValue, $this->domDocumentPage, $node);

						$this->root->appendChild($node);
					}
				} else {
					$node = $this->domDocumentPage->createElement($index);
					$txt = $this->domDocumentPage->createTextNode($value);
					$node->appendChild($txt);
					$this->root->appendChild($node);
				}
			}
		}
	}

	public function expandNode($index, $value) {
		// Create DomDocument Object
		if(!$this->domDocumentPage) {
			$this->domDocumentPage = new DOMDocument('1.0', 'UTF-8');
			$this->domDocumentPage->preserveWhiteSpace = true;
			$this->domDocumentPage->formatOutput = true;

			// Create Document Rootnode <root>
			// Relates to #007: Document root name, must not named as 'root' otherwise it couldt named as html, document or alternative
			$this->root = $this->domDocumentPage->createElement('root');
		}

		$element = $this->domDocumentPage->createElement($index);
		$txt = $this->domDocumentPage->createTextNode($value);
		$element->appendChild($txt);
		$this->root->appendChild($element);
	}

	public function loadPage($page = false, $action = false) {
		$objPage = false;

		// Create DomDocument Object
		if(!$this->domDocumentPage) {
			$this->domDocumentPage = new DOMDocument('1.0', 'UTF-8');
			$this->domDocumentPage->preserveWhiteSpace = true;
			$this->domDocumentPage->formatOutput = true;
				
			// Create Document Rootnode <root>
			// Relates @todo #001: Document root name, must not named as 'root' otherwise it couldt named as html, document or alternative
			$this->root = $this->domDocumentPage->createElement('root');
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

		$objPage->loadAddons($action);

		$returnTypeHooks = hooks::getHooks('returnType');

		// relates #002 - Returntype json and xml is not an individual for text/html
		switch($returnType) {
			case 'Array':
				// Contruct DomDocument Elements from a given array, returned from $objPage->call($action);
				$returnvalue = $objPage->call($action);
	
				if($returnvalue && is_array($returnvalue)) {
					if(!elements::createElementRepresentation($returnvalue, $this->domDocumentPage, $this->root))
						throw new Exception(elements::getError());
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

				header('Content-Type: text/html; charset=UTF-8');
				ob_end_flush();

				return true;

				break;

			case 'json':
				header('Content-Type: application/json; charset=UTF-8');
				ob_start();

				if(!$action) {
					$send = array();
					foreach($objPage->load() as $index => $value) {
						if(!$value)
							break;
						$send[$index][] = $value;
					}
					echo json_encode($send);
				} else {
					$send = $objPage->call($action);
					echo json_encode($send);
				}

				if((!is_array($send) && $send) || (is_array($send) && count($send) > 0))
					ob_end_flush();

				return true;

				break;

			case 'file':
				$returnvalue = $objPage->call($action);
				if($returnvalue) {
					ob_start();
					header('Content-Type: text/html; charset=UTF-8');

					$stylesheet = $objPage->getStylesheet($action);
					$handle = @fopen($stylesheet, "r");
					if($handle) {
						while(($buffer = fgets($handle)) !== false) {
							echo $buffer;
						}
	
						if (!feof($handle)) {
							throw new Exception("Fatal Error: cannot read ".$stylesheet);
						}
						fclose($handle);
					} else {
						throw new Exception("Fatal Error: cannot open ".$stylesheet);
					}
	
					ob_end_flush();
	
					return true;
				}

				break;

			case 'DomDocument':
				// Import the node, and all its children, to the document
				$returnvalue = $objPage->call($action);
				if($returnvalue) {
					try {
						foreach($returnvalue->childNodes as $sibling) {
							$node = $this->domDocumentPage->importNode($sibling, true);
							$this->root->appendChild($node);
						}
					} catch(DOMException $exeption) {
						die(var_dump($exeption->getMessage()));
					}
				
					$expand = $objPage->getLocalNodes($action);
					$this->expandNodes($objPage, $action);
				}
				break;

			default:
				ob_start();
				if($returnTypeHooks) {
					foreach($returnTypeHooks as $hookName => $values) {
						if($returnType == $hookName) {
							$values['callback']($objPage, $action);
						}
					}
				}
				ob_end_flush();
				break;
		}

		$rendertype = $objPage->getRendering($action);
		switch($rendertype) {
			case 'client':
				$stylesheet = $objPage->getStylesheet($action);

				if(!empty($stylesheet)) {
					$xslt = $this->domDocumentPage->createProcessingInstruction(
						'xml-stylesheet', 'type="text/xsl" href="'.$stylesheet.'"'
					);

					$this->domDocumentPage->appendChild($xslt);
					$this->domDocumentPage->appendChild($this->root);
				} else {
					return false;
				}
	
				$this->domDocumentPage->appendChild($this->root);
	
				$this->sendXml();
	
				break;
	
			case 'server':
				$this->domDocumentPage->appendChild($this->root);
				$stylesheet = $objPage->getStylesheet($action);

				$this->sendHTML($stylesheet);
	
				break;

			default:
				if($returnType == "DomDocument") {
					header('Content-type: text/xml; charset=UTF-8');
					$this->domDocumentPage->appendChild($this->root);
					$this->sendXml();
				}
	
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

		$this->domDocumentPage->save('php://output');

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
	
		$output = $xpr->transformToDoc($this->domDocumentPage);
	
		header('Content-Type: text/html; charset=UTF-8');

		echo $output->saveHTML();
	
		return true;
	}
}