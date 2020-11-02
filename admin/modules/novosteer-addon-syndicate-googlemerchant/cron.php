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
class CNovosteerAddonSyndicateGoogleMerchant extends CNovosteerAddonSyndicateGoogleMerchantBackend{

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

		$this->__initTemplates([
			"main"	=> "main.twig"
		]);
		
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
	
		$feeds = $this->export->getAllFeedsByName("syndicategooglemerchant");

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

		$this->export->MapRuleLoadByFeed($feed);


		$products = $this->export->getExportProducts($feed , $start , 1000000);
		if (is_array($products)) {
			foreach ($products as $key => $product) {
				$products[$key] = $this->processProduct($product);
			}				
		}	

		$job->log("Saving file to private spage");

		$this->storage->private->save(
			"novosteer/googlemerchant/" . $feed["feed_id"] . ".xml",
			$this->_t("main")->blockReplace(
				"Main",
				[
					"feed"	=> $feed , 
					"products"	=> $products
				]				
			)
		);


		$this->export->recordHistory(
			$feed , 
			"googlemerchant.xml" , 
			$this->storage->private->getStream("novosteer/googlemerchant/" . $feed["feed_id"] . ".xml")
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
			$last = 0;
			foreach ($product["gallery"] as $k => $v) {
				$last = max($last , $v["date"]);
				$images[] = $v["overlay"] ? $v["overlay"] : $v["original"];
			}			
		}

		$product["images"] = $images;



		$details = [
			[
				"name"	=> "Overview",
				"attributes" => [
					"Make"		=> "brand_name",
					"Model"		=> "model_name",
					"Year"		=> "year",
					"Vin"		=> "vin",
					"Stock ID"	=> "stock_id",
					"Mileage"	=> "mileage"
				]
			],
			
			[
				"name"	=> "Exterior",
				"attributes" => [
					"Color"				=> "color",
					"Color Detailed"	=> "exterior_color_detailed",
					"Doors"				=> "doors",
					"Body"				=> "body_style",
					"Wheelbase Code"	=> "wheelbase_code",
				]
			],
			[
				"name"	=> "Interior",
				"attributes" => [
					"Color"				=> "interior_color",
					"Color Detailed"	=> "interior_color_detailed",
					"Passengers"		=> "passengers"
				]
			],

			[
				"name"	=> "Engine",
				"attributes" => [
					"Description"		=> "engine_description",
					"Engine"			=> "engine",
					"Cylinders"			=> "engine_cylinders",
					"Displacement"		=> "engine_displacemenet",
					"Aspiration"		=> "engine_aspiration",
					"Block Type"		=> "engine_block",

					"Fuel"				=> "fuel",
					"City MPG"			=> "citympg",
					"Highway MPG"		=> "highwaympg",
				]
			],

			[
				"name"	=> "Transmission",
				"attributes" => [
					"Description"	=> "transmission_description",
					"Type"			=> "transmission",
					"Speed"			=> "transmission_speed",
					"Drive Train"	=> "drivetrain"
				]
			],

			[
				"name"	=> "Options",
				"attributes" => [
					"General"		=> "options",
					"Exterior"		=> "options_exterior",
					"Interior"		=> "options_interior",
					"Mechanical"	=> "options_mechanical",
					"Safety"		=> "options_safety",
				]
			],

		];

		$product["details"] = [];


		foreach ($details as $detail) {
			foreach ($detail["attributes"] as $attribute => $value) {

				if ($product[$value]) {
					$product["details"][] = [
						"section"	=> $detail["name"],
						"attribute"	=> $attribute,
						"value"		=> is_array($product[$value]) ? implode(", " , $product[$value]) : $product[$value]
					];
				}				
			}			
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
	function validateTag($original, $matches, $default) {
		global $_LANG_ID; 

		$original = strtoupper($original);

		if (!in_array($original , $matches)) {
			return $default;
		} else {
			return $original;
		}
	
	}
	

}