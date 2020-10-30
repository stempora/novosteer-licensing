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
class CNovosteerAddonSyndicateHomenet extends CNovosteerAddonSyndicateHomenetBackend{

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
	
		$feeds = $this->export->getAllFeedsByName("syndicatehomenet");

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


		//fputcsv($temp , $this->getHeader());

		do {
			$products = $this->export->getExportProducts($feed , $start , $batch);
			$start += $batch;

			if (is_array($products)) {
				foreach ($products as $key => $product) {
					$csv->insertOne($this->processProduct($product));

//					fputcsv($temp, $this->processProduct($product));
				}				
			}
			
		} while ( $start <= $productsCount);

		

		$job->log("Uploading file to remote ".$feed["settings"]["set_path"]);

		$content = $this->export->recordHistory($feed , "export.csv" , $temp);

		$this->export->uploadFileToFTP(
			[

				"server"		=> $feed["settings"]["set_server"],
				"port"			=> $feed["settings"]["set_port"],
				"username"		=> $feed["settings"]["set_username"],
				"password"		=> $feed["settings"]["set_password"],
				"passive"		=> $feed["settings"]["set_passive"],
				"ssl"			=> $feed["settings"]["set_ssl"],
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
		$last = 0;

		if (is_array($product["gallery"])) {
			foreach ($product["gallery"] as $k => $v) {
				$last = max($last , $v["date"]);
				$images[] = $v["overlay"] ? $v["overlay"] : $v["original"];
			}
			
		}
		
		return [
			$product["vin"],
			$last ? CDate::toStr("%Y.%m.%d %r" , $last) : null,
			$product["stock_id"],			
			$product["price_sale"],
			$product["link"],
			implode("|" , $images),
			$product["title"],
			$product["description"],
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
		return [			
			"VIN",
			"DATE_IMAGES_MODIFIED",
			"STOCK",				
			"SALE_PRICE",				
			"VDP_URL",				
			"IMAGE_LIST",				
			"TITLE",				
			"DESCRIPTION",
		];
	}
}