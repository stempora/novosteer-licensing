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
class CNovosteerAddonExport extends CNovosteerAddonExportBackend{
	
	var $tplvars; 
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
	function DoEvents(){
		global $base, $_CONF, $_TSM , $_VARS , $_USER , $_BASE , $_SESS , $site;

		if ($_GET["mod"] == $this->name) {

			$this->tpl_module = $this->module->plugins["modules"]->LoadDefaultModule($this->name);
			parent::DoEvents();

			$sub = $_GET["sub"];
			$action = $_GET["action"];

			return $this->CronImportFeed(array("params" => array("feed_id" => "6")));
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
	function __init() {
		global $_CONF , $_SESS, $site;

		if ($this->__inited) {
			return "";
		}

		$this->__inited = true;
		
		$this->__initTemplates([
		]);

		$this->tpl_module = $this->plugins["modules"]->LoadDefaultModule($this->name);
		$this->module = &$this->plugins["products"];
		$this->module->__init();

		$this->search = clone $this->module->search;

		$this->cache = $site->shared->addChild($this->name);
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
	function CronExportFeed($job) {

		$this->__init();
	

		$feeds = $this->getExportsByDealershipID($job->getInfo()["params"]["dealership_id"]);

		if (!is_array($feeds)) {
			$job->removeLog();
		}
		
		foreach ($feeds as $key => $feed) {
			$client = $this->getExportObject($feed["feed_id"]);

			if (is_object($client)) {
				$client->setCronJob($job);
				$client->runFeed();
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
	function cronVehiclesPrice($job) {
		global $base , $_USER , $_SESS , $_CONF , $_LANG_ID; 

		$this->__init();

		$dealership = $this->plugins["novosteer-dealerships"]->getDealershipById($job->getInfo()["params"]["dealership_id"]);

		$calculator = $this->plugins["novosteer-dealerships"]->getCalculatorObject($dealership["calculator_id"]);
		$calculator->setJob($job);

		//get all products that are not published 
		$vehicles = $this->db->QFetchRowArray(
			"SELECT * FROM 
				%s as vehicles 
			INNER JOIN 
				%s as brands
				ON
					vehicles.brand_id = brands.brand_id
			INNER JOIN 
				%s as models 
				ON 
					vehicles.model_id = models.model_id 
			LEFT JOIN 
				%s as trims
				ON 
					vehicles.trim_id = trims.trim_id
		
			WHERE 
				dealership_id = %d
			",
			[
				$this->tables["plugin:novosteer_vehicles"],
				$this->tables["plugin:novosteer_addon_autobrands_brands"],
				$this->tables["plugin:novosteer_addon_autobrands_models"],
				$this->tables["plugin:novosteer_addon_autobrands_trims"],
				$dealership["dealership_id"]
			]
		);

		if (is_array($vehicles)) {
			foreach ($vehicles as $key => $vehicle) {

				$job->log("Calculating price for " . $vehicle["vin"]);
				$calculator->setVehicle($vehicle);
				$calculator->calculatePrice();
			}
			
		}
		

		//debug($dealership,1);


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
	function CronCleanLogs($job) {
		global $_CONF;

		$this->__init();

		if (!$this->_s("set_keep")) {
			$job->removeLog();
		} else {

			$history = $this->db->QFetchRowArray(
				"SELECT * FROM %s WHERE file_date < %d",
				[
					$this->tables['plugin:novosteer_addon_export_history'],
					time() - $this->_s("set_keep") * 3600 * 24
				]
			);

			if (is_array($history)) {
				echo "Deleting " . count($history) . " importer logs ... ";

				foreach ($history as $key => $val) {
					CFile::Remove("upload/novosteer/importer/history/" . $val["file_id"]. ".file");
				}
				
				$this->db->Query(
					"DELETE FROM %s WHERE file_date < %d",
					[
						$this->tables['plugin:novosteer_addon_export_history'],
						time() - $this->_s("set_keep") * 3600 * 24
					]
				);

				echo "done\n";
			} else {
				echo "Nothing to delete!";
			}
			
		}

	}

	

}
