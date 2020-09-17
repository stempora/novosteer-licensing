<?php
/*
	Stempora Web Framework
	Copyright (c) 2002-2020 Stempora. 
	All rights reserved.
		web:  www.stempora.com
		mail: support@stempora.com				
*/


namespace Stembase\Modules\Novosteer_Dealerships\Core\Models;

use \CTemplateDynamic;
use \CConfig;
use \CFile;
use \CHTTP;

class Calculator extends Base{

	/**
	* description
	*
	* @var type
	*
	* @access type
	*/
	var $event = null;
	
	/**
	* description
	*
	* @var type
	*
	* @access type
	*/
	var $info = [];
	/**
	* description
	*
	* @var type
	*
	* @access type
	*/
	var $form = null;

	/**
	* description
	*
	* @var type
	*
	* @access type
	*/
	var $user = null;
	
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
	var $db = null;


	/**
	* description
	*
	* @var type
	*
	* @access type
	*/
	var $cronJob = null;

	/**
	* description
	*
	* @var type
	*
	* @access type
	*/
	var $skuField = "";

	/**
	* description
	*
	* @var type
	*
	* @access type
	*/
	var $skus = [
		"updates"	=> [],
		"new"		=> [],
		"all"		=> [],
		"ignored"	=> [],
	];
	
	
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
	
	function __construct($info = null) {
		global $base , $_USER , $_SESS , $_MODULES , $site; 

		if ($info !== null) {
			$this->info = $info;
		}	

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
	public function setInfo($data) {
		global $base , $_USER , $_SESS; 

		$this->info = $data;

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
	public function setForm($data) {
		global $base , $_USER , $_SESS; 

		$this->form = $data;

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
	public function getInfo() {
		return $this->info;
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
	public function getPath() {
		global $_ADMIN;

		$name = explode("\\" , get_called_class());
		$file = strtolower($name[count($name)-1]);


		$path = strtolower(str_replacE(
			[
				"\\" , 
				"_"
			], 
			[
				"/",
				"-"
			], 
			__NAMESPACE__
		) . "/" . $file);

		$path = str_replace("models/" , "calculators/" , $path);

		if ($_ADMIN) {
			return "../locals/admin/" . str_replace("stembase/" , "" , $path);
		} else {
			return "./locals/" . str_replace("stembase/" , "admin/" , $path);
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
	public function getAdminFields() {
		global $base , $_USER , $_SESS; 

		$path = $this->getPath() . ".xml";

		if (!file_exists($path)) {
			return null;
		}

		$conf = new CConfig($path);
		return $conf->vars["form"];
	}


	public function setJob($job) {
		$this->job = $job;

		return $this;
	}



	public function calculatePrice() {

		$this->loadDiscounts();


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
	function loadDiscounts() {
		global $base , $_USER , $_SESS , $_CONF , $_LANG_ID; 

		return $this;
	}

	
	public function getRemoteCSV($url) {
		if (!$url) {
			return null;
		}

		$client = new \GuzzleHttp\Client();

		$res = $client->request('GET', $url);

		\CFile::Save(
			"upload/tmp/tmp_calc_" . $this->info["calculator_id"] . ".csv" , 
			$res->getBody()->getContents()				
		);

		$data = \CFile::LoadArray("upload/tmp/tmp_calc_" . $this->info["calculator_id"] . ".csv" , true);

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
	function log($data =  "", $params = [] , $suf = "\n") {
		global $base , $_USER , $_SESS; 

		$this->cronJob->log(vsprintf($data , $params) , $suf );		

		return $this;
	}	
}