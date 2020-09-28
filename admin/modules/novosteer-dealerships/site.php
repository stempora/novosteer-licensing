<?php
/*
	Stempora Web Framework
	Copyright (c) 2002-2020 Stempora. 
	All rights reserved.
		web:  www.stempora.com
		mail: support@stempora.com				
*/

################ NOTE here i'll need to take in consideration the volume discounts ,

/**
* description
*
* @library	
* @author	
* @since	
*/
class CNovoSteerDealerships extends CNovoSteerDealershipsBackend{	

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

		//$this->__initBranding();

		parent::DoEvents();

		if ($_GET["mod"] == $this->name) {
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
		global $_CONF , $_SESS;

		if ($this->__inited) {
			return "";
		}

		$this->__inited = true;
		
		$this->__initTemplates([
			"developer"	=> "developer.twig"
		]);

		$this->tpl_module = $this->plugins["modules"]->LoadDefaultModule($this->name);
	
	} 		


}
