<?php
/*
	Stempora Web Framework
	Copyright (c) 2002-2020 Stempora. 
	All rights reserved.
		web:  www.stempora.com
		mail: support@stempora.com				
*/


namespace Stembase\Modules\Novosteer_Addon_Import\Core\Importer;

if (!defined("STPBase")) {
	die("This file can't be accessed directly!");
}

use \Stembase\Modules\Novosteer_Addon_Import\Core\Models\Importer;
use \Stembase\Modules\Novosteer_Addon_Import\Core\Interfaces\ImporterInterface;
use \Stembase\Modules\Novosteer_Dealerships\Core\Models\BlackBook;
use \CHeaders;



class CanadianBlackBook extends Importer implements ImporterInterface{

	/**
	* description
	*
	* @var type
	*
	* @access type
	*/
	var $client = null;
	

	/**
	* description
	*
	* @param
	*
	* @return
	*
	* @access
	*/
	function runWeb() {
		global $_LANG_ID ,$site; 


		$headers = getAllHeaders();

		if (!(isset($headers["Novosteer-Authorization"]) && $headers["Novosteer-Authorization"] == $this->info["settings"]["set_request_key"])) {
			return $site->plugins["redirects"]->ErrorPage("404" , true);
		}

		switch ($_POST["service"]) {
			case "vindecoder":
				return $this->vinDecoder($_POST["vin"]);
			break;
		}
		
		die();
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
	function vinDecoder($vin) {
		global $_LANG_ID; 

		$vehicle = $this->initClient()->decodeVin($vin);

		$vehicle = $this->processVehicle($vehicle);

		if (is_array($vehicle)) {
			$vehicle["vin"] = $vin;
		}
		



		Cheaders::newInstance()
			->ContentTypeByExt("novosteer.json")
			->FileName("novosteer" , "inline"); 

		$this->module->json([
			"vin"		=> $vin,
			"product"	=> $vehicle
		]);

		die();
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
	function initClient() {
		global $_LANG_ID; 

		if ($this->client) {
			return $this->client;
		}

		$this->client = Blackbook::create()
			->setCustomerID($this->info["settings"]["set_client"])
			->setCredentials(
				$this->module->plugins["modules"]->modules["novosteer-dealerships"]["settings"]["set_cbb_api_user"], 
				$this->module->plugins["modules"]->modules["novosteer-dealerships"]["settings"]["set_cbb_api_pass"]
		);

		return $this->client;
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
	function processVehicle($vehicle) {
		global $_LANG_ID; 

		$engine = [];
		$exterior = [];
		$interior = [];
		$mechanical = [];
		$safety = [];
		$options = [];

		foreach ($vehicle["options"] as $category) {
			switch ($category["name"]) {
				case "Exterior":
					foreach ($category["equipment_list"] as $option) {
						$exterior[] = $option["equipment_value"] . " " . $option["sub_category"];
					}					
				break;

				case "Interior":
					foreach ($category["equipment_list"] as $option) {
						$interior[] = $option["equipment_value"] . " " . $option["sub_category"];
					}					
				break;

				case "Mechanical":
					foreach ($category["equipment_list"] as $option) {
						$mechanical[] = $option["equipment_value"] . " " . $option["sub_category"];
					}					
				break;

				case "Safety":
					foreach ($category["equipment_list"] as $option) {
						$safety[] = $option["equipment_value"] . " " . $option["sub_category"];
					}					
				break;

				case "Suspension":
				case "Dimensions":
				case "Warranty":
					foreach ($category["equipment_list"] as $option) {
						$options[] = $option["equipment_value"] . " " . $option["sub_category"];
					}					
				break;

				case "Drivetrain - Engine":
					foreach ($category["equipment_list"] as $option) {
						$engine[$option["sub_category"]] = $option["equipment_value"];
					}					
				break;
				case "Drivetrain - Transmission":
				break;

			}
			
		}
		

		$final = [
			"cat" => 'Used',
			"vin" => $vehicle['vin'],
			"year" => $vehicle['model_year'],
			"brand_name" => $vehicle['make'],
			"model_name" => $vehicle['model'],
			"trim_name" => $vehicle['series'],
			"modelnumber"	=> $vehicle["model_number_list"][0],
			"body" => $vehicle['class_name'],
			"enginecylinders" => $engine['Cylinders'],
			"enginedisplacement" => $engine['Displacement'] . " L",
			"transmission" => $vehicle['transmission'] == "A" ? "Automatic" : "Manual",
			"msrp" => $vehicle['msrp'],
			"options"	=> $options,	 
			"options_exterior"	=> $exterior,	 
			"options_interior"	=> $interior,	 
			"options_mechanical"	=> $mechanical,	 
			"options_safety"		=> $safety,	  
			"style_description" => $vehicle['style'],
			"engine_block_type" => $engine["Cylinder Configuration"],
			"engine_aspiration_type" => $engine['Fuel Delivery'],
			"engine_description" => $vehicle['engine_description'],
			"transmission_speed" => $vehicle['num_gears'],
			"transmission_description" => $vehicle[''],
			"drivetrain" => $vehicle['drivetrain'],
			"fuel_type" => $vehicle['fuel_type'],
			"citympg" => $vehicle['city_mpg'],
			"highwaympg" => $vehicle['hwy_mpg'],

		];

		return $final;

	}
	
	
}
