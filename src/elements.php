<?php

class elements {
	private static $error = "";

	public static function setError($value) {
		self::$error = $value;
	}

	public static function getError() {
		return self::$error;
	}

	public static function createDomRepresentation($name, $param, &$domDocumentObj) {
		if(!is_array($param)) {
			self::setError('Failed to process, invalid parameter for method createDomRepresentation, array expected!');
				
			return false;
		}

		$rootNode = $domDocumentObj->createElement($name);
		foreach($param as $index => $value) {
			if(is_int($index)) {
				$self::setError('Failed to process, invalid numeric index name in method createDomRepresentation!');
	
				return false;
			}
	
			if(is_array($value)) {
				foreach($value as $subIndex => $subValue) {
					if(is_array($subValue)) {
						if(is_int($subIndex)) {
							$node = $domDocumentObj->createElement($index);
							self::createElementRepresentation($subValue, $domDocumentObj, $node);
						} else {
							$node = $domDocumentObj->createElement($subIndex);
							self::createElementRepresentation($subValue, $domDocumentObj, $node);
						}
					} else {
						$node = $domDocumentObj->createElement($index);

						self::createElementRepresentation($value, $domDocumentObj, $node);
						$rootNode->appendChild($node);
						
						break;
					}
	
					$rootNode->appendChild($node);
				}
			} else {
				$node = $domDocumentObj->createElement($index);
				$text = $domDocumentObj->createTextNode($value);
				$node->appendChild($text);
				$rootNode->appendChild($node);
			}
		}

		$domDocumentObj->appendChild($rootNode);
	
		return true;
	}
	
	public static function createElementRepresentation($param, &$domDocumentObj, &$domElement) {
		if(!is_array($param)) {
			self::setError('Failed to process, invalid parameter for method createElementRepresentation, array expected!');
				
			return false;
		}

		foreach($param as $index => $value) {
			if(is_int($index)) {
				self::setError('Failed to process, invalid numeric index name in method createElementRepresentation!');
	
				return false;
			}
	
			if(is_array($value)) {
				foreach($value as $subIndex => $subValue) {
					if(is_array($subValue)) {
						if(is_int($subIndex)) {
							$node = $domDocumentObj->createElement($index);
								
							if(!self::createElementRepresentation($subValue, $domDocumentObj, $node)) {
								self::setError('Cannot createElementRepresentation from numeric subIndex');
							}
						} else {
							$node = $domDocumentObj->createElement($subIndex);
							if(is_array($subValue)) {
								$midNode = $domDocumentObj->createElement($subIndex);
								if(!self::createElementRepresentation($subValue, $domDocumentObj, $midNode)) {
									self::setError('Cannot createElementRepresentation from numeric subIndex');
								}
	
								$node->appendChild($midNode);
							} else {
								$midNode = $domDocumentObj->createElement($subIndex);
								$text = $domDocumentObj->createTextNode($subValue);
								$midNode->appendChild($text);
								$node->appendChild($midNode);
							}
						}
					} else {
						$node = $domDocumentObj->createElement($index);
						$text = $domDocumentObj->createTextNode($subValue);
						$node->appendChild($text);
					}
	
					$domElement->appendChild($node);
				}
			} else {
				$node = $domDocumentObj->createElement($index);
				$text = $domDocumentObj->createTextNode($value);
				$node->appendChild($text);
				$domElement->appendChild($node);
			}
		}

		return true;
	}
}