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

class ModelsFeed extends Importer {

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

		if (!$this->info["settings"]["set_brands"]) {
			return null;
		}

		$items = $this->db->QFetchRowArray(
			"SELECT * FROM 
				%s as vehicles
			INNER JOIN 
				%s as models 
				ON
					vehicles.model_id = models.model_id
			INNER JOIN 
				%s as brands
				ON 
					brands.brand_id = models.brand_id 
			INNER JOIN 
				%s as trims
				ON 
					trims.trim_id = vehicles.trim_id 
			INNER JOIN 
				%s as types
				ON
					models.type_id = types.type_id
			WHERE
				brands.brand_id IN (%s) AND 
				vehicles.vehicle_status = 1
			ORDER BY 
				vehicle_default DESC,
				brand_order ASC,
				model_order ASC,
				trim_order ASC,
				vehicle_id DESC
			",
			[
				$this->module->tables["plugin:novosteer_addon_autobrands_vehicles"],
				$this->module->tables["plugin:novosteer_addon_autobrands_models"],
				$this->module->tables["plugin:novosteer_addon_autobrands_brands"],
				$this->module->tables["plugin:novosteer_addon_autobrands_trims"],
				$this->module->tables["plugin:novosteer_addon_autobrands_types"],
				$this->info["settings"]["set_brands"]
			]
		);

		if (!is_array($items)) {
			return null;
		}

		$menu = [];
		foreach ($items as $key => $val) {

			$hash = $val["vehicle_year"] . "-" . $val["brand_name"] . "-" . $val["model_name"] . "-" . $val["trim_name"];

			if (!is_array($menu[$hash])) {
				$menu[$hash] = [
					"year"	=> $val["vehicle_year"],
					"brand"	=> $val["brand_name"],
					"model"	=> $val["model_name"],
					"trim"	=> $val["trim_name"],
					"type"	=> $val["type_name"],
					"image"	=> $this->module->storage->public->getUrl("vehicles/stock/" . $val["vehicle_id"] . "." . $val["vehicle_image_type"] , $val["vehicle_image_date"])
				];
			}			
		}

		$data = json_encode([
			"models"	=> $menu
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
					"action"	=> "cron-models",
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
