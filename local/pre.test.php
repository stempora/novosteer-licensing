<?php
/*
	Stempora Web Framework
	Copyright (c) 2002-2020 Stempora. 
	All rights reserved.
		web:  www.stempora.com
		mail: support@stempora.com				
*/

if (!defined("STPBase")) {
	die("This file can't be accessed directly!");
}


$site->storage->root->save("test4/x.x" , "y");
//die();
//$site->storage->database->deleteDirectory("/upload/_cache");
//debug($site->storage->database->dirSimple("/upload/_cache", true),1);



//$site->storage->database->write("test2.json" , "xx");
//debug($site->storage->cache->read("test2.json"));
//debug($site->storage->cache->getUrl("test2.json" ));