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
class CNovosteerAddonExportBackend extends CPlugin{

	var $tplvars; 

	var $_field = "_locked_fields";

	/**
	* description
	*
	* @var type
	*
	* @access type
	*/
	var $mapRules = [];
	


	function __construct() {
		$this->name = "novosteer-addon-export";
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
	function transformMap($feed = null, $fid , $value) {
		global $base , $_USER , $_SESS; 

		if ($feed === null) {
			return $value;
		}

		$data = is_array($value) ? $value["name"] : $value;

		//check if the exists a mapping rule
		$map = $this->db->QFetchArray(
			"SELECT * FROM %s WHERE find_in_set(%d , export_id) AND map_original LIKE '%s' AND attribute_id = %d",
			[
				$this->tables["plugin:products_addon_export_map"],
				$feed,
				trim($data),
				$fid
			]
		);

		if (is_Array($map)) {

			if (is_array($value)) {
				$value["name"] = $map["map_final"];
			} else {
				$value = $map["map_final"];
			}
		}

		return $value;
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
	function recordHistory($feed , $file , $stream) {
		global $base , $_USER , $_SESS; 

		try {
			fseek($stream , 0);
		} catch (\Exception $e) {
		}

		$id = $this->db->QueryInsert(
			$this->tables["plugin:novosteer_addon_export_history"],
			[
				"feed_id"			=> $feed["feed_id"],
				"file_date"			=> time(),
				"history_file"		=> "1",
				"feed_extension"	=> $feed["feed_extension"],
				"history_file_file"	=> $file
			]
		);

		//update the feeed last run
		if ($feed["feed_id"]) {
			$this->db->QueryUpdateByID(
				$this->tables["plugin:novosteer_addon_export_feeds"],
				[
					"feed_lastrun"	=> time()
				],
				$feed["feed_id"]
			);
		} else {
			$this->db->QueryUpdate(
				$this->tables["plugin:novosteer_addon_export_feeds"],
				[
					"feed_lastrun"	=> time()
				],
				$this->db->Statement("feed_status = 1 AND feed_extension LIKE '%s'" , [$feed["feed_extension"]])
			);
		}
		

		$this->storage->private->saveStream(
			"novosteer/export/" . $id . ".file",
			$stream
		);

		if (is_resource($stream)) {
			fclose($stream);
		}		

		return $this->storage->private->getStream(
			"novosteer/export/" . $id . ".file",
		);
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
	function getExportById($id) {
		global $_LANG_ID;

		$data = $this->db->QFetchArray(
			"SELECT * FROM 	%s  as feeds
			INNER JOIN 
				%s AS dealerships
				ON
					feeds.dealership_id = dealerships.dealership_id
			WHERE				
				feed_id=%d
			",
			[
				$this->tables['plugin:novosteer_addon_export_feeds'],
				$this->tables['plugin:novosteer_dealerships'],
				$id
			]			
		);

		if ($data["feed_settings"]) {
			$data["settings"] = json_decode($data["feed_settings"] , true );
		}

		return $data;
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
	function getExportObject($feed = null) {
		global $base , $_USER , $_SESS; 

		$this->__init();

		if (!is_array($feed)) {			
			$feed = $this->getExportById($feed);				
		}

		if (!is_object($this->exports[$feed["feed_id"]])) {

			if (class_exists($feed["feed_class"],true)) {

				$this->exports[$feed["feed_id"]] = new $feed["feed_class"]($feed);
				$this->exports[$feed["feed_id"]]->setModule($this);
				$this->exports[$feed["feed_id"]]->setInfo($feed);

				if ($this->module->search) {
					$this->exports[$feed["feed_id"]]->setSearch($this->module->search , true);
				}
				
			}
		}

		return $this->exports[$feed["feed_id"]];		
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
	function getAllExports() {
		global $base , $_USER , $_SESS; 

		return $this->db->QFetchRowArray("
			SELECT * FROM 
				%s
			LEFT JOIN
				%s
				ON 
					feed_set = set_id 				
			",
			[
				$this->tables['plugin:novosteer_addon_export_feeds'],
				$this->tables['plugin:products_field_sets']
			]
		);
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
	function getExportsByDealershipID($id) {
		global $base , $_USER , $_SESS , $_CONF , $_LANG_ID; 

		$jobs = $this->db->QFetchRowArray(
			"SELECT * FROM %s WHERE dealership_id = %d AND feed_status = 1 ORDER BY feed_order ASC",
			[
				$this->tables["plugin:novosteer_addon_export_feeds"],
				$id
			]
		);

		return $jobs;
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
	function getExportsSets() {
		global $base , $_USER , $_SESS , $_CONF , $_LANG_ID; 

		return $this->db->Linear($this->db->QFetchRowArray(
			"SELECT feed_set FROM %s",
			[
				$this->tables['plugin:novosteer_addon_export_feeds']
			]
		));
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
	function getTableFields() {
		global $base , $_USER , $_SESS , $_CONF , $_LANG_ID; 

		$fields = $this->db->getTableFields($this->tables["plugin:novosteer_vehicles_export"])["fields"];

		return $fields;
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
	function getAllFeedsByName($name) {
		global $_LANG_ID; 

		$this->__init();


		$feeds = $this->db->QFetchRowArray(
			"SELECT * FROM %s as feeds 
				INNER JOIN 
					%s as dealerships
				ON 
					feeds.dealership_id = dealerships.dealership_id					
			WHERE 
				feeds.feed_extension LIKE '%s' AND
				feed_status = 1",
			[
				$this->tables["plugin:novosteer_addon_export_feeds"],
				$this->tables["plugin:novosteer_dealerships"],
				$name
			]
		);

		$feeds = array_map(
			function($feed) {
				if ($feed["feed_settings"]) {
					$feed["settings"] = json_decode($feed["feed_settings"] ,true);
				}

				return $feed;				
			},
			$feeds
		);

		return $feeds;
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
	function getExportProductsCount($feed) {
		global $_LANG_ID; 

		return $this->db->QFetchArray(
			"SELECT count(product_id) as cnt FROM %s WHERE feed_id = %d ",
			[
				$this->tables["plugin:novosteer_addon_export_products"],
				$feed["feed_id"]
			]
		)["cnt"];
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
	function getExportProducts($feed , $start , $batch) {
		global $_LANG_ID; 

		$products = $this->db->QFetchRowArray(
			"SELECT * FROM %s as feeds
				INNER JOIN 
					%s as products 
				ON 
					feeds.product_id= products.product_id 
				INNER JOIN 
					%s as brands
				ON 
					products.brand_id = brands.brand_id
				INNER JOIN 
					%s as models
				ON 
					products.model_id = models.model_id
			WHERE
				feeds.feed_id = %d
			ORDER BY 
				products.product_id
			LIMIT 
				%d , %d",			[
				$this->tables["plugin:novosteer_addon_export_products"],
				$this->tables["plugin:novosteer_vehicles_export"],
				$this->tables["plugin:novosteer_addon_autobrands_brands"],
				$this->tables["plugin:novosteer_addon_autobrands_models"],
				$feed["feed_id"] ,
				$start,
				$batch
			]
		);

		$fields = ["options" , "options_exterior" , "options_interior" , "options_mechanical" , "options_safety" , "factory-codes" ];

		if (is_array($products)) {
			$_products = [];
			foreach ($products as $key => $product) {

				foreach ($fields as $k => $val) {
					$product[$val] = json_decode($product[$val], true);
				}

				$_products[$product["product_id"]] = $product;
				$pids[] = $product["product_id"];
			}
			
			$products = $_products;

			$images = $this->db->QFetchRowArray(
				"SELECT * FROM %s WHERE product_id in (%s) and image_deleted = 0 ORDER BY image_order ASC" ,
				[
					$this->tables["plugin:novosteer_vehicles_export_images"],
					implode("," , $pids)
				]
			);

			if (is_array($images)) {
				foreach ($images as $k => $image) {
					if (!is_array($products[$image["product_id"]]["gallery"])) {
						$products[$image["product_id"]]["gallery"] = [];
					}

					$products[$image["product_id"]]["gallery"][] = [
						"original"	=> $image["image_downloaded"] 
							? $this->storage->getLocation($feed["dealership_location"])->getUrl($feed["dealership_location_prefix"] . "/export/" . $products[$image["product_id"]]['product_sku'] . "/original/" . $image["image_id"] . ".jpg")
							: $image["image_source"],

						"overlay"	=> $image["image_overlay"] 
							? $this->storage->getLocation($feed["dealership_location"])->getUrl($feed["dealership_location_prefix"] . "/export/" . $products[$image["product_id"]]['product_sku'] . "/final/" . $image["image_id"] . ".jpg")
							: null,

						"date"		=> $image["image_last_update"]
							
					];
				}				
			}			
		}
		

		return $products;
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
	public function uploadFileToFTP($options , $verbose = true , $job) {
		global $base , $_USER; 

		$job->log("Connecting to {$options['server']} ...");

		if ($options['ssl']) {
			$conn_id = ftp_ssl_connect($options['server'] , $options['port'] ? $options['port']  : 21);
		} else {				
			$conn_id = ftp_connect($options['server'] , $options['port'] ? $options['port']  : 21);
		}

		if (!$conn_id) {
			$job->log("Error connecting to {$options['server']}");
			return false;
		}
		

		$login_result = @ftp_login(
			$conn_id, 
			$options["username"],
			$options["password"]
		);

		if (!$login_result) {
			$job->log("Error loggin into the server");
			return false;
		} else {
			$job->log("Connection OK");
		}

		if ($options["passive"]) {
			$job->log("Entering passive mode");
			ftp_pasv($conn_id, true);
		}

		if (is_resource($options["local_file"])) {

			fseek($options["local_file"],0);

			if (ftp_fput(
				$conn_id, 
				$options["remote_file"], 
				$options["local_file"], 			
				FTP_BINARY
				)
			) {

				$job->log("Successfully uploaded " . $options['remote_file']);
			} else {
				$error = error_get_last();
				$job->log("There was a problem uploading the file: " . $error['message']);
				return null;
			}
		} else {		
			if (ftp_put(
				$conn_id, 
				$options["remote_file"], 
				$options["local_file"], 			
				FTP_BINARY
				)
			) {

				$job->log("Successfully uploaded " . $options['remote_file']);
			} else {
				$error = error_get_last();
				$job->log("There was a problem uploading the file: " . $error['message']);
				return null;
			}
		}

		// close the connection
		ftp_close($conn_id);

		if (is_resource($options["local_file"])) {
			fclose($options["local_file"]);
		}		

		$job->log("Closed ftp connection");
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
	function MapRuleLoadByFeed($feed) {
		global $_LANG_ID; 

		//reset rules
		$this->mapRules = [];

		$rules = $this->db->QFetchRowArray(
			"SELECT * FROM %s WHERE (find_in_set(%d , export_feeds) OR export_all = 1 )AND export_extension LIKE '%s'",
			[
				$this->tables["plugin:novosteer_addon_export_map"],
				$feed["feed_id"],
				$feed["feed_extension"]
			]
		);

		if (is_Array($rules)) {
			foreach ($rules as $key => $val) {
				$original = explode("\n" , $val["map_source"]);
				$final = explode("\n" , $val["map_destination"]);

				$data = [
					"from"	=> [],
					"to"	=> [],
				];
				foreach ($original as $k => $v) {
					$_tmp = explode("|" , trim($v));
					$data["from"][$_tmp[0]] = $_tmp[1];
				}

				foreach ($final as $k => $v) {
					$_tmp = explode("|" , trim($v));
					$data["to"][$_tmp[0]] = $_tmp[1];
				}

				$this->mapRules[] = $data;			
			}

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
	function MapRuleProcess(&$item) {
		global $_LANG_ID; 

		if (!(is_array($this->mapRules) && count($this->mapRules))) {
			return $this;
		}

		foreach ($this->mapRules as $map) {

			if (!count(array_diff($map["from"] , array_intersect($item , $map["from"]))) ) {
				
				if (is_array($map["to"])) {
					$item = array_merge(
						$item , 
						$map["to"]
					);
				}			
			}			
		}

		return $item;	
	}
	
	
}
