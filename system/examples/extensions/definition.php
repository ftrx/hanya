<?php
/**
 * Hanya - A rapid Website Engine
 *
 * @author Joël Gähwiler <joel.gaehwiler@bluewin.ch>
 * @copyright (c) 2011 Joël Gähwiler 
 * @package Hanya
 **/

// INSTRUCTION: rename file to example.php and move to user/definitions

class Example_Definition extends Definition {
	
	public static $settings = array(
		"orderable" => true,
	);
	
	public static $blueprint = array(
		"string" => array("as"=>"string"),
		"text" => array("as"=>"text"),
		"html" => array("as"=>"html"),
		"textile" => array("as"=>"textile"),
		"boolean" => array("as"=>"boolean"),
		"number" => array("as"=>"number"),
		"time" => array("as"=>"time"),
		"date" => array("as"=>"date"),
		"selection" => array("as"=>"selection","options"=>array("opt1"=>"Option 1","opt2"=>"Option 2","opt3"=>"Option 3")),
		"reference" => array("as"=>"reference","definition"=>"string","field"=>"value"),
		"file" => array("as"=>"file","folder"=>".","blank"=>true),
	);
	
}