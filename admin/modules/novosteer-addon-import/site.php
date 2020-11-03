<?php
/*
	Stempora Web Framework
	Copyright (c) 2002-2018 Stempora. 
	All rights reserved.
		web:  www.stempora.com
		mail: support@stempora.com				
*/


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
	* @var type
	*
	* @access type
	*/
	var $search = null;
	

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
		global $base, $_CONF, $_TSM , $_VARS , $_USER , $_BASE , $_SESS , $site;

		if ($_GET["mod"] == $this->name) {

			$this->tpl_module = $this->module->plugins["modules"]->LoadDefaultModule($this->name);
			parent::DoEvents();

			$sub = $_GET["sub"];
			$action = $_GET["action"];


			switch ($sub) {
				case "feed.run":
					return $this->runFeed();
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
		global $_CONF , $_SESS, $site;

		if ($this->__inited) {
			return "";
		}

		$this->__inited = true;
		
		$this->__initTemplates([
		]);

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
	function runFeed() {

		$feed = $this->getImporterByCode($_GET["feed_code"]);

		if (!is_array($feed)) {
			return $this->module->plugins["redirects"]->ErrorPage("404" , true);
		}

		$client = $this->getImporterObject($feed["feed_id"]);	

		if (!method_exists($client , "runWeb")) {
			return $this->module->plugins["redirects"]->ErrorPage("404" , true);
		}
		
		$client->runWeb();

		return true;
	}


}
