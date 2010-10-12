<?php
/**
 * ###################################################
 * #                   IMPORTANT!!!                  #
 * ###################################################
 * # Requires sequrity_quotes function to be called  #
 * # before new object definition from this class    #
 * ###################################################
 */

class Gps extends DbAccessor
{
	const ROOT_NODE=1;

	const TBL_TREE = 'wgps_tree';
	const TBL_TYPES = 'wgps_types';
	const TBL_LABELS = 'wgps_labels';
	const TBL_CONFIG = 'wgps_config';
	const TBL_CUST_FIELDS = 'wgps_cust_fields';
	const TBL_CUST_SAVE = 'wgps_cust_save';
	const TBL_ZIP_CODES = 'wgps_zip_codes';


	public function __construct(){
		parent::__construct();
	}

	/**
	 * Get children of node $id
	 *
	 * @param int $node_id=ROOT_NODE
	 * @return array(id,name) if there are nodes
	 * @return false otherwise
	 */
	public function getChildren($node_id=0, $cacheMinutes = null){
		if(empty($node_id) or $node_id<0){
			$node_id=static::ROOT_NODE;
		}
		$this->query->exec("	SELECT `id`,`name` 
								FROM `".static::TBL_TREE."` 
								WHERE `parent_id`='$node_id' AND `type_id`<>1 
								ORDER BY `name`", $cacheMinutes);
		if($this->query->countRecords()){
			return $this->query->fetchRecords();
		}
		return array();
	}
	
	/**
	 * Get count of node's children
	 *
	 * @param int $node_id
	 * @return int
	 */
	public function getChildrenCount($node_id=0, $cacheMinutes = null){
		if(empty($node_id) or $node_id<0){
			$node_id=static::ROOT_NODE;
		}
		$this->query->exec("	SELECT count(*) AS `count` 
								FROM `".static::TBL_TREE."` 
								WHERE `parent_id`='$node_id' AND `type_id`<>1", $cacheMinutes);
		return $this->query->fetchField('count');
	}
	
	/**
	 * Get parent of the node
	 *
	 * @param int $node_id
	 * @return array(id,name,type_id)
	 */
	public function getParent($node_id, $cacheMinutes = null){
		$this->query->exec("	SELECT `id`,`name`,`type_id` 
								FROM `".static::TBL_TREE."` 
								WHERE `id` = (	SELECT `parent_id` 
												FROM `".static::TBL_TREE."` 
												WHERE `id`='$node_id' AND `type_id`<>1) 
										AND `type_id`<>1", $cacheMinutes);
		if($this->query->countRecords()){
			return $this->query->fetchRecord();
		}
		return array();
	}
	
	/**
	 * Get list of parents of the node
	 *
	 * @param int $node_id
	 * @param int $type_id
	 * @return array('id','name','type_id','type_name')
	 */
	public function getParentByType($node_id, $type_id, $cacheMinutes = null){
		if(empty($node_id) or $node_id<0){
			$node_id = static::ROOT_NODE;
		}

		$my_id = $node_id;
		while ($my_id!=static::ROOT_NODE){
			$this->query->exec("SELECT `tree`.`id`,`tree`.`parent_id`, `tree`.`name`, `types`.`id` as `type_id`, `types`.`type`
								FROM `".static::TBL_TREE."` `tree`
								LEFT JOIN ".static::TBL_TYPES." `types`
								ON (`tree`.`type_id`=`types`.`id`)
								WHERE `tree`.`id`='$my_id'", $cacheMinutes);
			if($this->query->countRecords()){
				$result=$this->query->fetchRecord();
				$my_id = $result['parent_id'];
				
				if($type_id != static::ROOT_NODE and $result["type_id"] == $type_id){
					return $result;
				}
			}
			else{
				return false;
			}
		}
	}
	
	/**
	 * Get type of the children
	 *
	 * @param int $node_id
	 * @return array(id, type_name)
	 */
	public function getChildrenType($node_id, $cacheMinutes = null){
		if(empty($node_id) or $node_id<0){
			$node_id=static::ROOT_NODE;
		}
		$this->query->exec("SELECT `types`.`id`, `types`.`type`
							FROM `".static::TBL_TREE."` AS `tree`
							LEFT JOIN `".static::TBL_TYPES."` AS `types`
							ON (`tree`.`type_id`=`types`.`id`)
							WHERE `parent_id`='$node_id' AND `type_id`<>1 LIMIT 1", $cacheMinutes);
		if($this->query->countRecords()){
			return $this->query->fetchRecord();
		}
		return array();
	}

	/**
	 * Get fields to show for given node
	 *
	 * @param int $node_id
	 * @return array of field_ids to show
	 */
	public function fieldsToShow($node_id=0, $cacheMinutes = null){
		if(empty($node_id) or $node_id<0){
			$node_id=static::ROOT_NODE;
		}
		$return_array=array();
		$this->query->exec("SELECT * FROM `".static::TBL_TREE."` WHERE `id`='$node_id'", $cacheMinutes);
		if(!$this->query->countRecords()){
			return false;
		}
		$node=$this->query->fetchRecord();
		
		$this->query->exec("SELECT * FROM `".static::TBL_CUST_FIELDS."` ORDER BY `const_name`", $cacheMinutes);
		if(!$this->query->countRecords()){
			return $return_array;
		}
		$fields=$this->query->fetchRecords();

		$config_array=array();
		foreach ($fields as $field){
			$this->query->exec("SELECT * FROM `".static::TBL_CONFIG."` WHERE `field_id`='{$field['id']}'", $cacheMinutes);
			if($this->query->countRecords()){
				if(($act_rule=$this->findActualRule($node_id, $node['type_id'], $this->query->fetchRecords()))){
					array_push($config_array, $act_rule);
				}
			}
		}
		$final_array=array();
		foreach ($config_array as $conf){
			if($conf['action']==1){
				array_push($final_array, $conf['field_id']);
			}
		}
		return $final_array;
	}
	
	/**
	 * Get field name by id
	 *
	 * @param int $field_id
	 * @return string
	 */
	public function getFieldName($field_id, $cacheMinutes = null){
		$this->query->exec("SELECT `const_name` FROM `".static::TBL_CUST_FIELDS."` WHERE `id`='$field_id'", $cacheMinutes);
		return $this->query->fetchField('const_name');
	}
	
	/**
	 * Get name of the specified type ID
	 *
	 * @param int $type_id
	 * @return string
	 */
	public function getTypeName($type_id, $cacheMinutes = null){
		$this->query->exec("SELECT `type` FROM `".static::TBL_TYPES."` WHERE `id`='$type_id'", $cacheMinutes);
		return $this->query->fetchField('type');
	}
	
	/**
	 * Get Id of the specified type
	 *
	 * @param string $type
	 * @return string
	 */
	public function getTypeId($type, $cacheMinutes = null){
		$this->query->exec("SELECT `id` FROM `".static::TBL_TYPES."` WHERE `type`='$type'", $cacheMinutes);
		return $this->query->fetchField('id');
	}

	/**
	 * Get list of parents of the node
	 *
	 * @param int $node_id
	 * @return array('node_id','name','type_id','type_name')
	 */
	public function getNodeTree($node_id, $cacheMinutes = null){
		if(empty($node_id) or $node_id<0){
			$node_id=static::ROOT_NODE;
		}
		$node_tree=array();
		$my_id=$node_id;
		while ($my_id!=static::ROOT_NODE){
			$this->query->exec("SELECT `tree`.`id`,`tree`.`parent_id`, `tree`.`name`, `types`.`id` as `type_id`, `types`.`type`
								FROM `".static::TBL_TREE."` `tree`
								LEFT JOIN `".static::TBL_TYPES."` `types`
								ON (`tree`.`type_id`=`types`.`id`)
								WHERE `tree`.`id`='$my_id'", $cacheMinutes);
			if($this->query->countRecords()){
				$result=$this->query->fetchRecord();
				array_push($node_tree, array('node_id'=>$result['id'],'name'=>$result['name'], 'type_id'=>$result['type_id'], 'type'=>$result['type']));
				$par_id=$result['parent_id'];
				$my_id=$par_id;
			}
			else{
				break;
			}
		}
		return array_reverse($node_tree);
	}

	/**
	 * Returns node name of given ID
	 *
	 * @param int $nodeId
	 * @param int $cacheMinutes
	 */
	public function getNodeName($nodeId, $cacheMinutes = null){
		if(!is_numeric($nodeId)){
			throw new InvalidIntegerArgumentException();
		}
		
		$this->query->exec("SELECT `name`
							FROM `".static::TBL_TREE."`
							WHERE `id`='$nodeId'", $cacheMinutes);
		return $this->query->fetchField("name");
	}
	
	/**
	 * Returns node type of given ID
	 *
	 * @param int $nodeId
	 * @param int $cacheMinutes
	 * @return array (id, type_name)
	 */
	public function getNodeType($nodeId, $cacheMinutes = null){
		if(!is_numeric($nodeId)){
			throw new InvalidIntegerArgumentException();
		}
		
		$this->query->exec("SELECT types.*
							FROM `".static::TBL_TREE."` tree
							LEFT JOIN `".static::TBL_TYPES."` types
							ON (tree.`type_id`=types.`id`)
							WHERE tree.`id`='$nodeId'", $cacheMinutes);
		return $this->query->fetchRecord();
	}
	
	/**
	 * Get label constant name of given type and country
	 *
	 * @param string $typeName
	 * @param int $countryId
	 * @return string Constant name
	 */
	public function getTypeLabel($typeName,$countryId){
		$this->query->exec("SELECT `constant` FROM `".static::TBL_LABELS."`
							WHERE `country_id`='{$countryId}' AND `type`='{$typeName}'");
		if($this->query->countRecords()){
			return $this->query->fetchField('constant');
		}
		return $typeName;
	}
	
	public function getNodesByZip($zip, $countryNodeId){
		$gpsNodes = array();
		$this->query->exec("SELECT gps_id, zip FROM `".static::TBL_ZIP_CODES."`
							WHERE `country_id`='{$countryNodeId}' AND `zip` LIKE '{$zip}%'
							LIMIT 20");
		$zipNodes = $this->query->fetchRecords();
		foreach ($zipNodes as $zipNode) {
			$node = $this->getNode($zipNode["gps_id"]);
			$zip = array("zip"=>$zipNode["zip"]);
			$gpsNodes[] = $node + $zip;
		}
		return $gpsNodes;
	}
	
	/**
	 * Returns node with all properties (id,name,paren_id,type_id)
	 *
	 * @param int $nodeId
	 * @return array
	 */
	
	public function getNode($nodeId,$cacheMinutes = null){
		if(!is_numeric($nodeId)){
			throw new InvalidIntegerArgumentException();
		}
		$this->query->exec("SELECT * FROM `".static::TBL_TREE."`
							WHERE `id`='$nodeId'", $cacheMinutes);
		return $this->query->fetchRecord();
	}
	
	/**
	 * Save custom entered field
	 *
	 * @param int $user_id
	 * @param int $field_id
	 * @param string $value
	 * @return bool
	 */
	public function saveField($user_id, $field_id, $value){
		$value=addslashes($value);
		$this->query->exec("DELETE FROM ".static::TBL_CUST_SAVE." WHERE `user_id`='$user_id' AND `field_id`='$field_id'");
		if($this->query->exec("INSERT INTO ".static::TBL_CUST_SAVE." (`user_id`,`field_id`,`text`) VALUES('$user_id','$field_id','$value')")){
			return true;
		}
		return false;
	}

	/**
	 * Get customly saved field
	 *
	 * @param int $user_id
	 * @param int $field_id
	 * @return string
	 */
	public function getField($user_id, $field_id, $cacheMinutes = null){
		$this->query->exec("SELECT `text` FROM `".static::TBL_CUST_SAVE."` WHERE `user_id`='$user_id' AND `field_id`='$field_id'", $cacheMinutes);
		return $this->query->fetchField('text');
	}
	
	/**
	 * Get customly saved fields of user
	 *
	 * @param int $user_id
	 * @return array
	 */
	public function getUserFields($user_id){
		$this->query->exec("SELECT `field_id`, `text` FROM `".static::TBL_CUST_SAVE."` WHERE `user_id`='$user_id'");
		return $this->query->fetchRecords();
	}
	
	/**
	 * Get node ID by name
	 *
	 * @param string $node_name
	 * @return int
	 */
	public function getIdByName($node_name, $type_id = 0, $do_like = false, $cacheMinutes = null){
		$sql_query = "SELECT `id` FROM `".static::TBL_TREE."` WHERE ";
		
		if($do_like){
			$sql_query .= "`name` LIKE '$node_name'";
		}
		else{
			$sql_query .= "`name`='$node_name'";
		}
		
		if($type_id){
			$sql_query .= " and `type_id`='$type_id'";
		}
		
		$this->query->exec($sql_query, $cacheMinutes);
		return $this->query->fetchField('id');
	}
	
	/**
	 * Get nodes with given name and type
	 *
	 * @param string $node_name
	 * @param int type Id
	 * @param bool use like or not
	 * @param int cache in minute
	 * @return array nodes
	 */
	public function getNodesByName($node_name, $type_id, $do_like = false, $cacheMinutes = null){

		$sql_query = "SELECT * FROM `".static::TBL_TREE."` WHERE ";
		
		if($do_like){
			$sql_query .= "`name` LIKE '$node_name'";
		}
		else{
			$sql_query .= "`name`='$node_name'";
		}
		
		if($type_id){
			$sql_query .= " and `type_id`='$type_id'";
		}
		
		$this->query->exec($sql_query, $cacheMinutes);
		return $this->query->fetchRecords();
	}

	/**
	 * Returns all available types filtered
	 * by passed $where clause
	 *
	 * @param string $where
	 * @param string $order
	 *
	 * @todo write a filter for class
	 */
	public function getTypes($where = null, $order = "`id` ASC"){
		if(!empty($where)){
			$where = " WHERE $where";
		}
		
		$this->query->exec("SELECT * FROM `".static::TBL_TYPES."`
							$where
							ORDER BY $order");
							
		return $this->query->fetchRecords();
	}

	///////////END OF PUBLIC PART///////////

	
	private function findActualRule($node_id, $type_id, $configs){
		$closest_parent=$this->closestParent($node_id,$configs);
		if($closest_parent){
			if($closest_parent['type_id']==$type_id){
				return $closest_parent;
			}
		}
		return false;
	}
	
	private function closestParent($node_id, $parents, $cacheMinutes = null){
		$my_id=$node_id;
		foreach ($parents as $parent){
			if($node_id == $parent['node_id']){
				return $parent;
			}
		}
		while ($my_id!=static::ROOT_NODE){
			$this->query->exec("SELECT `parent_id` FROM `".static::TBL_TREE."` WHERE `id`='$my_id'", $cacheMinutes);
			$par_id=$this->query->fetchField('parent_id');
			foreach ($parents as $parent){
				if($par_id==$parent['node_id']){
					return $parent;
				}
			}
			$my_id=$par_id;
		}
		return false;
	}
}
?>