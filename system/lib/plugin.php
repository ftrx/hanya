<?php
/**
 * Hanya - A rapid Website Engine
 *
 * @author Joël Gähwiler <joel.gaehwiler@bluewin.ch>
 * @version 0.2
 * @copyright (c) 2011 Joël Gähwiler 
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
			$for::$method();
		}
	}

	// Dispatch Event to all Plugins
	public static function dispatch($event,$options=null) {
		foreach(Registry::get("loaded.plugins") as $plugin) {
			$classname = ucfirst($plugin)."Plugin";
			if(class_exists($classname)) {
				if(method_exists($classname,$event)) {
					$classname::$event($options);
				}
			} else {
				die("Hanya: Plugin '".$plugin."' defines no Class '".$classname."!");
			}
		}
	}

}