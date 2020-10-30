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
	* @var type
	*
	* @access type
	*/
	var $skus = [
		"updated" => [] , "created" => [], "ignored" => [] , "all" => []
	];
	
	

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

		$item["images"] = is_array($item["gallery"]) ? count($item["gallery"]) : 0;
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
			"SELECT * FROM %s WHERE product_id = %d AND image_deleted = 0",
			[
				$this->module->tables['plugin:novosteer_vehicles_export_images'],
				$product["product_id"]
			]
		);

		$new = [];

		if (is_array($data["gallery"])) {
			$cnt = 1;
			foreach ($data["gallery"] as $key => $val) {
				$new[$val] = [
					"product_id"		=> $product["product_id"],
					"image_order"		=> $cnt ++,
					"image_main"		=> $first,
					"image_source"			=> $val,
					"image_last_update"	=> time()
				];
			}			
		}

		if (is_array($old)) {
			foreach ($old as $k => $v) {
				if ($new[$v["image_source"]]) {
					unset($new[$v["image_source"]]);
					unset($old[$k]);
				}
			}	
		}

		if (is_array($old) && count($old)) {
			$ids = array_map(function($item) { return $item["image_id"]; } ,$old );

			$this->db->QueryUpdate(
				$this->module->tables["plugin:novosteer_vehicles_export_images"],
				[ "image_deleted" => "1"] ,
				$this->db->Statement("image_id in (%s)" , [implode("," , $ids)])
			);
		}

		if (is_array($new) && count($new)) {
			$this->db->QueryInsertMulti(
				$this->module->tables["plugin:novosteer_vehicles_export_images"],
				$new
			);
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
	public function updateProduct(&$product , &$item) {
		global $base , $_USER , $_SESS , $_LANG_ID; 

		$this->log("Updating %s..." , [$item[$this->skuField]]);


		$newData = [
			//add the field set
			"product_id"			=> $product["product_id"],
			"product_sku"			=> $item[$this->skuField],
			"product_last_update"	=> time(),
			"dealership_id"			=> $this->info["dealership_id"],
		];

		$fields = ["options" , "options_exterior" , "options_interior" , "options_mechanical" , "options_safety" , "factory_codes" ];

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

			$this->skus["updated"][] = $item[$this->skuField];
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
	public function createProduct($item) {
		global $base , $_USER , $_SESS; 

		$this->log("Creating product %s ..." , [$item[$this->skuField]]);

		$fields = ["options" , "options_exterior" , "options_interior" , "options_mechanical" , "options_safety" , "factory-codes" ];

		foreach ($fields as $key => $val) {
			$item[$val] = json_encode($item[$val]);
		}

		$item["dealership_id"]			= $this->info["dealership_id"];	
		$item["product_sku"]			= $item[$this->skuField];
		$item["product_last_update"]	= time();
		
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

			//check if exists a product with this sku already
			$product = $this->getProduct($item);
			$changeLog = null;

			$this->skus["all"][] = $item[$this->skuField];

			$cache = $item;

			if (is_Array($product)) {
				$this->event->setProduct($product["product_id"]);

				$this->log("Checking %s for changes..." , [$item[$this->skuField]]);

				if ($this->wasUpdated("" , $this->event->getHash($cache))) {
					$this->updateProduct($product , $item);
					$this->updateProductImages($product , $item);
				} else {
					$this->log("No Change, skipping \n");
					$this->skus["ignored"][] = $item[$this->skuField];
				}							
				
			} else {
				$this->event->setProduct($product["product_id"]);
				$product = $this->createProduct($item);				
			}

			if (is_array($product)) {				
				$this->event->productRecordUpdate("" , $this->event->getHash($cache));

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
		$this->log("\tDeleting database records");
		$this->db->Query(
			"DELETE FROM %s WHERE dealership_id=%d AND product_sku LIKE '%s'",
			[
				$this->module->tables["plugin:novosteer_vehicles_export"],
				$this->info["dealership_id"],
				$sku
			]
		);

		$this->log("\tDeleting images & resources");
		$this->module->storage->resources->deleteDirectoryRecursive(
			$this->info["dealership_location_prefix"] . "/export/{$sku}/"
		);

		$this->log("Done\n");
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

		if (!is_array($this->skus["all"])) {
			return null;
		}
		

		$delete = $this->db->QFetchRowArray(
			"SELECT product_sku FROM %s WHERE dealership_id = %d AND product_sku NOT IN (':cond')",
			[
				$this->module->tables["plugin:novosteer_vehicles_export"],
				$this->info["dealership_id"],
				
			],
			[ ":cond" => implode("','" , $this->skus["all"])]
		);

		if (is_array($delete)) {
			foreach ($delete as $key => $product) {
				$this->deleteProduct($product["product_sku"]);
			}			
		}

		$this->log(
			"Final Stats: \n\tUpdated %d, \n\tCreated %d, \n\tDeleted %d, \n\tIgnored %d\n",
			[
				count($this->skus["updated"]),
				count($this->skus["created"]),
				count((array)$delete),
				count($this->skus["ignored"]),
			]
		);

		$this->deleteImages();
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
	function deleteImages() {
		global $_LANG_ID; 


		$images = 	$this->db->QFetchRowArray(
			"SELECT 
				product_sku,products.product_id,images.* 
			FROM %s as images
				INNER JOIN 
					%s as products
				ON 
					products.product_id = images.product_id
			
			WHERE 
				dealership_id = %d AND
				images.image_deleted = 1
			",
			[
				$this->module->tables["plugin:novosteer_vehicles_export_images"],
				$this->module->tables["plugin:novosteer_vehicles_export"],
				$this->info["dealership_id"],
				
			]
		);

		if (is_array($images)) {
			foreach ($images as $key => $image) {
				$source = $this->info["dealership_location_prefix"] . "/export/" . $image['product_sku'] . "/original/" . $image["image_id"] . ".jpg";
				$overlay = $this->info["dealership_location_prefix"] . "/export/" . $image['product_sku'] . "/original/" . $image["image_id"] . ".jpg";

				if ($image["image_downloaded"] && $this->module->storage->resources->fileExists($source)) {
					$this->log("Deleting original %s" , [$source]);
					$this->module->storage->resources->delete($source);
				}

				if ($image["image_overlay"] && $this->module->storage->resources->fileExists($overlay)) {
					$this->log("Deleting overlay %s" , [$overlay]);
					$this->module->storage->resources->delete($overlay);
				}
				
				$this->log("Deleting database record.\n");
				$this->db->Query(
					"DELETE FROM %s WHERE image_id = %d",
					[
						$this->module->tables["plugin:novosteer_vehicles_export_images"],
						$image["image_id"]
					]
				);
			}
			
			$this->log("Deleted %d schedule images." , [count($images)]);
		}
		

	}
	
	
	
}
