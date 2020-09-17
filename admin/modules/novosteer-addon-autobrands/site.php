<?php



class CNovosteerAddonAutoBrands extends CNovosteerAddonAutoBrandsBackend{

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

		parent::DoEvents();

		if ($_GET["mod"] == $this->name) {

			$this->__init();
			$this->plugins["globalhooks"]->SetModule($this->tpl_module);

			switch ($_GET["sub"]) {
				case "ajax.menu":
					return $this->ajaxMenu();
				break;

				case "infopage":
					return $this->InfoPage();
				break;

				case "infopage.download":
					return $this->InfoPageDownload();
				break;

				case "infopages":
					return $this->InfoPages();
				break;

			}
			
		
		}
	}


	function __init() {
		global $_CONF , $_SESS , $site;

		if ($this->__inited) {
			return "";
		}

		$this->__inited = true;
		
		$this->__initTemplates([
			"elements"	=> "elements.twig",
			"menu"		=> "menu.twig",
			"infopages"	=> "infopages.twig",
		]);

		$this->tpl_module = $this->plugins["modules"]->LoadDefaultModule($this->name);
		$this->module = &$this->plugins["products"];
		$this->module->__init();

		//$this->decodeParams();

		$this->cache = $site->shared->addChild($this->name);
		$this->cache->addChild("brands");
		$this->cache->addChild("models");
		$this->cache->addChild("trims");
		$this->cache->addChild("pages");
		$this->cache->addChild("types");
		$this->cache->addChild("discountimages");
		$this->cache->addChild("certifiedimages");
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

		parent::setHooks();
	}

	
}



