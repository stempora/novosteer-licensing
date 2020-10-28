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


class ImagesOverlays extends Export implements ExportInterface{

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

		$images = $this->db->QFetchRowArray(
			"SELECT products.product_id, product_sku , image_id , image_url FROM %s as images
				INNER JOIN 
					%s as products
				ON 
					images.product_id = products.product_id
			WHERE
				image_alert = 0 AND 
				dealership_id = %d AND
				image_url_overlay  = 0
			",
			[
				$this->module->tables["plugin:novosteer_vehicles_export_images"],
				$this->module->tables["plugin:novosteer_vehicles_export"],
				$this->info["dealership_id"]
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

		$source = $this->info["dealership_location_prefix"] . "/export/{$item['product_sku']}/original/" . $item["image_id"] . ".jpg";
		$destination = $this->info["dealership_location_prefix"] . "/export/{$item['product_sku']}/final/" . $item["image_id"] . ".jpg";

		$this->log("Processing image %s" , $source);

		if (!$this->module->storage->resources->fileExists($source)) {
			$this->log("Missing File!");

			$this->db->QueryUpdateByID(
				$this->module->tables["plugin:novosteer_vehicles_export_images"],
				["image_alert" => "1"],
				$item["image_id"]
			);

		}
		

		$image = $this->image->make(
			$this->module->storage->resources->get(
				$source
			)
		)->resize($this->info["settings"]["set_image_width"] , null , function($constraint) {
			$constraint->aspectRatio();
		});

		if (is_array($this->watermarks)) {
			foreach ($this->watermarks as $key => $watermark) {
				$image->insert(
					$watermark["content"] , 
					$watermak["position"] , 
					(int)$watermark["offset_x"] , 
					(int)$watermark["offset_y"]
				);
			}			
		}

		//save the image in final		
		$this->module->storage->cache2->saveStream(
			$destination, 
			$image->stream("jpg", $this->info["settings"]["set_image_quality"])->detach()
		);		

		$this->db->QueryUpdateByID(
			$this->module->tables["plugin:novosteer_vehicles_export_images"],
			["image_url_overlay" => "1"],
			$item["image_id"]
		);

		$this->log("Done");

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

		for ($i = 1; $i <= 4; $i++) {
			if ($this->info["settings"]["set_w{$i}"]) {

				$this->watermarks[] = [
					"content"	=> $this->module->storage->public->get("novosteer/export/overlays/w{$i}_" . $this->info["feed_id"] . "." . $this->info["settings"]["set_w{$i}_type"]),
					"position"	=> $this->info["settings"]["set_w{$i}_position"],
					"offset_x"	=> $this->info["settings"]["set_w{$i}_offset_x"],
					"offset_y"	=> $this->info["settings"]["set_w{$i}_offset_y"],
				];
			}
			
		}
	}
	
	

}
