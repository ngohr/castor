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
class View {
	static $elements = array();

	static function Urlencode($uri) {
		$uri = rawurlencode($uri);

		// Feel Free to add other problematic or reserved characters
		$uri = str_replace('%2F', rawurlencode('%2F'), $uri);

		return $uri;
	}

	static function Urldecode($uri) {
		// Feel Free to add other problematic or reserved characters
		$uri = str_replace('%252F', '%2F', $uri);
		return rawurldecode($uri);
	}

	static function Add($name, $element, $escape = false) {
		if($escape) {
			if(is_array($element)) {
				foreach($element as $index => $value) {
					$element[$index] = htmlspecialchars($value);
				}
			} else {
				$element = htmlspecialchars($element);
			}
		}
		if(!array_key_exists($name, self::$elements)) {
			self::$elements[$name] = $element;

			return true;
		}

		return false;
	}

	static function Get($name = false, $index = false) {
		if(!$name) {
			return self::$elements;
		}

		if(array_key_exists($name, self::$elements)) {
			if(!$index) {
				return self::$elements[$name];
			} else {
				return self::$elements[$name][$index];
			}
		}

		return false;
	}
}