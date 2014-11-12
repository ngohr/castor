<?php

class indexController extends Application {
	public function indexAction() {
		// Create DomDocument Object
		$domDocumentObj = new DOMDocument('1.0', 'UTF-8');
		$domDocumentObj->preserveWhiteSpace = true;
		$domDocumentObj->formatOutput = true;

		// Add sitemap nodes
		if($elementSitemap = $this->getSitemap(false)) {
			$node = $domDocumentObj->importNode($elementSitemap, true);
			$domDocumentObj->appendChild($node);
		}

		// Get elements and append a child
		$elements = $this->getElements('myelement');
		$node = $domDocumentObj->createElement('myelement');
		$txt = $domDocumentObj->createTextNode($elements['element']);
		$node->appendChild($txt);
		$domDocumentObj->appendChild($node);

		// return object
		return $domDocumentObj;
	}
}