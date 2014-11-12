<?php

/*
 *
 * phtmlDocument Class extends Document
 *
 * - Objects of type Array or given to class view::add() will given to a phtml template
 * - The output buffer stoped and flush the output from *.phtml stylesheets
 * - phtmlDocument cannot expand nodes form config expand/add nodes
 *
 * @todos
 *
 * #001 Returntype DomDocument is not supported
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
require_once(SITE_PATH."/src/phtmlView.php");

class phtmlDocument extends Document {
	// If a page exists in array $this->arrPage, return true
	public function pageExists($name) {
		if(!array_key_exists($name, $this->sitemap))
			return false;
	
		return true;
	}

	public function loadPage($page = false, $action = false) {
		$objPage = false;

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

		// relates #006 - Returntype json and xml is not an individual for text/html
		switch($returnType) {
			case 'Array':
				// Contruct DomDocument Elements from a given array, returned from $objPage->call($action);
				$returnvalue = $objPage->call($action);
				if($returnvalue && is_array($returnvalue)) {
					foreach($returnvalue as $index => $value) {
						if(!is_array($value)) {
							View::Add($index, $value);
						} else {
							$arr[$index] = array();
							if(is_numeric($index)) {
								for($i = 0; $i < count($value); $i++) {
									$arr[$index][$i] = array();
									foreach($value[$i] as $xxx => $yyy) {
										$arr[$index][$i][$xxx] = $yyy;
									}
									View::Add($index, $arr[$index]);
								}
							} else {
								foreach($value as $xxx => $yyy) {
									$arr[$index][$xxx] = $yyy;
								}
							}
						}
						View::Add($index, $arr[$index]);
					}
				}

				break;

			case 'text/html':
				ob_start();

				// Print Plain Text from $objPage->load();
				if(!$action) {
					foreach($objPage->load() as $index => $value)
						echo $value."\n";
				} else {
					echo $objPage->call($action);
				}

				ob_flush();

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

		$stylesheet = $objPage->getStylesheet($action);

		ob_start();

		if(!empty($stylesheet)) {
			require_once($stylesheet);
		} else {
			return false;
		}

		ob_flush();

		return true;
	}
}