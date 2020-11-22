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

class BannersFeed extends Importer {

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
		global $_LANG_ID , $_CONF; 

		$banners = $this->db->QFetchRowArray(
			"SELECT 
				* 
			FROM 
				%s 
			WHERE 
				banner_status = 1 AND 
				find_in_set(%d , banner_dealerships )
			ORDER BY banner_order ASC",
			[
				$this->module->tables["plugin:novosteer_addon_banners"],
				$this->info["dealership_id"]
			]
		);

		//try the global banners
		if (!is_array($banners)) {
			$banners = $this->db->QFetchRowArray(
				"SELECT 
					* 
				FROM 
					%s 
				WHERE 
					banner_status = 1 AND 
					banner_dealerships = ''
				ORDER BY banner_order ASC",
				[
					$this->module->tables["plugin:novosteer_addon_banners"],
					$this->info["dealership_id"]
				]
			);
		}
		
		$data = [];

		if (is_array($banners)) {
			foreach ($banners as $key => $val) {

				$banner = [
					"code"	=> $val["banner_code"],
					"type"	=> $val["banner_type"] == 1 ? "small" : "wide",
					"image"	=> $this->module->storage->public->getUrl("novosteer/banners/" . $val["banner_id"] . "." . $val["banner_image_type"] , $val["banner_image_date"]),
					"years"	=> explode("," , $val["banner_years"]),
					"brands"	=> $val["banner_brands"] 
						? $this->db->Linear(
								$this->db->QfetchRowArray(
									"SELECT brand_name FROM %s WHERE brand_id in (%s)",
									[
										$this->module->tables["plugin:novosteer_addon_autobrands_brands"],
										$val["banner_brands"]
									]
								)
							)
						: null,
					"models"	=> $val["banner_models"] 
						? 	$this->db->Linear(
								$this->db->QfetchRowArray(
									"SELECT 
										concat(brand_name , '|', model_name) 
									FROM 
										%s as models 
									INNER JOIN 
										%s as brands 
									ON 
										models.brand_id = brands.brand_id 
									WHERE 
										models.model_id in (%s)",
									[
										$this->module->tables["plugin:novosteer_addon_autobrands_models"],
										$this->module->tables["plugin:novosteer_addon_autobrands_brands"],
										$val["banner_models"]
									]
								)
							)
						: null,

					"trims"	=> $val["banner_trims"] 
						? 	$this->db->Linear(
								$this->db->QfetchRowArray(
									"SELECT 
										concat(brand_name , '|', trim_name) 
									FROM 
										%s as trims 
									INNER JOIN 
										%s as brands 
									ON 
										trims.brand_id = brands.brand_id 
									WHERE 
										trims.trim_id in (%s)",
									[
										$this->module->tables["plugin:novosteer_addon_autobrands_trims"],
										$this->module->tables["plugin:novosteer_addon_autobrands_brands"],
										$val["banner_trims"]
									]
								)
							)
						: null,
				];

				$data[] = $banner;
			}
		}

		$data = json_encode([
			"banners"	=> $data
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

			$this->cronJob->removeLog();
		}		

		
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
					"action"	=> "cron-banners",
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
