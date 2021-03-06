<?php
/**
 * Hanya - A rapid Website Engine
 *
 * @author Joël Gähwiler <joel.gaehwiler@bluewin.ch>
 * @copyright Joël Gähwiler 
 * @package Hanya
 **/

class Memory {
	
	// Initialize Memory System
	public static function initialize() {
		session_start();
	}
	
	// Get Value by Key
	public static function get($key) {
		if(array_key_exists($key,$_SESSION)) {
			return $_SESSION[$key];
		} else {
			return null;
		}
	}
	
	// Set Value by Key
	public static function set($key,$value) {
		$_SESSION[$key] = $value;
	}
	
	// Check Key
	public static function has($key) {
		return array_key_exists($key,$_SESSION);
	}
	
	// Get All Values
	public static function all() {
		return $_SESSION;
	}

	// Remove a Value
	public static function remove($key) {
		unset($_SESSION[$key]);
	}
	
	// Raise Error
	public static function raise($error) {
		self::set("error",$error);
	}
	
	// Get Errors
	public static function errors() {
		$error = self::get("error");
		self::set("error",null);
		return $error;
	}
	
}