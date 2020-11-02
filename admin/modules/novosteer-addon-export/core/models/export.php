<?php
/*
	Stempora Web Framework
	Copyright (c) 2002-2020 Stempora. 
	All rights reserved.
		web:  www.stempora.com
		mail: support@stempora.com				
*/


namespace Stembase\Modules\Novosteer_Addon_Export\Core\Models;

use \CTemplateDynamic;
use \CConfig;
use \CFile;

class Export extends Base{

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
		$this->map->setFeed($this->info);

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
			$this->runItem($item);			
		}
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

		$path_file .= "/export/" . $file;
						
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
		return $conf->vars["form"];
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
			$this->module->tables["plugin:novosteer_addon_export_history"],
			[
				"feed_id"			=> $this->info["feed_id"],
				"dealership_id"			=> $this->info["dealership_id"],
				"file_date"			=> time(),
				"file_file"			=> "1",
				"file_file_file"	=> $file
			]
		);

		$this->module->storage->private->SaveStream(
			"novosteer/import/history/" . $id . ".file",
			$this->module->storage->private->getStream(	$file)
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
	
	public function runPostProcess() {
		global $base , $_USER , $_SESS , $_CONF , $_LANG_ID; 
	}


	public function getProduct(&$item) {

		return $this->db->QFetchArray(
			"SELECT * FROM %s WHERE dealership_id = %d AND product_sku LIKE '%s'",
			[
				$this->module->tables['plugin:novosteer_vehicles_export'],
				$this->info["dealership_id"],
				$item[$this->skuField]
			]
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
	function runPreProcess() {
		global $_LANG_ID; 
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
	function getSyndicationProducts() {
		global $_LANG_ID; 

		return $this->db->QFetchRowArray(
			"SELECT product_id from %s WHERE feed_id = %d" , 
			[
				$this->module->tables["plugin:novosteer_addon_export_products"],
				$this->info["feed_id"]
			]
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
	}
	
	
	
}