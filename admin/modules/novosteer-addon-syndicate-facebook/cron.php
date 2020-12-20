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
class CNovosteerAddonSyndicateFacebook extends CNovosteerAddonSyndicateFacebookBackend{

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
	
		$feeds = $this->export->getAllFeedsByName("syndicatefacebook");

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
				$products[$key] = $this->processProduct($product , $feed);
			}				
		}	

		$job->log("Saving file to private spage");

		$this->storage->private->save(
			"novosteer/facebook/" . $feed["feed_id"] . ".xml",
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
			"facebook.xml" , 
			$this->storage->private->getStream("novosteer/facebook/" . $feed["feed_id"] . ".xml")
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
	function processProduct($product , $feed) {
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

		$product["body_style"] = $this->validateTag($product["body_style"] , ["CONVERTIBLE", "COUPE", "CROSSOVER", "HATCHBACK", "MINIVAN", "TRUCK", "SEDAN", "SMALL_CAR", "SUV", "VAN", "WAGON"] , "OTHER");
		$product["drivetrain"] = $this->validateTag($product["drivetrain"] , ["4X2", "4X4", "AWD", "FWD", "RWD"] , "OTHER");
		$product["transmission"] = $this->validateTag($product["transmission"] , ["AUTOMATIC", "MANUAL", "NONE"] , "OTHER");
		$product["fuel"] = $this->validateTag($product["fuel"] , ["DIESEL", "ELECTRIC", "GASOLINE", "FLEX", "HYBRID", "PETROL", "PLUGIN_HYBRID", "NONE"] , "OTHER");

		$product["trim"] = $product[$feed["settings"]["set_trim"]];

		$product["images"] = $images;
		
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