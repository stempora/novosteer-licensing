<?php
/*
	Stempora Web Framework
	Copyright (c) 2002-2020 Stempora. 
	All rights reserved.
		web:  www.stempora.com
		mail: support@stempora.com				
*/


namespace Stembase\Modules\Novosteer_Addon_Import\Core\Models;

if (!defined("STPBase")) {
	die("This file can't be accessed directly!");
}


use \Stembase\Modules\Novosteer_Addon_Import\Core\Models\Importer;
use \Stembase\Modules\Novosteer_Addon_Import\Core\Models\Locks;
use \Stembase\Modules\Novosteer_Addon_Import\Core\Interfaces\ImporterInterface;
use \CTemplateStatic;
use \CFile;

class DealerFormula extends Importer implements ImporterInterface{

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
	var $hints = "";
	

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
			"SELECT * FROM %s as products
			WHERE				
				dealership_id = %d
			",
			[
				$this->module->tables["plugin:novosteer_vehicles_import"],
				$this->info["dealership_id"]
			],
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
	function updateProduct($product , $item) {
		global $base , $_USER , $_SESS; 

		$data = [
			"sellingprice"		=> $item["sellingprice"],
			"bookvalue"			=> $item["bookvalue"],
			"invoice"			=> $item["invoice"],
			"internet_price"	=> $item["internet_price"],
			"misc_price1"		=> $item["misc_price1"],
			"misc_price2"		=> $item["misc_price2"],
			"misc_price3"		=> $item["misc_price3"],
			"price_1"			=> $item["price_1"],
			"price_2"			=> $item["price_2"],
			"price_3"			=> $item["price_3"],
			"price_4"			=> $item["price_4"],
			"price_5"			=> $item["price_5"],
			"price_6"			=> $item["price_6"],
		];

		$hash = $this->event->getHash($data);


		if ($this->wasUpdated("" , $hash)) {	
			$this->log("Updating product pricing...\n");
			$this->db->QueryUpdateByID(
				$this->module->tables["plugin:novosteer_vehicles_import"],
				$data,
				$product["product_id"]
			);

			$this->event->productRecordUpdate("" , $hash);
		} else {
			$this->log("No Change, skipping.\n");
		}

		$this->event->setProduct(null);
				
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
	function createProduct($item) {
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
	function getAdminFields() {
		global $_LANG_ID; 

		return [
			"fields"	=> [
				"box"	=> [
					"title"	=> "Hints",
					"width"	=> "7",
					"fields"	=> [
						"comments"	=> [
							"type"				=> "comment",
							"ondetails"			=> "true",
							"html"				=> "true",
							"description"		=> $this->hints
						]
					]
				]
			],
			"remove_fields" => [
				"feed_data_type" => 1 ,
				"subtitle_feed_location"	=> 1,
				"subtitle_feed_manual" => 1,
				"comment_manual" => 1,
				"feed_data_file" => 1,
				"subtitle_feed_ftp" => 1,
				"feed_data_server" => 1,
				"feed_data_port" => 1,
				"feed_data_enc" => 1,
				"feed_data_passive" => 1,
				"feed_data_user" => 1,
				"feed_data_pass" => 1,
				"feed_data_path" => 1,
				"subtitle_feed_web" => 1,
				"feed_data_link" => 1,
				"cache_control" => 1,
				"button_all" => 1,
				"button_info" => 1,
				"button_list" => 1,
				"button_details" => 1,

			],
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
	function runPreProcess() {
		global $_LANG_ID; 

		$this->setSKUField("vin");
	}
	
	
}
