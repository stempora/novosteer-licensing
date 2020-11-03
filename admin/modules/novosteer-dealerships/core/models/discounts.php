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


class Discounts extends Base{

	/**
	* description
	*
	* @var type
	*
	* @access type
	*/
	var $rules = [];
	

	/**
	* description
	*
	* @param
	*
	* @return
	*
	* @access
	*/
	function addRule($rule) {
		global $base , $_USER , $_SESS , $_CONF , $_LANG_ID; 

		$this->rules[] = $rule;

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
	function getRules() {
		global $base , $_USER , $_SESS , $_CONF , $_LANG_ID; 

		return $this->rules;
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
	function getMatchingRule() {
		global $base , $_USER , $_SESS , $_CONF , $_LANG_ID; 

		$found = [];

		foreach ($this->rules as $key => $rule) {

			if ($this->compareRuleAgainstProduct($rule)) {
				$found[] = $rule;
			}			
		}

		if (is_array($found)) {
			$cur = null;
			foreach ($found as $key => $val) {
				if ($cur == null) {
					$cur = $val;
				} elseif (count($cur["conds"]) < count($val["conds"])) {
					$cur = $val;				
				}
			}
			
			return $val;
		}

		return null;	
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
	function compareRuleAgainstProduct($rule) {
		global $base , $_USER , $_SESS , $_CONF , $_LANG_ID; 

		if (!is_array($rule["conds"])) {
			return false;
		}		
		
		foreach ($rule["conds"] as $k => $ruleItem) {

			$value = $this->vehicle[$ruleItem["field"]];
		
			if (!$value) {
				return false;
			}			

			switch ($ruleItem["type"]) {
				//must include
				case "1":
					if (is_array($value)) {
						if (!array_intersect($value , $ruleItem["values"])) {
							return false;
						}
						
					} else {
						if (!in_arrayi($value , $ruleItem["values"])) {
							return false;
						}						
					}
					
				break;

				//must not include
				case "2":
					if (is_array($value)) {
						if (array_intersect($value , $ruleItem["values"])) {
							return false;
						}
					} else {
						if (in_arrayi($value , $ruleItem["values"])) {
							return false;
						}						
					}
				break;
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
	function setVehicle($vehicle) {
		global $base , $_USER , $_SESS , $_CONF , $_LANG_ID; 

		$vehicle["factory_codes"] = json_decode($vehicle["factory_codes"] , true);

		$this->vehicle = $vehicle;
		
		return $this;
	}
	
	
}
