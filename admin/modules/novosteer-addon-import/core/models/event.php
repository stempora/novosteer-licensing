<?php
/*
	Stempora Web Framework
	Copyright (c) 2002-2019 Stempora. 
	All rights reserved.
		web:  www.stempora.com
		mail: support@stempora.com				
*/



namespace Stembase\Modules\Novosteer_Addon_Import\Core\Models;


/**
* description
*
* @library	
* @author	
* @since	
*/
class Event extends Base{
	
	/**
	* description
	*
	* @var type
	*
	* @access type
	*/
	var $module = null;

	/**
	* description
	*
	* @var type
	*
	* @access type
	*/
	var $product = null;

	/**
	* description
	*
	* @var type
	*
	* @access type
	*/
	var $feed = null;
	
	

	/**
	* description
	*
	* @var type
	*
	* @access type
	*/
	var $db = null;

	
	/**
	* description
	*
	* @param
	*
	* @return
	*
	* @access
	*/
	function __construct() {
		global $base , $_USER , $_SESS , $site; 

		$this->db = $site->db;
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
	public function setFeed($feed) {
		global $base , $_USER , $_SESS; 

		$this->feed = $feed;

		return $this;
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
	function setProduct($id) {
		global $base , $_USER , $_SESS; 

		$this->product = $id;

		return $this;
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
	function productWasUpdated($scope = "" , $hash ) {
		global $base , $_USER , $_SESS; 

		if (!$this->product) {
			return true;
		}
		
		$existing = $this->db->QFetchArray(
			"SELECT * FROM %s WHERE feed_id = %d  AND product_id = %d AND product_scope LIKE '%s'",
			[
				$this->module->tables['plugin:novosteer_addon_importer_items'],
				$this->feed,
				$this->product , 
				$scope
			]
		);

		if (!is_array($existing)) {
			return true;
		}

		return $hash != $existing['product_hash'];		
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
	function productRecordUpdate($scope = "" , $hash) {
		global $base , $_USER , $_SESS; 

		$existing = $this->db->QFetchArray(
			"SELECT * FROM %s WHERE feed_id = %d AND product_id = %d AND product_scope LIKE '%s'",
			[
				$this->module->tables['plugin:novosteer_addon_importer_items'],
				$this->feed,
				$this->product , 
				$scope
			]
		);

		if (is_array($existing)) {
			$this->db->QueryUpdate(
				$this->module->tables['plugin:novosteer_addon_importer_items'],
				[
					"product_hash"	=> $hash, 
					"product_scope"	=> $scope,
					"product_update"	=> time(),
				],
				$this->db->Statement(
					"feed_id = %d AND product_id = %d AND product_scope LIKE '%s'",
					[
						"feed_id"		=> $this->feed,
						"product_id"	=> $this->product,
						"product_scope"	=> $scope,
					]
				)
			);
		} else {
			$this->db->QueryInsert(
				$this->module->tables['plugin:novosteer_addon_importer_items'],
				[
					"product_hash"	=> $hash, 
					"product_scope"	=> $scope,
					"product_update"=> time(),
					"feed_id"		=> $this->feed,
					"product_id"	=> $this->product
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
	function getHash($data) {
		global $base , $_USER , $_SESS; 

		return md5(json_encode($data));
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
	function productDeleteLog($scope = null) {
		global $base , $_USER , $_SESS; 

		if ($scope === null) {
			$this->db->Query(
				"DELETE FROM %s WHERE feed_id = %d AND product_id = %d",
				[
					$this->module->tables['plugin:novosteer_addon_importer_items'],				
					$this->feed,
					$this->product
				]
			);
		} else {
			$this->db->Query(
				"DELETE FROM %s WHERE feed_id = %d AND product_id = %d AND product_scope like '%s'",
				[
					$this->module->tables['plugin:novosteer_addon_importer_items'],				
					$this->feed,
					$this->product,
					$scope
				]
			);
		}

		return $this;		
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
	function feedDeleteLog($scope) {
		global $base , $_USER , $_SESS; 


		if ($scope === null) {
			$this->db->Query(
				"DELETE FROM %s WHERE feed_id = %d ",
				[
					$this->module->tables['plugin:novosteer_addon_importer_items'],				
					$this->feed
				]
			);
		} else {
			$this->db->Query(
				"DELETE FROM %s WHERE feed_id = %d AND product_scope like '%s'",
				[
					$this->module->tables['plugin:novosteer_addon_importer_items'],				
					$this->feed,
					$scope
				]
			);
		}

		return $this;		

	}
	
	
}



