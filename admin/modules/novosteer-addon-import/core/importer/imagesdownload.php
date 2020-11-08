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



class ImagesDownload extends Importer implements ImporterInterface{

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
				image_downloaded = 0 AND
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

		$source = $item["image_source"];
		$destination = $this->info["dealership_location_prefix"] . "/inventory/" . $item['product_sku'] ."/" . $item["image_id"] . ".jpg";

		$this->log("Downloading image %s" , [$source]);

		try {
			$image = $this->image->make($source)
				->resize(
					$this->info["settings"]["set_image_width"] , 
					null , 
					function($constraint) {
						$constraint->aspectRatio();
					}
				);
			
			$this->module->storage->getLocation($this->info["dealership_location"])->saveStream(
				$destination, 
				$image->stream("jpg", $this->info["settings"]["set_image_quality"])->detach()
			);		

			$this->db->QueryUpdateByID(
				$this->module->tables["plugin:novosteer_vehicles_import_images"],
				[
					"image_error_download"	=> "",
					"image_downloaded"		=> 1,
				],
				$item["image_id"]
			);

			$this->log("Download Successfuly\n");
		} catch ( \Exception $e ) {

			$this->db->QueryUpdateByID(
				$this->module->tables["plugin:novosteer_vehicles_import_images"],
				[
					"image_error_download"	=> $e->getMessage()
				],
				$item["image_id"]
			);


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
	function runPreprocess() {
		global $_LANG_ID; 

		$this->image = new ImageManager(array('driver' => "gd"));

	}
	
	

}
