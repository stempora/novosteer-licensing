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
use \Stembase\Modules\Novosteer_Addon_Import\Core\Interfaces\ImporterInterface;
use \Intervention\Image\ImageManager; 


class ImagesOverlays extends Importer implements ImporterInterface{

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
	function getFile($default = null) {
		global $_LANG_ID; 

		//just ro return something
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
			"SELECT products.product_id, product_sku , images.* FROM %s as images

				INNER JOIN 
					%s as products
				ON 
					images.product_id = products.product_id

				INNER JOIN 
					%s as brands
				ON
					products.brand_id = brands.brand_id 
				INNER JOIN 
					%s as models
				ON 
					products.model_id = models.model_id 
			WHERE				
				image_alert = 0 AND 
				dealership_id = %d AND
				image_overlay = 0 AND
				image_deleted = 0

				:cond				
			",
			[
				$this->module->tables["plugin:novosteer_vehicles_import_images"],
				$this->module->tables["plugin:novosteer_vehicles_import"],
				$this->module->tables["plugin:novosteer_addon_autobrands_brands"],
				$this->module->tables["plugin:novosteer_addon_autobrands_models"],
				$this->info["dealership_id"]
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
	function runItem($item) {
		global $_LANG_ID; 
		
		if ($item["image_downloaded"]) {
			$source = $this->info["dealership_location_prefix"] . "/inventory/" . $item['product_sku'] . "/" . $item["image_id"] . ".jpg";
		} else {
			$source = $item["image_source"];
		}


		$destination = $this->info["dealership_location_prefix"] . "/inventory/" . $item['product_sku'] . "/over_" . $item["image_id"] . ".jpg";

		$this->log("Processing image %s" , $source);
	
		try {

			$image = $this->image->make(
					$item["image_downloaded"] 
						? $this->module->storage->getLocation($this->info["dealership_location"])->get($source)
						: $source
				)
				->resize(
					$this->info["settings"]["set_image_width"] , 
					null , 
					function($constraint) {
						$constraint->aspectRatio();
					}
				);

			if (is_array($this->watermarks)) {
				foreach ($this->watermarks as $key => $watermark) {
					$image->insert(
						$watermark["content"] , 
						$watermark["position"] , 
						(int)$watermark["offset_x"] , 
						(int)$watermark["offset_y"]
					);
				}			
			}

			//save the image in final		
			$this->module->storage->getLocation($this->info["dealership_location"])->saveStream(
				$destination, 
				$image->stream("jpg", $this->info["settings"]["set_image_quality"])->detach()
			);		

			$this->db->QueryUpdateByID(
				$this->module->tables["plugin:novosteer_vehicles_import_images"],
				["image_overlay" => $this->info["feed_id"]],
				$item["image_id"]
			);

			$this->log("Process Successfuly \n");

		} catch (\Exception $e ) {
			$this->log("Error: %s\n" ,$e->getMessage() );

			$this->db->QueryUpdateByID(
				$this->module->tables["plugin:novosteer_vehicles_import_images"],
				[
					"image_error_overlay" => $e->getMessage()
				],
				$item["image_id"]
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
	function runPreprocess() {

		$this->deleteOldImages();

		$this->image = new ImageManager(array('driver' => "gd"));

		for ($i = 1; $i <= 4; $i++) {
			if ($this->info["settings"]["set_w{$i}"]) {

				if ($this->module->storage->public->fileExists("novosteer/import/overlays/w{$i}_" . $this->info["feed_id"] . "." . $this->info["settings"]["set_w{$i}_type"])) {				
					$this->watermarks[] = [
						"content"	=> $this->module->storage->public->get("novosteer/import/overlays/w{$i}_" . $this->info["feed_id"] . "." . $this->info["settings"]["set_w{$i}_type"]),
						"position"	=> $this->info["settings"]["set_w{$i}_position"],
						"offset_x"	=> $this->info["settings"]["set_w{$i}_offset_x"],
						"offset_y"	=> $this->info["settings"]["set_w{$i}_offset_y"],
					];
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
	function deleteOldImages() {
		global $_LANG_ID; 

		$images = $this->db->QFetchRowArray(
			"SELECT products.product_id, product_sku , images.* FROM %s as images

				INNER JOIN 
					%s as products
				ON 
					images.product_id = products.product_id				
			WHERE				
				dealership_id = %d AND
				image_overlay = -1 AND
				image_deleted = 0
			",
			[
				$this->module->tables["plugin:novosteer_vehicles_import_images"],
				$this->module->tables["plugin:novosteer_vehicles_import"],
				$this->info["dealership_id"]
			],
		);

		if (is_array($images)) {
			$this->log("Deleting old images");

			foreach ($images as $key => $image) {

				$destination = $this->info["dealership_location_prefix"] . "/inventory/" . $image['product_sku'] . "/over_" . $image["image_id"] . ".jpg";

				if ($this->module->storage->getLocation($this->info["dealership_location"])->fileExists($destination)) {
					$this->log("Deleting %s" , $destination);
					$this->module->storage->getLocation($this->info["dealership_location"])->delete($destination);
				}		
				
				$this->db->QueryUpdateByID(
					$this->module->tables["plugin:novosteer_vehicles_import_images"],
					[
						"image_overlay"	=> -1
					],
					$image["image_id"]
				);
			}

			$this->log("Done");			
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
	function runOnDelete() {
		global $_LANG_ID;
		
		//mark all images as not havin an overlay, the phisical images will be deleted on product deletion
		$this->db->QueryUpdate(
			$this->module->tables["plugin:novosteer_vehicles_export_images"],
			["image_overlay" => "-1"],
			$this->db->Statement("image_overlay=%d" , $this->info["feed_id"])
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

		$compare = [	
			"set_image_width" , 
			"set_w1_date" ,"set_w1_position" , "set_w1_offset_x" , "set_w1_offset_y",
			"set_w2_date" ,"set_w2_position" , "set_w2_offset_x" , "set_w2_offset_y",
			"set_w3_date" ,"set_w3_position" , "set_w3_offset_x" , "set_w3_offset_y",
			"set_w4_date" ,"set_w4_position" , "set_w4_offset_x" , "set_w4_offset_y",
		];

		//reset the overlays 
		foreach ($compare as $key => $val) {
			if ($this->info["settings"][$val] != $old["settings"][$val]) {
				$this->db->QueryUpdate(
					$this->module->tables["plugin:novosteer_vehicles_export_images"],
					["image_overlay" => "-1"],
					$this->db->Statement("image_overlay=%d" , $this->info["feed_id"])
				);

				return true;
			}
			
		}	
	}
	

}
