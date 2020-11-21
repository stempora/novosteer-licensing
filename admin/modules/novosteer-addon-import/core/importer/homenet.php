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

class Homenet extends Importer implements ImporterInterface{
	/**
	* description
	*
	* @var type
	*
	* @access type
	*/
	var $first = false;
	

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
		global $base , $_USER , $_SESS; 

		//$item = $this->lowerKeys($item);

		$item["cat"] = $item["type"];
		unset($item["type"]);

		//dont import new
		if (($item["cat"] == "New") && ($this->info["settings"]["set_import_type"] == "2")) {
			unset($item[$this->skuField]);
			return false;
		}

		//dont import used
		if (($item["cat"] != "New") && ($this->info["settings"]["set_import_type"] == "3")) {
			unset($item[$this->skuField]);
			return false;
		}

		//remove images field if not activated
		if (!$this->info["settings"]["set_import_" . strtolower($item["cat"]) . "_images"]) {
			unset($item["imagelist"]);
		} 
		//remove thr prices if not activated
		if (!$this->info["settings"]["set_import_" . strtolower($item["cat"]) . "_prices"]) {
			unset($item["msrp"]);
			unset($item["sellingprice"]);
			unset($item["bookvalue"]);
			unset($item["invoice"]);
			unset($item["internet_price"]);
			unset($item["misc_price_1"]);
			unset($item["misc_price_2"]);
			unset($item["misc_price_3"]);
		}
		
		if ($item["cat"] == "Used") {

			if (strtolower($item["certified"]) == "true")  {
				$item["cat"] = "Certified";
			}			
		}

		$item["description"] = strip_tags($item["description"]);
		
		//build the engine filter fields
		$item["engine"] = strtoupper($item["engine_block_type"]) ."-". $item["enginecylinders"] . " " . str_replace(" " , "" , $item["enginedisplacement"]);		
		$item["factory_codes"] = implode("," , explode(" " , $item["factory_codes"]));

		$item["age"] = max(0 , date("Y") - $item["year"]);


		switch ($item["cat"]) {
			case "New":
				$item["brand_id"] = $this->module->plugins["novosteer-addon-autobrands"]->getBrandIdByName(
					$item["make"], 
					true, 
					true
				);

				$item["model_id"] = $this->module->plugins["novosteer-addon-autobrands"]->getModelIdByName(
					$item["brand_id"] , 
					$item["model"], 
					true,
					$item["model_type"],
					true
				);

				$item["trim_id"] = $this->module->plugins["novosteer-addon-autobrands"]->getTrimIdByName(
					$item["brand_id"] , 
					$item["trim"], 
					true,
					true
				);

				$item["color_id"] = $this->module->plugins["novosteer-addon-autobrands"]->getColorIdByName(
					$item["brand_id"] , 
					$item["exteriorcolor"], 
					true,
					$item["ext_color_generic"],
					$item["ext_color_code"],
					$item["extcolorhexcode"],
					true
				);

				unset($item["trim"]);
			break;

			default:
				$item["brand_id"] = $this->module->plugins["novosteer-addon-autobrands"]->getBrandIdByName(
					$item["make"], 
					true , 
					false
				);

				$item["model_id"] = $this->module->plugins["novosteer-addon-autobrands"]->getModelIdByName(
					$item["brand_id"] , 
					$item["model"], 
					true, 
					$item["model_type"],
					false
				);				

				$item["color_id"] = $this->module->plugins["novosteer-addon-autobrands"]->getColorIdByName(
					$item["brand_id"] , 
					$item["exteriorcolor"], 
					false
				);


			break;
		}

		$item["feed_id"] = $this->info["feed_id"];


		//process categorized options
		if ($item["categorized_options"]) {
			$options = explode("~" , $item["categorized_options"]);
			$data = [];

			foreach ($options as $key => $cat) {
				$tmp = explode("@" , $cat);
				$data[strtolower("options_" . $tmp[0])][] = $tmp[1];
			}	
			
			foreach ($data as $k => $v) {
				$item[$k] = json_encode($v);
			}			
		}



		if (!is_Array($item["options"]) &&  $item["options"]) {
			$item["options"] = json_encode(explode("," , $item["options"]));
		}

		$item["engine"] = strtoupper($item["engine_block_type"]) ."-". $item["enginecylinders"] . " " . str_replace(" " , "" , $item["enginedisplacement"]);		
		$item["factory_codes"] = json_encode(explode("," , $item["factory_codes"]));

		$item["dateinstock"] = strtotime($item["dateinstock"]);
		
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
		global $base , $_USER , $_SESS; 


		if ($this->info["settings"]["set_adjust_doc"]) {
			$data  = $this->getRemoteCSV($this->info["settings"]["set_adjust_doc"]);

			if (is_array($data)) {
				foreach ($data as $key => $val) {
					if ($val['Stock']) {
						$_data[$val['Stock']] = $val;
					}					
				}				

				$data = $_data;

				
				foreach ($items as $key => $val) {
					if ($_data[$val["Stock"]]) {
						$this->log("Adjusting %s" , [ $val["Stock"] ]);


						foreach ($_data[$val["Stock"]] as $k => $v) {
							if ($v == "") {
								unset($_data[$val["Stock"]][$k]);
							}						
						}
						

						$items[$key] = array_merge(
							$val , 
							$_data[$val["Stock"]]
						);
					}				
				}			
			}
		}
		
		if ($this->info["settings"]["set_import_stock"]) {
			$existing = explode("," , trim($this->info["settings"]["set_import_stock"]));

			foreach ($items as $key => $val) {
				if (!in_array($val["Stock"] , $existing) || in_array($val["VIN"] , $existing)) {
					unset($items[$key]);
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
	function runPreProcess() {
		$this->setSKUField("vin");
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
		global $_LANG_ID; 

			$this->log("Checking %s for changes..." , [$item[$this->skuField]]);
			parent::updateProduct($product , $item);
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
	function postUpdateProduct($product , $item) {
		global $_LANG_ID; 

		parent::postUpdateProduct($product , $item);

		if (!isset($item["imagelist"])) {
			return null;
		}
		

		$images = explode(","  , $item["imagelist"]);
		$hash = $this->event->getHash($images);

		if ($this->wasUpdated("images" , $hash)) {
			$this->log("Updating images...");
			$this->updateProductImages($product , $images);
			$this->event->productRecordUpdate("images" , $hash);
		}
	}
	

	public function runPostProcess() {
		global $base , $_USER , $_SESS , $_CONF , $_LANG_ID; 

		if ($this->info["settings"]["set_missing"] == "3") {

			//empty feed ?? impossible
			if (!is_array($this->skus["all"])) {
				return false;
			}

			$missing = $this->db->QFetchRowArray(
				"SELECT product_sku FROM %s WHERE 
					dealership_id = %d AND 
					feed_id = %d AND 
					product_sku not in (':cond')",
				[
					$this->module->tables["plugin:novosteer_vehicles_import"],
					$this->info["dealership_id"],
					$this->info["feed_id"]
				],
				[
					":cond"	=> implode("','" , $this->skus["all"])
				]
			);

			if (is_array($missing)) {
				foreach ($missing as $key => $product) {
					$this->deleteProduct($product["product_sku"]);
				}				
			}

			//delete the images also 
			$this->log("done");

		}

	}
}
