<?php

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
		self::Init($lastModified, 8640000);
	}

	public static function Init($lastModifiedTimestamp, $maxAge) {
		if(self::IsModifiedSince($lastModifiedTimestamp)) {
			self::SetLastModifiedHeader($lastModifiedTimestamp, $maxAge);
		} else {
			self::SetNotModifiedHeader($lastModifiedTimestamp, $maxAge);
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

	private static function SetNotModifiedHeader($lastModifiedTimestamp, $maxAge) {
		// Set headers
		header("HTTP/1.1 304 Not Modified", true);
		header("Cache-Control: public, max-age=".$maxAge, true);
		header("ETag: \"".md5($lastModifiedTimestamp)."\"");
		die();
	}

	private static function SetLastModifiedHeader($lastModifiedTimestamp, $maxAge) {
		// Fetching the last modified time of the XML file
		$date = gmdate("D, j M Y H:i:s", $lastModifiedTimestamp)." GMT";

		// Set headers
		header("HTTP/1.1 200 OK", true);
		header("Cache-Control: public, max-age=".$maxAge, true);
		header("ETag: \"".md5($lastModifiedTimestamp)."\"");
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

	header('Content-Type: text/html; charset=UTF-8', true);

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

	$content = $output->saveHTML();
	$content = str_replace(" SYSTEM \"about:legacy-compat\"", '', $content);
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
	if(is_file($filename)) {
		ob_start(NULL, 0, PHP_OUTPUT_HANDLER_FLUSHABLE ^ PHP_OUTPUT_HANDLER_REMOVABLE);
		include $filename;
		return ob_get_clean();
	}
	return false;
}

function CompressPhtml(&$objPage, $action) {
	header("Content-type: text/html; charset=UTF-8", true);
	$returnvalue = $objPage->call($action);
	if($returnvalue && is_array($returnvalue)) {
		phtmlDocument::addArray($returnvalue);
	} else {
		if($returnvalue === false) {
			exit();
		}
	}

	ob_implicit_flush(false);
	$stylesheet = $objPage->getStylesheet($action);
	$content = get_include_contents($stylesheet);
	if($content) {
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
		echo $content;
	}

	ob_end_flush();
	// flush();
}

hooks::addHook('returnType', 'CompressCss', 'CompressCss');
hooks::addHook('returnType', 'CompressJavascript', 'CompressJavascript');
hooks::addHook('returnType', 'CompressDomDocument', 'CompressDomDocument');
hooks::addHook('returnType', 'CompressPhtml', 'CompressPhtml');
