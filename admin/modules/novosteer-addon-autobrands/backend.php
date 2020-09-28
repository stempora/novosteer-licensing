<?php
/*
	Stempora Web Framework
	Copyright (c) 2002-2019 Stempora. 
	All rights reserved.
		web:  www.stempora.com
		mail: support@stempora.com				
*/




class CNovosteerAddonAutoBrandsBackend extends CPlugin {

	
	
	function __construct() {
		$this->name = "novosteer-addon-autobrands";
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
	function getBrandsIdsByCode($titles) {
		global $base , $_USER , $_SESS; 

		return $this->db->Linear(
			$this->db->QFetchRowArray(
				"SELECT brand_id FROM %s WHERE brand_url in (':query')",
				[
					$this->tables['plugin:novosteer_addon_autobrands_brands'],
					$name
				],
				[":query" => implode("','" , $titles)]
			)
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
	function getModelsIdsByCode($titles) {
		global $base , $_USER , $_SESS; 

		return $this->db->Linear(
			$this->db->QFetchRowArray(
				"SELECT model_id FROM %s WHERE model_url in (':query')",
				[
					$this->tables['plugin:novosteer_addon_autobrands_models'],
					$name
				],
				[":query" => implode("','" , $titles)]
			)
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
	function getTrimsIdsByCode($titles) {
		global $base , $_USER , $_SESS; 

		return $this->db->Linear(
			$this->db->QFetchRowArray(
				"SELECT trim_id FROM %s WHERE trim_url in (':query')",
				[
					$this->tables['plugin:products_addon_autobrands_trims'],
				],
				[":query" => implode("','" , $titles)]
			)
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
	function getTypesIdsByCode($titles) {
		global $base , $_USER , $_SESS, $_LANG_ID; 

		return $this->db->Linear(
			$this->db->QFetchRowArray(
				"SELECT types.type_id FROM 
					%s as types
					INNER JOIN 
						%s as types_lang 
					ON 
						types.type_id = types_lang.type_id AND
						types_lang.lang_id = %d
				WHERE 
					type_url in (':query')",
				[
					$this->tables['plugin:products_addon_autobrands_types'],
					$this->tables['plugin:products_addon_autobrands_types_lang'],
					$_LANG_ID
				],
				[":query" => implode("','" , $titles)]
			)
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
	function getBrandIdByName($name , $create = false) {
		global $base , $_USER , $_SESS; 

		$this->__init();

		$name = trim($name);

		if (!$name) {
			return null;
		}		

		$brand = $this->db->QFetchArray(
			"SELECT * FROM %s WHERE brand_name LIKE '%s'",
			[
				$this->tables['plugin:novosteer_addon_autobrands_brands'],
				$name
			]
		);

		if (is_array($brand)) {
			return $brand["brand_id"];
		}
		
		if (!$create) {
			return null;
		}

		$id = $this->db->QueryInsert(
			$this->tables['plugin:novosteer_addon_autobrands_brands'],
			[
				"brand_name"	=> $name,
			]
		);	
		
		return $id;

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
	function getModelIdByName($brand , $name , $create = false , $model_type = null) {
		global $base , $_USER , $_SESS; 
		$this->__init();

		$name = trim($name);

		if (!$name) {
			return null;
		}		

		$model = $this->db->QFetchArray(
			"SELECT * FROM %s WHERE brand_id = %d AND model_name LIKE '%s'",
			[
				$this->tables['plugin:novosteer_addon_autobrands_models'],
				$brand , 
				$name
			]
		);

		if (is_array($model)) {

			//update the model if thats the cas 
			if (!$model["type_id"] && $model_type !== null) {
				$type = $this->getTypeIDByName($model_type , false);

				if ($type) {
					$this->db->QueryUpdate(
						$this->tables['plugin:novosteer_addon_autobrands_models'],
						[
							"type_id"	=> $type
						],
						$this->db->Statement("model_id = %d", [$model["model_id"]])
					);
				}
			}


			return $model["model_id"];
		}
		
		if (!$create) {
			return null;
		}

		$id =  $this->db->QueryInsert(
			$this->tables['plugin:novosteer_addon_autobrands_models'],
			[
				"brand_id"		=> $brand,
				"model_name"	=> $name,
				"type_id"		=> $model_type !== null 
					? $this->getTypeIDByName($model_type , false)
					: 0

			]
		);	
		

		return $id;
		
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
	function getTrimIdByName($brand , $name , $create = false) {
		global $base , $_USER , $_SESS; 
		$this->__init();

		$name = trim($name);

		if (!$name) {
			return null;
		}		

		$trim = $this->db->QFetchArray(
			"SELECT * FROM %s WHERE brand_id = %d AND trim_name LIKE '%s'",
			[
				$this->tables['plugin:novosteer_addon_autobrands_trims'],
				$brand , 
				$name
			]
		);

		if (is_array($trim)) {
			return $trim["trim_id"];
		}
		
		if (!$create) {
			return null;
		}

		$brand_data = $this->getBrandByID($brand);

		return $this->db->QueryInsert(
			$this->tables['plugin:novosteer_addon_autobrands_trims'],
			[
				"brand_id"		=> $brand,
				"trim_name"		=> $name,
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
	function getTypeIdByName($name , $create = false) {
		global $base , $_USER , $_SESS , $_LANG_ID; 

		$this->__init();

		$name = trim($name);

		if (!$name) {
			return null;
		}		

		$type = $this->db->QFetchArray(
			"SELECT * FROM %s as type
				WHERE type_name LIKE '%s'",
			[
				$this->tables['plugin:novosteer_addon_autobrands_types'],
				$name
			]
		);

		if (is_array($type)) {
			return $type["type_id"];
		}
		
		if (!$create) {
			return null;
		}

		return $id;

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
	function getAllBrands() {
		global $base , $_USER , $_SESS; 

		return $this->db->QFetchRowArray(
			"SELECT * FROM %s ORDER BY brand_order ASC",
			[
				$this->tables["plugin:novosteer_addon_autobrands_brands"]
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
	function getAllTypes() {
		global $base , $_USER , $_SESS , $_LANG_ID; 

		return $this->db->QFetchRowArray(
			"SELECT * 
			FROM 
				%s types 
				INNER JOIN 
					%s as types_lang 
				ON 
					types.type_id = types_lang.type_id AND 
					types_lang.lang_id = %d 
			ORDER BY 
				type_order ASC",
			[
				$this->tables["plugin:novosteer_addon_autobrands_types"],
				$this->tables["plugin:novosteer_addon_autobrands_types_lang"],
				$_LANG_ID
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
	function getAllModels() {
		global $base , $_USER , $_SESS; 

		return $this->db->QFetchRowArray(
			"SELECT * FROM %s ORDER BY model_name ASC",
			[
				$this->tables["plugin:novosteer_addon_autobrands_models"]
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
	function getAllTrims() {
		global $base , $_USER , $_SESS; 

		return $this->db->QFetchRowArray(
			"SELECT * FROM %s ORDER BY trim_name ASC",
			[
				$this->tables["plugin:novosteer_addon_autobrands_trims"]
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
	function getBrandById($id) {
		global $base , $_USER , $_SESS; 

		if (!$id) {
			return null;
		}

		if ($this->cache->brands) {
			$data = $this->cache->brands->getVar($id);
		}

		if (!is_array($data)) {
			$data = $this->db->QFetchArray(
				"SELECT * FROM %s WHERE brand_id=%d",
				[
					$this->tables['plugin:novosteer_addon_autobrands_brands'],
					$id
				]
			);

			if ($this->cache->brands) {
				$this->cache->brands->addVar($id , $data);
			}
		}
		
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
	function getModelById($id) {
		global $base , $_USER , $_SESS; 

		if (!$id) {
			return null;
		}
		

		if ($this->cache->models) {
			$data = $this->cache->models->getVar($id);
		}
		
		if (!is_array($data)) {
			$data = $this->db->QFetchArray(
				"SELECT * FROM %s WHERE model_id = %d",
				[
					$this->tables['plugin:novosteer_addon_autobrands_models'],
					$id
				]
			);

			if ($this->cache->models) {
				$this->cache->models->addVar($id , $data);
			}
		}

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
	function getTrimById($id ) {
		global $base , $_USER , $_SESS; 

		if (!$id) {
			return null;
		}

		$this->__init();


		$data = $this->cache->trims->getVar($id);

		if (!is_array($data)) {
			$data = $this->db->QFetchArray(
				"SELECT * FROM %s WHERE trim_id = %d",
				[
					$this->tables['plugin:novosteer_addon_autobrands_trims'],
					$id
				]
			);

			$this->cache->trims->addVar($id , $data);
		}

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
	function getBrandsbyIDS($ids) {
		global $base , $_USER , $_SESS; 

		if (!$ids) {
			return null;
		}

		return $this->db->QFetchRowArray(
			"SELECT * FROM %s WHERE brand_id in (%s)ORDER BY brand_name ASC",
			[
				$this->tables["plugin:novosteer_addon_autobrands_brands"],
				is_array($ids) ? implode("," , $ids) : $ids
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
	function getModelsbyIDS($ids) {
		global $base , $_USER , $_SESS; 

		if (!$ids) {
			return null;
		}

		return $this->db->QFetchRowArray(
			"SELECT * FROM %s WHERE model_id in (%s)ORDER BY model_name ASC",
			[
				$this->tables["plugin:novosteer_addon_autobrands_models"],
				is_array($ids) ? implode("," , $ids) : $ids
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
	function getTrimsbyIDS($ids) {
		global $base , $_USER , $_SESS; 

		if (!$ids) {
			return null;
		}


		return $this->db->QFetchRowArray(
			"SELECT * FROM %s WHERE trim_id in (%s) ORDER BY trim_name ASC",
			[
				$this->tables["plugin:novosteer_addon_autobrands_trims"],
				is_array($ids) ? implode("," , $ids) : $ids
			]
		);
	}


	function getTypeById($id) {
		global $base , $_USER , $_SESS , $_LANG_ID; 

		$this->__init();


		if (!$id) {
			return null;
		}

		if ($this->cache->types) {
			$data = $this->cache->types->getVar($id);
		}

		if (!is_array($data)) {
			$data = $this->db->QFetchArray(
				"SELECT * FROM %s as type
					WHERE 
						type.type_id = %d",
				[
					$this->tables['plugin:novosteer_addon_autobrands_types'],
					$id
				]
			);

			if ($this->cache->types) {
				$this->cache->types->addVar($id , $data);
			}

		}
		

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
	function getTypesbyIDS($ids) {
		global $base , $_USER , $_SESS , $_LANG_ID; 


		return $this->db->QFetchRowArray(
			"SELECT * 
			FROM 
				%s as types
				INNER JOIN 
					%s as types_lang 
				ON	
					types.type_id = types_lang.type_id AND 
					types_lang.lang_id = %d
			WHERE 
				types.type_id in (%s) 
			ORDER BY 
				type_order ASC",
			[
				$this->tables["plugin:novosteer_addon_autobrands_types"],
				$this->tables["plugin:novosteer_addon_autobrands_types_lang"],
				$_LANG_ID,
				$ids
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
	function getBrandTrimsByID($bid) {
		global $base , $_USER , $_SESS; 

		return $this->db->QFetchRowArray(
			"SELECT * FROM %s WHERE brand_id = %d ORDER BY trim_name ASC",
			[
				$this->tables["plugin:novosteer_addon_autobrands_trims"],
				$bid
			]
		);
	}

	
	
}