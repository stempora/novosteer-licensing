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

		//dont import new
		if (($item["type"] == "New") && ($this->info["settings"]["set_import_type"] == "2")) {
			unset($item[$this->skuField]);
			return false;
		}

		//dont import used
		if (($item["type"] != "New") && ($this->info["settings"]["set_import_type"] == "3")) {
			unset($item[$this->skuField]);
			return false;
		}

		//remove images field if not activated
		if (!$this->info["settings"]["set_import_" . strtolower($item["type"]) . "_images"]) {
			unset($item["imagelist"]);
		} 
		//remove thr prices if not activated
		if (!$this->info["settings"]["set_import_" . strtolower($item["type"]) . "_prices"]) {
			unset($item["msrp"]);
			unset($item["sellingprice"]);
			unset($item["bookvalue"]);
			unset($item["invoice"]);
			unset($item["internet_price"]);
			unset($item["misc_price_1"]);
			unset($item["misc_price_2"]);
			unset($item["misc_price_3"]);
		}
		
		if ($item["type"] == "Used") {

			if (strtolower($item["certified"]) == "true")  {
				$item["type"] = "Certified";
			}			
		}

		$item["description"] = strip_tags($item["description"]);
		
		//build the engine filter fields
		$item["engine"] = strtoupper($item["engine_block_type"]) ."-". $item["enginecylinders"] . " " . str_replace(" " , "" , $item["enginedisplacement"]);		
		$item["factory_codes"] = implode("," , explode(" " , $item["factory_codes"]));

		$item["age"] = date("Y") - $item["year"];


		switch ($item["type"]) {
			case "New":
				$item["brand_id"] = $this->module->plugins["novosteer-addon-autobrands"]->getBrandIdByName(
					$item["make"] , 
					true
				);

				$item["model_id"] = $this->module->plugins["novosteer-addon-autobrands"]->getModelIdByName(
					$item["brand_id"] , 
					$item["model"], 
					true,
					$item["model_type"]
				);

				$item["trim_id"] = $this->module->plugins["novosteer-addon-autobrands"]->getTrimIdByName(
					$item["brand_id"] , 
					$item["trim"], 
					true
				);

				unset($item["trim"]);
			break;

			default:
				$item["brand_id"] = $this->module->plugins["novosteer-addon-autobrands"]->getBrandIdByName($item["make"], true);
				$item["model_id"] = $this->module->plugins["novosteer-addon-autobrands"]->getModelIdByName(
					$item["brand_id"] , 
					$item["model"], true, 
					$item["model_type"]
				);				
			break;
		}

		$item["feed_id"] = $this->info["feed_id"];
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
	function _processFeed(&$items) {
		global $base , $_USER , $_SESS; 


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

}
