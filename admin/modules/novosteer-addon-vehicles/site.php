<?php
/*
	Stempora Web Framework
	Copyright (c) 2002-2020 Stempora. 
	All rights reserved.
		web:  www.stempora.com
		mail: support@stempora.com				
*/

################ NOTE here i'll need to take in consideration the volume discounts ,

/**
* description
*
* @library	
* @author	
* @since	
*/
class CNovoSteerAddonVehicles extends CNovoSteerAddonVehiclesBackend{	

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
		global $base, $_CONF, $_TSM , $_VARS , $_USER , $_BASE , $_SESS;

		//$this->__initBranding();

		parent::DoEvents();

		if ($_GET["mod"] == $this->name) {

			switch ($_GET["sub"]) {

				case "feed":
					return $this->FeedInventory($_GET['dealer_id']);
				break;
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
	function __init() {
		global $_CONF , $_SESS;

		if ($this->__inited) {
			return "";
		}

		$this->__inited = true;
		$this->tpl_module = $this->plugins["modules"]->LoadDefaultModule($this->name);
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
	function feedInventory($id) {
		global $base , $_USER , $_SESS , $_CONF , $_LANG_ID; 


		$dealer = $this->plugins["novosteer-dealerships"]->getDealershipByID($id);

		if (!is_array($dealer)) {
			return $this->json(["status" => "error" , "error" => "Invalid Dealership"]);
		}

		$vehicles = $this->getDealershipVehicles($dealer["dealership_id"]);

		$data = [
			""
		];

		foreach ($vehicles as $key => $vehicle) {
			$dataItem = [
				"vin"	=> $vehicle["vin"],
				"cat"	=> $vehicle["type"],
				"stock"	=> $vehicle["stock"],
				"make"	=> $vehicle["brand_name"],
				"model"	=> $vehicle["model_name"],
				"trim"	=> $vehicle["trim_name"],
				"body" => $vehicle["body"],
				"modelnumber" => $vehicle["modelnumber"],
				"doors" => $vehicle["doors"],
				"miles" => $vehicle["miles"],
				"sellingprice" => $vehicle["sellingprice"],
				"msrp" => $vehicle["msrp"],
				"calc_price" => $vehicle[""],
				"bookvalue" => $vehicle["bookvalue"],
				"invoice" => $vehicle["invoice"],
				"certified" => $vehicle["certified"],
				"dateinstock"	=> $vehicle["dateinstock"],
				"description"	=> $vehicle["description"],

				"style_description" => $vehicle["style_description"],

				"exteriorcolor" => $vehicle["exteriorcolor"],
				"ext_color_generic" => [
					"value" => $vehicle["ext_color_generic"],
					"color"	=> $vehicle["extcolorhexcode"],
				],

				"ext_color_code" => $vehicle["ext_color_code"],

				"interiorcolor" => $vehicle["interiorcolor"],

				"int_color_generic" => [ 
					"value"	=> $vehicle["int_color_generic"],
					"color"	=> $vehicle["intcolorhexcode"],
				],

				"int_color_code" => $vehicle["int_color_code"],
				"int_upholstery" => $vehicle[""],
				"engine" => $vehicle["engine"],
				"enginecylinders" => $vehicle["enginecylinders"],
				"enginedisplacement" => $vehicle["enginedisplacement"],
				"engine_block_type" => $vehicle["engine_block_type"],
				"engine_aspiration_type" => $vehicle["engine_aspiration_type"],
				"engine_description" => $vehicle["engine_description"],
				"transmission" => $vehicle["transmission"],
				"transmission_speed" => $vehicle["transmission_speed"],
				"transmission_description" => $vehicle["transmission_description"],
				"drivetrain" => $vehicle["drivetrain"],
				"fuel_type" => $vehicle["fuel_type"],
				"citympg" => $vehicle["citympg"],
				"highwaympg" => $vehicle["highwaympg"],
				"epaclassification" => $vehicle["epaclassification"],
				"wheelbase_code" => $vehicle["wheelbase_code"],
				"internet_price" => $vehicle[""],
				"misc_price1" => $vehicle[""],
				"misc_price2" => $vehicle[""],
				"misc_price3" => $vehicle[""],

				"factory_codes"	=> explode("," , $vehicle["factory_codes"]),
				
				"marketclass" => $vehicle["marketclass"],

				"passengercapacity" => $vehicle["passengercapacity"],
				"enginedisplacementcubicinches" => $vehicle["enginedisplacementcubicinches"],

				"mainimage" => $this->getVehicleMainImage($vehicle["product_id"]),
				"images"	=> [
					$this->getVehicleImages($vehicle["product_id"])
				],

				"options"	=> explode("," , $vehicle["options"]),
		
			];


			if ($vehicle["categorized_options"]) {
				$options = explode("~" , $vehicle["categorized_options"]);

				foreach ($options as $key => $cat) {
					$tmp = explode("@" , $cat);
					$dataItem[strtolower("options_" . $tmp[0])][] = $tmp[1];
				}			
			}


			$data["inventory"][] = $dataItem;

			$data["last_update"] = max($data["last_update"] , $vehicle["product_date_last_update"]);
		}
		

		return $this->json($data);

		debug($vehicles,1);
		
	}
	


}
