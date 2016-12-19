<?php

// Include castor mainDocument
require_once('../castor/Castor.php');

try {
	// Load with a config file
	$document = new xsltDocument('config/config.xml', 'home');
	$document->loadPage();

	// Alternative (phtmlDocument does not support DomDocument Objects)
	// $document = new phtmlDocument('config/config.xml', 'home');
	// $document->loadPage();

	// Alternative
	// $page = new Page('home', 'index');
	// $page->addFile('controller/indexController.php');
	// $page->setElement('myelement', array('element' => 'Hello World'));
	// $page->addAction('index', 'controller/indexController.php', 'indexAction');
	// $page->loadPage('home', 'index');

	// thx and good luck
} catch(Exception $exeption) {
	die($exeption->getMessage());
}