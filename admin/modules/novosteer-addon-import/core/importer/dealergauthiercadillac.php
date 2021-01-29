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


use \Stembase\Modules\Novosteer_Addon_Import\Core\Models\DealerFormula;

class DealerGauthierCadillac extends DealerFormula{

	var $hints = "		
		<p><b>Formula New Vehicles</b>

		<ul>
			<li>msrp = msrp</li>
			<li>price_1 = incentives </li>
			<li>price_2 = sales </li>
			<li>price_4 = protection </li>
			<li>price_5 = retail  </li>
		</ul>
		
		<p><br><b>Formula Used Vehicles</b>
		<ul>
			<li>price_2 = sales </li>
			<li>price_5 = retail </li>
			<li>price_6 = discount ( retail - sales) </li>
		</ul>

		";
	
	/**
	* description
	*
	* @param
	*
	* @return
	*
	* @access
	*/
	function processFeedItem(&$item) {
		global $base , $_USER , $_SESS , $_CONF , $_LANG_ID; 

		$item["price_1"]	= 				
		$item["price_2"]	= 				
		$item["price_3"]	= 				
		$item["price_4"]	= 				
		$item["price_5"]	=  
		$item["price_6"]	=  0;

		switch ($item["cat"]) {
			case "New":

				$item["price_1"] = $item["msrp"] - $item["bookvalue"]; 
				$item["price_2"] = $item["bookvalue"];
				$item["price_4"] = 999;
				$item["price_5"] = $item["msrp"];							
			break;

			default:
					$item["price_2"] = $item["sellingprice"];
					$newprice = 0;

					//if set use the value from RR
					if ($item["msrp"] && ($item["msrp"] > $item["sellingprice"])) {
						$newprice = $item["msrp"];
					} else {					
						//add a 20% on top of selling price
						$newprice = $item["sellingprice"] * ( (100 + 20) / 100 );
					}

					if ($item["price_2"] && ($item["price_2"] < $newprice)) {
						$item["price_6"] = $newprice - $item["price_2"];
						$item["price_2"] = $newprice;
					}
			break;
		}	
	}	
	
}
