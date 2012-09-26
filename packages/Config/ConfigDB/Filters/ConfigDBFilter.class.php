<?
/**
 * Filter Class for configDB class
 * @author Aram Gevorgyan
 *
 */
class ConfigDBFilter extends Filter{
	
	/**
	 * Constructor
	 */
	public function __construct(){
		parent::__construct();
		
		$this->qb->select(new Field("*"))
			->from(Tbl::get('TBL_CONFIGS', 'ConfigDBManager'), "cdb");
	}
	
	/**
	 * Set filter id
	 * @param Integer $id
	 * @throws InvalidIntegerArgumentException
	 */
	public function setId($id){
		if(empty($id) or !is_numeric($id)){
			throw new InvalidIntegerArgumentException("\$id have to be non zero integer.");
		}
		
		$this->qb->andWhere($this->qb->expr()->equal(new Field("id", "cdb"), $id));
		return $this;
	}
	
	/**
	 * Set No Alias filter
	 */
	public function setNoAlias(){
		$this->qb->andWhere($this->qb->expr()->isNull(new Field("alias_of", "cdb")));
		return $this;
	}
	
	/**
	 * 
	 * Set common configs filter
	 */
	public function setCommon(){
		$this->qb->andWhere($this->qb->expr()->isNull(new Field("host_lang_id", "cdb")));
		return $this;
	}
	
	/**
	 * Set common config or current host nad language config
	 * @param Integer $hostLangId
	 * @throws InvalidArgumentException
	 */	
	public function setCommonOrHostLang($hostLangId){
		if(!is_numeric($hostLangId)){
			throw new InvalidArgumentException('Host Langauge id is not numeric!');
		}
		$orX = new Orx();
		$orX->add($this->qb->expr()->isNull(new Field("host_lang_id", "cdb")));
		$orX->add($this->qb->expr()->equal(new Field("host_lang_id", "cdb"), $hostLangId));
		$this->qb->andWhere($orX);
		return $this;
	}
	
	/**
	 * Set host Language id filter
	 * @param Integer $hostLangId
	 * @throws InvalidArgumentException
	 */
	public function setHostLang($hostLangId){
		if(!is_numeric($hostLangId)){
			throw new InvalidArgumentException('Host Langauge id is not numeric!');
		}
		$this->qb->andWhere($this->qb->expr()->equal(new Field("host_lang_id", "cdb"), $hostLangId));
		return $this;
	}
	
	/**
	 * Set config name filter
	 * @param String $name
	 * @throws InvalidArgumentException
	 */
	public function setName($name){
		if(empty($name)){
			throw new InvalidArgumentException('DB Config name is have to be non empty string!');
		}
		$this->qb->andWhere($this->qb->expr()->equal(new Field("name", "cdb"), $name));
		return $this;
	}
	
	/**
	 * Set Confid location
	 * @param Array:String $location
	 * @throws InvalidArgumentException
	 */
	public function setLocation($location){
		if(empty($location)){
			throw new InvalidArgumentException("Location of DB config should be non empty");
		}
		if(is_array($location)){
			$this->qb->andWhere($this->qb->expr()->equal(new Field("location", "cdb"), implode(":", $location)));
		}
		else{
			$this->qb->andWhere($this->qb->expr()->equal(new Field("location", "cdb"), $location));
		}
		return $this;
	}
}
?>