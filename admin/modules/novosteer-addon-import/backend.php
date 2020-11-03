<?php
/*
	Stempora Web Framework
	Copyright (c) 2002-2018 Stempora. 
	All rights reserved.
		web:  www.stempora.com
		mail: support@stempora.com				
*/

define("NOVOSTEER_VEHICLE_STATUS_PENDING" ,1);
define("NOVOSTEER_VEHICLE_STATUS_PUBLISHED" ,2);
define("NOVOSTEER_VEHICLE_STATUS_DISABLED" ,3);
define("NOVOSTEER_VEHICLE_STATUS_CALCULATOR" ,4);

/**
* description
*
* @library	
* @author	
* @since	
*/
class CNovosteerAddonImportBackend extends CPlugin{

	var $tplvars; 

	var $_field = "_locked_fields";


	function __construct() {
		$this->name = "novosteer-addon-import";
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
			"SELECT * FROM %s WHERE find_in_set(%d , importer_id) AND map_original LIKE '%s' AND attribute_id = %d",
			[
				$this->tables["plugin:products_addon_importer_map"],
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
	function dropTables($tables) {
		foreach ($tables as $key => $val) {
//			echo "Dopping table {$val} ...";
			$this->db->Query("SET foreign_key_checks = 0;");
			$this->db->Query("TRUNCATE TABLE {$val}");
			$this->db->Query("SET foreign_key_checks = 1;");
//			echo "done\n";
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
	function getProductsNotInList($feed , $skus) {

		$cond = array();


		$this->search->appendCond(
			"product.item_sku NOT IN ('" . implode("','" , $skus) . "')"
		);


		//add the conditions 
		$this->search->appendCond(
			$this->search->getIDSWithFieldValue(
				$this->module->getFieldByCode(
					$this->plugins["products-addon-attributes"]->_field
				)["field_id"] , 
				$feed["feed_set"] 
			)
		);


		$this->search->setLimit(9999999);

		$products = $this->search->getResults();
		
		return $products;
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
			$this->tables["plugin:products_addon_importer_history"],
			[
				"feed_id"			=> $feed["feed_id"],
				"file_date"			=> time(),
				"file_file"			=> "1",
				"file_file_file"	=> $file
			]
		);

		CFile::Copy(
			$path , 
			"upload/products/import/history/" . $id . ".file"
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
	function getImporterById($id) {
		global $_LANG_ID;

		$data = $this->db->QFetchArray(
			"SELECT * FROM 			
				%s  as feed
			INNER JOIN 
				%s as dealership
			ON
				feed.dealership_id = dealership.dealership_id			
			WHERE				
				feed_id=%d
			",
			[
				$this->tables['plugin:novosteer_addon_importer_feeds'],
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
	function getImporterByCode($id) {
		global $_LANG_ID;

		$data = $this->db->QFetchArray(
			"SELECT * FROM 			
				%s  as feed
			INNER JOIN 
				%s as dealership
			ON
				feed.dealership_id = dealership.dealership_id			
			WHERE				
				feed_code LIKE '%s'
			",
			[
				$this->tables['plugin:novosteer_addon_importer_feeds'],
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
	function getImporterObject($feed = null) {
		global $base , $_USER , $_SESS; 

		$this->__init();

		if (!is_array($feed)) {			
			$feed = $this->getImporterById($feed);				
		}

		if (!is_object($this->importers[$feed["feed_id"]])) {

			if (class_exists($feed["feed_class"],true)) {

				$this->importers[$feed["feed_id"]] = new $feed["feed_class"]($feed);
				$this->importers[$feed["feed_id"]]->setModule($this);
				$this->importers[$feed["feed_id"]]->setInfo($feed);
			}
		}

		return $this->importers[$feed["feed_id"]];		
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
	function getAllImporters() {
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
				$this->tables['plugin:novosteer_addon_importer_feeds'],
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
	function getImportersByDealershipID($id) {
		global $base , $_USER , $_SESS , $_CONF , $_LANG_ID; 

		$jobs = $this->db->QFetchRowArray(
			"SELECT * 
			FROM %s as feeds
				INNER JOIN 
					%s as dealerships
				ON 
					feeds.dealership_id = dealerships.dealership_id 

			WHERE feeds.dealership_id = %d AND feed_status = 1 ORDER BY feed_order ASC",
			[
				$this->tables["plugin:novosteer_addon_importer_feeds"],
				$this->tables["plugin:novosteer_dealerships"],
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
	function getProductLockFields($pid) {
		global $base , $_USER , $_SESS; 


		return $this->db->QFetchArray(
			"SELECT * FROM %s WHERE product_id = %d",
			[
				$this->tables['plugin:products_addon_importer_locks_products'],
				$pid
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
	function updateProductLockFields($pid , $value) {
		global $base , $_USER , $_SESS; 


		$old = $this->getProductLockFields($pid);

		if (is_array($old)) {

			if (!$value) {
				$this->db->Query(
					"DELETE FROM %s WHERE product_id = %d",
					[
						$this->tables['plugin:products_addon_importer_locks_products'],
						$pid
					]
				);
			} else {			
				$old["lock_id"] = $value;
				$old["lock_last_update"] = time();

				$this->db->QueryUpdate(
					$this->tables['plugin:products_addon_importer_locks_products'],
					$old, 
					$this->db->Statement(
						"product_id = %d",
						[ $pid ]
					)
				);
			}
		} else {
			$this->db->QueryInsert(
				$this->tables['plugin:products_addon_importer_locks_products'],
				[
					"product_id"	=> $pid , 
					"lock_date"		=> time(),
					"lock_id"	=> $value
				]
			);
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
	function getImportersSets() {
		global $base , $_USER , $_SESS , $_CONF , $_LANG_ID; 

		return $this->db->Linear($this->db->QFetchRowArray(
			"SELECT feed_set FROM %s",
			[
				$this->tables['plugin:novosteer_addon_importer_feeds']
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

		$fields = $this->db->getTableFields($this->tables["plugin:novosteer_vehicles_import"])["fields"];

		return $fields;

		debug($fields,1);
	}

	
}
