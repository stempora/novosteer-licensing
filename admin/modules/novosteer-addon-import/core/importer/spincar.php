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
use \CTemplateStatic;
use \CFile;

class Spincar extends Importer implements ImporterInterface{
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
	function updateProduct(&$product , &$data , &$item) {
		global $base , $_USER , $_SESS; 


		if ($this->isLocked($product["product_id"] , Locks::LOCK_UPDATE)) {
			return null;
		}


		$images = explode("|" , $data["photo_url_list"]);

		if (count($images)) {
			$data["main_image"] = $images[0];
			$data["gallery"] = $images;
		}


		if ($data["main_image"]) {
			if ($this->wasUpdated("image" , $this->event->getHash($data["main_image"]))) {

				$this->event->productRecordUpdate("image" , $this->event->getHash($data["main_image"]));
				$this->log("Adding image to product: %s" , $data["main_image"] );

				$this->plugins["novosteer-addon-vehicles"]->deleteMainImage($product["product_id"]);

				$this->plugins["novosteer-addon-vehicles"]->addImage(
					$product["product_id"],
					[
						"url"	=> $data["main_image"],
						"main"	=> true
					]
				);

			}
		}
		
		
		if ($data["gallery"]) {
			if ($this->wasUpdated("galery" , $this->event->getHash($data["gallery"]))) {
				$this->event->productRecordUpdate("gallery" , $this->event->getHash($data["gallery"]));

				$this->plugins["novosteer-addon-vehicles"]->deleteGalleryImages($product["product_id"]);

				foreach ($data["gallery"] as $key => $val) {

					$this->log("Adding gallery to product: %s" , $val);

					$this->plugins["novosteer-addon-vehicles"]->addImage(
						$product["product_id"],
						[
							"url"	=> $val,
							"main"	=> false
						]
					);

				}
				

			} 
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
	function runPreProcess() {
		$this->setSKUField("vin");

		//force to update the exiting
		$this->info['feed_duplicates'] = '2';
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
	function createNewProduct(&$item , &$data) {
		global $base , $_USER , $_SESS , $_CONF , $_LANG_ID; 
		
		//disable new creation of product
		return null;
	}
	
}
