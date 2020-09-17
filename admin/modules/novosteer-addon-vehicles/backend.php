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
	
	

}