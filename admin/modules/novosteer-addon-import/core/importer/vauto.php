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

class Vauto extends Importer {

	/**
	* description
	*
	* @param
	*
	* @return
	*
	* @access
	*/
	function updateProduct($product , $item) {
		global $base , $_USER , $_SESS; 

		$price = $item["price"];
		$hash = $this->event->getHash($price);



		if ($this->wasUpdated("price" , $hash)) {
			$this->log("Updating prices...");

			$this->db->QueryUpdateByID(
				$this->module->tables["plugin:novosteer_vehicles_import"],
				[
					$this->info["settings"]["set_price_field"] => $price
				],
				$product["product_id"]
			);

			$this->event->productRecordUpdate("price" , $hash);
		} else {
			$this->log("No change skipping");
		}
		
		return $product;
	}

	function postUpdateProduct($product , $item ) {
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
		global $base , $_USER , $_SESS , $_CONF , $_LANG_ID; 
		
		//disable new creation of product
		return null;
	}


	function runPreProcess() {
		$this->setSKUField("vin");
	}


	function runPostProcess() { 

		if ($this->info["settings"]["set_missing"] == "2") {
			$missing = $this->db->QFetchRowArray(
				"SELECT 
					* 
				FROM 
					%s 
				WHERE 
					dealership_id = %d AND
					product_sku NOT IN (':skus') 
					:cond",
				[	
					$this->module->tables["plugin:novosteer_vehicles_import"],
					$this->info["dealership_id"],
				],
				[
					":skus"	=> implode("','" , $this->skus["all"]),
					":cond" => $this->processCondition($this->info["settings"]["set_condition"]) 
				]
			);

			if (is_array($missing)) {
				$this->log("Disabling missing vehicles");

				foreach ($missing as $key => $product) {
					$this->log("Disabling " . $product["product_sku"]);

					$this->db->QueryUpdateByID(
						$this->module->tables["plugin:novosteer_vehicles_import"],
						[ "product_status" => 0 ],
						$product["product_id"]
					);
				}				
			}			
		}
		
	}


}
