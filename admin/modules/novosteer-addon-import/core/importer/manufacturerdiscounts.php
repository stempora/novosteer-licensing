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
	var $epErrors = [];
	
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
	function getFile($default = null) {
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
			"SELECT product_id,vin,stock,%s FROM %s as products

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
				$this->info["settings"]["set_msrp_field"],
				$this->module->tables["plugin:novosteer_vehicles_import"],
				$this->module->tables["plugin:novosteer_addon_autobrands_brands"],
				$this->module->tables["plugin:novosteer_addon_autobrands_models"],
				$this->info["dealership_id"]
			],

			[ 
				":cond" => $this->processCondition($this->info["settings"]["set_condition"]) 
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
	function processFeedItem(&$item) {
		global $base , $_USER , $_SESS , $_CONF , $_LANG_ID; 

		$this->log("Calculating price for %s with msrp %d" , [$item["vin"] , $item[$this->info["settings"]["set_msrp_field"]] ]);

		$vehicle = $this->getVehicleByID($item["product_id"]);
		$this->calculator->setVehicle($vehicle);		
		$price = $this->calculator->calculatePrice();

		$rule = $this->calculator->serializeRule();

		switch ($price) {
			case "EP_ERROR":
				$this->log("No EP price set for this vehicle");
				$item["calculator_discounts"] = $rule["disc"];

				if ($vehicle["alert_price"] == "1") {
					$this->db->QueryUpdateBYId(
						$this->module->tables["plugin:novosteer_vehicles_import"],
						[
							"alert_price" => "0",
						],
						$vehicle["product_id"]
					);
				}

				$this->epErrors[$vehicle["stock"]] = [$vehicle["stock"]];
			break;

			case false:
				$this->log("No rule matched for %s" , [$item["vin"]]);
				$this->errors[$item["vin"]] = $item["stock"] . " " . $vehicle["year"] . " " . $vehicle["brand_name"] . " " . $vehicle["model_name"] . " ";

				$this->db->QueryUpdateBYId(
					$this->module->tables["plugin:novosteer_vehicles_import"],
					[
						"alert_price" => "1"
					],
					$vehicle["product_id"]
				);
			break;

			default: 
				$this->log("Calculated discount price of %d" , [$price]);

				if ($vehicle["alert_price"] == "1") {
					$this->db->QueryUpdateBYId(
						$this->module->tables["plugin:novosteer_vehicles_import"],
						[
							"alert_price" => "0",
						],
						$vehicle["product_id"]
					);
				}

				$item["calculator_discounts"] = $rule["disc"];
				$item[$this->info["settings"]["set_price_field"]] = $price !== false ? $price : 0;

			break;
		}
				
		$item["calculator_rule"] = $rule["rules"];


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
	function updateProduct($product , $item) {
		global $base , $_USER , $_SESS; 

		$hash = $this->event->getHash($item);


		if ($this->wasUpdated("" , $hash)) {	
			$this->log("Updating pricing...\n");
			$this->db->QueryUpdateByID(
				$this->module->tables["plugin:novosteer_vehicles_import"],
				[
					$this->info["settings"]["set_price_field"] => $item[$this->info["settings"]["set_price_field"]],
					"calculator_rule"			=> $item["calculator_rule"],
					"calculator_discounts"		=> $item["calculator_discounts"]
				],
				$product["product_id"]
			);

		} else {
			$this->log("No Change, skipping.\n");
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
	function runPreProcess() {
		$this->setSKUField("vin");

		$this->calculator = $this->module->plugins["novosteer-dealerships"]->getCalculatorObject($this->info["settings"]["set_calculator"]);
		$this->calculator->setJob($this->cronJob);
		$this->calculator->setMSRPField($this->info["settings"]["set_msrp_field"]);
		$this->calculator->setEPField($this->info["settings"]["set_eprice_field"]);
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


	public function wasUpdated($scope , $hash) {
		global $base , $_USER , $_SESS , $_CONF , $_LANG_ID; 
		return true;
		return $this->event->productWasUpdated($scope , $hash);
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
		global $_LANG_ID; 

		if (is_array($this->epErrors) && $this->info["settings"]["set_ep_sheet_id"]) {
			$this->module->plugins["novosteer-dealerships"]->__init();

			$client = \Stembase\Lib\File\GoogleSheets::create()
				->setCredentialsString($this->module->plugins["novosteer-dealerships"]->_s("set_keyfile"))
				->setSheetId($this->info["settings"]["set_ep_sheet_id"])
				->setWorksheet($this->info["settings"]["set_ep_worksheet"]);

			$existing = $client->getAllvalues();

			if (is_array($existing)) {
				foreach ($existing as $key => $val) {
					if ($this->epErrors[$val["Stock"]]) {
						unset($this->epErrors[$val["Stock"]]);
					}					
				}				
			}
			

			if (count($this->epErrors)) {
				$this->Log("Injecting errors in Google Sheets Document");
				$client->appendValues(array_values($this->epErrors));
			}
			
		}	
	}
	
	
}
