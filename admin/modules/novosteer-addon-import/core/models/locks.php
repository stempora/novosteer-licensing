<?php
/*
	Stempora Web Framework
	Copyright (c) 2002-2020 Stempora. 
	All rights reserved.
		web:  www.stempora.com
		mail: support@stempora.com				
*/

namespace Stembase\Modules\Novosteer_Addon_Import\Core\Models;


if (!defined("STPBase")) {
	die("This file can't be accessed directly!");
}


class Locks extends Base {

	//const LOCK_PRICE	= 1;
	//const LOCK_STOCK	= 2;
	const LOCK_TITLE	= 1;
	//const LOCK_OPTIONS= 4;
	//const LOCK_IMAGES	= 5;
	const LOCK_DELETE	= 5;
	const LOCK_UPDATE	= 4;
	const LOCK_SEO		= 6;
	const LOCK_DISPLAY	= 7;
	const LOCK_STATUS	= 8;


	/**
	* description
	*
	* @var type
	*
	* @access type
	*/
	var $locks =[];

	/**
	* description
	*
	* @var type
	*
	* @access type
	*/
	var $product = null;

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
	function __construct() {
		global $base , $_USER , $_SESS , $_CONF , $_LANG_ID , $site; 

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
	function setProduct($pid) {
		global $base , $_USER , $_SESS , $_CONF , $_LANG_ID; 

		if ($this->product != $pid) {
			$this->product = $pid;

			$this->loadLocks();
		}

		return $this;
	}


	

	private function loadLocks () {

		if (!$this->product) {
			return null;
		}

		$this->locks = [
			"types"	=> [],
			"fields" => [],
		];

		$locks = $this->db->QFetchArray(
			"SELECT * FROM %s WHERE product_id = %d",
			[
				$this->module->tables['plugin:novosteer_addon_importer_locks_products'],
				$this->product
			]
		);

		if (!is_array($locks)) {
			return false;
		}



		$lids = [];
		$tmp = explode("," , $locks["lock_id"]);

		if (!count($tmp)) {
			return false;
		}

		foreach ($tmp as $k => $v) {
			if ((int)$v) {
				$lids[$v]  = $v;
			}
			
		}				
		
		if (!count($lids)) {
			return false;
		}
		
		//load the locks
		$locks = $this->db->QFetchRowArray(
			"SELECT * FROM %s WHERE lock_id in (%s)",
			[
				$this->module->tables["plugin:novosteer_addon_importer_locks_groups"],
				implode("," , $lids)
			]
		);


		if (is_array($locks)) {
			foreach ($locks as $key => $lock) {

				if ($lock["lock_type"]) {
					$this->locks["types"][] = $lock["lock_type"];
				} elseif ($lock["lock_fields"]) {
					$fids = explode("," , $lock["lock_fields"]);

					if (!count($fids)) {
						return null;
					}

					foreach ($fids as $fid) {

						if ($fid != "") {
							$this->locks["fields"][] = $fid;
						}
							
					}										
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
	function isLocked($type) {
		global $base , $_USER , $_SESS , $_CONF , $_LANG_ID; 

		if (!$this->product) {
			return false;
		}		

		if (!is_array($this->locks["types"])) {
			return false;
		}

		if (!in_array($type , $this->locks["types"])) {
			return false;
		}

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
	function getFields() {
		global $base , $_USER , $_SESS , $_CONF , $_LANG_ID; 

		if (is_array($this->locks["fields"])) {
			return $this->locks["fields"];
		}

		return null;		
	}
	
	
}