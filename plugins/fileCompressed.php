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
 */
define('FILE_TIMEOUT', 0);

class HttpCache extends Application {
	public function compressAction() {
		$lastModified = 0;

		// Fond lastModified Timestamp
		$objPage = $this->getObjPage($this->getPagename());
		$stylesheet = $objPage->getStylesheet($this->getActionname());
		$files = explode(";", $stylesheet);
		foreach($files as $index => $file) {
			if(preg_match("/^\/.*/", $file)) {
				if(filemtime($_SERVER['DOCUMENT_ROOT'].$file) > $lastModified)
					$lastModified = filemtime($_SERVER['DOCUMENT_ROOT'].$file);
			}
		}
		self::Init($lastModified, 7200);
	}

	public static function Init($lastModifiedTimestamp, $maxAge) {
		if(self::IsModifiedSince($lastModifiedTimestamp)) {
			self::SetLastModifiedHeader($lastModifiedTimestamp, $maxAge);
		} else {
			self::SetNotModifiedHeader($maxAge);
		}
	}

	private static function IsModifiedSince($lastModifiedTimestamp) {
		$allHeaders = getallheaders();

		if(array_key_exists("If-Modified-Since", $allHeaders)) {
			$gmtSinceDate = $allHeaders["If-Modified-Since"];
			$sinceTimestamp = strtotime($gmtSinceDate);

			// Can the browser get it from the cache?
			if($sinceTimestamp != false && $lastModifiedTimestamp <= $sinceTimestamp) {
				return false;
			}
		}

		return true;
	}

	private static function SetNotModifiedHeader($maxAge) {
		// Set headers
		header("HTTP/1.1 304 Not Modified", true);
		header("Cache-Control: public, max-age=$maxAge", true);
		die();
	}

	private static function SetLastModifiedHeader($lastModifiedTimestamp, $maxAge) {
		// Fetching the last modified time of the XML file
		$date = gmdate("D, j M Y H:i:s", $lastModifiedTimestamp)." GMT";

		// Set headers
		header("HTTP/1.1 200 OK", true);
		header("Cache-Control: public, max-age=$maxAge", true);
		header("Last-Modified: $date", true);
	}
}

function CompressDomDocument(&$objPage, $action) {
	$domDocumentPage = new DOMDocument('1.0', 'UTF-8');
	$domDocumentPage->preserveWhiteSpace = true;
	$domDocumentPage->formatOutput = false;

	$rootNode = $domDocumentPage->createElement('root');

	// Expand Nodes for Page
	$expand = $objPage->getNodes();
	if($expand && count($expand) > 0) {
		foreach($expand as $index => $value) {
			if(is_array($value)) {
				foreach($value as $subIndex => $subValue) {
					$node = $domDocumentPage->createElement($index);
					elements::createElementRepresentation($subValue, $domDocumentPage, $node);
					$rootNode->appendChild($node);
				}
			} else {
				$node = $domDocumentPage->createElement($index);
				$txt = $domDocumentPage->createTextNode($value);
				$node->appendChild($txt);
				$rootNode->appendChild($node);
			}
		}
	}

	// Expand Nodes for Actions
	$expand = $objPage->getLocalNodes($action);
	if($expand && count($expand) > 0) {
		foreach($expand as $index => $value) {
			if(is_array($value)) {
				foreach($value as $subIndex => $subValue) {
					$node = $domDocumentPage->createElement($index);
					elements::createElementRepresentation($subValue, $domDocumentPage, $node);
					$rootNode->appendChild($node);
				}
			} else {
				$node = $domDocumentPage->createElement($index);
				$txt = $domDocumentPage->createTextNode($value);
				$node->appendChild($txt);
				$rootNode->appendChild($node);
			}
		}
	}

	// Import the node, and all its children, to the document
	$returnvalue = $objPage->call($action);
	if($returnvalue) {
		try {
			foreach($returnvalue->childNodes as $sibling) {
				$node = $domDocumentPage->importNode($sibling, true);
				$rootNode->appendChild($node);
			}
		} catch(DOMException $exeption) {
			die(var_dump($exeption->getMessage()));
		}
	}
	$domDocumentPage->appendChild($rootNode);
	$styleSheet = $objPage->getStylesheet($action);

	$xsl = new DomDocument();
	if(file_exists($styleSheet)) {
		$xsl->load($styleSheet);
	} else {
		throw new Exception('Couldt not load stylesheet from File: '.$styleSheet);
	}
	$xpr = new XsltProcessor();
	$xpr->importStylesheet($xsl);
	$output = $xpr->transformToDoc($domDocumentPage);
	header('Content-Type: text/html; charset=UTF-8', true);
	$content = $output->saveHTML();
	$content = str_replace("\t", "", $content);
	$content = str_replace("\r", "", $content);
	$content = preg_replace("/[^\/]\<--.*--\>/s", '', $content);
	$content = str_replace("//</script", "</script", $content);
	$content = str_replace("//<![CDATA[", "", $content);
	$content = str_replace("//]]></script>", "</script>", $content);
	if(preg_match('/(<script.+?>.+?)\/\*.+?\*\/(.*?<\/script>)/ism', $content))
		$content = preg_replace('/(<script.+?>.+?)\/\*.+?\*\/(.*?<\/script>)/ism', "$1$2", $content);
	if(preg_match('/(<script.+?>.*?)\n\/\/.+?(\n.+?<\/script>)/ism', $content))
		$content = preg_replace('/(<script.+?>.*?)\n\/\/.+?(\n.+?<\/script>)/ism', "$1$2", $content);
	$content = str_replace("\n\n", "\n", $content);

	while(preg_match('/(<style.+?>.*?)\{\n(.*?<\/style>)/ism', $content))
		$content = preg_replace('/(<style.+?>.*?)\{\n(.*?<\/style>)/ism', '$1{$2', $content);
	while(preg_match('/(<style.+?>.*?)\;\n(.*?<\/style>)/ism', $content))
		$content = preg_replace('/(<style.+?>.*?)\;\n(.*?<\/style>)/ism', "$1;$2", $content);
	while(preg_match('/(<style.+?>.*?)\}\n\}(.*?<\/style>)/ism', $content))
		$content = preg_replace('/(<style.+?>.*?)\}\n\}(.*?<\/style>)/ism', "$1}}$2", $content);

	echo $content;

	return true;
}

function cssCompressFunction($content) {
	$content = str_replace("\t", "", $content);
	$content = str_replace("\r", "", $content);
	$content = preg_replace("/\/\*.*?\*\//s", '', $content);
	$content = str_replace("\n", "", $content);
	$content = str_replace("{ ", "{", $content);
	$content = str_replace(" {", "{", $content);
	$content = str_replace(": ", ":", $content);

	/*
	$content = str_replace(" 0px", "0", $content);
	$content = str_replace(" 0%", "0", $content);
	$content = str_replace(" 0em", "0", $content);
	$content = str_replace(":0px", "0", $content);
	$content = str_replace(":0%", "0", $content);
	$content = str_replace(":0em", "0", $content);
	$content = preg_replace("/\s{2,}+/", '', $content);
	*/

	return $content;
}

function CompressCss(&$objPage, $action) {
	$objPage->call($action);
	if(file_exists("/tmp/".$objPage->getName()."_".$action.".css")) {
		if(filemtime("/tmp/".$objPage->getName()."_".$action.".css") > time() - FILE_TIMEOUT) {
			header("Content-type: text/css; charset=UTF-8", true);
			echo file_get_contents("/tmp/".$objPage->getName()."_".$action.".css")."\n";

			return 1;
		}
	}

	$content = '';
	$stylesheet = $objPage->getStylesheet($action);
	$files = explode(";", $stylesheet);
	foreach($files as $index => $file) {
		if(!preg_match("/^http.*/", $file)) {
			$content .= file_get_contents($_SERVER['DOCUMENT_ROOT'].$file)."\n";
		} else {
			$content .= file_get_contents($file)."\n";
		}
	}
	$content = cssCompressFunction($content);
	if(file_exists("/tmp/".$objPage->getName()."_".$action.".css")) {
		unlink("/tmp/".$objPage->getName()."_".$action.".css");
	}

	file_put_contents("/tmp/".$objPage->getName()."_".$action.".css", $content);

	header("Content-type: text/css; charset=UTF-8", true);
	echo $content;

	return true;
}

function javascriptCompressFunction($content) {
	$content = str_replace("\t", "", $content);
	$content = str_replace("\r", "", $content);
	$content = preg_replace("/\n\/\/.*?\n/m", "\n", $content);
	$content = preg_replace("/\/\*.*?\*\//s", '', $content);
	$content = preg_replace('/[^\S\n]+/', ' ', $content);
	$content = str_replace(array(" \n", "\n "), "\n", $content);
	$content = preg_replace('/\n+/', "\n", $content);
	$content = preg_replace("/\n\/\/.*?\n/m", "\n", $content);
	$content = preg_replace("/{\nif/", '{if', $content);
	$content = preg_replace("/{\nreturn/", '{return', $content);
	$content = preg_replace("/{\nvar/", '{var', $content);
	$content = preg_replace("/\)\s*{\n*/", '){', $content);
	$content = preg_replace("/\;\n/m", '; ', $content);
	$content = preg_replace("/\},\n}/", '},}', $content);
	$content = preg_replace("/\{\nalert/", '{alert', $content);

	// $content = preg_replace("/\s{2,}+/", '', $content);
	// $content = str_replace("{ ", "{", $content);
	// $content = str_replace(" {", "{", $content);
	// $content = str_replace("}\n\n", "}\n", $content);
	// $content = str_replace(";\n\n", ";\n", $content);
	// $content = str_replace(";\n", '; ', $content);
	// $content = str_replace("\nfunction", "\n", $content);

	return $content;
}

function CompressJavascript(&$objPage, $action) {
	$objPage->call($action);
	if(file_exists("/tmp/".$objPage->getName()."_".$action.".js")) {
		if(filemtime("/tmp/".$objPage->getName()."_".$action.".js") > time() - FILE_TIMEOUT) {
			header("Content-type: application/javascript; charset=UTF-8", true);
			echo file_get_contents("/tmp/".$objPage->getName()."_".$action.".js")."\n";

			return 1;
		}
	}

	$content = '';
	$stylesheet = $objPage->getStylesheet($action);
	$files = explode(";", $stylesheet);
	foreach($files as $index => $file) {
		if(!preg_match("/^http.*/", $file)) {
			$content .= file_get_contents($_SERVER['DOCUMENT_ROOT'].$file)."\n";
		} else {
			$content .= file_get_contents($file)."\n";
		}
	}

	$content = javascriptCompressFunction($content);
	if(file_exists("/tmp/".$objPage->getName()."_".$action.".js")) {
		unlink("/tmp/".$objPage->getName()."_".$action.".js");
	}

	file_put_contents("/tmp/".$objPage->getName()."_".$action.".js", $content);
	header("Content-type: application/javascript; charset=UTF-8", true);
	echo $content;
}

function get_include_contents($filename) {
	if (is_file($filename)) {
		ob_start();
		include $filename;
		return ob_get_clean();
	}
	return false;
}

function CompressPhtml(&$objPage, $action) {
	$returnvalue = $objPage->call($action);
	if($returnvalue && is_array($returnvalue)) {
		phtmlDocument::addArray($returnvalue);
	} else {
		if($returnvalue === false) {
			exit();
		}
	}

	$stylesheet = $objPage->getStylesheet($action);
	$content = get_include_contents($stylesheet);

	$content = str_replace("\t", "", $content);
	$content = str_replace("\r", "", $content);
	$content = preg_replace("/\>\n\</m", "><", $content);
	$content = str_replace("\s*\n*", "\s", $content);
	$content = preg_replace("/<\!--.+?-->/sm", "", $content);
	if(preg_match('/(\<script.+?type\=\"text\/javascript\".*?\>.+?)\/\*.+?\*\/(.+?\<\/script\>)/ism', $content, $match))
		$content = preg_replace('/(\<script.+?type\=\"text\/javascript\".*?\>.+?)\/\*.+?\*\/(.+?\<\/script\>)/ism', "$1$2", $content);
	while(preg_match('/(<style.+?>.*?)\{\n(.*?<\/style>)/ism', $content))
		$content = preg_replace('/(<style.+?>.*?)\{\n(.*?<\/style>)/ism', '$1{$2', $content);
	while(preg_match('/(<style.+?>.*?)\;\n(.*?<\/style>)/ism', $content))
		$content = preg_replace('/(<style.+?>.*?)\;\n(.*?<\/style>)/ism', "$1;$2", $content);
	while(preg_match('/(<style.+?>.*?)\}\n\}(.*?<\/style>)/ism', $content))
		$content = preg_replace('/(<style.+?>.*?)\}\n\}(.*?<\/style>)/ism', "$1}}$2", $content);
	while(preg_match('/\n{2,}/mis', $content))
		$content = str_replace("\n\n", "\n", $content);

		/*
	$content = str_replace(" 0px", "0", $content);
	$content = str_replace(" 0%", "0", $content);
	$content = str_replace(" 0em", "0", $content);
	$content = str_replace(":0px", "0", $content);
	$content = str_replace(":0%", "0", $content);
	$content = str_replace(":0em", "0", $content);
	*/
	header("Content-type: text/html; charset=UTF-8", true);
	echo $content;

	ob_end_flush();
}

hooks::addHook('returnType', 'CompressCss', 'CompressCss');
hooks::addHook('returnType', 'CompressJavascript', 'CompressJavascript');
hooks::addHook('returnType', 'CompressDomDocument', 'CompressDomDocument');
hooks::addHook('returnType', 'CompressPhtml', 'CompressPhtml');
