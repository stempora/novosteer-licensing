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

class ManufacturerDiscounts extends Importer implements ImporterInterface{

	/**
	* description
	*
	* @var type
	*
	* @access type
	*/
	var $calculator = null;

	/**
	* description
	*
	* @var type
	*
	* @access type
	*/
	var $errors = [];
	
	
	/**
	* description
	*
	* @param
	*
	* @return
	*
	* @access
	*/
	function getFile() {
		global $base , $_USER , $_SESS , $_CONF , $_LANG_ID; 
		return "fake_file";
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
		global $base , $_USER , $_SESS , $_CONF , $_LANG_ID; 

		$items = $this->db->QFetchRowArray(
			"SELECT product_id,vin,stock,%s FROM %s WHERE type='New' AND dealership_id = %d",
			[
				$this->info["settings"]["set_msrp_field"],
				$this->module->tables["plugin:novosteer_vehicles"],
				$this->info["dealership_id"]				
			]
		);

		return $items;
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
	function getProduct(&$item) {
		global $base , $_USER , $_SESS , $_CONF , $_LANG_ID; 

		return $item;
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
	function processFeedItem(&$item) {
		global $base , $_USER , $_SESS , $_CONF , $_LANG_ID; 

		$this->log("Calculating price for %s with msrp %d" , [$item["vin"] , $item[$this->info["settings"]["set_msrp_field"]] ]);

		$vehicle = $this->getVehicleByID($item["product_id"]);
		$this->calculator->setVehicle($vehicle);
		$price = $this->calculator->calculatePrice();

		if ($price === false) {
			$this->log("No rule matched for %s" , [$item["vin"]]);
			$this->errors[] = $item["stock"] . " " . $vehicle["year"] . " " . $vehicle["brand_name"] . " " . $vehicle["model_name"] . " ";
		} else {
			$this->log("Calculated discount price of %d" , [$price]);
		}

		$item[$this->info["settings"]["set_price_field"]] = $price !== false ? $price : -1;
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
	function updateProduct(&$product , &$data , &$item) {
		global $base , $_USER , $_SESS; 



		if ($this->isLocked($product["product_id"] , Locks::LOCK_UPDATE)) {
			return null;
		}
		
		$this->db->QueryUpdateByID(
			$this->module->tables["plugin:novosteer_vehicles"],
			[
				$this->info["settings"]["set_price_field"] => $data[$this->info["settings"]["set_price_field"]],
			],
			$data["product_id"]
		);
				
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
	function runPreProcess() {
		$this->setSKUField("vin");

		//force to update the exiting
		$this->info['feed_duplicates'] = '2';

		$this->calculator = $this->module->plugins["novosteer-dealerships"]->getCalculatorObject($this->info["settings"]["set_calculator"]);
		$this->calculator->setJob($this->cronJob);
		$this->calculator->setMSRPField($this->info["settings"]["set_msrp_field"]);

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
	function createNewProduct(&$item , &$data) {
		global $base , $_USER , $_SESS , $_CONF , $_LANG_ID; 
		
		//disable new creation of product
		return null;
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
	function getVehicleByID($id) {
		global $base , $_USER , $_SESS , $_CONF , $_LANG_ID; 

		//get all products that are not published 
		return $this->db->QFetchArray(
			"SELECT * FROM 
				%s as vehicles 
			INNER JOIN 
				%s as brands
				ON
					vehicles.brand_id = brands.brand_id
			INNER JOIN 
				%s as models 
				ON 
					vehicles.model_id = models.model_id 
			LEFT JOIN 
				%s as trims
				ON 
					vehicles.trim_id = trims.trim_id
		
			WHERE 
				product_id = %d
			",
			[
				$this->module->tables["plugin:novosteer_vehicles"],
				$this->module->tables["plugin:novosteer_addon_autobrands_brands"],
				$this->module->tables["plugin:novosteer_addon_autobrands_models"],
				$this->module->tables["plugin:novosteer_addon_autobrands_trims"],
				$id
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
	function runPostProcess() {
		global $base , $_USER , $_SESS , $_CONF , $_LANG_ID; 

		if (count($this->errors)) {

			$this->log("Error vehicles: \n%s "  , [ implode("\n" , $this->errors) ]);
		}
		
	}
	
	
}
