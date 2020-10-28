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
class CNovosteerAddonExport extends CNovosteerAddonExportBackend{
	
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

				case "exports":
					$data = new CSQLAdmin($sub, $this->__parent_templates,$this->db,$this->tables,$extra);
					$data->setAclMod($this->tpl_module);
					$this->PrepareFields($data->forms["forms"]);
					$this->ExportPrepareFields($data->forms);
					//$this->ExportPrepareValues($data->forms["forms"]);

					$data->functions = [
							"onstore" => [&$this , "ExportStore" ],
							"ondetails"			=> array(&$this , "ExportDecodeValues" ),
							"onedit"			=> array(&$this , "ExportDecodeValues" ),

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

				case "exporter.add":
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
						$this->SaveExport();
					}					
					return $data->Show($_GET);

				break;

				case "exporter.duplicate":
					return $this->ExportDuplicate($_GET['feed_id']);
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
	function SaveExport() {

		$data = [
			"dealership_id"			=> $_POST["dealership_id"],
			"feed_extension"	=> $_POST["feed_extension"],
			"feed_status"		=> 0,
			"feed_class"		=> "\\Stembase\\Modules\\Novosteer_Addon_Export\\Core\\Export\\" . str_replace("." , "" , $_POST["feed_extension"]),
			"feed_code"			=> md5(uniqid()),

		];


		$this->db->QueryUpdate(
			$this->tables["plugin:novosteer_addon_export_feeds"],
			[
				"feed_order"	=> 			$id = $this->db->QueryInsert(
					$this->tables["plugin:novosteer_addon_export_feeds"],
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
				"sub"		=> "exports",
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
					"action"			=> "CronExportFeed",
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
	function ExportDuplicate($id) {
		global $base , $_USER , $_SESS; 

		$old = $this->getExportByID($id);

		if (is_array($old)) {
			unset($old["feed_id"]);
			unset($old["feed_status"]);
			$old["feed_name"] .= " (copy)";

			$nid = $this->db->QueryInsert(
				$this->tables['plugin:novosteer_addon_export_feeds'],
				$old
			);

			\Stembase\Lib\Link::Go(
				"index.php",
				[
					"mod"		=> $this->name,
					"sub"		=> "exports",
					"action"	=> "edit",
					"feed_id"	=> $nid,
					"_tb"		=> \Stembase\Lib\Trail::Save(
						"index.php",
						[
							"mod"		=> $this->name,
							"sub"		=> "exports",
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
			[ $this->tables['plugin:novosteer_addon_export_history'] , $_GET['file_id'] ]
		);

		if (is_Array($item)) {

		CHeaders::newInstance()
			->ContentTypeByExt($item["file_file_file"])
			->Filename($item["file_file_file"]);

			readfile("../upload/products/export/history/{$item['file_id']}.file");
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
					$this->tables['plugin:novosteer_addon_export_items'] , 
					["product_update" => 0 , "product_hash" => ""],
					$this->db->Statement(
						"feed_id = %d",
						$_GET['feed_id']
					)
				);
			} else {
				$this->db->QueryUpdate(
					$this->tables['plugin:novosteer_addon_export_items'] , 
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
	function ExportPrepareFields(&$forms) {


		if ($_REQUEST["feed_id"]) {			

			$client = $this->getExportObject($_REQUEST["feed_id"]);

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

	function ExportDecodeValues($data , &$forms) {

		$settings = json_decode($data["feed_settings"],true);

		$data = array_merge(
			$data , 
			$settings
		);

		//debug($settings,1);

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
	function ExportStore($record , $forms , $old ) {

		if ($record["feed_id"]) {

			//fields
			$old = $this->getExportById($record["feed_id"]);
		
			//get all fields
			$fields = CForm::AllFields($forms["forms"]["edit"]);

			foreach ($fields as $key => $val) {

				if (stristr($key , "set_") !== false) {					


					switch ($val["type"]) {
						case "image":
							if (!$record[$key] && $old["settings"][$key] && !($record[$key . "_radio_type"] == "-1")) {
								$record[$key] = $old["settings"][$key];
							}					

							if ($record[$key . "_temp"]) {
								$record[$key . "_file"] = $record[$key . "_temp"];
								unset($record[$key . "_temp"]);
							} else {
								$record[$key . "_file"] = $old["settings"][$key . "_file"];
							}

							if (!$record[$key . "_alt"]) {
								$record[$key . "_alt"] = $old["settings"][$key . "_alt"];
							}

							if (!$record[$key . "_type"]) {
								$record[$key . "_type"] = $old["settings"][$key . "_type"];
							}

							if (!$record[$key . "_date"]) {
								$record[$key . "_date"] = $old["settings"][$key . "_date"];
							}							
						break;
					}
				}
			}				


			foreach ($record as $key => $val) {
				if (stristr($key , "set_")) {

					if (!(stristr($key , "_temp") ||  
							stristr($key , "_radio_type") || 
							stristr($key , "_crop_oxbc")
					)) {
						$data[$key] = $val;
					}
				}			
			}

			$this->db->QueryUpdate(
				$this->tables["plugin:novosteer_addon_export_feeds"],
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



	/**
	* description
	*
	* @param
	*
	* @return
	*
	* @access
	*/
	function productStore(&$record) {
		global $base , $_USER , $_SESS; 


		$set = $this->plugins["products-addon-attributes"]->getProductSet($record["item_id"]);

		if (!in_array($set , $this->getExportsSets())) {
			return null;
		}

		$this->updateProductLockFields($record["item_id"] , $record[$this->_field]);
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

			if ($_GET["section"] == "addon-export") {
				$this->module->media = &$this->media;

				$forms["details"]["after"] = [
					"type"	=> "sqladmin",
					"xml"	=> "../../novosteer-addon-export/forms/dealership.feeds"
				];				
			}
			
			$forms["details"]["title"]["details"]["options"][] = [
				"name"		=> "addon-export",
				"link"		=> $forms["details"]["title"]["details"]["options"]["o1"]["link"] . "&section=addon-export",
				"title"		=> "Export Feeds",
				"icon"		=> "cloud-upload",
				"active"	=> ($_GET["section"] == "addon-export") ? "true" : "false",
			];

			if ($_GET["section"] == "addon-export") {
				$this->module->dealershipRemoveBoxes($forms);
			}

		} 

	}
	
	
}
