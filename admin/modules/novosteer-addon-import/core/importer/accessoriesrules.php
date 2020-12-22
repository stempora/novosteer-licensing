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
use \Stembase\Modules\Novosteer_Dealerships\Core\Models\Discounts;



class AccessoriesRules extends Importer implements ImporterInterface{
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
			"SELECT *,brands.brand_name as make, models.model_name as model, trims.trim_name as trim FROM 
					%s as products

				INNER JOIN 
					%s as brands
				ON
					products.brand_id = brands.brand_id 
				INNER JOIN 
					%s as models
				ON 
					products.model_id = models.model_id 
				INNER JOIN 
					%s as trims
				ON 
					products.trim_id = trims.trim_id 
			WHERE	
				cat = 'New' AND
				dealership_id = %d
				:cond				
			",
			[
				$this->module->tables["plugin:novosteer_vehicles_import"],
				$this->module->tables["plugin:novosteer_addon_autobrands_brands"],
				$this->module->tables["plugin:novosteer_addon_autobrands_models"],
				$this->module->tables["plugin:novosteer_addon_autobrands_trims"],
				$this->info["dealership_id"]
			],

			[ 
				":cond" => $this->processCondition($this->info["settings"]["set_condition"]) 
			]
		);

		//debug($products,1);

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
	function runPreProcess() {
		global $_LANG_ID; 

	
		$this->setSKUField("product_sku");

		$this->module->plugins["novosteer-dealerships"]->__init(); 

		$client = \Stembase\Lib\File\GoogleSheets::create()
			->setCredentialsString($this->module->plugins["novosteer-dealerships"]->_s("set_keyfile"))
			->setSheetId($this->info["settings"]["set_sheet_id"])
			->setWorksheet($this->info["settings"]["set_worksheet"]);


		$adjustments = $client->getAllvalues();


		$this->rules = new Discounts();

		foreach ($adjustments as $key => $adjustment) {
			$rules = [];

			foreach ($adjustment as $k => $v) {

				$rule = [];

				if (trim($v)) {
					$v = trim($v);

					if (stristr($k , "a:")) {
						$rules["adjust"][str_replace("a:" , "" , $k)] = strstr($v, "%" ) ? $v : str_replace("," ,'' , $v);						
					} else {						
						if (stristr($v , "|not")) {
							$rule["type"] = "2";
							$v = str_ireplace("|not" , ""  , $v);
						} else {
							$rule["type"] = "1";
						}
						
						$tmp = explode("," , $v);
						$rule["values"] = $tmp;
						$rule["field"] = $k;

						$rules["conds"][] = $rule;
					}					
				}
			}

			$this->rules->addRule($rules);				

		}		

		//debug($this->rules->rules,1);

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
		global $_LANG_ID; 


		$rules = $this->rules
			->setVehicle($item)
			->getMatchingRule();

		if (is_array($rules)) {
			$product = [
				"product_sku" => $item["product_sku"]
			];

			foreach ($rules["adjust"] as $field => $value) {
				if ($item[$field] < $value) {
					$product[$field] = $value;	
					$product["old_" . $field] = $item[$field];
				}
				
			}

			if (count($product) > 1) {
				$item = $product;
			} else {
				unset($item["product_sku"]);
			}
			
			

		} else {
			unset($item["product_sku"]);
		}		
	}



}
