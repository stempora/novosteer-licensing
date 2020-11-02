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
use \Intervention\Image\ImageManager;


class ImagesInject extends Export implements ExportInterface{

	/**
	* description
	*
	* @var type
	*
	* @access type
	*/
	var $image = null;

	/**
	* description
	*
	* @var type
	*
	* @access type
	*/
	var $watermaks = null;
	
	
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

		//just ro return something
		return "xx";
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
			"SELECT product_id , product_sku FROM %s as products 
				WHERE 
					dealership_id = %d AND 
					product_id NOT IN (
						SELECT images.product_id from %s as images
						WHERE 
							image_overlay = %d 
					)

					:cond
			",
			[
				$this->module->tables["plugin:novosteer_vehicles_export"],
				$this->info["dealership_id"],
				$this->module->tables["plugin:novosteer_vehicles_export_images"],
				$this->info["feed_id"]
					
			],
			[ 
				":cond" => $this->processCondition($this->info["settings"]["set_condition"]) 
			]
		);
	

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
	function runItem($item) {
		global $_LANG_ID; 

		$this->log("Processing vehicle %s" , [$item["product_sku"]]);

		$image = [
			"product_id" => $item["product_id"],
			"image_last_update"	=> time(),
			"image_source"		=> $this->module->storage->getLocation($this->info["dealership_location"])->getUrl($this->info["dealership_location_prefix"] . "/global/image_" . $this->info["feed_id"] . ".jpg"),
			"image_overlay_url"	=> $this->module->storage->getLocation($this->info["dealership_location"])->getUrl($this->info["dealership_location_prefix"] . "/global/image_" . $this->info["feed_id"] . ".jpg"),
			"image_overlay"		=> $this->info["feed_id"],
			"image_system"		=> "1", //prevent deletion when updating product images list
		];

		$imagesCount = $this->db->QFetchArray(
			"SELECT count(image_id) as image FROM %s WHERE product_id = %d AND image_deleted = 0",
			[
				$this->module->tables["plugin:novosteer_vehicles_export_images"],
				$item["product_id"]
			]
		)["cnt"];

		switch ($this->info["settings"]["set_position"]) {
			//first
			case "1":
				$image["image_order"] = 2;
			break;

			//middle
			case "2":
				$image["image_order"] = max(2,round($imagesCount/2));
			break;
		
			//last
			case "3":
				$image["image_order"] = $imageCount + 1;
			break;
		}

		//increase the order
		$this->db->Query(
			"UPDATE %s SET image_order = image_order + 1 WHERE image_order >= %d AND product_id = %d",
			[
				$this->module->tables["plugin:novosteer_vehicles_export_images"],
				$image["image_order"],
				$item["product_id"]				
			]
		);

		//insert the new image
		$id = $this->db->QueryInsert(
			$this->module->tables["plugin:novosteer_vehicles_export_images"],
			$image
		);

		//increase the number of images for products
		$this->db->Query(
			"UPDATE %s set images = images + 1 where product_id = %d",
			[
				$this->module->tables["plugin:novosteer_vehicles_export"],
				$item["product_id"]				
			]
		);


		$this->log("Process Successfuly \n");

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
	function runPreprocess() {
		//copy the image to destination
		$this->module->storage->getLocation($this->info["dealership_location"])->saveStream(
			$this->info["dealership_location_prefix"] . "/global/image_" . $this->info["feed_id"] . ".jpg",
			$this->module->storage->public->getStream("novosteer/export/inject/image_" . $this->info["feed_id"] . ".jpg")
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
	function runOnDelete() {
		global $_LANG_ID;
		
		$this->db->QueryUpdate(
			["image_deleted" => "1"],
			$this->db->Statement("image_overlay=%d" , $this->feed["feed_id"])
		);

		//decrease the number of images for products
		$this->db->Query(
			"UPDATE %s set images = images - 1 where product_id in (select product_id FROM %s WHERE image_overlay = %d)",
			[
				$this->module->tables["plugin:novosteer_vehicles_export"],
				$this->module->tables["plugin:novosteer_vehicles_export_images"],
				$this->feed["feed_id"]				
			]
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
	function runOnUpdate($old = null) {
		global $_LANG_ID; 		

		if ($this->info["feed_settings"] = $old["feed_settings"]) {
			return true;
		}

		$dealership = $this->module->plugins["novosteer-dealerships"]->getDealershipByID($this->info["dealership_id"]);

		$path = $dealership["dealership_location_prefix"] . "/global/image_" . $this->info["feed_id"] . ".jpg";

		$this->module->storage->getLocation($dealership["dealership_location"])->saveStream(
			$path,
			$this->module->storage->public->getStream("novosteer/export/inject/image_" . $this->info["feed_id"] . ".jpg")
		);		

		$this->db->QueryUpdate(
			$this->tables["plugin:novosteer_vehicles_export_images"],
			[
				"image_overlay_url" => $this->module->storage->getLocation($dealership["dealership_location"])->getUrl($path,$this->info["settings"]["set_image_date"])
			],
			$this->db->Statement("image_overlay=%d" , $this->feed["feed_id"])
		);
	}

	

}
