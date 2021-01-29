<?php
/*
	Stempora Web Framework
	Copyright (c) 2002-2020 Stempora. 
	All rights reserved.
		web:  www.stempora.com
		mail: support@stempora.com				
*/


namespace Stembase\Modules\Novosteer_Addon_Import\Core\Importer;

if (!defined("STPBase")) {
	die("This file can't be accessed directly!");
}


use \Stembase\Modules\Novosteer_Addon_Import\Core\Models\Importer;
use \Stembase\Modules\Novosteer_Addon_Import\Core\Models\Locks;
use \Stembase\Modules\Novosteer_Addon_Import\Core\Interfaces\ImporterInterface;
use \CTemplateStatic;
use \CFile;

class GoogleSheets extends Importer implements ImporterInterface{




	/**
	* description
	*
	* @param
	*
	* @return
	*
	* @access
	*/
	function getFile($default = null) {
		global $_LANG_ID; 
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
	function loadFeedFile($file) {
		global $_LANG_ID; 

		$this->module->plugins["novosteer-dealerships"]->__init(); 

		$client = \Stembase\Lib\File\GoogleSheets::create()
			->setCredentialsString($this->module->plugins["novosteer-dealerships"]->_s("set_keyfile"))
			->setSheetId($this->info["settings"]["set_sheet_id"])
			->setWorksheet($this->info["settings"]["set_worksheet"]);

		$data = $client->getAllvalues();

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
	function processFeed(&$items) {
		global $_LANG_ID; 

		$tmpmap = explode("\n" , $this->info["settings"]["set_maping"]);

		$map[$this->info["settings"]["set_feed_field"]]  = $this->info["settings"]["set_relation_field"];

		foreach ($tmpmap as $key => $val) {
			$tmp = explode("|" , trim($val));

			$map[$tmp[0]] = $tmp[1];
		}

		if (is_array($items)) {
			foreach ($items as $key => &$item) {
				$data = [];
				foreach ($map as $k => $v) {
					if (trim($item[$k]) != "") {
						$data[$v] = $item[$k];
					}					
				}

				$item = $data;				
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
	function runPreProcess() {
		global $_LANG_ID; 

		$this->setSKUField($this->info["settings"]["set_relation_field"]);
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
	function createProduct($item) {
		global $_LANG_ID; 

		return null;
	}
	

	public function getProduct(&$item) {

		$product = $this->db->QFetchArray(
			"SELECT * FROM %s WHERE dealership_id = %d AND %s LIKE '%s'",
			[
				$this->module->tables['plugin:novosteer_vehicles_import'],
				$this->info["dealership_id"],
				$this->skuField,
				$item[$this->skuField]
			]
		);

		if (is_array($product)) {
			$this->event->setProduct($product["product_id"]);
			$this->lock->setProduct($product["product_id"]);
		}

		return $product;
		
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
	public function wasUpdated($scope , $hash) {
		global $_LANG_ID; 

		return true;
	}
	
}
