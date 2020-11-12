<?php
/*
	Stempora Web Framework
	Copyright (c) 2002-2019 Stempora. 
	All rights reserved.
		web:  www.stempora.com
		mail: support@stempora.com				
*/



namespace Stembase\Modules\Novosteer_Addon_Import\Core\Models;

/**
* description
*
* @library	
* @author	
* @since	
*/
class Map extends Base{
	
	/**
	* description
	*
	* @var type
	*
	* @access type
	*/
	var $feed = null;
	
	

	/**
	* description
	*
	* @var type
	*
	* @access type
	*/
	var $db = null;

	/**
	* description
	*
	* @var type
	*
	* @access type
	*/
	var $table = null;
	
	/**
	* description
	*
	* @param
	*
	* @return
	*
	* @access
	*/
	function __construct() {
		global $base , $_USER , $_SESS , $site; 

		$this->db = $site->db;
	}
	/**
	* description
	*
	* @param
	*
	* @return
	*
	* @access
	*/
	public function setFeed($feed) {
		global $base , $_USER , $_SESS; 

		$this->feed = $feed;

		$this->load();

		return $this;
	}

	function load() {
		global $site;

		$this->table = null;


		$data = $this->db->QFetchRowArray(
			"SELECT * FROM %s where find_in_set(%d , importer_id) OR importer_all = 1",
			[
				$this->module->tables['plugin:novosteer_addon_importer_map_pre'],
				$this->feed
			]
		);

		if (!is_array($data)) {
			return $this;
		}

		foreach ($data as $key => $val) {
			$original = explode("\n" , $val["map_source"]);
			$final = explode("\n" , $val["map_destination"]);

			$data = [
				"from"	=> [],
				"to"	=> [],
			];

			$found = false;

			foreach ($original as $k => $v) {

				if (trim($v) != "" ) {
					$_tmp = explode("|" , trim($v));
					$data["from"][$_tmp[0]] = $_tmp[1];
					$found = true;
				}
				
			}

			foreach ($final as $k => $v) {
				if (trim($v) != "" ) {
					$_tmp = explode("|" , trim($v));
					$data["to"][$_tmp[0]] = $_tmp[1];
					$found = true;
				}
				
			}

			if ($found) {
				$this->table[] = $data;			
			}
			
		}
				
		return $this;
	}


	/**
	* description
	*
	* @param
	*
	* @return
	*
	* @access
	*/
	function process(&$item) {
		global $base , $_USER , $_SESS; 

		if (!is_array($item)) {
			return $this;
		}

		if (!(is_array($this->table) && count($this->table))) {
			return $this;
		}

		foreach ($this->table as $map) {

			$int = array_intersect($item , $map["from"]);
			$diff = array_diff($map["from"] , $int);

			if (!count($diff) ) {
				
				if (is_array($map["to"])) {
					$item = array_merge(
						$item , 
						$map["to"]
					);
				}			
			}			
		}		
	}
	

	/**
	* description
	*
	* @param
	*
	* @return
	*
	* @access
	*/
	function addRule($rule) {
		global $base , $_USER , $_SESS; 

		$this->table[] = $rule;
	}
	
	
}



