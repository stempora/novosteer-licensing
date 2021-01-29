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
use \Stembase\Modules\Novosteer_Dealerships\Core\Models\Discounts;
use \CHeaders;



class FinancingFeed extends Importer implements ImporterInterface{
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

		return [];		
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
	public function wasUpdated($scope , $hash) {
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
	function runPreProcess() {
		global $_LANG_ID; 

	
		$this->setSKUField("product_sku");

		$this->module->plugins["novosteer-dealerships"]->__init(); 

		$client = \Stembase\Lib\File\GoogleSheets::create()
			->setCredentialsString($this->module->plugins["novosteer-dealerships"]->_s("set_keyfile"))
			->setSheetId($this->info["settings"]["set_sheet_id"])
			->setWorksheet($this->info["settings"]["set_worksheet"]);


		$rates = $client->getAllvalues();

		foreach ($rates as $key => $val) {
			$found = false;

			foreach ($val as $k => $v) {
				if (trim($v)) {
					$found = true;
				}				
			}

			if (!$found) {
				unset($rates[$key]);
			}				
		}
		
		$data = json_encode([
			"rates"	=> $rates
		]);


		$this->log("Saving financial rules");
		$this->module->storage->private->save(
			"novosteer/inventory/" . $this->info["feed_id"] . ".json",
			$data
		);

		if ($_GET["ping"] == "true") {
			$hash = time();
		} else {	
			$hash = md5($data);

			$this->db->QueryUpdateByID(
				$this->module->tables["plugin:novosteer_addon_importer_feeds"],
				["feed_reserved" => $hash],
				$this->info["feed_id"]
			);
		}

		if ($hash != $this->info["feed_reserved"]) {
			$this->log("Pinging dealer website to request the new rates");
			$this->pingDealer();

			$this->log("Done");

		} else {
			$this->log("No changes to the rates table.");
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
	function runWeb() {
		global $_LANG_ID ,$site; 


		$headers = getAllHeaders();

		if (!(isset($headers["Novosteer-Authorization"]) && $headers["Novosteer-Authorization"] == $this->info["settings"]["set_request_key"])) {
			return $site->plugins["redirects"]->ErrorPage("404" , true);
		}

		\Cheaders::newInstance()
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
					"action"	=> "cron-financial",
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
