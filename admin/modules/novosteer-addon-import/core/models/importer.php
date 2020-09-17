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
	function setSearch($search , $clone = false) {
		global $base , $_USER , $_SESS; 

		if ($clone) {
			$this->search = clone $search;
		} else {
			$this->search = $search;
		}


		$this->search->disableHooks();
		$this->search->resetConditions();
	
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
		$this->log("Starting feed importer %s \n" , [$this->info['feed_name']]);

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

			$data = null;

			//$this->log("Queries as far: " . $this->db->queries_cnt );
			if ($item[$this->skuField]) {
				$this->log();
				$this->log("Processing %s" , [$item[$this->skuField]]);
				$this->updateCronJob();

				$data = null;


				//check if exists a product with this sku already
				$product = $this->getProduct($item);
				$changeLog = null;

				$this->skus["all"][] = $item[$this->skuField];

				if (is_Array($product)) {
					$this->event->setProduct($product["product_id"]);
					$this->lock->setProduct($product["product_id"]);


					if ($this->info['feed_duplicates'] == '2') {
						$this->log("Checking %s for changes..." , [$item[$this->skuField]]);

						if ($this->wasUpdated("" , $this->event->getHash($item))) {
							$this->updateProduct($product , $item , $data);
						} else {
							$this->log("No Change, skipping \n");
							$this->skus["ignored"][] = $item[$this->skuField];
						}							
					} else {
						$this->skus["ignored"][] = $item[$this->skuField];
					}
					
				} else {
					$product = $this->createNewProduct($item , $data);
				}

				if (is_array($product)) {
					$this->event->productRecordUpdate("" , $this->event->getHash($item));

					$this->postUpdateProduct($product , $data , $item);					
				} else {
					$this->log("Ignoring");
				}
				
				
			}
			
		}

		$this->processMissing();
		$this->runPostProcess();

		$this->log("Finished feed importer %s after %d seconds" , [$this->info['feed_name'] , time() - $start]);
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

		return CFile::LoadArray($file , true);
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

		$local_file = "upload/tmp/feed_" . $this->info["feed_id"] . ".csv";	

		if (file_exists($local_file)) {
			unlink($local_file);
		}
		

		switch ($this->info["feed_data_type"]) {
			//local file
			case "1":
				if ($this->info["feed_data_file"]) {

					CFile::Copy(
						"upload/novosteer/import/feeds/" . $this->info["feed_id"] . ".csv",
						$local_file
					);

					$this->recordHistory(
						"upload/novosteer/import/feeds/" . $this->info["feed_id"] . ".csv",
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
				
				// try to download $server_file and save to $local_file
				if (ftp_get(
					$conn_id, 
					$local_file, 
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

				$this->recordHistory(
					$local_file,
					basename($this->info["feed_data_path"])
				);


				return 	$local_file;

			break;

			//web
			case "3":
				$data = CHTTP::newInstance()
					->Get($this->info["feed_data_link"])
					->Raw();

				CFile::Save(
					$local_file,
					$data						
				);

				$this->recordHistory(
					$local_file,
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
	function processProductData(&$data , $item) {
		global $base , $_USER , $_SESS; 

		if (is_Array($data) && is_array($item)) {
			$data = array_merge(
				$data , 
				$item
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
	public function updateProduct(&$product , &$item , &$data) {
		global $base , $_USER , $_SESS , $_LANG_ID; 

		$this->log("Updating %s..." , [$item[$this->skuField]]);

		if ($this->isLocked($product["product_id"] , Locks::LOCK_UPDATE)) {
			$this->log("\tProduct locked for any update");
			return null;
		}

		$newData = [
			//add the field set
			"product_id"	=> $product["product_id"],
			"product_sku"	=> $item[$this->skuField]
		];

		$this->processProductData($newData , $item);
		$this->removeLockedFields($product["product_id"] , $newData);

		$compare = [
			"msrp","sellingprice","bookvalue" , "misc_price1" , "misc_price2" , "misc_price3"
		];

		//update only if i have any differences
		if (count($newData) > 3) {

			//compare the prices in order to see if we need to recalculate anything		
			foreach ($compare as $k => $v) {
				if (isset($newData[$v]) && ($product[$v] != $newData[$v])) {
					$newData["product_status"] = NOVOSTEER_VEHICLE_STATUS_PENDING;
				}				
			}
			

			$this->db->QueryUpdate(
				$this->module->tables["plugin:novosteer_vehicles"],
				$newData,
				$this->db->Statement(
					"product_id = %d",
					$product["product_id"]
				)
			);

			$this->skus["updates"][] = $item[$this->skuField];
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
	function CreateProduct($data) {
		global $base , $_USER , $_SESS; 

		$data["product_id"] = $this->db->QueryInsert(
			$this->module->tables["plugin:novosteer_vehicles"],
			$data
		);

		return $data;

		
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
	function recordHistory($path , $file) {
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

		CFile::Copy(
			$path , 
			"upload/novosteer/import/history/" . $id . ".file"
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
	function postUpdateProduct($product , $data, $item ) {
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
	function processMissing() {
		global $base , $_USER , $_SESS; 

		if ($this->info["feed_missing"] < 2) {
			return true;
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
	function missingProductDelete($product) {
		global $base , $_USER , $_SESS; 

		$this->log("Deleting missing product %s ..." , $product["product_sku"] , "");

		if ($this->isLocked($product["product_id"] , Locks::LOCK_DELETE)) {
			$this->log("\n\tProduct locked for deletion");
			return false;
		}

		$this->db->Query(
			"DELETE FROM %s WHERE product_id = %d",
			[
				$this->module->tables["plugin:novosteer_vehicles"],
				$product["product_id"]
			]
		);

		$this->log("done");
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

		return true;

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
	public function createNewProduct(&$item , &$data) {
		global $base , $_USER , $_SESS , $_CONF , $_LANG_ID; 


		$data = [
			"product_sku"	=> $item[$this->skuField],
			"dealership_id"	=> $this->info["dealership_id"],
			"product_status" => NOVOSTEER_VEHICLE_STATUS_PENDING,
		];

		$this->lock->setProduct(null);
		$this->processProductData($data , $item);


		$this->log("Creating product %s ..." , [$item[$this->skuField]]);
		$product = $this->createProduct($data);
		$this->log("Done");

		$this->event->setProduct($product["product_id"]);

		$this->skus["new"][] = $item[$this->skuField];

		return $product;
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

		return $this->db->QFetchArray(
			"SELECT * FROM %s WHERE dealership_id = %d AND product_sku LIKE '%s'",
			[
				$this->module->tables['plugin:novosteer_vehicles'],
				$this->info["dealership_id"],
				$item[$this->skuField]
			]
		);
	}
	
}