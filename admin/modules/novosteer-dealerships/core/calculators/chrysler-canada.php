<?php
/*
	Stempora Web Framework
	Copyright (c) 2002-2020 Stempora. 
	All rights reserved.
		web:  www.stempora.com
		mail: support@stempora.com				
*/

namespace Stembase\Modules\Novosteer_Dealerships\Core\Calculators;

if (!defined("STPBase")) {
	die("This file can't be accessed directly!");
}



use \Stembase\Modules\Novosteer_Dealerships\Core\Models\Calculator;
use \Stembase\Modules\Novosteer_Dealerships\Core\Models\Discounts;
use \CTemplateStatic;
use \CFile;

class Chrysler_Canada extends Calculator {


	/**
	* description
	*
	* @param
	*
	* @return
	*
	* @access
	*/
	function getPath() {
		global $base , $_USER , $_SESS; 

		$path = parent::getPath();

		return str_replace(".php" , "" , __FILE__);
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
	function loadDiscounts() {
		global $base , $_USER , $_SESS , $_CONF , $_LANG_ID; 

		if (!$this->discounts) {
			$discounts = $this->getRemoteCSV($this->info["settings"]["set_discounts_doc"]);

			$this->discounts = new Discounts();

			foreach ($discounts as $key => $discount) {
				$rules = [];

				foreach ($discount as $k => $v) {

					$rule = [];

					if (trim($v)) {
						$v = trim($v);

						if (stristr($k , "d:")) {
							$rules["discounts"][str_replace("d:" , "" , $k)] = $v;
						} elseif (!in_array($k , ["y1" , "y2" , "y3" , "y4" , "y5" , "y6" , "y7" , "y8"])) {						
							if (stristr($v , "|not")) {
								$rule["type"] = "2";
								$v = str_ireplace("|not" , ""  , $v);
							} else {
								$rule["type"] = "1";
							}
							
							$tmp = explode("," , $v);
							$rule["values"] = $tmp;
							$rule["field"] = $k;

							$rules["conds"][] = $rule;
						}

						
					}
				}

				$this->discounts->addRule($rules);				
			}
		}


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
	function calculatePrice() {
		global $base , $_USER , $_SESS , $_CONF , $_LANG_ID; 

		if ($this->vehicle["type"] != "New") {
			return false;
		}

		$this->loadDiscounts();

		$rule = $this->discounts
			->setVehicle($this->vehicle)
			->getMatchingRule();
		
		//no rule found, so i mark the product as with problems to investigate
		if (!is_array($rule)) {
			return false;
		} else {
			$price = $this->vehicle[$this->msrpField];

			if (!is_array($rule["discounts"]) || !$this->vehicle[$this->msrpField]) {
				return false;
			} else {			
				foreach ($rule["discounts"] as $key => $discount) {

					if (stristr($discount , "%") !== false) {
						//percentage
						$price = $price - ( $price * str_replace("%" , "" , $discount) / 100);

					} elseif ( $discount == "EP") {
						//belit pula, dealersocket
					} else {
						//flat discount

						$price -= abs($discount);
					}
									
				}


				return $price;
			}
			
		}
	}
	

	
}

