<?php
/*
	Stempora Web Framework
	Copyright (c) 2002-2019 Stempora. 
	All rights reserved.
		web:  www.stempora.com
		mail: support@stempora.com				
*/




class CNovoSteerAddonVehiclesBackend extends CPlugin {
	
	function __construct() {
		$this->name = "novosteer-addon-vehicles";
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
	function deleteGalleryImages($pid) {
		global $base , $_USER , $_SESS , $_CONF , $_LANG_ID; 

		$this->db->Query(
			"DELETE FROM %s where product_id = %d AND image_main=0" , 
			[
				$this->tables["plugin:novosteer_vehicles_images"],
				$pid
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
	function deleteMainImage($pid) {
		global $base , $_USER , $_SESS , $_CONF , $_LANG_ID; 

		$this->db->Query(
			"DELETE FROM %s where product_id = %d and image_main = 1" , 
			[
				$this->tables["plugin:novosteer_vehicles_images"],
				$pid
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
	function addImage($pid , $image) {
		global $base , $_USER , $_SESS , $_CONF , $_LANG_ID; 

		$data = [
			"image_url"			=> $image["url"], 
			"product_id"		=> $pid , 
			"image_date"		=> time(),
			"image_reserved"	=> $image["reserved"],
			"image_main"		=> $image["main"] ? true : false			
		];

		return $this->db->QueryInsert(
			$this->tables['plugin:novosteer_vehicles_images'],
			$data
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
	function getDealershipVehicles($id) {
		global $base , $_USER , $_SESS , $_CONF , $_LANG_ID; 

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
				dealership_id = %d AND 
				product_status = 1
				
			LIMIT 10",

			[
				$this->tables["plugin:novosteer_vehicles"],
				$this->tables["plugin:novosteer_addon_autobrands_brands"],
				$this->tables["plugin:novosteer_addon_autobrands_models"],
				$this->tables["plugin:novosteer_addon_autobrands_trims"],

				$id
			]
			
		);

		return $vehicles;
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
	function getExportVehicleByID($id) {
		global $_LANG_ID; 

		return $this->db->QFetchArray(
			"SELECT * FROM %s WHERE product_id = %d",
			[
				$this->tables["plugin:novosteer_vehicles_export"],
				$id
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
	function getVehicleImages($id) {
		global $base , $_USER , $_SESS , $_CONF , $_LANG_ID; 

		$images = $this->db->Linear($this->db->QFetchRowArray(
			"SELECT image_url FROM %s WHERE product_id = %d AND image_main=0 ORDER BY image_id ASC",
			[
				$this->tables["plugin:novosteer_vehicles_images"] , 
				$id
			]
		));

		return $images;
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
	function getVehicleMainImage($id) {
		global $base , $_USER , $_SESS , $_CONF , $_LANG_ID; 

		$images = $this->db->QFetchArray(
			"SELECT image_url FROM %s WHERE product_id = %d AND image_main=1 ORDER BY image_id ASC",
			[
				$this->tables["plugin:novosteer_vehicles_images"] , 
				$id
			]
		);

		return $images["image_url"];
	}
	
	

}