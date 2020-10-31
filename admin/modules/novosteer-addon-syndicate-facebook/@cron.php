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

		$encoder = new \Symfony\Component\Serializer\Encoder\XmlEncoder();

		$job->log("Starting feed " .$feed["feed_name"] . " for dealership " . $feed["dealership_name"]);

		$this->export->MapRuleLoadByFeed($feed);

		$productsCount = $this->export->getExportProductsCount($feed);
		$batch = 50;
		$start = 0;

		$this->export->MapRuleLoadByFeed($feed);

		$listings = $this->getXMLBody($feed);

		do {
			$products = $this->export->getExportProducts($feed , $start , $batch);
			$start += $batch;

			if (is_array($products)) {
				foreach ($products as $key => $product) {

					$product = $this->processProduct($feed , $product);

					if (is_array($product)) {
						$listings["listings"]["listings"][] = $product;
					}
				}				
			}
			
		} while ( $start <= $productsCount);



		$job->log("Saving file to private space");

		$this->storage->private->save(
			"novosteer/facebook/" . $feed["feed_id"] . ".xml",
			$encoder->encode($listings , "xml")
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
	function processProduct($feed , $product) {
		global $_LANG_ID; 

		$this->export->MapRuleProcess($product);

		$data = [
			"address"	=> [
				"@format"	=> "simple",
				"component" => ["@name" => "addr1" , "" => $feed["dealership_syn_street"] ]
			]
		];

		return $data;

		$images = [];
		$image = null;

		if (is_array($product["gallery"])) {
			$image= $product["gallery"][0]["overlay"] ? $product["gallery"][0]["overlay"] : $product["gallery"][0]["original"];

			$last = 0;
			foreach ($product["gallery"] as $k => $v) {

				$last = max($last , $v["date"]);

				if ($k > 0 ) {
					$images[] = $v["overlay"] ? $v["overlay"] : $v["original"];
				}				

			}			
		}

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
	function getXMLBody($feed) {
		global $_LANG_ID; 

		return [
			"listings" => [
				"title"	=> $feed["settings"]["set_feed_title"],
				"link"	=> [ 
					"@rel"	=> "self",
					"@href" => $feed["settings"]["set_feed_link"]
				],

				"listings"	=> [],
				
			]
		];
	}
	

}