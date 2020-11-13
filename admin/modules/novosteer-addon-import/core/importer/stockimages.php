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
use \Intervention\Image\ImageManager;

class StockImages extends Importer implements ImporterInterface{
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
	function runItem($item) {
		global $_LANG_ID; 

		$image = $this->module->plugins["novosteer-addon-autobrands"]->getVehicleByYearModelTrimColor(
			$item["year"],
			$item["model_id"],
			$item["trim_id"],
			$item["color_id"],
			false
		);

		if (!is_array($image)) {
			return null;
		}

		$source = $image["image"];

		$this->log("Preparing stock image %s" , [$source]);

		try {
	
			//force white background, later heere we can add the background of the dealership
			$base = $this->image->canvas( 
				$this->info["settings"]["set_image_width"] , 
				$this->info["settings"]["set_image_height"] , 
				"#ffffff"
			);

			//resize the stock image 
			$image = $this->image->make($source)
				->resize(
					$this->info["settings"]["set_image_width"] , 
					$this->info["settings"]["set_image_height"] , 
					function($constraint) {
						$constraint->aspectRatio();
						//$constraint->upsize();
					}
				);

			$base->insert($image , "center");

			//create a new image record for this product
			$id = $this->db->QueryInsert(
				$this->module->tables["plugin:novosteer_vehicles_import_images"],
				[
					"image_order"		=> 1,
					"feed_id"			=> $this->info["feed_id"],
					"product_id"		=> $item["product_id"],
					"image_source"		=> $source , 
					"image_downloaded"	=> 1,
				]
			);

			$destination = $this->info["dealership_location_prefix"] . "/inventory/" . $item['product_sku'] ."/" . $id . ".jpg";

			$this->module->storage->getLocation($this->info["dealership_location"])->saveStream(
				$destination,
				$base->stream("jpg", $this->info["settings"]["set_image_quality"])->detach()
			);

			$this->log("Processing successfuly\n");

		} catch ( \Exception $e ) {
			$this->log("Error: %s\n" , $e->getMessage());
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
	function postUpdateProduct($product , $item ) {
		global $base , $_USER , $_SESS; 
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
	function getFile($default = null) {
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


		$images = $this->db->QFetchRowArray(
			"SELECT * FROM 
					%s as products
				INNER JOIN 
					%s as brands
				ON
					products.brand_id = brands.brand_id 
				INNER JOIN 
					%s as models
				ON 
					products.model_id = models.model_id 
			WHERE				
				dealership_id = %d AND
				products.product_id NOT IN (
					SELECT product_id FROM %s WHERE image_deleted = 0
				)

				:cond				
			",
			[
				$this->module->tables["plugin:novosteer_vehicles_import"],
				$this->module->tables["plugin:novosteer_addon_autobrands_brands"],
				$this->module->tables["plugin:novosteer_addon_autobrands_models"],
				$this->info["dealership_id"],
				$this->module->tables["plugin:novosteer_vehicles_import_images"],
			],

			[ 
				":cond" => $this->processCondition($this->info["settings"]["set_condition"]) 
			]
		);

		return $images;
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
		global $_LANG_ID; 

		$this->image = new ImageManager(array('driver' => "gd"));
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
	function runOnDelete() {
		global $_LANG_ID; 

		$this->db->QueryUpdate(
			$this->module->tables["plugin:novosteer_vehicles_import_images"],
			["image_deleted" => "1"],
			$this->db->Statement("feed_id=%d" , $this->feed["feed_id"])
		);

		//decrease the number of images for products
		$this->db->Query(
			"UPDATE %s set images = images - 1 where product_id in (select product_id FROM %s WHERE feed_id = %d)",
			[
				$this->module->tables["plugin:novosteer_vehicles_import"],
				$this->module->tables["plugin:novosteer_vehicles_import_images"],
				$this->feed["feed_id"]				
			]
		);
	}
	
		
} 

