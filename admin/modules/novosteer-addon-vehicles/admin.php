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
					$_GET["sub"] = $sub = "export";
				case "import":
				case "export":
				case "alerts":
					$data = new CSQLAdmin($sub, $this->__parent_templates,$this->db,$this->tables,$extra);
					$data->setAclMod($this->tpl_module);
					$this->PrepareFields($data->forms["forms"]);

					if ($sub == "import") {
						$data->functions = [
								"onstore" => [&$this , "VehicleImportStore" ],
						];					
					}
					

					return $data->DoEvents();
				break;

				case "import.status":
					return $this->importStatus();
				break;

				case "images.delete":
					return $this->imagesDelete();
				break;

				case "cache.delete":
					return $this->cacheDelete();
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
					$image["image_source"] = "<a href=\"{$image['image_source']}\" rel=\"prettyPhoto\"><img width=\"100%\" src=\"" . $image["image_source"] . "\" /></a>";
				}				

				if ($image["image_downloaded"]) {
					$source = $this->storage->getLocation($dealership["dealership_location"])->getUrl($dealership["dealership_location_prefix"] . "/export/" . $vehicle['product_sku'] . "/original/" . $image["image_id"] . ".jpg");
					$image["image_downloaded"] = "<a href=\"{$source}\" rel=\"prettyPhoto\"><img width=\"100%\" src=\"" . $source . "\" /></a>";
				}				

				if ($image["image_overlay"] ) {
					$source = $image["image_overlay_url"] 
						? $image["image_overlay_url"] 
						: $this->storage->getLocation($dealership["dealership_location"])->getUrl($dealership["dealership_location_prefix"] . "/export/" . $vehicle['product_sku'] . "/final/" . $image["image_id"] . ".jpg");

					$image["image_overlay"] = "<a href=\"{$source}\" rel=\"prettyPhoto\"><img width=\"100%\" src=\"" . $source . "\" /></a>";
				}				
			}
			
		}
		
		return [
			"items"	=>$images,
			"count"	=>$count,
		];
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
	function ImportImages($images , $count) {
		global $_LANG_ID; 

		$vehicle = $this->getImportVehicleByID($_GET["product_id"]);
		$dealership = $this->plugins["novosteer-dealerships"]->getDealershipByID($vehicle["dealership_id"]);

		if (is_array($images)) {
			foreach ($images as $key => &$image) {

				if ($image["image_source"]) {
					$image["image_source"] = "<a href=\"{$image['image_source']}\" rel=\"prettyPhoto\"><img width=\"100%\" src=\"" . $image["image_source"] . "\" /></a>";
				}				

				if ($image["image_downloaded"]) {
					$source = $this->storage->getLocation($dealership["dealership_location"])->getUrl($dealership["dealership_location_prefix"] . "/inventory/" . $vehicle['product_sku'] . "/" . $image["image_id"] . ".jpg" , $image["image_downloaded_date"]);
					$image["image_downloaded"] = "<a href=\"{$source}\" rel=\"prettyPhoto\"><img width=\"100%\" src=\"" . $source . "\" /></a>";
				}				

				if ($image["image_overlay"]) {
					$source = $this->storage->getLocation($dealership["dealership_location"])->getUrl($dealership["dealership_location_prefix"] . "/inventory/" . $vehicle['product_sku'] . "/over_" . $image["image_id"] . ".jpg" , $image["image_overlay_date"]);
					$image["image_overlay"] = "<a href=\"{$source}\" rel=\"prettyPhoto\"><img width=\"100%\" src=\"" . $source . "\" /></a>";
				}				

			}
			
		}
		
		return [
			"items"	=>$images,
			"count"	=>$count,
		];
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
	function importStatus() {
		global $_LANG_ID; 

		if (is_array($_POST["product_id"])) {
			$this->db->QueryUpdate(
				$this->tables["plugin:novosteer_vehicles_import"],
				[
					"product_status"	=> $_GET['type'] == "disabled" ? 0 : 1,
				],
				$this->db->Statement(
					"product_id in (%s)",
					[ implode("," , $_POST["product_id"])]
				)
			);
		}

		die("1");		
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
	function imagesDelete() {
		global $_LANG_ID; 

		if (is_array($_POST["image_id"])) {
			$this->db->QueryUpdate(
				$this->tables["plugin:novosteer_vehicles_" . ($_GET["type"] == "import" ? "import" : "export") . "_images"],
				[
					"image_deleted"	=> 1,
				],
				$this->db->Statement(
					"image_id in (%s)",
					[ implode("," , $_POST["image_id"])]
				)
			);

		}

		die("1");		

		debug($_POST,1);
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
	function cacheDelete() {
		global $_LANG_ID; 

		if (is_array($_POST["product_id"])) {
			$this->db->Query(
				"DELETE FROM %s WHERE product_id IN (%s)",
				[
					$this->tables["plugin:novosteer_addon_" . ($_GET["type"] == "import" ? "importer" : "export") . "_items"],
					implode("," , $_POST["product_id"])
				]
			);
		}
		die("1");		
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
	function VehicleImportStore($record) {
		global $_LANG_ID; 

		$this->db->Query(
			"DELETE FROM %s WHERE product_id IN (%s)",
			[
				$this->tables["plugin:novosteer_addon_importer" . ($_GET["type"] == "import" ? "import" : "export") . "_items"],			
			],
			$this->db->Statement(
				"product_id = %d",
				[  $record["product_id"] ]
			)
		);
	}
	
}
