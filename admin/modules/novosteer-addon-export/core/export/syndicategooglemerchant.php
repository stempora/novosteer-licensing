<?php
/*
	Stempora Web Framework
	Copyright (c) 2002-2020 Stempora. 
	All rights reserved.
		web:  www.stempora.com
		mail: support@stempora.com				
*/


namespace Stembase\Modules\Novosteer_Addon_Export\Core\Export;

if (!defined("STPBase")) {
	die("This file can't be accessed directly!");
}


use \Stembase\Modules\Novosteer_Addon_Export\Core\Models\Syndicate;
use \CHeaders;

class SyndicateGoogleMerchant extends Syndicate{
	/**
	* description
	*
	* @param
	*
	* @return
	*
	* @access
	*/
	function runWeb() {
		global $_LANG_ID; 

		Cheaders::newInstance()
			->ContentTypeByExt("google.xml")
			->FileName("google" , "inline");


		if ($this->module->storage->private->fileExists("novosteer/googlemerchant/" . $this->info["feed_id"] . ".xml")) {
			$this->module->storage->private->readChunked("novosteer/googlemerchant/" . $this->info["feed_id"] . ".xml");
		}
		
		die();
	}
	
}
