<?php

/*
 * Copyright (c) 2016, Nanno Gohr 
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
 
/*
 * Class userDB is an Operator Plugin
 * 
 * - Provides secure session initalization for differnet server coniguration with cookies only.
 * 
 * @todos
 * 
 * #001 Data Models shouldt be a difently clean collection of abstract classes and it must be an experimental features for years of testing, relates module.php #001
 * 
 * @version
 *
 * 0.9 / 02.08.2016
 *
 * @author
 *
 * Nanno Gohr 
 * 
 */

define("RESET_CONFIG", true);

abstract class sessionManagement extends Operator {
	public $rights;
	private $token = false;
	private $hashAlgorythm = 'md5';
	private $constants;
	private $lastSessionId;

	public $fixRemoteAddr = false;
	public $fixUserAgent = false;
	public $maxLivetime = false;
	public $forwardSecrecy = false;
	public $sessionTimeoutSec = 1440;

	public $helpText = "The Plugin sessionManagement, opens a session on init and respects common security behaivors! Typical constants of the PHP installation will overwriten on init, set constant RESET_CONFIG to false to skip that.";
	public $helpParameters = array(
		'constants' => array(
			'fixremoteaddr' => "If 'yes', the session will destroyed, if the REMOTE_ADDR was changed. Mobile connections couldt ran into it!",
			'fixuseragent' => "If 'yes', the session will destroyed, if the HTTP_USER_AGENT header was changed. Mobile browsers changes his header with functions like: show desktop version and couldt ran into it!",
			'maxlifetime' => "If 'yes', a sessionId will regenerated after session.gc_maxlifetime in seconds",
			'forwardsecrecy' => "If 'yes', the available hash algorythms of the php installation will searched through the strongest sha encryption to use for the session. Not available on PHP >= 7.1"
		)
	);

	public $helpMethods = array(
		'session_started' => "check if a session was started, parameters: none",
		'session_save_start' => "recreates a sessions with known constants set, parameters: none",
		'session_destroy' => "unset session variables, session cookies and destroyes the session on server side, parameters: none"
	);

	// abstract function onError();

	public function init() {
		if(ini_get('session.gc_maxlifetime') > 0)
			$this->sessionTimeoutSec = ini_get('session.gc_maxlifetime');
		$this->constants = (object) $this->getElements('constants');

		if($this->constants) {
			if(isset($this->constants->fixremoteaddr) && $this->constants->fixremoteaddr == 'yes')
				$this->fixRemoteAddr = true;
			if(isset($this->constants->fixuseragent) && $this->constants->fixuseragent == 'yes')
				$this->fixUserAgent = true;
			if(isset($this->constants->maxlifetime) && $this->constants->maxlifetime == 'yes')
				$this->maxLivetime = true;
			if(isset($this->constants->forwardsecrecy) && $this->constants->forwardsecrecy == 'yes')
				$this->forwardSecrecy = true;
		}

		// Set typical session configurations...
		if(!$this->session_started()) {
			ini_set('session.use_only_cookies', 1);
			ini_set('session.cookie_lifetime', 0);
			ini_set('session.use_cookies', 'On');
			ini_set('session.use_only_cookies', 'On');
			ini_set('session.use_trans_sid', 'Off');
			ini_set('session.use_strict_mode', 'On');
			ini_set('session.cookie_httponly', 'On');
		}

		if(isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1)) {
			ini_set('session.cookie_secure', 'On');
		}

		// Find a strong hash algorythm available
		if(version_compare(phpversion(), '7.1', '<')) {
			if($this->forwardSecrecy) {
				if(in_array('sha512', hash_algos()))
					$this->hashAlgorythm = 'sha512';
				elseif(in_array('sha384', hash_algos()))
					$this->hashAlgorythm = 'sha384';
				elseif(in_array('sha256', hash_algos()))
					$this->hashAlgorythm = 'sha256';
			}
		}

		// Start session...
		if(!$this->session_started()) {
			$this->session_save_start();
			$this->lastSessionId = session_id();
		}

		// Check remoteaddr and useragent bindings if the session was started with session_save_start()
		if(array_key_exists('REMOTE_ADDR', $_SESSION) && $this->fixRemoteAddr) {
			if($_SESSION['REMOTE_ADDR'] != $_SERVER['REMOTE_ADDR']) {
				$this->setError("#001 Security violation, remote address changed due session lifetime!");
				$this->session_destroy();
		
				return false;
			}
		}

		if(array_key_exists('HTTP_USER_AGENT', $_SESSION) && $this->fixUserAgent) {
			if($_SESSION['HTTP_USER_AGENT'] != $_SERVER['HTTP_USER_AGENT']) {
				$this->setError("#002 Security violation, user agent changed due session lifetime!");
				$this->session_destroy();
		
				return false;
			}
		}

		return true;
	}

	public function session_started() {
		return function_exists('session_status') ? (PHP_SESSION_ACTIVE == session_status()) : (!empty(session_id()));
	}

	public function session_save_start($secretKey = false) {
		if($this->forwardSecrecy && version_compare(phpversion(), '7.1', '<'))
			ini_set('session.hash_function', $this->hashAlgorythm);

		if(!session_start()) {
			trigger_error("Fatal Error: Couldt not start Session");
		}

		if(array_key_exists('REMOTE_ADDR', $_SERVER) && $this->fixRemoteAddr) {
			if(!array_key_exists('REMOTE_ADDR', $_SESSION)) {
				// Bind Session to REMOTE_ADDR
				$_SESSION['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'];
			}
		}

		if(array_key_exists('HTTP_USER_AGENT', $_SERVER) && $this->fixUserAgent) {
			if(!array_key_exists('HTTP_USER_AGENT', $_SESSION)) {
				// Bind Session to HTTP_USER_AGENT
				$_SESSION['HTTP_USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'];
			}
		}

		if($this->maxLivetime && (!array_key_exists('HTTP_X_REQUESTED_WITH', $_SERVER) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest')) {
			if(!array_key_exists('SESSIONID_MAXLIFETIME', $_SESSION)) {
				$_SESSION['SESSIONID_MAXLIFETIME'] = time() + $this->sessionTimeoutSec;
			} else {
				// Regernate id after $this->sessionTimeoutSec seconds
				if($_SESSION['SESSIONID_MAXLIFETIME'] < time()) {
					session_regenerate_id(true);
					if(method_exists($this, "onRegenerate"))
						call_user_func(array($this, "onRegenerate") , session_id());
				}
				$_SESSION['SESSIONID_MAXLIFETIME'] = time() + $this->sessionTimeoutSec;
			}
		}

		return session_id();
	}

	public function session_destroy() {
		// Löschen aller Session-Variablen.
		$_SESSION = array();
		session_unset();

		if(ini_get("session.use_cookies")) {
			$params = session_get_cookie_params();
			setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
		}

		// Löschen der Session.
		session_destroy();

		return true;
	}
}
