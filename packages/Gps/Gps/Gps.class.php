<?
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
	const TBL_COUNTRY_ISO = 'wgps_country_iso';


	public function __construct($dbInstanceKey = null){
		parent::__construct($dbInstanceKey);
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
		
		$qb = new QueryBuilder();
		$qb->select(array(new Field('id'), new Field('name')))
			->from(Tbl::get('TBL_TREE'))
			->where($qb->expr()->equal(new Field('parent_id'), $node_id))
			->andWhere($qb->expr()->notEqual(new Field('type_id'), 1))
			->orderBy(new Field('name'), OrderBy::ASC);
			
		$this->query->exec($qb->getSQL(), $cacheMinutes);
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
		
		$qb = new QueryBuilder();
		$qb->select($qb->expr()->count("*", 'count'))
			->from(Tbl::get('TBL_TREE'))
			->where($qb->expr()->equal(new Field('parent_id'), $node_id))
			->andWhere($qb->expr()->notEqual(new Field('type_id'), 1));
		
		$this->query->exec($qb->getSQL(), $cacheMinutes);
		return $this->query->fetchField('count');
	}
	
	/**
	 * Get parent of the node
	 *
	 * @param int $node_id
	 * @return array(id,name,type_id)
	 */
	public function getParent($node_id, $cacheMinutes = null){
		$qb = new QueryBuilder();
		$innerQb = new QueryBuilder();
		
		$innerQb->select(array(new Field('parent_id')))
			->from(Tbl::get('TBL_TREE'))
			->where($qb->expr()->equal(new Field('id'), $node_id))
			->andWhere($qb->expr()->notEqual(new Field('type_id'), 1));
		
		$qb->select(array(new Field('id'), new Field('name'), new Field('type_id')))
			->from(Tbl::get('TBL_TREE'))
			->where($qb->expr()->equal(new Field('id'), $innerQb))
			->andWhere($qb->expr()->notEqual(new Field('type_id'), 1));
		
		$this->query->exec($qb->getSQL(), $cacheMinutes);
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
			$qb = new QueryBuilder();
			$qb->select(array(
					new Field('id', 'tree'), 
					new Field('parent_id', 'tree'), 
					new Field('name', 'tree'), 
					new Field('id', 'types', 'type_id'), 
					new Field('type', 'types')))
				->from(Tbl::get('TBL_TREE'), 'tree')
				->leftJoin(Tbl::get('TBL_TYPES'), 'types', $qb->expr()->equal(new Field('type_id', 'tree'), new Field('id', 'types')))
				->where($qb->expr()->equal(new Field('id', 'tree'), $my_id));
			
			$this->query->exec($qb->getSQL(), $cacheMinutes);
			
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
		
		$qb = new QueryBuilder();
		$qb->select(array(new Field('id', 'types'),	new Field('type', 'types')))
			->from(Tbl::get('TBL_TREE'), 'tree')
			->leftJoin(Tbl::get('TBL_TYPES'), 'types', $qb->expr()->equal(new Field('type_id', 'tree'), new Field('id', 'types')))
			->where($qb->expr()->equal(new Field('parent_id', 'tree'), $node_id))
			->andWhere($qb->expr()->notEqual(new Field('type_id', 'tree'), 1))
			->limit(1);
		
		$this->query->exec($qb->getSQL(), $cacheMinutes);
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
		
		$qb = new QueryBuilder();
		$qb->select(new Field('*'))
			->from(Tbl::get('TBL_TREE'))
			->where($qb->expr()->equal(new Field('id'), $node_id));
		
		$this->query->exec($qb->getSQL(), $cacheMinutes);
		if(!$this->query->countRecords()){
			return false;
		}
		$node=$this->query->fetchRecord();
		
		$qb = new QueryBuilder();
		$qb->select(new Field('*'))
			->from(Tbl::get('TBL_CUST_FIELDS'))
			->orderBy(new Field('const_name'));
		
		$this->query->exec($qb->getSQL(), $cacheMinutes);
		if(!$this->query->countRecords()){
			return $return_array;
		}
		$fields=$this->query->fetchRecords();

		$config_array=array();
		foreach ($fields as $field){
			$qb = new QueryBuilder();
			$qb->select(new Field('*'))
				->from(Tbl::get('TBL_CONFIG'))
				->where($qb->expr()->equal(new Field('field_id'), $field['id']));
			
			$this->query->exec($qb->getSQL(), $cacheMinutes);
			if($this->query->countRecords()){
				if(($act_rule=$this->findActualRule($node_id, $node['type_id'], $this->query->fetchRecords())) != false){
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
		$qb = new QueryBuilder();
		$qb->select(new Field('const_name'))
			->from(Tbl::get('TBL_CUST_FIELDS'))
			->where($qb->expr()->equal(new Field('id'), $field_id));
		
		$this->query->exec($qb->getSQL(), $cacheMinutes);
		return $this->query->fetchField('const_name');
	}
	
	/**
	 * Get name of the specified type ID
	 *
	 * @param int $type_id
	 * @return string
	 */
	public function getTypeName($type_id, $cacheMinutes = null){
		$qb = new QueryBuilder();
		$qb->select(new Field('type'))
			->from(Tbl::get('TBL_TYPES'))
			->where($qb->expr()->equal(new Field('id'), $type_id));
		
		$this->query->exec($qb->getSQL(), $cacheMinutes);
		return $this->query->fetchField('type');
	}
	
	/**
	 * Get Id of the specified type
	 *
	 * @param string $type
	 * @return string
	 */
	public function getTypeId($type, $cacheMinutes = null){
		$qb = new QueryBuilder();
		$qb->select(new Field('id'))
			->from(Tbl::get('TBL_TYPES'))
			->where($qb->expr()->equal(new Field('type'), $type));
		
		$this->query->exec($qb->getSQL(), $cacheMinutes);
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
			$qb = new QueryBuilder();
			$qb->select(array(
					new Field('id', 'tree'),
					new Field('parent_id', 'tree'),
					new Field('name', 'tree'),
					new Field('id', 'types', 'type_id'),
					new Field('type', 'types')
					))
				->from(Tbl::get('TBL_TREE'), 'tree')
				->leftJoin(Tbl::get('TBL_TYPES'), 'types', $qb->expr()->equal(new Field('type_id', 'tree'), new Field('id', 'types')))
				->where($qb->expr()->equal(new Field('id', 'tree'), $my_id));
			
			$this->query->exec($qb->getSQL(), $cacheMinutes);
			if($this->query->countRecords()){
				$result=$this->query->fetchRecord();
				array_push($node_tree, array(	'node_id'=>$result['id'],
												'name'=>$result['name'], 
												'type_id'=>$result['type_id'], 
												'type'=>$result['type']
											)
										);
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
		
		$qb = new QueryBuilder();
		$qb->select(new Field('name'))
			->from(Tbl::get('TBL_TREE'))
			->where($qb->expr()->equal(new Field('id'), $nodeId));
		
		$this->query->exec($qb->getSQL(), $cacheMinutes);
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
		
		$qb = new QueryBuilder();
		$qb->select(new Field('*', 'types'))
			->from(Tbl::get('TBL_TREE'), 'tree')
			->leftJoin(Tbl::get('TBL_TYPES'), 'types', $qb->expr()->equal(new Field('type_id', 'tree'), new Field('id', 'types')))
			->where($qb->expr()->equal(new Field('id', 'tree'), $nodeId));
		
		$this->query->exec($qb->getSQL(), $cacheMinutes);
		return $this->query->fetchRecord();
	}
	
	/**
	 * Get label constant name of given type and country
	 *
	 * @param string $typeName
	 * @param int $countryId
	 * @return string Constant name
	 */
	public function getTypeLabel($typeName,$countryId, $cacheMinutes = null){
		$qb = new QueryBuilder();
		$qb->select(new Field('constant'))
			->from(Tbl::get('TBL_LABELS'))
			->where($qb->expr()->equal(new Field('country_id'), $countryId))
			->andWhere($qb->expr()->equal(new Field('type'), $typeName));
		
		$this->query->exec($qb->getSQL(), $cacheMinutes);
		if($this->query->countRecords()){
			return $this->query->fetchField('constant');
		}
		return $typeName;
	}
	
	/**
	 * Get gps nodes by given zip code and country
	 * If volme is 0 then return all gps nodes that matches given criteria.
	 * 
	 * @param string $zip
	 * @param int $countryNodeId
	 * @param int $volume
	 */
	public function getNodesByZip($zip, $countryNodeId, $volume = 20, $exactMatch = true, $cacheMinutes = null){
		$gpsNodes = array();
		
		$qb = new QueryBuilder();
		$qb->select(array(new Field('gps_id'), new Field('zip')))
			->from(Tbl::get('TBL_ZIP_CODES'))
			->where($qb->expr()->equal(new Field('country_id'), $countryNodeId));
		
		if($volume != 0){
			$qb->limit($volume);
		}
		if($exactMatch){
			$qb->andWhere($qb->expr()->equal(new Field('zip'), $zip));
		}
		else{
			$qb->andWhere($qb->expr()->like(new Field('zip'), $zip . '%'));
		}
		
		$this->query->exec($qb->getSQL(), $cacheMinutes);
		if($this->query->countRecords()){
			$zipNodes = $this->query->fetchRecords();
			foreach ($zipNodes as $zipNode) {
				$node = $this->getNode($zipNode["gps_id"]);
				$zip = array("zip"=>$zipNode["zip"]);
				$gpsNodes[] = array_merge($node, $zip);
			}
		}
		elseif(strpos($zip,"-") !== false){
			return $this->getNodesByZip(str_replace("-"," ",$zip), $countryNodeId);
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
		
		$qb = new QueryBuilder();
		$qb->select(new Field('*'))
			->from(Tbl::get('TBL_TREE'))
			->where($qb->expr()->equal(new Field('id'), $nodeId));
		
		$this->query->exec($qb->getSQL(), $cacheMinutes);
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
		
		$qb = new QueryBuilder();
		$qb->delete(Tbl::get('TBL_CUST_SAVE'))
			->where($qb->expr()->equal(new Field('user_id'), $user_id))
			->andWhere($qb->expr()->equal(new Field('field_id'), $field_id));
		
		$this->query->exec($qb->getSQL());
		
		$qb = new QueryBuilder();
		$qb->insert(Tbl::get('TBL_CUST_SAVE'))
			->values(array(
				'user_id' => $user_id,
				'field_id' => $field_id,
				'text' => $value
				));
		
		if($this->query->exec($qb->getSQL())){
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
		$qb = new QueryBuilder();
		$qb->select(new Field('text'))
			->from(Tbl::get('TBL_CUST_SAVE'))
			->where($qb->expr()->equal(new Field('user_id'), $user_id))
			->andWhere($qb->expr()->equal(new Field('field_id'), $field_id));
		
		$this->query->exec($qb->getSQL(), $cacheMinutes);
		
		return $this->query->fetchField('text');
	}
	
	/**
	 * Get customly saved fields of user
	 *
	 * @param int $user_id
	 * @return array
	 */
	public function getUserFields($user_id){
		$qb = new QueryBuilder();
		$qb->select(array(new Field('field_id'), new Field('text')))
			->from(Tbl::get('TBL_CUST_SAVE'))
			->where($qb->expr()->equal(new Field('user_id'), $user_id));
		
		$this->query->exec($qb->getSQL());
		return $this->query->fetchRecords();
	}
	
	/**
	 * Get node ID by name
	 *
	 * @param string $node_name
	 * @return int
	 */
	public function getIdByName($node_name, $type_id = 0, $do_like = false, $cacheMinutes = null){
		$qb = new QueryBuilder();
		$qb->select(new Field('id'))
			->from(Tbl::get('TBL_TREE'));
		
		if($do_like){
			$qb->andWhere($qb->expr()->like(new Field('name'), $node_name));
		}
		else{
			$qb->andWhere($qb->expr()->equal(new Field('name'), $node_name));
		}
		
		if($type_id){
			$qb->andWhere($qb->expr()->like(new Field('type_id'), $type_id));
		}
		
		$this->query->exec($qb->getSQL(), $cacheMinutes);
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
		$qb = new QueryBuilder();
		$qb->select(new Field('*'))
			->from(Tbl::get('TBL_TREE'));
		
		if($do_like){
			$qb->andWhere($qb->expr()->like(new Field('name'), $node_name));
		}
		else{
			$qb->andWhere($qb->expr()->equal(new Field('name'), $node_name));
		}
		
		if($type_id){
			$qb->andWhere($qb->expr()->like(new Field('type_id'), $type_id));
		}
		
		$this->query->exec($qb->getSQL(), $cacheMinutes);
		return $this->query->fetchRecords();
	}

	/**
	 * Returns all available types filtered
	 * by passed $where clause
	 *
	 * @param string $where
	 * @param string $order
	 *
	 */
	/*public function getTypes($where = null, $order = "`id` ASC", $cacheMinutes = null){
		if(!empty($where)){
			$where = " WHERE $where";
		}
		
		$this->query->exec("SELECT * FROM `".Tbl::get('TBL_TYPES')."`
							$where
							ORDER BY $order", $cacheMinutes);
							
		return $this->query->fetchRecords();
	}*/
	/**
	 * Get country by iso code. 
	 * @param string iso code, three or two digits
	 * @return array
	 */
	public function getCountryByCode($isoCode, $cacheMinutes = null){
		$qb = new QueryBuilder();
		$qb->select(new Field('*'))
			->from(Tbl::get('TBL_COUNTRY_ISO'));
		
		if(strlen($isoCode) == 2){
			$qb->andWhere($qb->expr()->equal(new Field('iso2'), strtoupper($isoCode)));
		}
		elseif (strlen($isoCode) == 3){
			$qb->andWhere($qb->expr()->equal(new Field('iso3'), strtoupper($isoCode)));
		}
		else{
			throw new InvalidIntegerArgumentException("isoCode should be two or three chars length");
		}
		$this->query->exec($qb->getSQL(), $cacheMinutes);
		return $this->query->fetchRecord();
	}
	
	/**
	 * Get closest node by latitude and longitude
	 * This function use fast, but not exact algorithm
	 * 
	 * @param float $latitude
	 * @param float $longitude
	 * @param int $type_id
	 * @param int $cacheMinutes
	 */
	public function getClosestNode($latitude, $longitude, $type_id = null, $cacheMinutes = null){
		if(empty($latitude) or empty($longitude)){
			return false;
		}
		
		$qb = new QueryBuilder();
		$qb->select(new Field('*'))
			->from(Tbl::get('TBL_TREE'))
			->where($qb->expr()->equal(new Field('lat'), $latitude))
			->andWhere($qb->expr()->equal(new Field('lng'), $longitude));
		
		if($type_id !== null){
			$qb->andWhere($qb->expr()->equal(new Field('type_id'), $type_id));
		}
		
		
		$this->query->exec($qb->getSQL(), $cacheMinutes);		
		if($this->query->countRecords()){
			return $this->query->fetchRecord();
		}
						
		// If above query doesn't return record, find in some range of posible latitude and longitude
		// As $latitude, $longitude are decimal(8,4)  find with 3 digits to the right of the decimal point
		$digits_after = 3;
				 
		while($digits_after > -2){	//2 digits to the left of the decimal point
	
			$pow = pow(10,$digits_after);
			
			$lat_min = $latitude - (1/2)/$pow;
			$lat_max = $latitude + (1/2)/$pow;
			
			$lng_min = $longitude - (1/2)/$pow;
			$lng_max = $longitude + (1/2)/$pow;			
			
			$qb = new QueryBuilder();
			$qb->select(new Field('*'))
				->from(Tbl::get('TBL_TREE'))
				->where($qb->expr()->between(new Field('lat'), $lat_min, $lat_max))
				->andWhere($qb->expr()->between(new Field('lng'), $lng_min, $lng_max));
			
			$this->query->exec($qb->getSQL());
			if($this->query->countRecords()){
				return $this->query->fetchRecord();
			}
			$digits_after --; //decrease accuracy
		}
		//////////////////////////////////////////////////////
		// Appear here only if there is no such record		//
		// in wgps_tree table with zero precision/accuracy.	//
		//////////////////////////////////////////////////////
		return null;		
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
			$qb = new QueryBuilder();
			$qb->select(new Field('parent_id'))
				->from(Tbl::get('TBL_TREE'))
				->where($qb->expr()->equal(new Field('id'), $my_id));
			
			$this->query->exec($qb->getSQL(), $cacheMinutes);
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