<?php
/*
	Stempora Web Framework
	Copyright (c) 2002-2019 Stempora. 
	All rights reserved.
		web:  www.stempora.com
		mail: support@stempora.com				
*/




class CNovoSteerDealershipsBackend extends CPlugin {

	/**
	* description
	*
	* @var type
	*
	* @access type
	*/
	var $calculators = [];
	
	
	function __construct() {
		$this->name = "novosteer-dealerships";
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
	function getAllDealerships() {
		global $base , $_USER , $_SESS , $_CONF , $_LANG_ID; 

		return $this->db->QFetchRowArray(
			"SELECT * FROM %s WHERE dealership_status = 1",
			[
				$this->tables['plugin:novosteer_dealerships'],
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
	function getDealershipByID($id) {
		global $base , $_USER , $_SESS , $_CONF , $_LANG_ID; 

		return $this->db->QFetchArray(
			"SELECT * FROM %s WHERE dealership_id = %d",
			[
				$this->tables['plugin:novosteer_dealerships'],
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
	function getCalculatorById($id) {
		global $_LANG_ID;

		$data = $this->db->QFetchArray(
			"SELECT * FROM 			
				%s 
			WHERE				
				calculator_id=%d
			",
			[
				$this->tables['plugin:novosteer_manufacturers_calculators'],
				$id
			]			
		);

		if ($data["calculator_settings"]) {
			$data["settings"] = json_decode($data["calculator_settings"] , true );
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
	function getCalculatorObject($calculator = null) {
		global $base , $_USER , $_SESS; 

		$this->__init();

		if (!is_array($calculator)) {			
			$calculator = $this->getCalculatorById($calculator);				
		}


		if (!is_object($this->calculators[$calculator["calculator_id"]])) {

			$class = "\\Stembase\\Modules\\NovoSteer_Dealerships\\Core\\Calculators\\" . str_replace("-" , "_" ,  $calculator["calculator_extension"] );


			if (class_exists($class,true)) {

				$this->calculators[$calculator["calculator_id"]] = new $class($calculator);
				$this->calculators[$calculator["calculator_id"]]->setModule($this);
				$this->calculators[$calculator["calculator_id"]]->setInfo($calculator);				
			}
		}

		return $this->calculators[$calculator["calculator_id"]];		
	}


}