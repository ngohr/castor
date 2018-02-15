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

								return false;
							}
						} else {
							$node = $domDocumentObj->createElement($subIndex);
							if(is_array($subValue)) {
								$midNode = $domDocumentObj->createElement($subIndex);
								if(!self::createElementRepresentation($subValue, $domDocumentObj, $midNode)) {
									self::setError('Cannot createElementRepresentation from numeric subIndex');

									return false;
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