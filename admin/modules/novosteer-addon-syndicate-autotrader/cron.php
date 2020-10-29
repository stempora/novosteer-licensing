<?php
/*
	Stempora Web Framework
	Copyright (c) 2002-2018 Stempora. 
	All rights reserved.
		web:  www.stempora.com
		mail: support@stempora.com				
*/


/**
* description
*
* @library	
* @author	
* @since	
*/
class CNovosteerAddonSyndicateAutotrader extends CNovosteerAddonSyndicateAutotraderBackend{

	/**
	* description
	*
	* @param
	*
	* @return
	*
	* @access
	*/
	function __init() {
		global $_CONF , $_SESS, $site;

		if ($this->__inited) {
			return "";
		}

		$this->__inited = true;
		
		$this->tpl_module = $this->plugins["modules"]->LoadDefaultModule($this->name);
		$this->export = &$this->plugins["novosteer-addon-export"];
		$this->export->__init();
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
	function PushFeeds($job) {

		$this->__init();
	
		$feeds = $this->export->getAllFeedsByName("syndicateautotrader");

		if (!is_array($feeds)) {
			$job->removeLog();
		}
		
		foreach ($feeds as $key => $feed) {
			$this->pushFeed($job , $feed);
		}

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
	function pushFeed($job , $feed) {
		global $_LANG_ID; 

		$job->log("Starting feed " .$feed["feed_name"] . " for dealership " . $feed["dealership_name"]);

		$productsCount = $this->export->getExportProductsCount($feed);
		$batch = 50;
		$start = 0;

		$temp = tmpfile();

		do {
			$products = $this->export->getExportProducts($feed , $start , $batch);
			$start += $batch;

			if (is_array($products)) {
				foreach ($products as $key => $product) {
					fputcsv($temp, $this->processProduct($product));
				}				
			}
			
		} while ( $start <= $productsCount);


		$job->log("Uploading file to remote ".$feed["settings"]["set_path"]);

		$this->export->uploadFileToFTP(
			[

				"server"		=> $feed["settings"]["set_server"],
				"port"			=> $feed["settings"]["set_port"],
				"username"		=> $feed["settings"]["set_username"],
				"password"		=> $feed["settings"]["set_password"],
				"passive"		=> $feed["settings"]["set_passive"],
				"ssl"			=> $feed["settings"]["set_ssl"],
				"remote_file"	=> $feed["settings"]["set_path"],
				"local_file"	=> $temp
			],
			true,
			$job
		);

		$job->log("Done\n");
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
	function processProduct($product) {
		global $_LANG_ID; 

		return [
			"vin"		=> $product["vin"],
			"stock"		=> $product["stock"],
			"make"		=> $product["brand_name"],
			"model"		=> $product["model_name"],

		];
	}
	
	

}
