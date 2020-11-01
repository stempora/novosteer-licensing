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

class SyndicateFacebook extends Syndicate{
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
			->ContentTypeByExt("facebook.xml")
			->FileName("facebook" , "inline");


		if ($this->module->storage->private->fileExists("novosteer/facebook/" . $this->info["feed_id"] . ".xml")) {
			$this->module->storage->private->readChunked("novosteer/facebook/" . $this->info["feed_id"] . ".xml");
		}
		
		die();
	}
	
}
