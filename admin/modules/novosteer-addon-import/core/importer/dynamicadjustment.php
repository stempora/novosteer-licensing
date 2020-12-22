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
use \Stembase\Modules\Novosteer_Addon_Import\Core\Interfaces\ImporterInterface;
use \Intervention\Image\ImageManager;



class DynamicAdjustment extends Importer implements ImporterInterface{

	
	
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

		//just ro return something
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

		$products = $this->db->QFetchRowArray(
			"SELECT * FROM 
					%s as products

				INNER JOIN 
					%s as brands
				ON
					products.brand_id = brands.brand_id 
				INNER JOIN 
					%s as models
				ON 
					products.model_id = models.model_id 
			WHERE				
				dealership_id = %d
				:cond				
			",
			[
				$this->module->tables["plugin:novosteer_vehicles_import"],
				$this->module->tables["plugin:novosteer_addon_autobrands_brands"],
				$this->module->tables["plugin:novosteer_addon_autobrands_models"],
				$this->info["dealership_id"]
			],

			[ 
				":cond" => $this->processCondition($this->info["settings"]["set_condition"]) 
			]
		);

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
	public function wasUpdated($scope , $hash) {
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
	function processFeed(&$items) {
		global $_LANG_ID; 

		$map = explode("\n" , trim($this->info["settings"]["set_map"]));
		foreach ($map as $key => $val) {
			$tmp = explode("|" , $val);
			$data[trim($tmp[0])] = trim($tmp[1]);
		}

		$map = $data;
	
		if (is_array($items)) {
			foreach ($items as $key => &$item) {
				$data = [
					"product_sku"	=> $item["product_sku"]
				];

				foreach ($map as $k => $v) {
					$data[$k] = $v;
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

		$this->setSKUField("product_sku");
	}


}
