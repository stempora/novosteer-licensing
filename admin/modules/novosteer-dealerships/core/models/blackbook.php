<?php
/*
	Stempora Web Framework
	Copyright (c) 2002-2020 Stempora. 
	All rights reserved.
		web:  www.stempora.com
		mail: support@stempora.com				
*/

namespace Stembase\Modules\Novosteer_Dealerships\Core\Models; 
if (!defined("STPBase")) {
	die("This file can't be accessed directly!");
}


class BlackBook {

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
	* @var type
	*
	* @access type
	*/
	var $country = "C";
	
	

	/**
	* description
	*
	* @param
	*
	* @return
	*
	* @access
	*/
	function construct() {
		global $_LANG_ID; 
		
	}

	static function create() {
		return new static();
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
	function setCredentials($user , $pass) {
		global $_LANG_ID; 

		$this->user = $user;
		$this->pass = $pass;

		return $this;
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
	function setCustomerId($customer) {
		global $_LANG_ID; 

		$this->customer = $customer;

		return $this;
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
	function setCountry($country) {
		global $_LANG_ID; 

		$this->country = $country;
		return $this;
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
	function decodeVin($vin) {
		global $_LANG_ID; 

		$client =  new \GuzzleHttp\Client();
		$response = $client->request(
			'GET', 	
			'https://service.canadianblackbook.com/UsedCarWS/CanUsedAPI/UsedVehicle/VIN/' . $vin, 	
			[
				'auth' => [$this->user, $this->pass],
				'query'	=> [
					"customerid"	=> $this->customer,
					"country"		=> $this->country
				]
			]
		);


		if ($response->getStatusCode() !== 200) {
			return null;
		}

		$data = json_decode($response->getBody()->getContents(),true);

		$vehicle = $data["used_vehicles"]["used_vehicle_list"][0];


		if ($vehicle["uvc"]) {
			//get the standard equipment
			$vehicle["options"] = $this->getStandardEquipment($vehicle["uvc"]);
		}
		
		return $vehicle;

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
	function getStandardEquipment($uvc) {
		global $_LANG_ID; 

		$client =  new \GuzzleHttp\Client();

		$response = $client->request(
			'GET', 	
			'https://service.canadianblackbook.com/UsedCarWS/CanUsedAPI/StdEquip/' . $uvc, 	
			[
				'auth' => [$this->user, $this->pass],
				'query'	=> [
					"customerid"	=> $this->customer,
					"country"		=> $this->country
				]

			]
		);


		if ($response->getStatusCode() !== 200) {
			return null;
		}

		$data = json_decode($response->getBody()->getContents(),true);


		return $data["std_equip"]["category_list"];
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
	function getAutoComplete($query) {
		global $_LANG_ID; 

		$client =  new \GuzzleHttp\Client();

		$response = $client->request(
			'GET', 	
			'https://service.canadianblackbook.com/UsedCarWS/CanUsedAPI/Autocomplete', 	
			[
				'auth' => [$this->user, $this->pass],
				'query'	=> [
					"searchText"	=> $query,
					"customerid"	=> $this->customer,
					"country"		=> $this->country,
					"template"		=> 12
				]

			]
		);


		if ($response->getStatusCode() !== 200) {
			return null;
		}

		$data = json_decode($response->getBody()->getContents(),true);

		return $data["autocomplete"];

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
	function getUsedYears($process = false) {
		global $_LANG_ID; 


		$client =  new \GuzzleHttp\Client();

		$response = $client->request(
			'GET', 	
			'https://service.canadianblackbook.com/UsedCarWS/CanUsedAPI/Drilldown/ALL', 	
			[
				'auth' => [$this->user, $this->pass],
				'query'	=> [
					"customerid"	=> $this->customer,
					"country"		=> $this->country,
					"template"		=> 12,
					"drilldeep"		=> "false"
				]

			]
		);


		if ($response->getStatusCode() !== 200) {
			return null;
		}

		$data = json_decode($response->getBody()->getContents(),true);

		if ($process) {
			debug($data["drilldown"]["class_list"],1);
			foreach ($data["drilldown"]["class_list"] as $year) {
			}
			
		}
		

		return $data["drilldown"]["class_list"][0]["year_list"];


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
	function getUsedMakesByYear($year) {
		global $_LANG_ID; 

		$client =  new \GuzzleHttp\Client();

		if (!($year > 1987 && $year <= date("Y"))) {
			return null;
		}
	
		$response = $client->request(
			'GET', 	
			'https://service.canadianblackbook.com/UsedCarWS/CanUsedAPI/Drilldown/ALL', 	
			[
				'auth' => [$this->user, $this->pass],
				'query'	=> [
					"customerid"	=> $this->customer,
					"country"		=> $this->country,
					"template"		=> 12,
					"drilldeep"		=> "false",
					"year"			=> $year
				]

			]
		);


		if ($response->getStatusCode() !== 200) {
			return null;
		}

		$data = json_decode($response->getBody()->getContents(),true);
		return $data["drilldown"]["class_list"][0]["year_list"][0]["make_list"];


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
	function getUsedModelsByYearMake($year , $make) {
		global $_LANG_ID; 

		$client =  new \GuzzleHttp\Client();

		if (!($year > 1987 && $year <= date("Y"))) {
			return null;
		}
	
		$response = $client->request(
			'GET', 	
			'https://service.canadianblackbook.com/UsedCarWS/CanUsedAPI/Drilldown/ALL', 	
			[
				'auth' => [$this->user, $this->pass],
				'query'	=> [
					"customerid"	=> $this->customer,
					"country"		=> $this->country,
					"template"		=> 12,
					"drilldeep"		=> "false",
					"year"			=> $year,
					"make"			=> $make
				]

			]
		);


		if ($response->getStatusCode() !== 200) {
			return null;
		}

		$data = json_decode($response->getBody()->getContents(),true);

		return $data["drilldown"]["class_list"][0]["year_list"][0]["make_list"][0]["model_list"];
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
	function getUsedTrimsByYearMakeModel($year , $make , $model) {
		global $_LANG_ID; 

		$client =  new \GuzzleHttp\Client();

		if (!($year > 1987 && $year <= date("Y"))) {
			return null;
		}
	
		$response = $client->request(
			'GET', 	
			'https://service.canadianblackbook.com/UsedCarWS/CanUsedAPI/Drilldown/ALL', 	
			[
				'auth' => [$this->user, $this->pass],
				'query'	=> [
					"customerid"	=> $this->customer,
					"country"		=> $this->country,
					"template"		=> 12,
					"drilldeep"		=> "false",
					"year"			=> $year,
					"make"			=> $make,
					"model"			=> $model,
				]

			]
		);


		if ($response->getStatusCode() !== 200) {
			return null;
		}

		$data = json_decode($response->getBody()->getContents(),true);

		return $data["drilldown"]["class_list"][0]["year_list"][0]["make_list"][0]["model_list"][0]["series_list"];


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
	function getTradeInByVIN($vin, $km = "") {
		global $_LANG_ID; 


		$client =  new \GuzzleHttp\Client();
	
		$response = $client->request(
			'GET', 	
			'https://service.canadianblackbook.com/UsedCarWS/CanUsedAPI/UsedVehicle/VIN/' . $vin, 	
			[
				'auth' => [$this->user, $this->pass],
				'query'	=> [
					"customerid"	=> $this->customer,
					"country"		=> $this->country,
					"template"		=> 12,
					"kilometres"	=> $km,
				]

			]
		);

		if ($response->getStatusCode() !== 200) {
			return null;
		}

		$data = json_decode($response->getBody()->getContents(),true);

		return $data["used_vehicles"]["used_vehicle_list"][0];

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
	function getTradeInByUVC($uvc, $km = "") {
		global $_LANG_ID; 


		$client =  new \GuzzleHttp\Client();
	
		$response = $client->request(
			'GET', 	
			'https://service.canadianblackbook.com/UsedCarWS/CanUsedAPI/UsedVehicle/UVC/' . $uvc, 	
			[
				'auth' => [$this->user, $this->pass],
				'query'	=> [
					"customerid"	=> $this->customer,
					"country"		=> $this->country,
					"template"		=> 12,
					"kilometres"	=> $km,
				]

			]
		);

		if ($response->getStatusCode() !== 200) {
			return null;
		}

		$data = json_decode($response->getBody()->getContents(),true);

		return $data["used_vehicles"]["used_vehicle_list"][0];

	}
	
}
/*


if ($_GET["X"] == "X") {

	debug( CBB::create()
		->setCustomerID("test")
		->setCredentials('Novo_API_Whsl', 'qL8JF6yGcu89')
		//->setCredentials('NovoSteer_API_Trd' , 'VtEkN68MW2pd')
		->decodeVIN("2C4RDGBGXLR167622")
		//->getAutoComplete("2017 Honda O")
	);

}


*/