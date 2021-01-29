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



class CustomDiscounts extends Importer implements ImporterInterface{
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
	
					

					if (stristr($k , "d:")) {
						$rules["discounts"][str_replace("d:" , "" , $k)] = strstr($v, "%" ) ? $v : str_replace("," ,'' , $v);
					} elseif ($k == "overwrite") {
						$rules["overwrite"] = ($v == "overwrite") ? true : false;
					}else {						
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

		$rule = $this->rules
			->setVehicle($item)
			->getMatchingRule();

		//transform it to multi rules

		if (is_array($rule)) {

			$ser = $this->serializeRule($rule);

			$product = [
				"product_sku" => $item["product_sku"],
				"calculator_discounts"	=> $rule["overwrite"] ? $ser["disc"] : ($item["calculator_discounts"] . " + EXTRA " . $ser["disc"]),

				"calculator_rule" => $rule["overwrite"] ? $ser["rules"] : ($item["calculator_rule"] . " <br>" . $ser["rules"]),
				$this->info["settings"]["set_price_field"] => $this->calculatePrice($item , $rule)
			];


			$item = $product;
			
		} else {
			unset($item["product_sku"]);
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
	function serializeRule($rule) {
		global $_LANG_ID; 

		$list = [];

		if (is_array($rule["conds"])) {
			foreach ($rule["conds"] as $ruleItem) {
				$data = $ruleItem["field"] . 
					($ruleItem["type"] == "1" 
						? " IN "  
						: " NOT IN  ") . 

					"( " . implode(", " , $ruleItem["values"]) . " )";

				if ($ruleItem["type"] == "1") {					
					$data = "<span style='color: green'>" . $data . "</span>";
				} else {
					$data = "<span style='color: red'>" . $data . "</span>";
				}


				$list[] = $data;
			}			
		}

		$disc = [];
		if (is_array($rule["discounts"])) {
			foreach ($rule["discounts"] as $discount) {

				if ($discount == "EP") {
					$disc[] = "EP";
				} elseif (stristr($discount , "%")) {
					$disc[] = $discount;
				} else {
					$disc[] = number_format($discount , 0);
				}				
			}			
		} else {
			$disc[] = "0.00";
		}

		return [
			"rules"	=> ($rule["overwrite"] ? "<b>CUSTOM DISCOUNT</b> " : "<b>EXTRA DISCOUNT</b><br> "). implode(" <br> " , $list), 
			"disc"	=> implode(" + "  , $disc), 
		];		
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
	function calculatePrice($item , $rule) {
		global $_LANG_ID; 

			if ($rule["overwrite"]) {
				$price = $item[$this->info["settings"]["set_msrp_field"]];
			} else {
				$price = $item[$this->info["settings"]["set_price_field"]];
			}
			

			//i have no discounts for certain vehicles
			if (!is_array($rule["discounts"]) || !$price) {
				return $price;				
			} else {			
				foreach ($rule["discounts"] as $key => $discount) {

					if (stristr($discount , "%") !== false) {
						//percentage
						$price = $price - ( $price * str_replace("%" , "" , $discount) / 100);

					} else {
						//flat discount
						$price -= abs($discount);
					}									
				}


				return $price;
			}
	}
	
}
