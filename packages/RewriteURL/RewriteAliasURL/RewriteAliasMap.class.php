<?
/**
 * Manager class for Alises
 *
 * @todo Change $host_ext to Host object. Use Host::hosts_tblname table.
 */

class RewriteAliasMap extends DbAccessor {
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

	/**
	 * Name of the table with aliases
	 *
	 * @var string
	 */
	const  alias_tblname = "url_alias";

	/**
	 * Name od the table with host->aliase mapping
	 *
	 * @var string
	 */
	const alias_host_tblname = "url_alias_host";

	public function __construct(Host $host){
		parent::__construct();

		if(empty($host)){
			throw new InvalidArgumentException("\$host_ext have to be non empty string");
		}

		$this->host = $host;
	}

	public function getAliasMap(Host $host=null, $cacheMinutes = null){
		if($host === null){
			$host = $this->host;
		}
		$query = "SELECT a.*, ah.host_id FROM `".static::alias_tblname."` a
				  	LEFT JOIN `".static::alias_host_tblname."` ah ON  a.id = ah.alias_id
					WHERE ah.host_id = '$host->id'";

		$this->query->exec($query, $cacheMinutes);
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

		$this->query->exec("UPDATE `".static::alias_tblname."` SET alias='$alias', map = '$map' WHERE id='$id'");
		if($host !== null){
			$this->query->exec("UPDATE `".static::alias_host_tblname."` SET host_id='{$host->id}'  WHERE alias_id='$id'");
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

		$this->query->exec("UPDATE `".static::alias_tblname."` SET map='$map' WHERE id='$id'");
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

		$this->query->exec("INSERT INTO `".static::alias_tblname."` (`alias`, `map`) VALUES ('$alias', '$map')");
		$inser_id = $this->query->getLastInsertId();
		$this->query->exec("INSERT INTO `".static::alias_host_tblname."` (`host_id`, `alias_id`) VALUES ('$host->id', '$inser_id')");
	}

	/**
	 * Delete alias with given id
	 *
	 * @param int $id
	 * @return bool
	 */
	public function deleteAlais($id){
		if(empty($id) or !is_numeric($id)){
			throw new InvalidArgumentException("\$id have to be non zero integer");
		}

		$this->query->exec("DELETE FROM `".static::alias_tblname."` WHERE `id` = '$id'");
	}
	/**
	 * Returns host extenstions for witch alias(es) exists in DB
	 *
	 * @return array
	 */
	public function getKnownAliasHosts($cacheMinutes = null){
		$hosts = array();
		$query = "SELECT DISTINCT host_id FROM `".static::alias_host_tblname."`";
		$this->query->exec($query, $cacheMinutes);
		while ($host_id = $this->query->fetchField("host_id")) {
			$hosts[] = new Host($host_id);
		}
		return $hosts;
		
	}
	
	public function getAliasHost($id, $cacheMinutes = null){
		$this->query->exec("SELECT `host_id` FROM `".static::alias_host_tblname."` WHERE `alias_id`={$id}", $cacheMinutes);
		return new Host($this->query->fetchField("host_id"));
	}
}
?>