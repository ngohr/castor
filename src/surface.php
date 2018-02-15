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
 * class Surface, a castor Module abstraction with a method load, called from module::get()
 *
 * - A Surface is a primitive data model, that copy elements, constants and pagenames from $instance to local data procced by $this->init() in final constructs.
 * - A Surface instead of an Operator, do not valid external data.
 * - A Surface must implement a method to connect a node, especially sockets or object instances
 * - A method close, shouldt shutdown the connections, a pointer for factory classes shouldt be unuseable after calling close...
 * - A method reset, shouldt be available to reset a surface, respective a connection to null and start it again...
 *
 * @todos
 *
 * #001 Data Models shouldt be a difently clean collection of abstract classes and it must be an experimental features for years of testing, relates module.php #001
 * a
 * @version
 *
 * 0.4 / 27.09.2013
 *
 * @author
 *
 * Nanno Gohr
 *
 */
abstract class Surface extends Application {
	public $error = false;

	abstract function init();
	abstract function &connect();
	abstract function close();
	abstract function reset();

	public function setError($value) {
		$this->error = $value;

		return true;
	}

	public function getError() {
		return $this->error;
	}

	public function load($instance = false) {
		if($instance) {
			$elements = $instance->getElements();
			if($elements) {
				foreach($elements as $index => $value) {
					$this->addElement($index, $value);
				}
			}
			$pageName = $instance->getPagename();
			if($pageName)
				$this->setPagename($pageName);
			$actionName = $instance->getActionname();
			if($actionName)
				$this->setActionname($actionName);
		}

		if(!$this->init()) {
			throw new Exception($this->getError());

			return false;
		}

		return $this;
	}
}