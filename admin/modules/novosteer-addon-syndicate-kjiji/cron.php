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
class CNovosteerAddonSyndicateKjiji extends CNovosteerAddonSyndicateKjijiBackend{

	/**
	* description
	*
	* @var type
	*
	* @access type
	*/
	var $csv = null;
	

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
	
		$feeds = $this->export->getAllFeedsByName("syndicatekjiji");

		$groups = $this->getAllGroups();
		if (!is_array($feeds)) {
			$job->removeLog();
		}

		if (!is_array($groups)) {
			$job->removeLog();
		}

		foreach ($feeds as $key => $feed) {
			if (is_array($groups[$feed["settings"]["set_group"]])) {
				$groups[$feed["settings"]["set_group"]]["feeds"][] = $feed;
			}			
		}

		foreach ($groups as $k => $group) {
			$job->log("Processing dealers group ". $group["group_name"]);

			$feeds = $group["feeds"];

			if (is_array($feeds) && count($feeds)) {

				$temp = tmpfile();
				$this->csv = League\Csv\Writer::createFromStream($temp);
				$this->csv->insertOne($this->getHeader());

				
				foreach ($feeds as $key => $feed) {
					$this->pushFeed($job , $feed);
				}


				$job->log("Uploading file to remote ".$this->_s("set_path"));

				$content = $this->export->recordHistory(["feed_extension" => "syndicatekjiji" , "history_group" => $group["group_id"]] , "export.csv" , $temp);
				$this->export->uploadFileToFTP(
					[
						"server"		=> $group["group_server"],
						"port"			=> $group["group_port"],
						"username"		=> $group["group_username"],
						"password"		=> $group["group_password"],
						"passive"		=> $group["group_passive"],
						"ssl"			=> $group["group_ssl"],
						"remote_file"	=> $group["group_path"],
						"local_file"	=> $content
					],
					true,
					$job
				);


			}
			

			
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

		$this->export->MapRuleLoadByFeed($feed);

		do {
			$products = $this->export->getExportProducts($feed , $start , $batch);
			$start += $batch;

			if (is_array($products)) {
				foreach ($products as $key => $product) {
					$this->csv->insertOne($this->processProduct($product, $feed));
				}				
			}
			
		} while ( $start <= $productsCount);




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
	function processProduct($product , $feed) {
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
			$feed["settings"]["set_dealer_id"],
			$feed["dealership_syn_name"],
			$feed["dealership_syn_street"],
			$feed["dealership_syn_phone"],
			$feed["dealership_syn_zip"],
			$feed["dealership_syn_email_adf"],
			$product["product_id"],
			$product["vin"],
			$product["stock_id"],			
			$product["cat"] == "New"? "0" : "1",
			$product["cat"] == "Certified"? "1" : "0",
			$product["year"],
			$product["brand_name"],
			$product["model_name"],
//			$product["engine"],
			$product["body_style"],
			$product[$feed["settings"]["set_trim"]],
			$product["transmission"],
			$product["mileage"],
			$product["exterior_color_detailed"],
			$product["price_sale"],
			$product["price_retail"],
			$product["model_number"],
			$product["description"],
			$product["drivetrain"],
			0,
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
			"dealer_id",
			"dealer_name",
			"address",
			"phone",
			"postalcode",
			"email",
			"vehicle_id",
			"vin",
			"stockid",
			"is_used",
			"is_certified",
			"year",
			"make",
			"model",
//			"engine",
			"body",
			"trim",
			"transmission",
			"kilometers",
			"exterior_color",
			"price",
			"msrp",
			"model_code",
			"comments",
			"drivetrain",
			"category",
			"images",
		];
	}
}