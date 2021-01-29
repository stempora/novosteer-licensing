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
class CNovosteerAddonSyndicateKjijiBackend extends CPlugin{

	function __construct() {
		$this->name = "novosteer-addon-syndicate-kjiji";
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
	function getAllGroups() {
		global $_LANG_ID;
		
		$groups = $this->db->QFetchRowArray(
			"SELECT * FROM %s WHERE group_status = 1",
			[
				$this->tables["plugin:novosteer_addon_syndicate_kjiji_groups"]
			]
		);

		if (!is_array($groups)) {
			return null;
		}

		$_groups = [];

		foreach ($groups as $key => $group) {
			$_groups[$group["group_id"]] = $group;
		}

		return $_groups;
		
		
	}
	
}
