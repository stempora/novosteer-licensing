<?php

class CNovosteerAddonVehicles extends CNovosteerAddonVehiclesBackend{

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
					$_GET["sub"] = $sub = "import";
				case "import":
				case "export":
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
	function ExportImages($images , $count) {
		global $_LANG_ID; 

		$vehicle = $this->getExportVehicleByID($_GET["product_id"]);
		$dealership = $this->plugins["novosteer-dealerships"]->getDealershipByID($vehicle["dealership_id"]);

		if (is_array($images)) {
			foreach ($images as $key => &$image) {

				if ($image["image_source"]) {
					$image["image_source"] = "<img width=\"100%\" src=\"" . $image["image_source"] . "\" />";
				}				

				if ($image["image_downloaded"]) {
					$source = $this->storage->getLocation($dealership["dealership_location"])->getUrl($dealership["dealership_location_prefix"] . "/export/" . $vehicle['product_sku'] . "/original/" . $image["image_id"] . ".jpg");
					$image["image_downloaded"] = "<img width=\"100%\" src=\"" . $source . "\" />";
				}				

				if ($image["image_overlay"]) {
					$source = $this->storage->getLocation($dealership["dealership_location"])->getUrl($dealership["dealership_location_prefix"] . "/export/" . $vehicle['product_sku'] . "/final/" . $image["image_id"] . ".jpg");
					$image["image_overlay"] = "<img width=\"100%\" src=\"" . $source . "\" />";
				}				
			}
			
		}
		
		return [
			"items"	=>$images,
			"count"	=>$count,
		];
	}
	
}
