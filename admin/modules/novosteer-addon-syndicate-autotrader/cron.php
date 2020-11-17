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
		$image = null;

		if (is_array($product["gallery"])) {
			$image= $product["gallery"][0]["overlay"] ? $product["gallery"][0]["overlay"] : $product["gallery"][0]["original"];
			$image_date = $product["gallery"][0]["image_last_update"];

			$images_date = 0;
			foreach ($product["gallery"] as $k => $v) {

				$images_date = max($images_date , $v["date"]);

				if ($k > 0 ) {
					$images[] = $v["overlay"] ? $v["overlay"] : $v["original"];
				}				

			}
			
		}
		
		return [
			$product["stock_id"],			
			$product["vin"],
			$product["cat"] == "New" ? "New" : "Used",
			$product["year"],
			$product["brand_name"],
			$product["model_name"],
			$product["trim_full"],
			$product["price_sale"],
			$product["mileage"],
			$product["color"],
			$product["exterior_color_detailed"],
			$product["interior_color_detailed"],
			$product["fuel"],
			$product["drivetrain"],
			$product["engine_displacement"],
			$product["transmission"],
			$product["doors"],
			$product["passengers"],
			$product["engine_cylinders"],
			$product["body_style"],
			implode(',' , $product["options"]),
			$product["description"],
			$image,
			$image_date ? CDate::toStr("%Y.%m.%d %r" , $image_date) : null,
			implode("|" , $images),
			$images_date ? CDate::toStr("%Y.%m.%d %r" , $images_date) : null,
			CDate::toStr("%Y.%m.%d %r" , $product["product_last_update"]),
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
			"StockNumber",
			"Vin"		,		
			"Status"	,				
			"Year"		,				
			"Make"		,				
			"Model"		,				
			"Trim"		,				
			"Price"						,
			"KMS"		,				
			"Exterior Color"			,
			"Mfg Exterior Color"		,
			"Interior Color"			,
			"FuelType"					,
			"Drive"						,
			"Engine Size"				,
			"Transmission"				,
			"Doors"						,
			"Passenger"					,
			"Cylinder"					,
			"Body"						,
			"Options"					,
			"Description"				,
			"MainPhoto"					,
			"MainPhotoLastModifiedDate" ,
			"ExtraPhotos"				,
			"ExtraPhotoLastModifiedDate",
			"AdLastModifiedDate"		,

		];
	}
}