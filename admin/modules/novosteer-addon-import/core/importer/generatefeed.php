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
use \CHeaders;

class GenerateFeed extends Importer {

	/**
	* description
	*
	* @param
	*
	* @return
	*
	* @access
	*/
	function getFile($default= null) {
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
	function loadFeedFile($file) {
		global $_LANG_ID; 


		$items = $this->db->QFetchRowArray(
			"SELECT * FROM %s as products

				INNER JOIN 
					%s as brands
				ON
					products.brand_id = brands.brand_id 

				INNER JOIN 
					%s as models
				ON 
					products.model_id = models.model_id 

				INNER JOIN 
					%s as types
				ON 
					models.type_id = types.type_id

				LEFT JOIN 
					%s as trims
				ON 
					products.trim_id = trims.trim_id

			WHERE	
				dealership_id = %d AND
				product_status = 1
				:cond		
			ORDER BY 
				products.product_id ASC
			",
			[
				$this->module->tables["plugin:novosteer_vehicles_import"],
				$this->module->tables["plugin:novosteer_addon_autobrands_brands"],
				$this->module->tables["plugin:novosteer_addon_autobrands_models"],
				$this->module->tables["plugin:novosteer_addon_autobrands_types"],
				$this->module->tables["plugin:novosteer_addon_autobrands_trims"],
				$this->info["dealership_id"]
			],

			[ 
				":cond" => $this->processCondition($this->info["settings"]["set_condition"]) 
			]
		);

		$alerts = [			
			"New"		=> explode("," , $this->info["settings"]["set_publish_new"]),
			"Used"		=> explode("," , $this->info["settings"]["set_publish_used"]),
			"Certified" => explode("," , $this->info["settings"]["set_publish_certified"]),
		];


		if (is_array($items)) {
			foreach ($items as $k => $v) {
				if (is_array($alerts[$v["cat"]]) && count($alerts[$v["cat"]])) {
					foreach ($alerts[$v["cat"]] as $_k => $_v) {
						if ($v[$_v] == "1") {
							return $this->alert();
						}						
					}					
				}									

				$_items[$v["product_id"]] = $v;
			}			

			$items = $_items;
		}

		$ids = array_map(function($item) { return $item["product_id"]; } ,$items );

		$wasChanged = false;

		if (is_array($ids)) {
			$images = $this->db->QFetchRowArray(
				"SELECT * FROM %s WHERE product_id in (%s) AND image_alert = 0 and image_deleted = 0 ORDER BY image_order ASC",
				[
					$this->module->tables["plugin:novosteer_vehicles_import_images"],
					implode("," , $ids)					
				]
			);


			if (is_Array($images)) {
				foreach ($images as $key => $image) {
					if (!is_array($items[$image["product_id"]]["images"])) {
						$items[$image["product_id"]]["images"] = [];
					}

					if ($image["image_overlay"]) {
						$_image = $this->module->storage->getLocation($this->info["dealership_location"])->getUrl($this->info["dealership_location_prefix"] . "/inventory/" . $items[$image["product_id"]]['product_sku'] ."/over_" . $image["image_id"] . ".jpg");
					} else {
						$_image = $image["image_downloaded"] 
							? $this->module->storage->getLocation($this->info["dealership_location"])->getUrl($this->info["dealership_location_prefix"] . "/inventory/" . $items[$image["product_id"]]['product_sku'] ."/" . $image["image_id"] . ".jpg")
							: $image["image_source"];
					}


					if ($image["image_order"] == "1") {
						$items[$image["product_id"]]["image_main"] = $_image;
					} else {
						$items[$image["product_id"]]["images"][] = $_image;
					}					

					$items[$image["product_id"]]["images_all"][] = $_image;
				}				
			}			
		}

		foreach ($items as $key => &$product) {
			$this->keepKeys($product);
		}

		$data = json_encode([
			"products"	=> $items
		]);

		$this->log("Saving inventory");
		$this->module->storage->private->save(
			"novosteer/inventory/" . $this->info["feed_id"] . ".json",
			$data
		);

		$hash = md5($data);

		$this->db->QueryUpdateByID(
			$this->module->tables["plugin:novosteer_addon_importer_feeds"],
			["feed_reserved" => $hash],
			$this->info["feed_id"]
		);

		if ($hash != $this->info["feed_reserved"]) {
			$this->log("Pinging dealer website to request the new inventory");
			$this->pingDealer();

			$this->log("Done");

		} else {
			$this->log("No changes to the inventory.");
		}		

		
		return null;
	}

	/**
	* description
	*
	* @param
	*
	* @return
	* enginecylinders	enginedisplacement
	* @access
	*/
	function keepKeys(&$item) {
		global $_LANG_ID; 


		$keys = [
			"brand_name" ,
			"model_name",
			"trim_name",
			"type_name",
			"trim_name",
			"trim",
			"cat",
			"stock",
			"vin",
			"year",
			"age",
			"body",
			"modelnumber",
			"doors",		
			"passengercapacity",	 
			"engine",	 
			"enginecylinders",	 
			"enginedisplacement",	 
			"engine_block_type",	 
			"engine_aspiration_type",	 
			"engine_description",	 
			"enginedisplacementcubicinches",	 
			"fuel_type",	 
			"citympg",	 
			"highwaympg",	 
			"transmission",	 
			"transmission_speed",	 
			"transmission_description",	 
			"drivetrain",	 
			"miles",
			"sellingprice",
			"msrp",
			"calc_price",
			"bookvalue",
			"invoice",
			"internet_price",	 
			"misc_price1",
			"misc_price2",
			"misc_price3",
			"dateinstock",	 
			"description",	 
			"options",	 
			"options_exterior",	 
			"options_interior",	 
			"options_mechanical",	 
			"options_safety",	 
			"comment_1",	 
			"comment_2",	 
			"comment_3",	 
			"comment_4",	 
			"comment_5",	 
			"style_description",	 
			"exteriorcolor",	 
			"interiorcolor",	 
			"ext_color_generic",	 
			"int_color_generic",	 
			"int_upholstery",	 
			"epaclassification",	 
			"wheelbase_code",	 
			"factory_codes",	 
			"marketclass",
			"price_1",
			"price_2",
			"price_3",
			"price_4",
			"price_5",
			"price_6",
			"image_main",
			"images_all",
			"images",
			"reserved_1",
			"reserved_2",
			"reserved_3",
			"reserved_4",
			"reserved_5",
		];

		//fix some fields
		$item["trim"] = $item["trim_name"] ? $item["trim_name"] : $item["trim"];

		$array = ["options", "options_exterior","options_interior","options_mechanical","options_safety","factory_codes"];

		foreach ($array as $k => $v) {
			$item[$v] = json_decode($item[$v] , true);
		}

		foreach ($item as $k => $v) {
			if (!in_array($k , $keys)) {
				unset($item[$k]);				
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
	function updateProduct($product , $item) {
		global $base , $_USER , $_SESS; 		

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
	function createProduct($item) {
		global $base , $_USER , $_SESS , $_CONF , $_LANG_ID; 
		
		//disable new creation of product
		return null;
	}


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
	function runWeb() {
		global $_LANG_ID ,$site; 


		$headers = getAllHeaders();

		if (!(isset($headers["Novosteer-Authorization"]) && $headers["Novosteer-Authorization"] == $this->info["settings"]["set_request_key"])) {
			return $site->plugins["redirects"]->ErrorPage("404" , true);
		}


		Cheaders::newInstance()
			->ContentTypeByExt("novosteer.json")
			->FileName("novosteer" , "inline");


		if ($this->module->storage->private->fileExists("novosteer/inventory/" . $this->info["feed_id"] . ".json")) {
			$this->module->storage->private->readChunked("novosteer/inventory/" . $this->info["feed_id"] . ".json");
		}
		
		die();
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
	function alert() {
		global $_LANG_ID; 

		$this->log("Stoping publishing feed because of errors.!");

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
	function pingDealer() {
		global $_LANG_ID , $_CONF; 

		if (!$this->info["settings"]["set_dealer"]) {
			return null;
		}
		

	
		$client = new \GuzzleHttp\Client();
		$res = $client->request(
			'POST', 
			'https://' . $this->info["settings"]["set_dealer_client"] . "/__novosteer/action", 
			[
				"form_params"	=> [
					"action"	=> "cron-inventory",
					"link"		=> $_CONF["url"] . "__novosteer_import/" . $this->info["feed_code"] . "/",
				],
				"headers"	=> [
					"Novosteer-Authorization"	=> $this->info["settings"]["set_dealer_key"]
				] 			
			]
		);

		if ($res->getStatusCode() == 200) {
			$data = json_decode($res->getBody()->getContents() , true);

			if ($data["response"] == "success") {
				$this->log("Successfuly sent inventory update command" , [$res->getStatusCode()]);
			} else {
				$this->log("Error pinging dealer, dealer refused to take command");
			}						
		} else {
			$this->log("Error pinging dealer code: %s" , [$res->getStatusCode()]);
		}
	}
	

}
