<?php
/*
	Stempora Web Framework
	Copyright (c) 2002-2020 Stempora. 
	All rights reserved.
		web:  www.stempora.com
		mail: support@stempora.com				
*/

namespace Stembase\Modules\Novosteer_Addon_Export\Core\Models;

if (!defined("STPBase")) {
	die("This file can't be accessed directly!");
}

class Base {

	/**
	* description
	*
	* @var type
	*
	* @access type
	*/
	var $module = null;
	

	/**
	* description
	*
	* @param
	*
	* @return
	*
	* @access
	*/
	public function setModule($module) {
		global $base , $_USER , $_SESS; 

		$this->module = $module;
		$this->plugins = &$this->module->plugins;
		$this->db = $this->module->db;

		return $this;
	}


	static public function newInstance() {
		return new static();
	}


	public 	function unsetFields(&$data , $keys) {
		global $base , $_USER , $_SESS , $_CONF , $_LANG_ID; 

		if (is_array($keys)) {
			foreach ($keys as $val) {
				if (isset($data[$val])) {
					unset($data[$val]);
				}				
			}
			
		}
		
	}
	
}

