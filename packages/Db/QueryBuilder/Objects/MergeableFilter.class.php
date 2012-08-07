<?
abstract class MergeableFilter extends Filter {
	protected $primaryTable;
	protected $primaryTableAlias;
	protected $primaryJoinField;
	
	
	public function __construct($primaryTable, $primaryTableAlias, $primaryJoinField){
		parent::__construct();
		
		$this->primaryTable = $primaryTable;
		$this->primaryTableAlias = $primaryTableAlias;
		$this->primaryJoinField = new Field($primaryJoinField, $primaryTableAlias);
	}
	
	public function getPrimaryTable(){
		if($this->primaryTable == null){
			throw new RuntimeException("Primary table have not been yet initialized!");
		}
		return $this->primaryTable;
	}
	
	public function getPrimaryTableAlias(){
		if($this->primaryTableAlias == null){
			throw new RuntimeException("Primary table alias have not been yet initialized!");
		}
		return $this->primaryTableAlias;
	}
	
	public function getPrimaryJoinfField(){
		if($this->primaryJoinField == null){
			throw new RuntimeException("Primary join field have not been yet initialized!");
		}
		return $this->primaryJoinField;
	}
}
?>