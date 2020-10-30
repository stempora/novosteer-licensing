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


use \Stembase\Modules\Novosteer_Addon_Export\Core\Models\Export;
use \Stembase\Modules\Novosteer_Addon_Export\Core\Models\Locks;
use \Stembase\Modules\Novosteer_Addon_Export\Core\Interfaces\ExportInterface;
use \CTemplateStatic;
use \CFile;

class Syndicate extends Export implements ExportInterface{


	function getFile() { return "xx"; }


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
			"SELECT products.product_id
				FROM
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
				product_alert = 0 AND 
				dealership_id = %d
				:cond				
			",
			[
				$this->module->tables["plugin:novosteer_vehicles_export"],
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
	function runItem(&$item) {
		global $_LANG_ID; 
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
	function processFeed(&$products) {
		global $_LANG_ID; 


		if (is_array($products)) {

			$old = $this->getSyndicationProducts();		

			if (is_array($old)) {
				$old_ids = array_map(function($item) { return $item["product_id"]; } ,$old );
			}

			$new_ids = array_map(function($item) { return $item["product_id"]; } ,$products );

			foreach ($products as $key => $val) {
				if (is_array($old_ids) && in_array($val["product_id"] , $old_ids)) {
					unset($products[$key]);
				} else {
					$products[$key] = [
						"product_id"	=> $val["product_id"],
						"feed_id"		=> $this->info["feed_id"],
					];
				}				
			}			

			if (is_array($products) && count($products)) {
				$this->db->QueryInsertMulti(
					$this->module->tables["plugin:novosteer_addon_export_products"],
					$products
				);
			}
			
			$this->db->Query(
				"DELETE FROM %s WHERE feed_id = %d AND product_id NOT IN ( %s )",
				[
					$this->module->tables["plugin:novosteer_addon_export_products"],
					$this->info["feed_id"],
					implode("," , $new_ids)
				]			
			);

			$this->log("Detected %d products" , [count($new_ids)]);
		} else {
			//nothing, so delete all products
			$this->db->Query(
				"DELETE FROM %s WHERE feed_id = %d ",
				[
					$this->module->tables["plugin:novosteer_addon_export_products"],
					$this->info["feed_id"],
				]
			);
		}
	}
	
	
}
