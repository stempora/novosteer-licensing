<?php

class CNovosteerDealerships extends CNovosteerDealershipsBackend{

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
		if ($this->__inited == true ) {
			return true;
		}

		$this->__inited = true;
	
		$this->tpl_module = $this->plugins["modules"]->LoadDefaultModule($this->name);
		$this->module = &$this->plugins["products"];

	}



	function DoEvents(){
		global $base, $_CONF, $_TSM , $_VARS , $_USER , $_BASE , $_SESS;

		if ($_GET["mod"] == $this->name) {
			//read the module

			$this->__init();

			parent::DoEvents();

			$sub = $_GET["sub"];
			$action = $_GET["action"];

			switch ($sub) {	
				case "landing":
					$_GET["sub"] = $sub = "dealerships/dealerships";
				Case "dealerships/dealerships":
				Case "manufacturers/manufacturers":
				Case "manufacturers/calculators":
				Case "vehicles/vehicles":
					$data = new CSQLAdmin($sub, $this->__parent_templates,$this->db,$this->tables,$extra);
					$data->setAclMod($this->tpl_module);
					$this->PrepareFields($data->forms["forms"]);

					if ($sub == "manufacturers/calculators") {
						$this->CalculatorPrepareFields($data->forms);
						$this->CalculatorPrepareValues($data->forms["forms"]);

						$data->functions = [
								"onstore" => [&$this , "CalculatorStore" ],
						];					
					}
					
					return $data->DoEvents();
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
	function dealershipRemoveBoxes(&$forms) {
		global $base , $_USER , $_SESS; 

		$forms["details"]["fields"]["box"] = [];
		unset($forms["details"]["fields"]["box"]);
		unset($forms["edit"]["append_fields"]);

		$this->removeBoxes = true;
		
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
	function CalculatorPrepareFields(&$forms) {


		if ($_REQUEST["calculator_id"]) {			

			$client = $this->getCalculatorObject($_REQUEST["calculator_id"]);

			if (is_object($client)) {
				$fields = $client->getAdminFields();

				if (is_array($fields)) {

					$forms["forms"]["edit"]["fields"]["box"][] = $fields["fields"]["box"];
					$forms["forms"]["details"]["fields"]["box"][] = $fields["fields"]["box"];
				}

				if ($fields["javascript"]["after"]) {
					$forms["forms"]["edit"]["javascript"]["after"] = $fields["javascript"]["after"];
					$forms["forms"]["details"]["javascript"]["after"] = $fields["javascript"]["after"];
				}				

				if (is_array($fields["remove_fields"])) {
					CForm::DeleteFields($forms["forms"]["edit"] , array_keys($fields["remove_fields"]));
					CForm::DeleteFields($forms["forms"]["details"] , array_keys($fields["remove_fields"]));
				}
				
			}			

		}
			
	}

	function CalculatorPrepareValues(&$forms) {

		if ($_REQUEST["calculator_id"]) {			

			$hook = $this->getCalculatorById($_REQUEST["calculator_id"]);
			$values = $hook["settings"];


			if (is_array($forms["edit"]["fields"])) {
				foreach ($forms["edit"]["fields"] as $key => $val) {

					if ($key == "box") {
						foreach ($val as $k => $v) {
							foreach ($v["fields"] as $_k => $_v) {
								$forms["edit"]["fields"][$key][$k]["fields"][$_k]["default"] = $values[$_k];
								$forms["details"]["fields"][$key][$k]["fields"][$_k]["default"] = $values[$_k];
							}
						}
						
					} else {				
						$forms["edit"]["fields"][$key]["default"] = $values[$key];
						$forms["details"]["fields"][$key]["default"] = $values[$key];
					}
				}
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
	function CalculatorStore($record , $form) {

		if ($record["calculator_id"]) {

			//fields
			$old = $this->getCalculatorById($record["calculator_id"]);
			
			foreach ($_POST as $key => $val) {
				if (stristr($key , "set_")) {
					$data[$key] = $val;
				}			
			}

			if (is_array($data)) {
				$this->db->QueryUpdate(
					$this->tables["plugin:novosteer_manufacturers_calculators"],
					array(
						"calculator_settings"	=> json_encode($data),
					),
					$this->db->Statement(
						"calculator_id=%d",
						[$record["calculator_id"]]
					)
				);
			}
		}		
	}

}
