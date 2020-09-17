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
	function processFeedItem(&$item) {
		global $base , $_USER , $_SESS; 

		parent::processFeedItem($item);

		$item = [
			"stock"				=> $item["stock_#"],
			"vin"				=> $item["vin"],
			"price_sale"		=> $item["price"],
			"price_discount"	=> 0,
			"price"				=> $item["price"],

			"price_msrp"			=> 0,
			"price_incentives"		=> 0,
			"price_accessories"		=> 0,
			"price_protection"		=> 0,
		];

		if ($this->info["settings"]["set_import_discount"]) {	

			if ($item["price_sale"]) {
				$item["price_sale"]	= $item["price_sale"] * ( (100 + $this->info["settings"]["set_import_discount"]) / 100 );
				$item["price_discount"] = $item["price_sale"] - $item["price"];
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
	function processProductData(&$data , $item) {
		global $base , $_USER , $_SESS , $_CONF , $_LANG_ID; 


		foreach ($this->info["settings"] as $key => $fid) {
			if (stristr($key , "set_field_") && $fid) {
				$this->appendItemField($data , $fid , $item[str_replace("set_field_" , "" , $key )]);
			}			
		}
	}
	


	function processFeed(&$items) {
		global $base , $_USER , $_SESS; 

		$data  = $this->getRemoteCSV($this->info["settings"]["set_adjust_doc"]);

		if (is_array($data)) {
			foreach ($data as $key => $val) {

				if ($val['Stock']) {
					$_data[$val['Stock']] = $val;
				}					
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

	function runPreProcess() {
		$this->setSKUField("vin");
		//update what exists
		$this->info['feed_duplicates'] = "2";
	}


	//disable creation of new products
	public function createNewProduct(&$item , &$data) {
		return null;
	}

	//get product by field value
	public function getProduct(&$item) {

		$product = $this->module->module->getProductByFieldValue(
			$this->module->module->getFieldByID($this->info["settings"]["set_stock"]),
			$item["stock"]
		);

		if (is_array($product)) {
			return $product;
		} else {
			return null;
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
	function getAdminFields() {
		global $base , $_USER , $_SESS , $_CONF , $_LANG_ID; 

		$importer = parent::getAdminFields();

		debug($importer,);

		$fields = [
			"set_field_price_msrp"			=> "MSRP",
			"set_field_price_incentives"	=> "Incentives",
			"set_field_price_sale"			=> "Sale Price",
			"set_field_price_accessories"	=> "Accessories",
			"set_field_price_protection"	=> "Protection",
			"set_field_price_discount"		=> "Discount",
		];

		$_fields = [];

		foreach ($fields as $key => $val) {
			$_fields[$key] = $this->generateRelationField($key , $val);
		}
		
		\CForm::insertFieldsAfterField($importer , "set_stock" , $_fields);

		return $importer;
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
	public function _wasUpdated($scope , $hash) {
		global $base , $_USER , $_SESS , $_CONF , $_LANG_ID; 

		return true;
	}

}
