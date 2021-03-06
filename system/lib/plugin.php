<?php
/**
 * Hanya - A rapid Website Engine
 *
 * @author Joël Gähwiler <joel.gaehwiler@bluewin.ch>
 * @copyright Joël Gähwiler 
 * @package Hanya
 **/

abstract class Plugin {
	
	/*
		[Events]
		after_initialize
		before_execution
		on_{$command}
	*/
	
	// Internal Action Delegation 
	protected static function _delegate($for,$action) {
		$method = "action_".$action;
		if(method_exists($for,$method)) {
			Hanya::call_static($for,$method);
		}
	}
	
	// Check for Admin Login
	protected static function _check_admin($privilege=null) {
		if(Memory::get("logged_in")) {
			if($privilege !== null) {
				if(Helper::user_has_privilege($privilege)) {
					return true;
				}
			} else {
				return true;
			}
		}
		HTTP::forbidden();
		die(Render::page("elements/errors/403.html"));
	}

	// Dispatch Event to all Plugins
	public static function dispatch($event,$options=null) {
		foreach(Registry::get("available.plugins") as $plugin) {
			$classname = ucfirst($plugin)."_Plugin";
			if(class_exists($classname)) {
				if(method_exists($classname,$event)) {
					Hanya::call_static($classname,$event,array($options));
				}
			} else {
				die("Hanya: Plugin '".$plugin."' defines no Class '".$classname."!");
			}
		}
	}

}