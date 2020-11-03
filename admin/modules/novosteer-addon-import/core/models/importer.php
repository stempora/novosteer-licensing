<?php
/*
	Stempora Web Framework
	Copyright (c) 2002-2020 Stempora. 
	All rights reserved.
		web:  www.stempora.com
		mail: support@stempora.com				
*/


namespace Stembase\Modules\Novosteer_Addon_Import\Core\Models;

use \CTemplateDynamic;
use \CConfig;
use \CFile;

class Importer extends Base{

	/**
	* description
	*
	* @var type
	*
	* @access type
	*/
	var $event = null;
	

	/**
	* description
	*
	* @var type
	*
	* @access type
	*/
	var $info = [];
	/**
	* description
	*
	* @var type
	*
	* @access type
	*/
	var $form = null;

	/**
	* description
	*
	* @var type
	*
	* @access type
	*/
	var $user = null;
	
	/**
	* description
	*
	* @var type
	*
	* @access type
	*/
	var $module = null;

	/**
	* description
	*
	* @var type
	*
	* @access type
	*/
	var $db = null;


	/**
	* description
	*
	* @var type
	*
	* @access type
	*/
	var $cronJob = null;

	/**
	* description
	*
	* @var type
	*
	* @access type
	*/
	var $skuField = "";

	/**
	* description
	*
	* @var type
	*
	* @access type
	*/
	var $skus = [
		"updates"	=> [],
		"new"		=> [],
		"all"		=> [],
		"ignored"	=> [],
	];
	
	
	/**
	* description
	*
	* @var type
	*
	* @access type
	*/
	var $search = null;
	
	/**
	* description
	*
	* @param
	*
	* @return
	*
	* @access
	*/
	
	function __construct($info = null) {
		global $base , $_USER , $_SESS , $_MODULES , $site; 

		if ($info !== null) {
			$this->info = $info;
		}	

		$this->db = $site->db;

		$this->event = new Event();
		$this->map = new Map();
		$this->lock = new Locks();
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
	function setSKUField($field) {
		global $base , $_USER , $_SESS; 

		$this->skuField = $field;
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
	public function setInfo($data) {
		global $base , $_USER , $_SESS; 

		$this->info = $data;

		$this->event->setFeed($this->info["feed_id"]);
		$this->map->setFeed($this->info["feed_id"]);

		return $this;
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
	public function setForm($data) {
		global $base , $_USER , $_SESS; 

		$this->form = $data;

		return $this;
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
	public function setModule($module) {
		global $base , $_USER , $_SESS; 

		parent::setModule($module);

		$this->event->setModule($module);
		$this->lock->setModule($module);
		$this->map->setModule($module);

		$this->module = $module;

		return $this;
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
	public function setCronJob($job) {
		global $base , $_USER , $_SESS; 

		$this->cronJob = $job;

		return $this;
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
	public function setUser($data) {
		global $base , $_USER , $_SESS; 

		$this->user = $data;

		return $this;
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
	public function getInfo() {
		return $this->info;
	}


	public function runFeed() {
		global $_LANG_ID;
		$start = time();
		$this->log("Starting feed importer %s (%s)\n" , [$this->info['feed_name'] , $this->info["feed_extension"]]);

		$file = $this->getFile();

		if (!$file) {
			$this->log("Cant retrive the new feed !");
			return false;
		}		


		$items = $this->loadFeedFile($file);

		if (!is_array($items)) {
			$this->log("No information in the feed");
			return false;
		}		

		$this->runPreProcess();
		$this->processFeed($items);

		foreach ($items as $key => $item) {
			$item = $this->lowerKeys($item);			

			$this->processPreMapItem($item);
			$this->processFeedItem($item);
			$this->processPostMapItem($item);

			$this->runItem($item);			
		}

		$this->runPostProcess();

		$this->log("Finished feed importer %s (%s) after %d seconds" , [$this->info['feed_name'] , $this->info["feed_extension"], time() - $start]);
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
	function processFeedItem(&$item) {
		global $base , $_USER , $_SESS; 
	}
	


	/**
	* description  allows any alteration of the feed before being run
	*
	* @param
	*
	* @return
	*
	* @access
	*/
	public function processFeed(&$items) {
	}


	/**
	* description  loads the file, by default processes it as an array
	*
	* @param
	*
	* @return
	*
	* @access
	*/
	function loadFeedFile($file) {
		global $base , $_USER , $_SESS; 

		return $this->module->storage->tmp->LoadArray($file , true);
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
	public function getPath() {
		global $_ADMIN;

		$path_file = dirname(dirname(__file__));

		$name = explode("\\" , get_called_class());
		$file = strtolower($name[count($name)-1]);

		$path_file .= "/importer/" . $file;
						
		return $path_file;
		
	}

	

	public function getTitle() {
		return $this->info["feed_name"];
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
	public function getAdminFields() {
		global $base , $_USER , $_SESS; 

		$path = $this->getPath() . ".xml";

		if (!file_exists($path)) {
			return null;
		}

		$conf = new CConfig($path);
		return $conf->vars["importer"];
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
	public function getFile() {

		$local_file = "feed_" . $this->info["feed_id"] . ".csv";	

		switch ($this->info["feed_data_type"]) {
			//local file
			case "1":
				if ($this->info["feed_data_file"]) {

					$this->module->storage->tmp->saveStream(
						$local,
						$this->module->storage->private->readStream("novosteer/import/feeds/" . $this->info["feed_id"] . ".csv")
					);

					$this->recordHistory(
						$this->module->storage->tmp->getStream($local),
						"feed.csv"
					);

					return $local_file;
				}
				
			break;

			//remote ftp 
			case "2":


				if ($this->info["feed_data_ssl"]) {
					$conn_id = ftp_ssl_connect($this->info["feed_data_server"] , $this->info["feed_data_port"]);
				} else {				
					$conn_id = ftp_connect($this->info["feed_data_server"], $this->info["feed_data_port"]);
				}

				// login with username and password

				$login_result = ftp_login(
					$conn_id, 
					$this->info["feed_data_user"],
					$this->info["feed_data_pass"]
				);

				if (!$login_result) {
					$this->log("Cant connect to ftp");
					return null;
				}

				if ($this->info["feed_data_passive"]) {
					ftp_pasv($conn_id, true);
				}
				
				$temp = tmpfile();

				// try to download $server_file and save to $local_file
				if (ftp_fget(
					$conn_id, 
					$temp, 
					$this->info["feed_data_path"], 
					FTP_BINARY
					)) {

					$this->log("Successfully written to %s" , [$local_file]);
				} else {
					$error = error_get_last();
					$this->log("There was a problem downloading the feed: %s" , [$error["message"]]);

					return null;
				}

				// close the connection
				ftp_close($conn_id);

				fseek($temp,0);

				$this->module->storage->tmp->saveStream(
					$local_file , 
					$temp
				);


				$this->recordHistory(
					$this->module->storage->tmp->getStream($local_file),
					basename($this->info["feed_data_path"])
				);


				return 	$local_file;

			break;

			//web
			case "3":
				$client = new \GuzzleHttp\Client();
				$res = $client->request(
					"GET" , 
					$this->info["feed_data_link"]
				);

				if ($res->getStatusCode() !== 200) {
					return null;
				}
				
				$this->module->storage->tmp->saveStream(
					$local_file , 
					$res->getBody()->detach()
				);

				$this->recordHistory(
					$this->module->storage->tmp->getStream($local_file),
					basename($this->info["feed_data_path"])
				);

				return $local_file;
			break;

				
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
	function lowerKeys($info) {
		foreach ($info as $key => $val) {
			$key = str_replace(" " , "_" , $key);
			$_info[strtolower($key)] = $val;
		}

		return $_info;
		
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
	function log($data =  "", $params = [] , $suf = "\n") {
		global $base , $_USER , $_SESS; 

		if (is_object($this->cronJob)) {
			$this->cronJob->log(vsprintf($data , $params) , $suf );		
		} else {
			echo vsprintf($data , $params);
		}
		

		return $this;
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
	function updateCronJob() {
		global $base , $_USER , $_SESS; 

		if ($this->cronjob) {
			$this->cronjob->updateLog();
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
	public function processProductData(&$item) {
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
	public function updateProduct($product , $item) {
		global $base , $_USER , $_SESS , $_LANG_ID; 

		if ($this->wasUpdated("" , $this->event->getHash($item))) {

			$this->log("Updating %s..." , [$item[$this->skuField]]);

			if ($this->isLocked($product["product_id"] , Locks::LOCK_UPDATE)) {
				$this->log("\tProduct locked for any update");
				return null;
			}

			$this->removeLockedFields($product["product_id"] , $item);		

			$this->db->QueryUpdate(
				$this->module->tables["plugin:novosteer_vehicles_import"],
				array_merge(
					$product , 
					$item,
					[
						"product_date_last_update" => time()
					]
				),
				$this->db->Statement(
					"product_id = %d",
					$product["product_id"]
				)
			);

			$this->skus["updates"][] = $item[$this->skuField];
		} else {
			$this->log("No Change, skipping \n");
			$this->skus["ignored"][] = $item[$this->skuField];
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
	function getStatus() {
		global $base , $_USER , $_SESS; 

		return $this->info["feed_status"];
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

	function recordHistory($stream , $file) {
		global $base , $_USER , $_SESS; 

		$id = $this->db->QueryInsert(
			$this->module->tables["plugin:novosteer_addon_importer_history"],
			[
				"feed_id"			=> $this->info["feed_id"],
				"dealership_id"			=> $this->info["dealership_id"],
				"file_date"			=> time(),
				"file_file"			=> "1",
				"file_file_file"	=> $file
			]
		);

		$this->module->storage->private->saveStream(
			"novosteer/import/history/" . $id . ".file",
			$stream
		);

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
	function postUpdateProduct($product , $item ) {
		global $base , $_USER , $_SESS; 

		$this->event->productRecordUpdate("" , $this->event->getHash($item));
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
	function processPreMapItem(&$item) {
		global $base , $_USER , $_SESS; 

		$this->map->process($item);

		return $this;
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
	function processPostMapItem(&$item) {
		global $base , $_USER , $_SESS; 

		return $this;
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
	function isLocked($pid , $type) {
		global $base , $_USER , $_SESS , $_CONF , $_LANG_ID; 

		return $this->lock->isLocked($type);
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
		global $base , $_USER , $_SESS , $_CONF , $_LANG_ID; 

		return $this->event->productWasUpdated($scope , $hash);
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
	function removeLockedFields($pid , &$data) {
		global $base , $_USER , $_SESS , $_CONF , $_LANG_ID; 

		$fields = $this->lock->getFields();

		if (is_array($fields)) {
			foreach ($fields as $key => $field) {

				if (isset($data[$field])) {
					unset($data[$field]);
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
	public function CreateProduct($item) {
		global $base , $_USER , $_SESS , $_CONF , $_LANG_ID; 


		$item["product_sku"]	= $item[$this->skuField];
		$item["dealership_id"]	= $this->info["dealership_id"];
		$item["product_status"] = NOVOSTEER_VEHICLE_STATUS_PENDING;
		$item["feed_id"]		= $this->info["feed_id"];
		$item["product_date_create"]	= time();
		$item["product_date_last_update"] = time();

		$this->lock->setProduct(null);

		$this->log("Creating product %s ..." , [$item[$this->skuField]]);

		$item["product_id"] = $this->db->QueryInsert(
			$this->module->tables["plugin:novosteer_vehicles_import"],
			$item
		);

		$this->event->setProduct($item["product_id"]);
		$this->lock->setProduct($item["product_id"]);

		$this->log("Done");

		$this->event->setProduct($item["product_id"]);

		$this->skus["new"][] = $item[$this->skuField];

		return $item;
	}


	public function runPostProcess() {
		global $base , $_USER , $_SESS , $_CONF , $_LANG_ID; 
	}

	public function getRemoteCSV($url) {
		if (!$url) {
			return null;
		}
		
		$this->log("Downloading adjustment document...");

		$client = new \GuzzleHttp\Client();

		$res = $client->request('GET', $url);

		\CFile::Save(
			"upload/tmp/tmp_" . $this->info["feed_id"] . ".csv" , 
			$res->getBody()->getContents()				
		);


		$data = \CFile::LoadArray("upload/tmp/tmp_" . $this->info["feed_id"] . ".csv" , true);

		return $data;
	}

	public function getProduct(&$item) {

		$product = $this->db->QFetchArray(
			"SELECT * FROM %s WHERE dealership_id = %d AND product_sku LIKE '%s'",
			[
				$this->module->tables['plugin:novosteer_vehicles_import'],
				$this->info["dealership_id"],
				$item[$this->skuField]
			]
		);

		if (is_array($product)) {
			$this->event->setProduct($product["product_id"]);
			$this->lock->setProduct($product["product_id"]);
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
	function processCondition($data , $pref = " AND ") {
		global $_LANG_ID; 

		$data = explode("\n" , $data);
		$cond = [];

		if (count($data)) {
			foreach ($data as $key => $val) {

				if (!trim($val)) {
					unset($data[$key]);
				} else {

					$format = explode("|" , trim($val));

					if ($format[0][0] != "#") {

						switch ($format[1]) {
							case "in":
								$cond[] = $format[0] . " in ('" . implode("','" , explode("," , $format[2])) . "')" ;
							break;

							case "!in":
								$cond[] = $format[0] . " not in ('" . implode("','" , explode("," , $format[2])) . "')" ;
							break;

							case "!=":
								$cond[] = $this->db->Statement("%s != %d" , [$format[0] , $format[2]]);
							break;

							case ">":
								$cond[] = $this->db->Statement("%s > %d" , [$format[0] , $format[2]]);
							break;

							case "<":
								$cond[] = $this->db->Statement("%s < %d" , [$format[0] , $format[2]]);
							break;

							case "=":
								$cond[] = $this->db->Statement("%s = %d" , [$format[0] , $format[2]]);
							break;
						}				
					}
				}				
			}			
		}

		if (count($cond)) {
			return $pref . " ( " . implode(" AND " , $cond) ." ) ";
		}

		return "";		
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
	public function runItem($item) {
		global $_LANG_ID; 

		//$this->log("Queries as far: " . $this->db->queries_cnt );
		if ($item[$this->skuField]) {
			$this->log();
			$this->log("Processing %s" , [$item[$this->skuField]]);
			$this->updateCronJob();


			//check if exists a product with this sku already
			$product = $this->getProduct($item);
			$changeLog = null;

			$this->skus["all"][] = $item[$this->skuField];

			if (is_Array($product)) {
				$this->updateProduct($product , $item);
			} else {
				$product = $this->createProduct($item);
			}

			if (is_array($product)) {

				$this->postUpdateProduct($product , $item);					
			} else {
				$this->log("Ignoring");
			}
			
			
		}
	}
	
	
	public function updateProductImages($product , $data) {

		$old = $this->db->qFetchRowArray(
			"SELECT * FROM %s WHERE product_id = %d AND image_deleted = 0 and image_system = 0",
			[
				$this->module->tables['plugin:novosteer_vehicles_import_images'],
				$product["product_id"]
			]
		);

		$new = [];

		if (is_array($data)) {
			$cnt = 1;
			foreach ($data as $key => $val) {
				$new[$val] = [
					"product_id"		=> $product["product_id"],
					"image_order"		=> $cnt ++,
					"image_main"		=> $first,
					"image_source"		=> $val,
					"feed_id"			=> $this->info["feed_id"],
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
				$this->module->tables["plugin:novosteer_vehicles_import_images"],
				[ "image_deleted" => "1"] ,
				$this->db->Statement("image_system = 0 AND image_id in (%s)" , [implode("," , $ids)])
			);
		}

		if (is_array($new) && count($new)) {
			$this->db->QueryInsertMulti(
				$this->module->tables["plugin:novosteer_vehicles_import_images"],
				$new
			);
		}	
		
		$this->db->QueryUpdateByID(
			$this->module->tables["plugin:novosteer_vehicles_import"],
			[ "images" => $cnt],
			$product["product_id"]
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
	function deleteProduct($sku) {
		global $_LANG_ID; 

		$this->log("Deleting product %s" , [$sku]);
		$this->log("\tDeleting database records");
		$this->db->Query(
			"DELETE FROM %s WHERE dealership_id=%d AND product_sku LIKE '%s'",
			[
				$this->module->tables["plugin:novosteer_vehicles_import"],
				$this->info["dealership_id"],
				$sku
			]
		);

		$this->log("\tDeleting images & resources");
		$this->module->storage->getLocation($this->info["dealership_location"])->deleteDirectoryRecursive(
			$this->info["dealership_location_prefix"] . "/import/{$sku}/"
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
	function getVehicleByID($id) {
		global $base , $_USER , $_SESS , $_CONF , $_LANG_ID; 

		//get all products that are not published 
		return $this->db->QFetchArray(
			"SELECT * FROM 
				%s as vehicles 
			INNER JOIN 
				%s as brands
				ON
					vehicles.brand_id = brands.brand_id
			INNER JOIN 
				%s as models 
				ON 
					vehicles.model_id = models.model_id 
			LEFT JOIN 
				%s as trims
				ON 
					vehicles.trim_id = trims.trim_id
		
			WHERE 
				product_id = %d
			",
			[
				$this->module->tables["plugin:novosteer_vehicles_import"],
				$this->module->tables["plugin:novosteer_addon_autobrands_brands"],
				$this->module->tables["plugin:novosteer_addon_autobrands_models"],
				$this->module->tables["plugin:novosteer_addon_autobrands_trims"],
				$id
			]
		);

	}


	public function runPreProcess() {
	}

	
}