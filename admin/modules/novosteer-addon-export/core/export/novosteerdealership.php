<?php
/*
	Stempora Web Framework
	Copyright (c) 2002-2020 Stempora. 
	All rights reserved.
		web:  www.stempora.com
		mail: support@stempora.com				
*/


namespace Stembase\Modules\Novosteer_Addon_Export\Core\Export;

if (!defined("STPBase")) {
	die("This file can't be accessed directly!");
}


use \Stembase\Modules\Novosteer_Addon_Export\Core\Models\Export;
use \Stembase\Modules\Novosteer_Addon_Export\Core\Models\Locks;
use \Stembase\Modules\Novosteer_Addon_Export\Core\Interfaces\ExportInterface;
use \CTemplateStatic;
use \CFile;

class NovosteerDealership extends Export implements ExportInterface{
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
	function getFile() {
		global $_LANG_ID; 

		return \Stembase\Lib\Link::Show(
			"https://" . $this->info["settings"]["set_dealer_client"] . "/__novosteer/inventory.json"
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
	function loadFeedFile($file) {
		global $_LANG_ID; 

		$client = new \GuzzleHttp\Client();
		$res = $client->request(
			"GET" , 
			$file,
			[
				"headers"	=> [
					"Novosteer-Authorization"	=> $this->info["settings"]["set_dealer_key"]
				]
			]
		);

		if ($res->getStatusCode() !== 200) {
			return null;
		}

		
		$data = json_decode($res->getBody()->getContents() , true);

		return $data;
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



	public function updateProductImages($product , $data) {

		$old = $this->db->qFetchRowArray(
			"SELECT * FROM %s WHERE product_id = %d",
			[
				$this->module->tables['plugin:novosteer_vehicles_export_images'],
				$product["product_id"]
			]
		);

		$new = [];

		if (is_array($data["gallery"])) {
			$first = true;
			foreach ($data["gallery"] as $key => $val) {
				$new[$val] = [
					"product_id"		=> $product["product_id"],
					"image_main"		=> $first,
					"image_url"			=> $val,
					"image_last_update"	=> time()
				];

				$first = false;
			}			
		}

		if (is_array($old)) {
			foreach ($old as $k => $v) {
				if ($new[$v["image_url"]]) {
					$new[$v["image_url"]] = $v;

					unset($old[$v["image_url"]]);
				}			
			}		
		}

		if (is_array($old) && count($old)) {
			foreach ($old as $key => $val) {
				$this->module->storage->resources->delete($this->info["dealership_location_prefix"] . "/export/{$product['product_sku']}/original/" . $val["image_id"] . ".jpg");
			}			
		}

		$this->db->Query(
			"DELETE FROM %s WHERE product_id = %d" , 
			[
				$this->module->tables["plugin:novosteer_vehicles_export_images"],
				$product["product_id"]
			]
		);


		if (is_array($new) && count($new)) {
			foreach ($new as $key => $image) {
				//is a new one
				$id = $this->db->QueryInsert(
					$this->module->tables["plugin:novosteer_vehicles_export_images"],
					$image
				);

				if (!$image["image_id"]) {

					$this->log("Downloading image %s" , [$image["image_url"]]);

					$client = new \GuzzleHttp\Client();
					$res = $client->request(
						"GET" , 
						$image["image_url"]
					);

					if ($res->getStatusCode() !== 200) {
						$this->log("Error downloading...");
					} else {
						$this->module->storage->resources->saveStream(
							$this->info["dealership_location_prefix"] . "/export/{$product['product_sku']}/original/" . $id . ".jpg",
							$res->getBody()->detach()		
						);
						$this->log("Download Successfuly");
					}
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
	public function updateProduct(&$product , &$item , &$data) {
		global $base , $_USER , $_SESS , $_LANG_ID; 

		$this->log("Updating %s..." , [$item[$this->skuField]]);


		$newData = [
			//add the field set
			"product_id"	=> $product["product_id"],
			"product_sku"	=> $item[$this->skuField],
			"dealership_id"	=> $this->info["dealership_id"],
		];

		$fields = ["options" , "options_exterior" , "options_interior" , "options_mechanical" , "options_safety" , "factory-codes" ];

		foreach ($fields as $key => $val) {
			$item[$val] = json_encode($item[$val]);
		}

		$newData = array_merge(
			$newData , 
			$item
		);

		//update only if i have any differences
		if (count($newData) > 3) {

			$this->db->QueryUpdate(
				$this->module->tables["plugin:novosteer_vehicles_export"],
				$newData,
				$this->db->Statement(
					"product_id = %d",
					$product["product_id"]
				)
			);

			$this->skus["updates"][] = $item[$this->skuField];
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
	public function createProduct($item , $data) {
		global $base , $_USER , $_SESS; 

		$this->log("Creating product %s ..." , [$item[$this->skuField]]);

		$fields = ["options" , "options_exterior" , "options_interior" , "options_mechanical" , "options_safety" , "factory-codes" ];

		foreach ($fields as $key => $val) {
			$item[$val] = json_encode($item[$val]);
		}

		$item["dealership_id"] = $this->info["dealership_id"];	
		$item["product_sku"] = $item[$this->skuField];

		
		$item["product_id"] = $this->db->QueryInsert(
			$this->module->tables["plugin:novosteer_vehicles_export"],
			$item
		);

		$this->event->setProduct($item["product_id"]);

		$this->log("Done");
		$this->skus["new"][] = $item[$this->skuField];


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
	function runItem($item) {
		global $_LANG_ID; 

		$data = null;

		//$this->log("Queries as far: " . $this->db->queries_cnt );
		if ($item[$this->skuField]) {
			$this->log();
			$this->log("Processing %s" , [$item[$this->skuField]]);
			$this->updateCronJob();

			$data = null;


			//check if exists a product with this sku already
			$product = $this->getProduct($item);
			$changeLog = null;

			$this->skus["all"][] = $item[$this->skuField];

			if (is_Array($product)) {
				$this->event->setProduct($product["product_id"]);
				//$this->lock->setProduct($product["product_id"]);

				$this->log("Checking %s for changes..." , [$item[$this->skuField]]);

				if ($this->wasUpdated("" , $this->event->getHash($item))) {
					$this->updateProduct($product , $item , $data);
					$this->updateProductImages($product , $item);
				} else {
					$this->log("No Change, skipping \n");
					$this->skus["ignored"][] = $item[$this->skuField];
				}							
				
			} else {
				$product = $this->createProduct($item , $data);				
			}

			if (is_array($product)) {
				$this->event->productRecordUpdate("" , $this->event->getHash($item));
				$this->updateProductImages($product , $item);
				$this->postUpdateProduct($product , $data , $item);					
			} else {
				$this->log("Ignoring");
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
	function deleteProduct($sku) {
		global $_LANG_ID; 

		$this->log("Deleting product %s" , [$sku]);

		$this->db->Query(
			"DELETE FROM %s WHERE product_sku LIKE '%s' AND dealership_id=%d",
			[
				$this->module->tables["plugin:novosteer_vehicles_export"],
				$sku
			]
		);

		$this->log("Deleging images & resources");
		$this->module->storage->resources->deleteDirectoryRecursive(
			$this->info["dealership_location_prefix"] . "/export/{$sku}/"
		);

		$this->log("Done");
	}
	
	
}
