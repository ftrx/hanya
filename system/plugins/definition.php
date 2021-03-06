<?php
/**
 * Hanya - A rapid Website Engine
 *
 * @author Joël Gähwiler <joel.gaehwiler@bluewin.ch>
 * @copyright Joël Gähwiler 
 * @package Hanya
 **/

class Definition_Plugin extends Plugin {
	
	// Send Back HTML to Display Form
	public static function on_definition_manager() {
		
		// Check Admin
		self::_check_admin();
		
		// Get Data
		$definition = Request::get("definition");
		$class = ucfirst($definition)."_Definition";
		$obj = new $class();
		$id = Request::get("id","int");
		$entry = ORM::for_table($definition);
		
		// Load Entry if isnt is a update or create
		$entry = ($id>0)?$entry->find_one($id):$obj->create($entry->create(),Request::get("argument","string"));
		
		// Manager
		$body = HTML::header(1,I18n::_("definition.".$definition.".edit_entry"));
		$body .= Helper::print_errors();
		
		// Open Body
		$body .= HTML::div_open(null,"content");
		$body .= HTML::form_open(Registry::get("request.referer")."?command=definition_update","post",array("enctype"=>"multipart/form-data"));
		$body .= HTML::hidden("definition",$definition);
		$body .= HTML::hidden("id",$id);
		
		// Print Form Elements
		foreach($obj->blueprint as $field => $config) {
			
			// Merge Config with defaults
			$config = array_merge($obj->default_config,$config);
			
			// Get Field Name
			$name = $definition."[".$field."]";
			
			// Check for Visibility
			if(!$config["hidden"]) {
				
				// Open Row
				$body .= HTML::div_open(null,"row");
				
				// Get Label and Name
				$label = ($config["label"])?I18n::_("definition.".$definition.".field_".$field):null;
				
				// Switch Field Type
				switch($config["as"]) {
					
					// Boolean
					case "boolean" : {
						$body .= HTML::label($name,$label);
						$body .= HTML::div_open(null,"radiogroup");
						$body .= HTML::radio($name,1,I18n::_("definition.".$definition.".field_".$field."_true"),($entry->$field==1)?array("checked"=>"checked"):array());
						$body .= HTML::radio($name,0,I18n::_("definition.".$definition.".field_".$field."_false"),($entry->$field==0)?array("checked"=>"checked"):array());
						$body .= HTML::div_close();
						break;
					}
					
					// Text Inputs
					case "number":
					case "string": $body .= HTML::text($name,$label,$entry->$field).HTML::br(); break;
					
					// Textareas
					case "html": $body .= HTML::textarea($name,$label,$entry->$field,array("class"=>"hanya-editor-html")); break;
					case "textile": $body .= HTML::textarea($name,$label,$entry->$field,array("class"=>"hanya-editor-textile")); break;
					case "markdown": $body .= HTML::textarea($name,$label,$entry->$field,array("class"=>"hanya-editor-markdown")); break;
					case "text": $body .= HTML::textarea($name,$label,$entry->$field).HTML::br(); break;
					
					// Special
					case "time": $body .= HTML::text($name,$label,$entry->$field,array("class"=>"hanya-timepicker")).HTML::br(); break;
					case "date": $body .= HTML::text($name,$label,$entry->$field,array("class"=>"hanya-datepicker")).HTML::br(); break;
					case "selection": $body .= HTML::select($name,$label,HTML::options($config["options"],$entry->$field)).HTML::br(); break;
					
					// Reference Select
					case "reference": {
						$data = array();
						foreach(ORM::for_table($config["definition"])->find_many() as $item) {
							$data[$item->id] = $item->$config["field"];
						}
						$body .= HTML::select($name,$label,HTML::options($data,$entry->$field)).HTML::br();
						break;
					}
					
					// File Select
					case "file": {
						$data = array();
						if($config["blank"]) {
							$data = array(""=>"---");
						}
						$content = Disk::read_directory("uploads/".$config["folder"]);
						$files = Disk::get_all_files($content);
						$directories = array("/"=>"/");
						foreach(Disk::get_all_directories($content) as $dir) {
							$directories["/".$dir] = "/".$dir;
						}
						foreach($files as $file) {
							$data[$file] = $file;
						}
						if($config["upload"]) {
							$upload = HTML::br().I18n::_("system.definition.upload_file").HTML::file($definition."[".$field."_upload]");
							$upload .= I18n::_("system.definition.upload_file_to").HTML::select($definition."[".$field."_upload_dir]",null,HTML::options($directories));
						} else {
							$upload = "";
						}
						$body .= HTML::label($name,$label).I18n::_("system.definition.select_file");
						$body .= HTML::select($name,null,HTML::options($data,$entry->$field)).$upload.HTML::br();
						break;
					}
				}
			} else {
				
				// Open Row
				$body .= HTML::div_open(null,"hidden-row");
				
				// Render Hidden Field
				$body .= HTML::hidden($name,$entry->$field);
			}
			
			// Close Row
			$body .= HTML::div_close();
		}
		
		// Close Manager
		$body .= HTML::submit(I18n::_("system.definition.save"));
		$body .= HTML::form_close();
		$body .= HTML::div_close();
		
		// End
		echo Render::file("system/views/definition/manager.html",array("body"=>$body)); exit;
	}
	
	// Perform a Creation or Update
	public static function on_definition_update() {
		
		// Check Admin
		self::_check_admin();
		
		// Get Data
		$definition = Request::post("definition");
		$id = Request::post("id","int");
		$data = Request::post($definition,"array");
		$class = $class= ucfirst($definition)."_Definition";
		$obj = new $class();
		$entry = ORM::for_table($definition);
		
		// Check for new Entry
		if($id > 0) {
			$is_new = false;
			$entry = $entry->find_one($id);
		} else {
			$is_new = true;
			$entry = $entry->create();
		}
		
		// Append Data
		foreach($data as $field => $value) {
			if(array_key_exists($field,$obj->blueprint)) {
				$entry->$field = stripslashes($value);
			}
		}
		
		// Check For Special Fields
		foreach($obj->blueprint as $field => $config) {
			switch($config["as"]) {
				case "file" : {
					$target_dir = Registry::get("system.path")."uploads/".$config["folder"].$data[$field."_upload_dir"];
					if($config["upload"] && $_FILES[$definition]["size"][$field."_upload"] > 0 && Disk::has_directory($target_dir)) {
						$filename = $_FILES[$definition]["name"][$field."_upload"];
						$tmpfile = $_FILES[$definition]["tmp_name"][$field."_upload"];
						$newfile = $target_dir."/".$filename;
						Disk::copy($tmpfile,$newfile);
						$entry->$field = $filename;
						break;
					}
					unset($data[$field."_upload"]);
				}
			}
		}
		
		// Do Ordering
		if($obj->orderable && $is_new) {
			$last_entry = ORM::for_table($definition)->select("ordering")->order_by_desc("ordering");
			foreach($obj->groups as $group) {
				if($entry->$group) {
					$last_entry = $last_entry->where($group,$entry->$group);
				}
			}
			$last_entry = $last_entry->limit(1)->find_one();
			if($last_entry) {
				$entry->ordering = $last_entry->ordering+1;
			} else {
				$entry->ordering = 1;
			}
		}
		
		// Validate and Save
		if(self::_validate($entry,$obj->blueprint,$obj->default_config)) {
			$entry->save();
		} else {
			die("Validation failed");
		}
		
		// Dispatch before_update Event
		$entry = $obj->before_update($entry);
		
		// Redirect
		echo Render::file("system/views/shared/close.html"); exit;
	}
	
	// Delete an Entry
	public static function on_definition_remove() {
		
		// Check Admin
		self::_check_admin();

		// Get Data
		$definition = Request::post("definition");
		$class = ucfirst($definition)."_Definition";
		$obj = new $class();
		$id = Request::post("id","int");
		$entry = ORM::for_table($definition)->find_one($id);
		
		// Check Ordering
		if($obj->orderable) {
			
			// Get Affected Rows
			$rows = ORM::for_table($definition)->where_gt("ordering",$entry->ordering);
			foreach($obj->groups as $group) {
				if($entry->$group) {
					$rows = $rows->where($group,$entry->$group);
				}
			}
			
			// Order Up
			foreach($rows->find_many() as $row) {
				$row->ordering--;
				$row->save();
			}
			
		}
		
		// Check Entry
		if($entry->id) {
			$entry->delete();
			echo "ok";
		} else {
			echo "Entry not found!";
		}
		
		// End
		exit;
	}
	
	// Delete an Entry
	public static function on_definition_orderup() {
		
		// Check Admin
		self::_check_admin();

		// Get Data
		$definition = Request::post("definition");
		$class = ucfirst($definition)."_Definition";
		$obj = new $class();
		$id = Request::post("id","int");
		$entry = ORM::for_table($definition)->find_one($id);
		
		// Check Entry
		if($entry && $obj->orderable) {
			if($entry->ordering > 1) {
				// Order Down Element on Position
				$upper = ORM::for_table($definition)->where("ordering",$entry->ordering-1);
				foreach($obj->groups as $group) {
					if($entry->$group) {
						$upper = $upper->where($group,$entry->$group);
					}
				}
				$upper = $upper->find_one();
				$upper->ordering = $entry->ordering;
				$upper->save();
				// Order Up Element
				$entry->ordering = $entry->ordering-1;
				$entry->save();
			}
			echo "ok";
		} else {
			echo "Entry not found!";
		}
		
		// End
		exit;
	}
	
	// Delete an Entry
	public static function on_definition_orderdown() {
		
		// Check Admin
		self::_check_admin();

		// Get Data
		$definition = Request::post("definition");
		$class = ucfirst($definition)."_Definition";
		$obj = new $class();
		$id = Request::post("id","int");
		$entry = ORM::for_table($definition)->find_one($id);
		
		// Check Entry
		if($entry && $obj->orderable) {
			if($entry->ordering < ORM::for_table($definition)->select("ordering")->order_by_desc("ordering")->find_one()->ordering) {
				// Order Down Element on Position
				$downer = ORM::for_table($definition)->where("ordering",$entry->ordering+1);
				foreach($obj->groups as $group) {
					if($entry->$group) {
						$downer = $downer->where($group,$entry->$group);
					}
				}
				$downer = $downer->find_one();
				$downer->ordering = $entry->ordering;
				$downer->save();
				// Order Up Element
				$entry->ordering = $entry->ordering+1;
				$entry->save();
			}
			echo "ok";
		} else {
			echo "Entry not found!";
		}
		
		// End
		exit;
	}
	
	// Validate Data
	private static function _validate($entry,$blueprint,$default_config) {
		
		// Load Validation for each field
		foreach($blueprint as $field => $config) {
			
			// Get Config
			$config = array_merge($default_config,$config);
			
			// Switch Validation Tyoe
			foreach($config["validation"] as $type => $parameter) {
				switch($type) {
					
					// Not Empty
					case "not_empty": if(strlen($entry->$field) < 1) { return false; } break;
					
					// Match Rege
					case "match": if(!preg_match($parameter,$entry->$field)) { return false; } break;
					
				}
			}
		}
		
		// End
		return true;
	}
	
}