<?php
/**
 * Hanya - A rapid Website Engine
 *
 * @author Joël Gähwiler <joel.gaehwiler@bluewin.ch>
 * @copyright Joël Gähwiler 
 * @package Hanya
 **/

class Include_Tag {
	
	public static function call($attributes) {
		return Render::file("elements/partials/".$attributes[0].".html");
	}
	
}