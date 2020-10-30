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
class CNovosteerAddonSyndicateSpinCar extends CNovosteerAddonSyndicateSpinCarBackend{

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
	
		$feeds = $this->export->getAllFeedsByName("syndicatespincar");

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

		$this->export->MapRuleLoadByFeed($feed);

		$csv = League\Csv\Writer::createFromStream($temp);
		$csv->insertOne($this->getHeader());

		do {
			$products = $this->export->getExportProducts($feed , $start , $batch);
			$start += $batch;

			if (is_array($products)) {
				foreach ($products as $key => $product) {
					$csv->insertOne($this->processProduct($product));
				}				
			}
			
		} while ( $start <= $productsCount);

		

		$job->log("Uploading file to remote ".$feed["settings"]["set_path"]);

		$content = $this->export->recordHistory($feed , "export.csv" , $temp);

		$this->export->uploadFileToFTP(
			[

				"server"		=> $this->_s("set_server"),
				"port"			=> $this->_s("set_port"),
				"username"		=> $this->_s("set_username"),
				"password"		=> $this->_s("set_password"),
				"passive"		=> $this->_s("set_passive"),
				"ssl"			=> $this->_s("set_ssl"),
				"remote_file"	=> $feed["settings"]["set_path"],
				"local_file"	=> $content
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

		$this->export->MapRuleProcess($product);

		$images = [];
		$image = null;

		if (is_array($product["gallery"])) {
			foreach ($product["gallery"] as $k => $v) {
				$images[] = $v["overlay"] ? $v["overlay"] : $v["original"];
			}			
		}
		
		return [
			$product["stock_id"],			
			$product["cat"] == "New" ? "New" : "Used",
			$product["price_retail"],
			$product["price_sale"],
			$product["year"],
			$product["brand_name"],
			$product["model_name"],
			$product["trim"],
			$product["vin"],
			$product["link"],
			implode(',' , $product["options"]),
			implode("|" , $images),
		];
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
	function getHeader() {
		global $_LANG_ID; 

		return [			
			"stock number",
			"vehicle status",		
			"msrp",		
			"sale_price",		
			"year",		
			"make",		
			"model",		
			"trim",		
			"vin",		
			"link",		
			"options",		
			"images",		
		];
	}
}