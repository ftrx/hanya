<?php
/**
 * Hanya - A rapid Website Engine
 *
 * @author Joël Gähwiler <joel.gaehwiler@bluewin.ch>
 * @copyright (c) 2011 Joël Gähwiler 
 * @package Hanya
 **/

class Render {
	
	// Render a Page with All Tags and Definitions
	public static function page($page) {
		
		//Check Existence
		if(file_exists($page)) {
			
			// Process Content
			$content = self::file($page);
			
		} else {
			
			// Send Header
			HTTP::not_found();
			
			// Check Error File
			$error_file = "elements/errors/404.html";
			if(file_exists($error_file)) {
				
				// Process Error File
				$content = self::file($error_file);
				
			} else {
				
				// Die with Error
				die("Error 404 - Page not found! (".$file.") - Define 'elements/404.html' to Render a Error Page");
				
			}
		}
		
		// Append to Content
		Registry::set("block.content",$content);
		
		// Get Template
		$template_file = "templates/".Registry::get("site.template").".html";
		
		// Process Template
		if(file_exists($template_file)) {
			return self::file($template_file);	
		} else {
			die("Hanya: Template '".$template_file."' does not exists!");
		}
	}
	
	// Process a File
	public static function file($file) {
		
		// Evaluate Fie
		$output = Disk::eval_file($file);
		
		// Process "request" Variables
		$output = self::_process_variables("request",Registry::get("request.variables"),$output);
		
		// Process Definitions
		$output = self::_process_definitions($output);
		
		// Process Tags
		$output = self::_process_tags($output);
		
		// End
		return $output;
	}
	
	// Process Variables
	private static function _process_variables($name,$vars,$output) {
		if($vars) {
			preg_match_all('!\$'.$name.'\((.+)\)!Us',$output,$matches);
			foreach($matches[0] as $i => $var) {
				$attributes = explode("|",$matches[1][$i]);
				$output = str_replace($matches[0][$i],$vars[$attributes[0]],$output);
			}
		}
		return $output;
	}
	
	// Process Definitions
	private static function _process_definitions($output) {
		while(preg_match('!\[(-*)def:([a-z]+)\((.*)\)\](.*)\[/\1def:\2\]!Us',$output,$match)) {
			$attributes = explode("|",$match[3]);
			$output = str_replace($match[0],self::_execute_definition($match[2],$attributes,$match[4]),$output);
		}
		return $output;
	}
	
	// Process Tags
	private static function _process_tags($output) {
		preg_match_all('!\{tag:(.+)\((.*)\)\}!Us',$output,$matches);
		foreach($matches[1] as $i => $tag) {
			$attributes = explode("|",$matches[2][$i]);
			$output = str_replace($matches[0][$i],self::_execute_tag($tag,$attributes),$output);
		}
		return $output;
	}
	
	// Execute a Tag
	private static function _execute_tag($tag,$attributes) {
		$classname = ucfirst($tag)."_Tag";
		if(method_exists($classname,"call")) {
			return $classname::call($attributes);
		} else {
			die("Hanya: Tag '".$tag."' is not defined!");
		}
	}
	
	// Execute Definition	
	private static function _execute_definition($definition,$attributes,$sub) {
		
		// Get Mode
		$mode = count($attributes);
		
		// Get ORM
		$items = ORM::for_table($definition);
		
		// Check for false Mode
		if($mode == 1 && $attributes[0] == "") {
			$attributes = array();
			$mode = 0;
		}
		
		// Add Conditions
		if($mode == 0) {
		} else if($mode == 1) {
			$items->where("id",$attributes[0]);
		} else if ($mode%2 == 0) {
			for($i=0; $i < $mode; $i = $i+2) {
				$items->where($attributes[$i],$attributes[$i+1]);
			}
		} else {
			die("Invalid Argument Count '".$mode."' for '".$definition."'");
		}
		
		// Set Output
		$output = "";
		
		// Process Items
		foreach($items->find_many() as $item) {
			
			// Process Variables
			$data = self::_process_variables($definition,$item->as_array(),$sub);
			
			// Check for Login
			if(Memory::get("edit_page")) {
				$output .= Helper::wrap_as_editable($data,$definition,$item->id);
			} else {
				$output .= $data;
			}
			
		}
		
		// End
		return $output;
	}
	
}