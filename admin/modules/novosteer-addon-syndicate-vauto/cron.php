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
class CNovosteerAddonSyndicatevAuto extends CNovosteerAddonSyndicatevAutoBackend{

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
	
		$feeds = $this->export->getAllFeedsByName("syndicatevauto");

		if (!is_array($feeds)) {
			$job->removeLog();
		}

		$temp = tmpfile();
		$this->csv = League\Csv\Writer::createFromStream($temp);
		$this->csv->insertOne($this->getHeader());

		
		foreach ($feeds as $key => $feed) {
			$this->pushFeed($job , $feed);
		}

		$job->log("Uploading file to remote ".$this->_s("set_path"));

		$content = $this->export->recordHistory(["feed_extension" => "syndicatevauto"] , "export.csv" , $temp);

		$this->export->uploadFileToFTP(
			[

				"server"		=> $this->_s("set_server"),
				"port"			=> $this->_s("set_port"),
				"username"		=> $this->_s("set_username"),
				"password"		=> $this->_s("set_password"),
				"passive"		=> $this->_s("set_passive"),
				"ssl"			=> $this->_s("set_ssl"),
				"remote_file"	=> $this->_s("set_path"),
				"local_file"	=> $content
			],
			true,
			$job
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
			$product["product_id"],			
			$feed["settings"]["set_dealer_id"],
			$feed["dealership_syn_name"],
			$feed["dealership_syn_street"],
			$feed["dealership_syn_city"],
			$feed["dealership_syn_state"],
			$feed["dealership_syn_zip"],
			$product["vin"],
			$product["price_retail"],
			$product["price_sale"],
			$product["link"],
			$product["mileage"],
			$product["cat"] == "New"? "New" : "Used",
			$product["stock_id"],
			$product["brand_name"],
			$product["model_name"],
			$product["year"],
			$product["trim"],
			$product["cat"] == "Certified"? "1" : "0",
			$product["transmission"],
			$product["exterior_color_detailed"],
			$product["interior_color_detailed"],
			$product["doors"],
			$product["fuel"],
			$product["drivetrain"],
			$product["model_number"],
			implode("," , $images),
			implode("," , $product["options"]),
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
			"Vehicle ID",
			"Seller ID",
			"Dealer Name",
			"Dealer Street Address",
			"Dealer City",
			"Dealer State",
			"Dealer Zip",
			"Vin",
			"Internet Price",
			"Conditional Price",
			"Detail URI",
			"Odometer",
			"Status",
			"Stock Number",
			"Make",
			"Model",
			"Year",
			"Trim",
			"isCertified",
			"TransmissionDescription",
			"ExteriorColor",
			"InteriorColor",
			"BodyDoorCount",
			"EngineFuelType",
			"DriveTrainDescription",
			"OEM Model Code",
			"Image URLs",
			"Equipment"
		];
	}
}