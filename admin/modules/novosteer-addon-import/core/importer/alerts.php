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
use \CHeaders;

class Alerts extends Importer {

	/**
	* description
	*
	* @param
	*
	* @return
	*
	* @access
	*/
	function getFile($default= null) {
		global $_LANG_ID; 

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
	function loadFeedFile($file) {
		global $_LANG_ID; 


		$alerts = explode("," , $this->info["settings"]["set_publish"]);

		$errors = [];

		if (in_array("alert_brand" , $alerts)) {
			$brands = $this->db->Linear(
				$this->db->QFetchRowArray(
					"SELECT 
						brand_name 
					FROM 
						%s as brands 
					WHERE 
						brand_id IN (
							SELECT 
								brand_id 
							FROM 
								%s as products
							WHERE 
								dealership_id = %d
						) AND 
						alert_brand = 1
					",
					[
						$this->module->tables["plugin:novosteer_addon_autobrands_brands"],
						$this->module->tables["plugin:novosteer_vehicles_import"],
						$this->info["dealership_id"]
						
					]
				)
			);

			if (is_array($brands)) {
				$errors[] = "Brands :" . implode(", " , $brands);
			}			
		}

		if (in_array("alert_model" , $alerts)) {
			$models = $this->db->Linear(
				$this->db->QFetchRowArray(
					"SELECT 
						model_name 
					FROM 
						%s as models 
					WHERE 
						model_id IN (
							SELECT 
								model_id 
							FROM 
								%s as products
							WHERE 
								dealership_id = %d
						) AND 
						alert_model = 1 OR NOT type_id
					",
					[
						$this->module->tables["plugin:novosteer_addon_autobrands_models"],
						$this->module->tables["plugin:novosteer_vehicles_import"],
						$this->info["dealership_id"]
						
					]
				)
			);

			if (is_array($models)) {
				$errors[] = "Models :" . implode(", " , $models);
			}			
		}


		if (in_array("alert_trim" , $alerts)) {
			$trims = $this->db->Linear(
				$this->db->QFetchRowArray(
					"SELECT 
						CONCAT(brand_name , ' - ' , trim_name ) as name
					FROM 
						%s as trims 
					INNER JOIN 
						%s as brands
						ON 
							trims.brand_id = brands.brand_id
					WHERE 
						trim_id IN (
							SELECT 
								trim_id 
							FROM 
								%s as products
							WHERE 
								dealership_id = %d AND 
								cat = 'New'									
						) AND 
						alert_trim = 1
					",
					[
						$this->module->tables["plugin:novosteer_addon_autobrands_trims"],
						$this->module->tables["plugin:novosteer_addon_autobrands_brands"],
						$this->module->tables["plugin:novosteer_vehicles_import"],
						$this->info["dealership_id"]						
					]
				)
			);

			if (is_array($trims)) {
				$errors[] = "Trims :" . implode(", " , $trims);
			}			
		}

		
		if (in_array("alert_price" , $alerts)) {
			$products = $this->db->Linear(
				$this->db->QFetchRowArray(
					"SELECT 
						stock
					FROM 
						%s
					WHERE
						dealership_id = %d AND
						alert_price = 1
					",
					[
						$this->module->tables["plugin:novosteer_vehicles_import"],
						$this->info["dealership_id"]						
					]
				)
			);

			if (is_array($products)) {
				$errors[] = "Vehicles :" . implode(", " , $products);
			}			
		}
	

		if (count($errors) && $this->info["settings"]["set_alert_email"]) {
			$this->log("Sending email alert ...");
			 $this->module->plugins["mail"]->SendMail(
				$this->module->plugins["mail"]->GetMail(
					$this->info["settings"]["set_alert_email"],				
					$this->info
				)
			);
		}

		if (count($errors) && $this->info["settings"]["set_alert_sms"]) {
			$this->log("Sending SMS alert ...");
			$this->plugins["sms"]->SendSMS(
				$this->plugins["sms"]->getTpl(
					$this->info["settings"]["set_alert_sms"],
					$this->info
				)
			);
		}


		return null;
	}

}
