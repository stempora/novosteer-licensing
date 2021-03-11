<?php



class CNovosteerAddonAutoBrands extends CNovosteerAddonAutoBrandsBackend{

	function DoEvents(){
		global $base, $_CONF, $_TSM , $_VARS , $_USER , $_BASE , $_SESS;

		if ($_GET["mod"] == $this->name) {

			//read the module
			$this->__init();
			parent::DoEvents();

			$sub = $_GET["sub"];
			$action = $_GET["action"];

			switch ($sub) {


				case "texts":
					return $this->__adminTexts();
				break;

				case "landing":
					$sub = "brands";
				case "brands":
				case "models":
				case "trims":
				case "types":
				case "info":
				case "menu":
				case "colors":
				case "vehicles":
				case "info.trims":
					$_REQUEST["module_id"] = $this->tpl_module["module_id"];

					$data = new CSQLAdmin($sub, $this->__parent_templates,$this->db,$this->tables,$extra);
					$data->setAclMod($this->tpl_module);
					$this->PrepareFields($data->forms["forms"]);

					if ($sub == "models") {
						$data->functions = [
							"onstore"	=> [&$this , "storeModel"],
						];					

					}

					if ($sub == "info") {
						$data->functions = [
							"onstore"	=> [&$this , "storeInfoPage"],
						];					

					}
					
					
					return $data->DoEvents();
				break;

				case "autocomplete.attributes":
					return $this->AutoCompleteAttributes();
				break;

				case "autocomplete.brands":
					return $this->AutoCompleteBrands();
				break;

				case "autocomplete.models":
					return $this->AutoCompleteModels();
				break;

				case "autocomplete.trims":
					return $this->AutoCompleteTrims();
				break;

				case "trims.autocomplete":
					return $this->AutoCompleteTrims();
				break;

				case "autocomplete.options":
					return $this->AutoCompleteOptions();
				break;

				case "info.duplicate":
					return $this->DuplicateInfoPage($_GET['info_id']);
				break;

				case "info.duplicate.trim":
					return $this->DuplicateInfoPageTrim($_GET['info_id']);
				break;

				case "preview":
					return $this->PreviewInfoPage();
				break;

				case "menu-status":
					return $this->MenuStatus();
				break;

				case "alert.brands":
					return $this->alertStatus(
						$this->tables["plugin:novosteer_addon_autobrands_brands"],
						"brand_id",
						"alert_brand"
					);
				break;

				case "alert.models":
					return $this->alertStatus(
						$this->tables["plugin:novosteer_addon_autobrands_models"],
						"model_id",
						"alert_model"
					);
				break;

				case "alert.trims":
					return $this->alertStatus(
						$this->tables["plugin:novosteer_addon_autobrands_trims"],
						"trim_id",
						"alert_trim"
					);
				break;

				case "alert.colors":
					return $this->alertStatus(
						$this->tables["plugin:novosteer_addon_autobrands_colors"],
						"color_id",
						"alert_color"
					);
				break;

				case "alert.vehicles":
					return $this->alertStatus(
						$this->tables["plugin:novosteer_addon_autobrands_vehicles"],
						"vehicle_id",
						"vehicle_status"
					);
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

		$this->cache = $site->shared->addChild($this->name);
		$this->cache->addChild("brands");
		$this->cache->addChild("models");
		$this->cache->addChild("trims");

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
	function productPrepareList(&$items) {
		global $base , $_USER , $_SESS; 

		if (!$_GET["_columns"]) {
			return false;
		}

		$this->__init();

		$fields = explode("," , $_GET["_columns"]);


		foreach ($items as $key => &$item) {

			if (in_array("autobrands:make" , $fields)) {
				$make_field = $this->module->getFieldByCode($this->_field_brand, [$this, "createField"]);
				$item["autobrands_make"] = 
					$this->getBrandById(
						$this->module->getProductValueByField($item["item_id"] , $make_field["field_id"])
					)["brand_name"];
			}

			if (in_array("autobrands:model" , $fields)) {
				$model_field = $this->module->getFieldByCode($this->_field_model, [$this, "createField"]);
				$item["autobrands_model"] = 
					$this->getModelById(
						$this->module->getProductValueByField($item["item_id"] , $model_field["field_id"])
					)["model_name"];
			}

			if (in_array("autobrands:trim" , $fields)) {
				$trim_field = $this->module->getFieldByCode($this->_field_trim, [$this, "createField"]);
				$item["autobrands_trim"] = 
					$this->getTrimById(
						$this->module->getProductValueByField($item["item_id"] , $trim_field["field_id"])
					)["trim_name"];
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
	function widgetForm(&$form , &$values) {
		global $base , $_USER , $_SESS; 

		$this->__init();

		if ($this->tpl_module["module_status"] != 1) {
			return false;
		}
			

		$field = CForm::GetField($form["edit"],"set_type");

		$field["options"]["autobrands"] = "AutoBrands"; 
		CForm::updateField($form["edit"] , "set_type" , $field);
		CForm::updateField($form["details"] , "set_type" , $field);


		$fields = [
			"set_autobrand_brand" => [
				"width"		=> "100%",
				"title"		=> "Auto Brand/Model/Trim/Type",
				"type"		=> "droplist",
				"multi"		=> "true",
				"default"	=> $values["set_autobrand_brand"],
				"description" => "Select the brands to narrow the search.",
				"relation"	=> [
					"table"			=> "plugin:products_addon_autobrands_brands",
					"id"			=> "brand_id",
					"text"			=> "brand_name",
					"order"			=> "brand_name ASC"	
				],
				"referers"	=> "set_autobrand_model,set_autobrand_trim"
			],

			"set_autobrand_model" => [
				"width"		=> "100%",
				"type"		=> "droplist",
				"multi"		=> "true",
				"referer"	=> "true",
				"default"	=> $values["set_autobrand_model"],
				"description" => "Select the brands to narrow the search.",
				"relation"	=> [
					"table"			=> "plugin:products_addon_autobrands_models",
					"id"			=> "model_id",
					"text"			=> "model_name",
					"order"			=> "model_name ASC"	
				],
			],

			"set_autobrand_trim" => [
				"width"		=> "100%",
				"type"		=> "droplist",
				"referer"	=> "true",
				"multi"		=> "true",
				"default"	=> $values["set_autobrand_trim"],
				"description" => "Select the brands to narrow the search.",
				"relation"	=> [
					"table"			=> "plugin:products_addon_autobrands_trims",
					"id"			=> "trim_id",
					"text"			=> "trim_name",
					"order"			=> "trim_name ASC"	
				],
			],

			"set_autobrand_type" => [
				"width"		=> "100%",
				"type"		=> "droplist",
				"referer"	=> "true",
				"multi"		=> "true",
				"default"	=> $values["set_autobrand_type"],
				"description" => "Select the type to narrow the search.",
				"relation"	=> [
					"table"			=> "plugin:products_addon_autobrands_types",
					"table_lang"	=> "plugin:products_addon_autobrands_types_lang",
					"id"			=> "type_id",
					"text"			=> "type_name",
					"order"			=> "type_name ASC"	
				],
			]

		];

		CForm::insertFieldsAfterField($form["edit"] , "subtitle_filter" , $fields);
		CForm::insertFieldsAfterField($form["details"] , "subtitle_filter" , $fields);


		$fields = [
			"subtitle_autobrands" => [
				"title"		=> "Auto-Brands",
				"type"		=> "subtitle",
			],
			"subtitle_autobrands_comment" => [
				"type"			=> "comment",
				"description"	=> "<p>This type of widget works only in the product details page.</p>",
			],
			"set_autobrands_type"	=>  [
				"type"		=> "droplist",
				"title"		=> "Similar Vehicles",
				"options"	=> [
					"4"		=>  "Same Type",
					"1"		=>  "Same Brand",
					"5"		=>  "Same Brand & Type",
					"2"		=>  "Same Model",
					"3"		=>  "Same Model & Trim",
				]
			],

			"set_autobrands_category"	=>  [
				"type"		=> "droplist",
				"title"		=> "Category",
				"options"	=> [
					""		=>  "[ all ]",
					"1"		=>  "Same As Current",
					"2"		=>  "New Vehicles Only",
					"3"		=>  "Used Vehicles Only",
				]
			],

		];

		CForm::insertFieldsBeforeField($form["edit"] , "subtitle_filter" , $fields);
		CForm::insertFieldsBeforeField($form["details"] , "subtitle_filter" , $fields);

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
	function AutocompleteAttributes() {
		global $base , $_USER , $_SESS; 

		return $this->plugins["products-addon-attributes"]->AutocompleteAttributes();
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
	function AutocompleteOptions() {
		global $base , $_USER , $_SESS; 

		if ($_GET["set_new_field"]) {
			$_GET["option_parent"] = $_GET["set_new_field"];
		}

		if ($_GET["set_used_field"]) {
			$_GET["option_parent"] = $_GET["set_used_field"];
		}		

		if ($_GET["set_certified_field"]) {
			$_GET["option_parent"] = $_GET["set_certified_field"];
		}		

		if ($_GET["field_id"]) {
			$_GET["option_parent"] = $_GET["field_id"];
		}		

		return $this->plugins["products-addon-attributes"]->AutocompleteOptions();
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
	function storeModel($model) {
		global $base , $_USER , $_SESS; 

		$field_model = $this->module->getFieldByCode($this->_field_model , [$this, "createField"]);
		$field_type = $this->module->getFieldByCode($this->_field_type , [$this, "createField"]);

		$pids = $this->db->Linear(
			$this->db->QFetchRowArray(
				"SELECT mproducts.product_id FROM 
					%s as 
						mproducts
					INNER JOIN 
						%s as models 
						ON 
							mproducts.value_val = models.model_id AND
							models.model_id = %d
				WHERE
					mproducts.field_id = %d",
				[
					$this->tables["plugin:products_field_values_options"],
					$this->tables["plugin:products_addon_autobrands_models"],
					$model["model_id"],
					$field_model["field_id"]
				]
			)
		);

		if (is_array($pids) && count($pids)) {
			//update the types ....
			$this->db->QueryUpdate(
				$this->tables["plugin:products_field_values_options"],
				[
					"value_val"	=> $model["type_id"]
				],
				$this->db->Statement(
					"field_id=%d AND product_id in ( %s )",
					[
						$field_type["field_id"],
						implode("," , $pids)
					]
				)
			);
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
	function MassUploadForm() {
		global $_CONF;

		if ($_REQUEST["module_id"]) {
			$this->AlterSettings();
		}

		return CPlUpload::NewInstance()
			->Render(
				[
					"action"		=> \Stembase\Lib\Link::Show(
						"json.php",
						[
							"mod"			=> $this->name,
							"sub"			=> "info.colors-360-mass.act",
							"item_parent"	=> $_GET["item_parent"],
						]
					),
					"skin"			=> "Gray",
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
	function MassUploadAction() {

		$temp = CPlUpload::NewInstance()
			->SetFunction(
				"onComplete",
				array(&$this , "StoreImage")
			)
			->Run();
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
	function StoreImage($path , $name) {
		global $base;

		$ext = CFile::Extension($name);

		$id = $this->db->QueryInsert(
			$this->tables["plugin:products_addon_autobrands_info_colors_360"],
			array(
				"item_parent"		=> $_GET["item_parent"],				
				"item_image"		=> "1",
				"item_image_type"	=> $ext,
			)
		);

		$this->db->QueryUpdate(
			$this->tables["plugin:products_addon_autobrands_info_colors_360"],
			[
				"item_order"	=> $id
			],
			$this->db->Statement("item_id=%d" , [ $id ])
		);

		//copy the base image to the full 
		CFile::Copy(
			$path , 
			"../upload/products/autobrands/360/{$id}." . $ext
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
	function getBrandTrims() {
		global $base , $_USER , $_SESS; 

		if ($_REQUEST["info_parent"]) {
			$page = $this->getInfoPageByID($_REQUEST["info_parent"]) ;
		} else {
			$page = $this->getInfoPageByID($_REQUEST["info_id"]) ;
			$page = $this->getInfoPageByID($page["info_parent"]) ;
		}

		$model = $this->GetModelByID($page["model_id"]);
		
		return $this->getBrandTrimsByID($model["brand_id"]);
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
	function DuplicateInfoPage($id , $redirect = true) {
		global $base , $_USER , $_SESS; 

		$info = $this->db->QFetchArray(
			"SELECT * FROM %s WHERE info_id = %d" , 
			[
				$this->tables["plugin:products_addon_autobrands_info"]	,
				$id
			]
		);

		unset($info["info_id"]);

		$new_id = $this->db->QueryInsert(
			$this->tables["plugin:products_addon_autobrands_info"],
			$info
		);

		if ($info["info_image"]) {
			CFile::Copy(
				"../upload/products/autobrands/info/" . $id . "." . $info["info_image_type"],
				"../upload/products/autobrands/info/" . $new_id . "." . $info["info_image_type"]
			);

			CFile::Copy(
				"../upload/products/autobrands/info/tn_" . $id . "." . $info["info_image_type"],
				"../upload/products/autobrands/info/tn_" . $new_id . "." . $info["info_image_type"]
			);
		}
		

		$info_langs = $this->db->QFetchRowArray(
			"SELECT * FROM %s WHERE info_id = %d",
			[
				$this->tables["plugin:products_addon_autobrands_info_lang"]	,
				$id
			]
		);

		foreach ($info_langs as $info_lang) {
			$info_lang["info_id"] = $new_id;
			$this->db->QueryInsert(
				$this->tables["plugin:products_addon_autobrands_info_lang"]	,
				$info_lang
			);
		}

		//get the trims
		$trims = $this->db->QFetchRowArray(
			"SELECT * FROM %s WHERE info_parent = %d",
			[
				$this->tables["plugin:products_addon_autobrands_info"]	,
				$id
			]
		);

		if (is_array($trims)) {
			foreach ($trims as $trim) {
				$new_trim_id = $this->DuplicateInfoPage($trim["info_id"] , false);
				$this->db->QueryUpdate(
					$this->tables["plugin:products_addon_autobrands_info"],
					["info_parent" => $new_id],
					$this->db->Statement("info_id = %d" , [$new_trim_id])
				);
			}			
		}
					
		if ($redirect) {

			\Stembase\Lib\Link::Go(
				"index.php",
				[	
					"mod"	=> $this->name,
					"sub"	=> "info",
					"info_id"	=> $new_id,
					"action"	=> "edit",
					"_tb"		=> \Stembase\Lib\Trail::Save(
						"index.php" ,
						[
							"mod"		=> $this->name,
							"sub"		=> "info",
							"info_id"	=> $new_id,
							"action"	=> "details",
							"_tb"		=> \Stembase\Lib\Trail::Save(
								"index.php",
								[
									"mod"		=> $this->name,
									"sub"		=> "info",
								]
							)
						]	
					)
				]
			);


		} else {		
			return $new_id;
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
	function AutoCompleteBrands() {
		global $base , $_USER , $_SESS; 

		if ($_GET["keywords"]) {
			$keyword = $_GET["keywords"];
		}		

		if ($_GET["ids"]) {
			$tmp = explode("," , $_GET["ids"]);
			$ids = array();

			foreach ($tmp as $k => $v) {
				if (trim($v)) {
					$ids[] = $v;
				}				
			}

			if (count($ids)) {
				$cond[] = " brand_id IN (" . implode("," , $ids) . ") ";
			}					
		}
		

		if ($keyword) {
			$tmp = explode(" " , $keyword);

			if (count($tmp)) {
				foreach ($tmp as $key => $val) {
					$cond[] = $this->db->Statement(
						" ( brand_name LIKE '%%%s%%') " , 
						[	$val ]
					);
				}				
			}			
		}


		$sql = $this->db->Statement(
			"FROM 
				%s 
				:cond ",
			[
				$this->tables['plugin:products_addon_autobrands_brands'],
			],
			[
				":cond"	=> is_array($cond) ? " WHERE " . implode(" AND " , $cond) : ""
			]
		);

		$items = $this->db->QFetchRowArray(
			"SELECT * {$sql} ORDER BY  brand_name LIMIT 100"
		);	



		if (is_array($items)) {
			foreach ($items as $key => $brand) {
				$_data[] = array(
					"id"		=> $brand["brand_id"],
					"name"		=> $brand["brand_name"],
				);
			}
		
			$return = array(
				"status"	=> "ok" , 
				"results"	=> $_data,
				"total"		=> count($brands)
			);

			return $this->json($return);
			
		}

		return $this->json(array(
				"status"	=> "empty" , 
				"message"	=> "No results available"
		));

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
	function AutoCompleteModels() {
		global $base , $_USER , $_SESS; 

		if ($_GET["keywords"]) {
			$keyword = $_GET["keywords"];
		}		

		if ($_GET["ids"]) {
			$tmp = explode("," , $_GET["ids"]);
			$ids = array();

			foreach ($tmp as $k => $v) {
				if (trim($v)) {
					$ids[] = $v;
				}				
			}

			if (count($ids)) {
				$cond[] = " model_id IN (" . implode("," , $ids) . ") ";
			}					
		} else {		
			$cond[] = $this->db->Statement(" brand_id = %d " , [$_GET[$this->_field_brand]]);
		}

		if ($keyword) {
			$tmp = explode(" " , $keyword);

			if (count($tmp)) {
				foreach ($tmp as $key => $val) {
					$cond[] = $this->db->Statement(
						" ( model_name LIKE '%%%s%%') " , 
						[	$val ]
					);
				}				
			}			
		}


		$sql = $this->db->Statement(
			"FROM 
				%s 
				:cond ",
			[
				$this->tables['plugin:products_addon_autobrands_models'],
			],
			[
				":cond"	=> is_array($cond) ? " WHERE " . implode(" AND " , $cond) : ""
			]
		);

		$items = $this->db->QFetchRowArray(
			"SELECT * {$sql} ORDER BY  model_name LIMIT 100"
		);	



		if (is_array($items)) {
			foreach ($items as $key => $model) {
				$_data[] = array(
					"id"		=> $model["model_id"],
					"name"		=> $model["model_name"],
				);
			}
		
			$return = array(
				"status"	=> "ok" , 
				"results"	=> $_data,
				"total"		=> count($models)
			);

			return $this->json($return);
			
		}

		return $this->json(array(
				"status"	=> "empty" , 
				"message"	=> "No results available"
		));

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
	function DuplicateInfoPageTrim($id) {
		global $base , $_USER , $_SESS; 

		$new_id = $this->duplicateInfoPage($id , false);

		\Stembase\Lib\Link::Go(
			"index.php",
			[	
				"mod"	=> $this->name,
				"sub"	=> "info.trims",
				"info_id"	=> $new_id,
				"action"	=> "edit",
				"_tb"		=> \Stembase\Lib\Trail::Save(
					"index.php" ,
					[
						"mod"		=> $this->name,
						"sub"		=> "info.trims",
						"info_id"	=> $new_id,
						"action"	=> "trimdetails",
						"_tb"		=> $_GET["_tb"]
					]	
				)
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
	function PreviewInfoPage() {
		global $_CONF;

		$mod = $this->plugins["modules"]->GetDefaultModuleInfoById($this->tpl_module["module_id"]);
		$lang = $this->plugins["languages"]->getLanguageById($_GET["lid"]);

		$page = $this->getInfoPageByID($_GET["info_id"]);

		if ($this->vars->data["set_multilanguage"]) {
			$link = $_CONF["url"] . $lang["lang_code"] . "/" ;
		} else {
			$link = $_CONF["url"] . $mod["module_url"] . "/";
		}		

		$model = $this->getModelById($page["model_id"]);
		$brand = $this->getBrandById($model["brand_id"]);


		$link .= $page["info_year"] . "/";
		$link .= $brand["brand_url"] . "/";
		$link .= $model["model_url"] . "/";

		urlredirect($link);
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
	function storeInfoPage($record) {
		global $base , $_USER , $_SESS; 

		if (!is_array($this->db->QFetchArray("SELECT * FROM %s WHERE model_id = %d " , [$this->tables['plugin:products_addon_autobrands_info_models'] , $record['model_id']]))) {
			$id = $this->db->QueryUpdate(
				$this->tables["plugin:products_addon_autobrands_info_models"],
				[
					"infomodel_order"	=> $id = $this->db->QueryInsert(
						$this->tables["plugin:products_addon_autobrands_info_models"],
						[
							"model_id"			=> $record["model_id"],
							"infomodel_status"	=> "1",
						]
					)
				],
				$this->db->Statement(
					"infomodel_id = %d",
					[ $id ]
				)
			);			
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
	function MenuStatus() {
		global $base , $_USER , $_SESS; 

		if (is_array($_POST["infomodel_id"]) && count($_POST["infomodel_id"])) {
			$this->db->QueryUpdate(
				$this->tables["plugin:products_addon_autobrands_info_models"],
				["infomodel_status" => $_GET["type"] == "menu.hide" ? 0 : 1],
				$this->db->Statement("infomodel_id IN (%s)" , implode("," , $_POST["infomodel_id"]))
			);
		}
		

		die("1");

		debug($_POST);
		debug($_GET,1);
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
	function getProductElementsCategories () {
		global $base , $_USER , $_SESS; 

		if (!$this->tpl_module["module_status"] == 1) {
			//return false;
		}

		$data = [
			[
				"title"		=> "Auto Brands",
				"value"		=> "autobrands",
				"parent"	=> "",
			],

		];
		
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
	function getProductElements() {
		global $base , $_USER , $_SESS; 

		if (!$this->tpl_module["module_status"] == 1) {
			//return false;
		}

		return [
			[
				"id"		=> 'autobrands:colors',
				"title"		=> "Add-on Auto Brands",
				"subtitle"	=> 'Colors Selector',
				"edit"		=> '',
				"css"		=> "",
				"parent"	=> "autobrands",
				"for"		=> ["details"]
			],

			[
				"id"		=> 'autobrands:colorswimage',
				"title"		=> "Add-on Auto Brands",
				"subtitle"	=> 'Colors Selector w/ Image',
				"edit"		=> '',
				"css"		=> "",
				"parent"	=> "autobrands",
				"for"		=> ["details"]
			],		];				
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
	function __menuButton() {
		global $base , $_USER , $_SESS , $_CONF , $_LANG_ID; 

		$module = $this->plugins["modules"]->LoadDefaultModule($this->name);

		return [
			[
				"id"	=> "1",
				"name"	=> $module["module_name"] . " - Fuck Knows"
			]
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
	function autoCompleteTrims() {
		global $base , $_USER , $_SESS , $_CONF , $_LANG_ID; 

		$model = $this->getModelByID($_GET["model_id"]);
		$trims = $this->getTrimsbyBrand($model["brand_id"]);

		$data = [];

		if (is_array($trims)) {
			foreach ($trims as $key => $val) {
				$data[] = [
					"value"	=> $val["trim_id"],
					"name"	=> $val["trim_name"]
				];
			}		
		}
		return $this->json($data);
	
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
	function alertStatus($table , $fid , $aid) {
		global $_LANG_ID; 

		if (is_array($_POST[$fid])) {
			$this->db->QueryUpdate(
				$table,
				[
					$aid	=> $_GET['type'] == "disabled" ? 0 : 1,
				],
				$this->db->Statement(
					"%s in (%s)",
					[ $fid , implode("," , $_POST[$fid])]
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
	function adminCronJob($module) {

		$jobs = [
			[
				"module"			=> $module["module_id"],
				"module_name"		=> $module["module_name"],					
				"module_code"		=> $module["module_code"],

				"type"				=> "1",
				"action"			=> "CronUpdateVehicles",

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
