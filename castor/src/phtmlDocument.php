<?php

/*
 * Copyright (c) 2016, Nanno Gohr
 *
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
 * 1.0 / 24.11.2017
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

		$stylesheet = $objPage->getStylesheet($action);

		$objPage->loadAddons($action);

		$returnTypeHooks = hooks::getHooks('returnType');

		// relates #006 - Returntype json and xml is not an individual for text/html
		switch($returnType) {
			case 'Array':
				// Contruct DomDocument Elements from a given array, returned from $objPage->call($action);
				$returnvalue = $objPage->call($action);
				if($returnvalue && is_array($returnvalue)) {
					self::addArray($returnvalue);

					ob_start();
					if(!empty($stylesheet)) {
						require_once($stylesheet);
					} else {
						return false;
					}
					ob_end_flush();
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

				header('Content-Type: text/html; charset=UTF-8');
				ob_end_flush();

				return true;

				break;

			case 'false':
				return true;

				break;

			case 'json':
				header('Content-Type: application/json');
				ob_start();

				if(!$action) {
					$send = array();
					foreach($objPage->load() as $index => $value) {
						$send[$index][] = $value;
					}
					echo json_encode($send);
				} else {
					echo json_encode($objPage->call($action));
				}

				ob_end_flush();

				return true;

				break;

			case 'file':
				ob_start();

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

				header('Content-Type: text/html; charset=UTF-8');
				ob_end_flush();

				return true;

				break;

			default:
				if($returnTypeHooks) {
					foreach($returnTypeHooks as $hookName => $values) {
						if($returnType == $hookName) {
							$values['callback']($objPage, $action);
						}
					}
				}
				break;
		}



		return true;
	}

	static function addArray($arr) {
		foreach($arr as $index => $value) {
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
}