<?
class RewriteAliasMap extends DbAccessor {
	
	const TBL_ALIAS 		= "url_alias";
	const TBL_ALIAS_HOST 	= "url_alias_host";

	/**
	 * Map container
	 *
	 * @var array
	 */
	private $aliasMap = array();

	/**
	 * Host extansion
	 *
	 * @var unknown_type
	 */
	private $host; // Host object


	public function __construct(Host $host, $dbInstanceKey = null){
		parent::__construct($dbInstanceKey);

		if(empty($host->host)){
			throw new InvalidArgumentException("\$host have to be non empty Host object");
		}

		$this->host = $host;
	}

	public function getAliasMap(Host $host=null, $cacheMinutes = null){
		if($host === null){
			$host = $this->host;
		}
		$qb = new QueryBuilder();
		$qb->select(new Field('*', 'a'), new Field('host_id', 'ah'))
			->from(Tbl::get('TBL_ALIAS'), 'a')
			->leftJoin(Tbl::get('TBL_ALIAS_HOST'), 'ah', 
						$qb->expr()->equal(new Field('id', 'a'), new Field('alias_id', 'ah')))
			->where($qb->expr()->equal(new Field('host_id', 'ah'), $host->id));
		
		$this->query->exec($qb->getSQL(), $cacheMinutes);
		return $this->query->fetchRecords();
	}
	/**
	 * Update aliase value of record with given id
	 *
	 * @param int $id alias Id
	 * @param string $aliase new aliase value
	 * @return bool
	 */
	public function updateAlias($id, $alias, $map, Host $host=null){
		if(empty($id) or !is_numeric($id)){
			throw new InvalidArgumentException("\$id have to be non zero integer");
		}
		if(empty($alias) or empty($map)){
			throw new InvalidArgumentException("\$alias, \$map and \$host have to be non empty string");
		}
		$qb = new QueryBuilder();
		$qb->update(Tbl::get('TBL_ALIAS'))
			->set(new Field('alias'), $alias)
			->set(new Field('map'), $map)
			->where($qb->expr()->equal(new Field('id'), $id));	
		$this->query->exec($qb->getSQL());
		if($host !== null){
			$qb = new QueryBuilder();
			$qb->update(Tbl::get('TBL_ALIAS_HOST'))
			->set(new Field('host_id'), $host->id)
			->where($qb->expr()->equal(new Field('alias_id'), $id));
			$this->query->exec($qb->getSQL());
		}
	}

	/**
	 * Update aliase map of record with given id
	 *
	 * @param int $id alias Id
	 * @param string $map new aliase map
	 * @return bool
	 */
	public function updateMap($id, $map){
		if(empty($id) or !is_numeric($id)){
			throw new InvalidArgumentException("\$id have to be non zero integer");
		}
		if(empty($map)){
			throw new InvalidArgumentException("\$map have to be non empty string");
		}
		$qb = new QueryBuilder();
		$qb->update(Tbl::get('TBL_ALIAS'))
			->set(new Field('map'), $map)
			->where($qb->expr()->equal(new Field('id'), $id));	
		$this->query->exec($qb->getSQL());
	}

	/**
	 * Add alias
	 *
	 * @param string $alias
	 * @param string $map
	 * @param string $host
	 * @return bool
	 */
	public function addAlias($alias, $map, Host $host){
		if(empty($alias)){
			throw new InvalidArgumentException("\$alias have to be non empty string");
		}
		if(empty($map)){
			throw new InvalidArgumentException("\$map have to be non empty string");
		}
		if(empty($host)){
			throw new InvalidArgumentException("\$host have to be non empty");
		}
		$qb = new QueryBuilder();
		$qb->insert(Tbl::get('TBL_ALIAS'))
			->values(array(
							"alias" => $alias, 
							"map" => $map
						)
					);	
		$this->query->exec($qb->getSQL());
		$inser_id = $this->query->getLastInsertId();
		$qb = new QueryBuilder();
		$qb->insert(Tbl::get('TBL_ALIAS_HOST'))
			->values(array(
							"host_id" => $host->id, 
							"alias_id" => $inser_id
						)
					);
		$this->query->exec($qb->getSQL());
	}

	/**
	 * Delete alias with given id
	 *
	 * @param int $id
	 * @return bool
	 */
	public function deleteAlias($id){
		if(empty($id) or !is_numeric($id)){
			throw new InvalidArgumentException("\$id have to be non zero integer");
		}
		$qb = new QueryBuilder();
		$qb->delete(Tbl::get('TBL_ALIAS'))
			->where($qb->expr()->equal(new Field("id"), $id));	
		$this->query->exec($qb->getSQL());
	}
	/**
	 * Returns host extenstions for witch alias(es) exists in DB
	 *
	 * @return array
	 */
	public function getKnownAliasHosts($cacheMinutes = null){
		$hosts = array();
		$qb = new QueryBuilder();
		//TODO: add in quieryBuilder function selectDistinct
		$query = "SELECT DISTINCT host_id FROM `".Tbl::get('TBL_ALIAS_HOST')."`";
		$this->query->exec($query, $cacheMinutes);
		while (($host_id = $this->query->fetchField("host_id")) != false) {
			$hosts[] = new Host($host_id);
		}
		return $hosts;
		
	}
	
	public function getAliasHost($id, $cacheMinutes = null){
		$qb = new QueryBuilder();
		$qb->select(new Field('host_id'))
			->from(Tbl::get('TBL_ALIAS_HOST'))
			->where($qb->expr()->equal(new Field('alias_id'), $id));
		$this->query->exec($qb->getSQL(), $cacheMinutes);
		return new Host($this->query->fetchField("host_id"));
	}
}
?>