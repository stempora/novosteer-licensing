<?php
/*
	Stempora Web Framework
	Copyright (c) 2002-2018 Stempora. 
	All rights reserved.
		web:  www.stempora.com
		mail: support@stempora.com				
*/


//NOTE LOOK IN AN OLD VERSION AND CHECK FOR SPECIAL HANDLING OF FILE / UPLOAD / IMAGE FIELDS
/**
* description
*
* @library	
* @author	
* @since	
*/
class CNovosteerAddonImport extends CNovosteerAddonImportBackend{
	
	var $tplvars; 
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


		if ($_GET["mod"] == $this->name) {

			$this->__init();
			parent::DoEvents();

			$sub = $_GET["sub"];
			$action = $_GET["action"];


		
			switch ($_GET["sub"]) {
				case  "landing":
					$sub = "products";

				case "importers":
					$data = new CSQLAdmin($sub, $this->__parent_templates,$this->db,$this->tables,$extra);
					$data->setAclMod($this->tpl_module);
					$this->PrepareFields($data->forms["forms"]);
					$this->ImporterPrepareFields($data->forms);
					$this->ImporterPrepareValues($data->forms["forms"]);

					$data->functions = [
							"onstore" => [&$this , "ImporterStore" ],
					];					
					return $data->DoEvents();
				break;

				case "map":
				case "map.pre":
				case "products":
				case "locks":
				case "log":
					$data = new CSQLAdmin($sub, $this->__parent_templates,$this->db,$this->tables,$extra);
					$data->setAclMod($this->tpl_module);
					$this->PrepareFields($data->forms["forms"]);
					return $data->DoEvents();
				break;

				case "importer.add":
				case "dealership.feeds.add":

					if (!$_GET["action"]) {
						$_GET["action"] = "edit";
					}


					$data = new CFormSettings($this->forms_path  . $sub . ".xml" ,$_CONF["forms"]["admintemplate"] , $this->db,$this->tables);

					if ($sub = "dealership.feeds.add") {
						$this->module->PrepareDashboard($data->form);
						$this->module->LangPrepareFields($data->form);	
					} else {					
						$this->PrepareDashboard($data->form);
						$this->LangPrepareFields($data->form);	
					}
					if ($data->Done()) {
						$this->SaveImporter();
					}					
					return $data->Show($_GET);

				break;

				case "importer.duplicate":
					return $this->ImporterDuplicate($_GET['feed_id']);
				break;


				case "download":
					return $this->DownloadFeed();
				break;

				case "delete.cache":
					return $this->deleteCache();
				break;

				case "products.unlock":
					return $this->unlockProducts();
				break;

				case "feed.status":
					return $this->feedStatus();
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
	function setHooks() {
		global $base , $_USER , $_SESS; 

		$this->hookRegister("module.novosteer-dealerships.forms" , [$this , "dealershipAddField"] , 1001); 
	}

	/*
	* description
	*
	* @param
	*
	* @return
	*
	* @access
	*/
	function __init() {
		global $_CONF , $_SESS , $site;

		if ($this->__inited) {
			return "";
		}

		$this->__inited = true;
		
		$this->__initTemplates([
		]);

		$this->tpl_module = $this->plugins["modules"]->LoadDefaultModule($this->name);
		$this->module = &$this->plugins["novosteer-dealerships"];
		$this->module->__init();

		$this->cache = $site->shared->addChild($this->name);
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
	function SaveImporter() {

		$data = [
			"dealership_id"			=> $_POST["dealership_id"],
			"feed_extension"	=> $_POST["feed_extension"],
			"feed_status"		=> 0,
			"feed_class"		=> "\\Stembase\\Modules\\Novosteer_Addon_Import\\Core\\Importer\\" . str_replace("." , "" , $_POST["feed_extension"]),
			"feed_code"			=> md5(uniqid()),

		];


		$this->db->QueryUpdate(
			$this->tables["plugin:novosteer_addon_importer_feeds"],
			[
				"feed_order"	=> 			$id = $this->db->QueryInsert(
					$this->tables["plugin:novosteer_addon_importer_feeds"],
					$data
				)
			],
			$this->db->Statement(
				"feed_id = %d",
				[$id]
			)
		);		

		\Stembase\Lib\Link::GO(
			"index.php",
			[
				"mod"		=> $this->name,
				"sub"		=> "importers",
				"action"	=> "edit",
				"feed_id"	=> $id,
				"_tb"		=> $_POST["_tb"]
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
	function adminCronJob($module) {

		$jobs = [
			[
				"module"			=> $module["module_id"],
				"module_name"		=> $module["module_name"],					
				"module_code"		=> $module["module_code"],

				"type"				=> "1",
				"action"			=> "CronCleanLogs",

				"minute"			=> "0",
				"hour"				=> "11",
				"day"				=> "*",
				"month"				=> "*",
				"year"				=> "*",
			],
		];

		$dealerships = $this->plugins["novosteer-dealerships"]->getAllDealerships();

		if (is_array($dealerships)) {
			foreach ($dealerships as $key => $dealership) {
				$jobs[] = array(
					"module"			=> $module["module_id"],
					"module_name"		=> $module["module_name"],					
					"module_code"		=> $module["module_code"],

					"type"				=> "1",
					"action"			=> "CronImportFeed",
					"description"		=> $dealership["dealership_name"],
					"params"			=> array("dealership_id" => $dealership["dealership_id"]),

					"minute"			=> "0",
					"hour"				=> "0",
					"day"				=> "*",
					"month"				=> "*",
					"year"				=> "*",
				);
			}			
		}
		
		return $jobs;
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
	function ImporterDuplicate($id) {
		global $base , $_USER , $_SESS; 

		$old = $this->getImporterByID($id);

		if (is_array($old)) {
			unset($old["feed_id"]);
			unset($old["feed_status"]);
			$old["feed_name"] .= " (copy)";

			$nid = $this->db->QueryInsert(
				$this->tables['plugin:novosteer_addon_importer_feeds'],
				$old
			);

			\Stembase\Lib\Link::Go(
				"index.php",
				[
					"mod"		=> $this->name,
					"sub"		=> "importers",
					"action"	=> "edit",
					"feed_id"	=> $nid,
					"_tb"		=> \Stembase\Lib\Trail::Save(
						"index.php",
						[
							"mod"		=> $this->name,
							"sub"		=> "importers",
						]
					)
				]
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
	function DownloadFeed() {
		$item = $this->db->QFetchArray(
			"SELECT * FROM %s WHERE file_id=%d",
			[ $this->tables['plugin:novosteer_addon_importer_history'] , $_GET['file_id'] ]
		);

		if (is_Array($item)) {

		CHeaders::newInstance()
			->ContentTypeByExt($item["file_file_file"])
			->Filename($item["file_file_file"]);

			$this->storage->private->readChunked("novosteer/import/history/{$item['file_id']}.file");
			die();
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
	function deleteCache() {
		global $base , $_USER , $_SESS; 

		$fields = ["hash" , "images" , "image"];
		$request = explode("," , $_GET["type"]);

		if (count($request)) {
			$data =[];

			if ($_GET["type"] == "*") {
				$this->db->QueryUpdate(
					$this->tables['plugin:novosteer_addon_importer_items'] , 
					["product_update" => 0 , "product_hash" => ""],
					$this->db->Statement(
						"feed_id = %d",
						$_GET['feed_id']
					)
				);
			} else {
				$this->db->QueryUpdate(
					$this->tables['plugin:novosteer_addon_importer_items'] , 
					["product_update" => 0, "product_hash" => ""],
					$this->db->Statement(
						"feed_id = %d and product_scope like '%s'",
						[ $_GET['feed_id'] , $_GET['type'] ]
					)
				);
			}

			return "1";
	
			
		}
		
		return "0";
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
	function ImporterPrepareFields(&$forms) {


		if ($_REQUEST["feed_id"]) {			

			$client = $this->getImporterObject($_REQUEST["feed_id"]);

			if (is_object($client)) {
				$fields = $client->getAdminFields();

				if (is_array($fields) && count($fields)) {

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

	function ImporterPrepareValues(&$forms) {

		if ($_REQUEST["feed_id"]) {			

			$hook = $this->getImporterById($_REQUEST["feed_id"]);
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
	function ImporterStore($record , $form) {

		if ($record["feed_id"]) {

			//fields
			$old = $this->getImporterById($record["feed_id"]);
			
			foreach ($_POST as $key => $val) {
				if (stristr($key , "set_")) {
					$data[$key] = $val;
				}			
			}

			if (is_array($data)) {
				$this->db->QueryUpdate(
					$this->tables["plugin:novosteer_addon_importer_feeds"],
					array(
						"feed_settings"	=> json_encode($data),
					),
					$this->db->Statement(
						"feed_id=%d",
						[$record["feed_id"]]
					)
				);
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
	function unlockProducts() {
		global $base , $_USER , $_SESS; 

		if (!(is_array($_POST["product_id"]) &&count($_POST["product_id"]))) {
			return "0";
		}
		
		$this->db->Query(
			"DELETE FROM %s WHERE product_id in (%s) ",
			[
				$this->tables['plugin:novosteer_addon_locks_products'],
				implode("," , $_POST["product_id"])				
			]
		);

		return "1";
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
	function dealershipAddField(&$forms) {
		global $base , $_USER , $_SESS , $_CONF , $_LANG_ID; 

		if (isModSubAct("novosteer-dealerships" , "dealerships/dealerships" , "details")) {

			$this->__init();

			if ($_GET["section"] == "addon-import") {
				$this->module->media = &$this->media;

				$forms["details"]["after"] = [
					"type"	=> "sqladmin",
					"xml"	=> "../../novosteer-addon-import/forms/dealership.feeds"
				];				
			}
			
			$forms["details"]["title"]["details"]["options"][] = [
				"name"		=> "addon-import",
				"link"		=> $forms["details"]["title"]["details"]["options"]["o1"]["link"] . "&section=addon-import",
				"title"		=> "Import Feeds",
				"icon"		=> "cloud2",
				"active"	=> ($_GET["section"] == "addon-import") ? "true" : "false",
			];

			if ($_GET["section"] == "addon-import") {
				$this->module->dealershipRemoveBoxes($forms);
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
	function feedStatus() {
		global $_LANG_ID; 

		if (is_array($_POST["feed_id"])) {
			$this->db->QueryUpdate(
				$this->tables["plugin:novosteer_addon_importer_feeds"],
				[
					"feed_status"	=> $_GET['type'] == "disabled" ? 0 : 1,
				],
				$this->db->Statement(
					"feed_id in (%s)",
					[ implode("," , $_POST["feed_id"])]
				)
			);
		}

		die("1");
		

		debug($_POST,1);
	}
	
	
}
