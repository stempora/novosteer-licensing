<?php



class CNovosteerAddonSyndicateAutotrader extends CNovosteerAddonSyndicateAutotraderBackend{

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
					$sub = "brands";
					$data = new CSQLAdmin($sub, $this->__parent_templates,$this->db,$this->tables,$extra);
					$data->setAclMod($this->tpl_module);
					$this->PrepareFields($data->forms["forms"]);					
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
	function __init() {
		global $site; 

		if ($this->__inited == true ) {
			return true;
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
	function adminCronJob($module) {

		$jobs = [
			[
				"module"			=> $module["module_id"],
				"module_name"		=> $module["module_name"],					
				"module_code"		=> $module["module_code"],

				"type"				=> "1",
				"action"			=> "PushFeeds",

				"minute"			=> "0",
				"hour"				=> "11",
				"day"				=> "*",
				"month"				=> "*",
				"year"				=> "*",
			],
		];
		
		return $jobs;
	}

}
