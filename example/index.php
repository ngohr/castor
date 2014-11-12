<?php

// Include xsltDocument main.php
require_once('../castor/Castor.php');

// Load with a config file
$document = new xsltDocument('config/config.xml', 'home');
$document->loadPage();

// Alternative (this time only when an array will returned)
// $document = new phtmlDocument('config/config.xml', 'home');
// $document->loadPage();

// Alternative
// $page = new Page('home');
// $page->addFile('controller/indexController.php');
// $page->setElement('myelement', array('element' => 'Hello World'));
// $page->addAction('index', 'controller/indexController.php', 'indexAction');
// $page->loadPage('home', 'index');

// thx and good luck
