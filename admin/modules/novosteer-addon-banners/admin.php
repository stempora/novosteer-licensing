<?php

class CNovosteerAddonBanners extends CNovosteerAddonBannersBackend{

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
		global $base, $_CONF, $_TSM , $_VARS , $_USER , $_BASE;

		if ($_GET["mod"] == $this->name) {
			//read the module

			$this->__init();

			parent::DoEvents();

			$sub = $_GET["sub"];
			$action = $_GET["action"];

			switch ($sub) {	
				case "landing":
					$sub = "banners";

				case "banners":
				case "widebanners":
					$data = new CSQLAdmin($sub, $this->__parent_templates,$this->db,$this->tables,$extra);
					$data->setAclMod($this->tpl_module);
					$this->PrepareFields($data->forms["forms"]);
					return $data->DoEvents();
				break;
			}
		}
	}

}
