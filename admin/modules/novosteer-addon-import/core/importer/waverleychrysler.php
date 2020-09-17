<?php
/*
	Stempora Web Framework
	Copyright (c) 2002-2020 Stempora. 
	All rights reserved.
		web:  www.stempora.com
		mail: support@stempora.com				
*/


namespace Stembase\Modules\Products_Addon_Import\Core\Importer;

if (!defined("STPBase")) {
	die("This file can't be accessed directly!");
}


use \Stembase\Modules\Products_Addon_Import\Core\Models\Importer;
use \Stembase\Modules\Products_Addon_Import\Core\Importer\Homenet;
use \Stembase\Modules\Products_Addon_Import\Core\Interfaces\ImporterInterface;
use \CTemplateStatic;
use \CFile;

class WaverleyChrysler extends Homenet {

	
		
	/**
	* description
	*
	* @param
	*
	* @return
	*
	* @access
	*/
	function adjustDiscounts(&$item) {
		global $base , $_USER , $_SESS , $_CONF , $_LANG_ID; 

		
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
		global $base , $_USER , $_SESS; 

		parent::processFeedItem($item);

		if ($this->info["settings"]["set_import_" . strtolower($item["type"]) . "_prices"]) {


			switch ($item["type"]) {
				case "New":

					if ($item["price_retail"] || $item["price_incentives"] || $item["price_sale"]) {

					} else {
							
						$item["price_retail"] = $item["price_incentives"] = $item["price_protection"] = $item["price_sale"] = $item["price_discount"] = 0;

						if ($item["sellingprice"]) {

							$this->adjustDiscounts($item);

							$item["price_retail"] = $item["sellingprice"];
		
							if ($item["msrp"] && $item["misc_price1"] && ($item["msrp"] > $item["misc_price1"])) {
								$item["price_incentives"]	= $item["msrp"] - $item["misc_price1"];
	
								//special discounts
								if ($item["make"] == "Ram" && $item["model"] == "1500 Classic" && $item["year"] == "2019") {
									$item["price_incentives"] += ($item["msrp"] * 5 / 100 );
								}

							}

							$item["price_sale"] = $item["price_retail"] - $item["price_incentives"] ;						
							$item["price_protection"]	= 899;
						} 
					}
				break;

				default:
					$item["price_retail"] = $item["price_incentives"] = $item["price_protection"] = $item["price_sale"] = $item["price_discount"] = 0;
					
					if ($item["msrp"] && $item["sellingprice"] && ($item["msrp"] > $item["sellingprice"])) {
						$item["price_sale"] = $item["msrp"];
						$item["price_discount"] = $item["msrp"] - $item["sellingprice"];
					} else {
						$item["price_sale"] = $item["sellingprice"];
					}					
				break;
			}			
		}


		if ($item["comment_1"] == "DEMO") {
			$item["demo"] = "Yes";
		} else {
			$item["demo"] = "No";
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
	function getPath() {
		global $base , $_USER , $_SESS; 

		$path = parent::getPath();
		return str_replace("waverleychrysler" , "homenet" , $path);
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

		//remove the existing prices()
		\CForm::deleteFields(
			$importer, 
			[
				'set_field_sellingprice' , 
				'set_field_msrp', 
				'set_field_bookvalue' , 
				'set_field_invoice' , 
				'set_field_internet_price' , 
				'set_field_misc_price1' , 
				'set_field_misc_price2' , 
				'set_field_misc_price3'
			]
		);

		$fields = [
			"set_field_price_retail"		=> "Retail",
			"set_field_price_incentives"	=> "Incentives",
			"set_field_price_sale"			=> "Sale Price",
			"set_field_price_accessories"	=> "Accessories",
			"set_field_price_protection"	=> "Protection",
			"set_field_price_discount"		=> "Discount",
			"set_field_demo"				=> "Demo Vehicle",
		];

		$_fields = [];

		foreach ($fields as $key => $val) {
			$_fields[$key] = $this->generateRelationField($key , $val);
		}
		
		\CForm::insertFieldsAfterField($importer , "minititle_pricing" , $_fields);

		return $importer;
	}
	
}
