<?php



class CNovosteerAddonAutoBrands extends CNovosteerAddonAutoBrandsBackend{

	/**
	* description
	*
	* @param
	*
	* @return
	*
	* @access
	*/
	function DoEvents(){
		global $base, $_CONF, $_TSM , $_VARS , $_USER , $_BASE , $_SESS;

		parent::DoEvents();

		if ($_GET["mod"] == $this->name) {

			$this->__init();
			$this->plugins["globalhooks"]->SetModule($this->tpl_module);

			switch ($_GET["sub"]) {
				case "ajax.menu":
					return $this->ajaxMenu();
				break;

				case "infopage":
					return $this->InfoPage();
				break;

				case "infopage.download":
					return $this->InfoPageDownload();
				break;

				case "infopages":
					return $this->InfoPages();
				break;

			}
			
		
		}
	}


	function __init() {
		global $_CONF , $_SESS , $site;

		if ($this->__inited) {
			return "";
		}

		$this->__inited = true;
		
		$this->__initTemplates([
			"elements"	=> "elements.twig",
			"menu"		=> "menu.twig",
			"infopages"	=> "infopages.twig",
		]);

		$this->tpl_module = $this->plugins["modules"]->LoadDefaultModule($this->name);

		//$this->decodeParams();

		$this->cache = $site->shared->addChild($this->name);
		$this->cache->addChild("brands");
		$this->cache->addChild("models");
		$this->cache->addChild("trims");
		$this->cache->addChild("pages");
		$this->cache->addChild("types");
		$this->cache->addChild("discountimages");
		$this->cache->addChild("certifiedimages");
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
	function setHooks() {
		global $base , $_USER , $_SESS; 

		parent::setHooks();
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
	function CronUpdateVehicles($job) {
		global $_LANG_ID; 

		$job->log("Starting vehicles images");


		$this->plugins["novosteer-dealerships"]->__init(); 

		$this->__init();

		$client = \Stembase\Lib\File\GoogleSheets::create()
			->setCredentialsString($this->plugins["novosteer-dealerships"]->_s("set_keyfile"))
			->setSheetId($this->_s("set_vehicles_sheet_id"))
			->setWorksheet($this->_s("set_vehicles_worksheet"));

		$vehicles = $client->getAllvalues();

		$ids = [];

		$imageProcessor = new \Intervention\Image\ImageManager(array('driver' => "gd"));

		if (is_array($vehicles)) {
			foreach ($vehicles as $key => $vehicle) {

				$data = explode(" - " , $vehicle["Model"]);
				$data[0] = trim($data[0]);
				$data[1] = trim($data[1]);

				$bid = $this->getBrandIdByName(
					trim($data[0]), 
					true, 
					true
				);

				$mid = $this->getModelIdByName(
					$bid , 
					$data[1], 
					true
				);

				$tid = $this->getTrimIdByName(
					$bid , 
					$vehicle["Trim"], 
					true,
					true
				);

				$cid = $this->getColorIdByName(
					$bid , 
					$vehicle["Color"], 
					true,
					null,
					null,
					null,
					true
				);

				$image = $this->getVehicleByYearModelTrimColor(
					$vehicle["Year"],
					$mid,
					$tid,
					$cid,
					true
				);

				if (is_array($image)) {
					$ids[] = $image["vehicle_id"];

					if ($image["vehicle_hash"] != md5($vehicle["Image Link"])) {
						$job->log("Updating model {$vehicle['Year']}/{$vehicle['Model']}/{$vehicle['Trim']}/{$vehicle['Color']}");
	
						$this->downloadVehicleImage($job , $image["vehicle_id"] , $vehicle["Image Link"] , $imageProcessor);
					} else {
						$job->log("No changes skipping.");
					}
				} else {
					$job->log("Creating new model {$vehicle['Year']}/{$vehicle['Model']}/{$vehicle['Trim']}/{$vehicle['Color']}");
					$id = $this->db->QueryInsert(
						$this->tables["plugin:novosteer_addon_autobrands_vehicles"],
						[
							"vehicle_status"	=> "-1",
							"vehicle_year"		=> $vehicle["Year"],
							"model_id"			=> $mid,
							"trim_id"			=> $tid,
							"color_id"			=> $cid,
						]
					);

					$ids[] = $id;

					$this->downloadVehicleImage($job , $id, $vehicle["Image Link"] , $imageProcessor);
				}			
			}			
		}

		//

//		debug($cnt);

		$job->log("Finished");		
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
	function downloadVehicleImage($job , $id, $link , $imageProcessor) {
		global $_LANG_ID; 

		$pathInfo = pathinfo(parse_url($link)["path"]);

		$job->log("Downloading image: " . $link . " ...");

		try {

			$image = $imageProcessor->make($link)
				->resize(
					null , 
					550 , 
					function($constraint) {
						$constraint->aspectRatio();
					}
				);
			
			$this->storage->public->saveStream(
				"vehicles/stock/" . $id . "." . $pathInfo["extension"], 
					$image
					->stream($pathInfo["extension"], 85)
					->detach()
			);

			//if png add a white background to the image
			if ($pathInfo["extension"] == "png") {
				$this->storage->public->saveStream(
					"vehicles/stock/" . $id . "." . "webp", 
						$image
						->stream("webp", 85)
						->detach()
				);
			}



			$this->db->QueryUpdateByID(
				$this->module->tables["plugin:novosteer_addon_autobrands_vehicles"],
				[
					"vehicle_image"			=> 1,
					"vehicle_image_type"	=> $pathInfo["extension"],
					"vehicle_image_date"	=> time(),
					"vehicle_hash"			=> md5($link)
				],
				$id
			);


			$job->log("Done\n");

		} catch ( \Exception $e ) {

			$this->db->QueryUpdateByID(
				$this->module->tables["plugin:novosteer_addon_autobrands_vehicles"],
				[
					"vehicle_image"	=> 0,
					"vehicle_hash"	=> "",
				],
				$id
			);

			$job->log("Error: %s\n" , $e->getMessage());
		}


	}
	
	
}



