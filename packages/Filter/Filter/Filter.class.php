<?

abstract class Filter {
	const MATCH_EQUAL		= "exact_match";
	const MATCH_NOT_EQUAL		= "not_equal";
	const MATCH_CONTAINS		= "contains";
	const MATCH_STARTS		= "starts";
	const MATCH_ENDS		= "ends";
	const MATCH_LESS		= "less";
	const MATCH_GREATER		= "greater";
	const MATCH_LESS_EQUAL		= "less_equal";
	const MATCH_GREATER_EQUAL	= "greater_equal";
	const MATCH_IN			= "in";
	const MATCH_NOT_IN		= "not_in";
	const MATCH_NULL		= "null";
	const MATCH_NOT_NULL		= "not_null";
	
	protected $conditions = array();
	protected $comparisons = array();
	protected $extraJoins = array();
	protected $extraWheres = array();
	protected $customWheres = array();
	protected $limits = array();
	protected $order = array();
	protected $extraOrder = array();
	
	private static function detectMatchConstant($name){
		return preg_match("/MATCH_[A-Z]+/", $name);
	}
	
	protected static function getPossibleMatches(){
		$refl = new ReflectionClass(__CLASS__);
		$possibleMatches = array();
		foreach($refl->getConstants() as $const => $value){
			if(static::detectMatchConstant($const)){
				array_push($possibleMatches, $value);
			}
		}
		return $possibleMatches;
	}
	
	public static function matchExists($match){
		$possibleMatches = static::getPossibleMatches();
		if(in_array($match, $possibleMatches)){
			return true;
		}
		else{
			return false;
		}
	}
	
	protected static function checkMatchExistance($match){
		if(!static::matchExists($match)){
			// TODO create a special exception for this case
			throw new Exception("Specified match type does not exist");
		}
	}
	
	protected function checkJoinedTableExistance($tableName){
		if(!is_array($this->extraJoins[$tableName])){
			// TODO create a special exception for this case
			throw new Exception("Specified table is not in joins list");
		}
	}
	
	protected function joinedTableAliasExists($tableAlias){
		foreach($this->extraJoins as $tableName => $joins){
			foreach($joins as $tableParams){
				list($alias, $joinField, $masterField, $joinType) = $tableParams;
				if($tableAlias == $alias){
					return true;
				}
			}
		}
	}
	
	protected function checkJoinedTableAliasExistance($tableAlias){
		if(!$this->joinedTableAliasExists($tableAlias)){
			// TODO create a special exception for this case
			throw new Exception("Specified table is not in joins list");
		}
	}
	
	public function setCondition($fieldName, $match, $values){
		static::checkMatchExistance($match);
		
		$this->conditions[] = array('field' => $fieldName, 'condition' => $match, 'params' => $values);
		return $this;
	}
	
	protected function setFieldsComparison($firstField, $secondField, $match = self::MATCH_EQUAL){
		static::checkMatchExistance($match);
		
		array_push($this->comparisons, array($firstField, $secondField, $match));
		return $this;
	}
	
	protected function setExtraJoin($tableName, $alias, $joinField, $masterField, $joinType){
		if(!isset($this->extraJoins[$tableName]) and empty($this->extraJoins[$tableName])){
			if(!isset($this->extraJoins[$tableName]) or !is_array($this->extraJoins[$tableName])){
				$this->extraJoins[$tableName] = array();
			}
			array_push($this->extraJoins[$tableName], array(
				$alias, $joinField, $masterField, $joinType
			));
		}
		return $this;
	}
	
	protected function setExtraWhere($tableAlias, $fieldName, $value, $match){
		static::checkMatchExistance($match);
		static::checkJoinedTableAliasExistance($tableAlias);
		
		array_push($this->extraWheres, array(
			$tableAlias, $fieldName, $value, $match
		));
		return $this;
	}
	
	protected function setCustomWhere($customWhere){
		array_push($this->customWheres, $customWhere);
		return $this;
	}
	
	public function setLimits($offset, $length = null){
		if($length === null){
			$this->limits = array(0, $offset);
		}
		else{
			$this->limits = array($offset, $length);
		}
		return $this;
	}
	
	public function setOrder($fieldName, $order){
		array_push($this->order, array(
			$fieldName, $order
		));
		return $this;
	}
	
	public function setRandOrder(){
		array_push($this->extraOrder, array(
			'' ,'rand()', ''
		));
		return $this;
	}
	
	public function removeCondition($fieldName){
		$condtions = & $this->conditions;
		foreach ($this->conditions as $key=>$condition) {
			if($condition['field'] == $fieldName){
				unset($condtions[$key]);
				break;
			}
		}
	}
	public function removeExtraJoin($tableName){
		$extraJoins = & $this->extraJoins;
		foreach ($this->extraJoins as $tbl=>$extraJoin) {
			if($tbl == $tableName){
				unset($extraJoins[$tbl]);
				break;
			}
		}
	}
	
	public function getLimits(){
		return $this->limits;
	}
	
	public function getOrder(){
		return $this->order;
	}
	
	public function getExtraOrder(){
		return $this->extraOrder;
	}
	
	public function getConditions(){
		return $this->conditions;
	}
	
	public function getComparisons(){
		return $this->comparisons;
	}
	
	public function getExtraJoins(){
		return $this->extraJoins;
	}
	
	public function getExtraWheres(){
		return $this->extraWheres;
	}
	
	public function getCustomWheres(){
		return $this->customWheres;
	}
}

?>
