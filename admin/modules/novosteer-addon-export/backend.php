<?php
/*
	Stempora Web Framework
	Copyright (c) 2002-2018 Stempora. 
	All rights reserved.
		web:  www.stempora.com
		mail: support@stempora.com				
*/

/**
* description
*
* @library	
* @author	
* @since	
*/
class CNovosteerAddonExportBackend extends CPlugin{

	var $tplvars; 

	var $_field = "_locked_fields";


	function __construct() {
		$this->name = "novosteer-addon-export";
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
	function transformMap($feed = null, $fid , $value) {
		global $base , $_USER , $_SESS; 

		if ($feed === null) {
			return $value;
		}

		$data = is_array($value) ? $value["name"] : $value;

		//check if the exists a mapping rule
		$map = $this->db->QFetchArray(
			"SELECT * FROM %s WHERE find_in_set(%d , export_id) AND map_original LIKE '%s' AND attribute_id = %d",
			[
				$this->tables["plugin:products_addon_export_map"],
				$feed,
				trim($data),
				$fid
			]
		);

		if (is_Array($map)) {

			if (is_array($value)) {
				$value["name"] = $map["map_final"];
			} else {
				$value = $map["map_final"];
			}
		}

		return $value;
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
	function recordHistory($feed , $path , $file) {
		global $base , $_USER , $_SESS; 

		$id = $this->db->QueryInsert(
			$this->tables["plugin:products_addon_export_history"],
			[
				"feed_id"			=> $feed["feed_id"],
				"file_date"			=> time(),
				"file_file"			=> "1",
				"file_file_file"	=> $file
			]
		);

		CFile::Copy(
			$path , 
			"upload/products/export/history/" . $id . ".file"
		);

		return true;

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
	function getExportById($id) {
		global $_LANG_ID;

		$data = $this->db->QFetchArray(
			"SELECT * FROM 	%s  as feeds
			INNER JOIN 
				%s AS dealerships
				ON
					feeds.dealership_id = dealerships.dealership_id
			WHERE				
				feed_id=%d
			",
			[
				$this->tables['plugin:novosteer_addon_export_feeds'],
				$this->tables['plugin:novosteer_dealerships'],
				$id
			]			
		);

		if ($data["feed_settings"]) {
			$data["settings"] = json_decode($data["feed_settings"] , true );
		}

		return $data;
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
	function getExportObject($feed = null) {
		global $base , $_USER , $_SESS; 

		$this->__init();

		if (!is_array($feed)) {			
			$feed = $this->getExportById($feed);				
		}

		if (!is_object($this->exports[$feed["feed_id"]])) {

			if (class_exists($feed["feed_class"],true)) {

				$this->exports[$feed["feed_id"]] = new $feed["feed_class"]($feed);
				$this->exports[$feed["feed_id"]]->setModule($this);
				$this->exports[$feed["feed_id"]]->setInfo($feed);

				if ($this->module->search) {
					$this->exports[$feed["feed_id"]]->setSearch($this->module->search , true);
				}
				
			}
		}

		return $this->exports[$feed["feed_id"]];		
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
	function getAllExports() {
		global $base , $_USER , $_SESS; 

		return $this->db->QFetchRowArray("
			SELECT * FROM 
				%s
			LEFT JOIN
				%s
				ON 
					feed_set = set_id 				
			",
			[
				$this->tables['plugin:novosteer_addon_export_feeds'],
				$this->tables['plugin:products_field_sets']
			]
		);
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
	function getExportsByDealershipID($id) {
		global $base , $_USER , $_SESS , $_CONF , $_LANG_ID; 

		$jobs = $this->db->QFetchRowArray(
			"SELECT * FROM %s WHERE dealership_id = %d AND feed_status = 1 ORDER BY feed_order ASC",
			[
				$this->tables["plugin:novosteer_addon_export_feeds"],
				$id
			]
		);

		return $jobs;
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
	function getExportsSets() {
		global $base , $_USER , $_SESS , $_CONF , $_LANG_ID; 

		return $this->db->Linear($this->db->QFetchRowArray(
			"SELECT feed_set FROM %s",
			[
				$this->tables['plugin:novosteer_addon_export_feeds']
			]
		));
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
	function getTableFields() {
		global $base , $_USER , $_SESS , $_CONF , $_LANG_ID; 

		$fields = $this->db->getTableFields($this->tables["plugin:novosteer_vehicles_export"])["fields"];

		return $fields;
	}

	
}
