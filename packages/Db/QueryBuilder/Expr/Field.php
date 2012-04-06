<?
class Field extends Base{
	
	protected $_preSeparator = '';
	protected $_postSeparator = '';
	
	protected $_field = '';
	protected $_alias;
	protected $_newAalias;
	
	public function __construct($field, $alias = null, $newAlias = null){
		$this->_field = $field;
		$this->_alias = $alias;
		$this->_newAalias = $newAlias;
	}
	
	public function __toString(){
		$field = $this->_field;
		if($this->_field != "*"){
			$field = "`$this->_field`";
		}
		
		$returnString = $field;
		
		if($this->_alias !== null){
			$returnString = "`$this->_alias`.$field";
		}
		
		if($this->_newAalias !== null){
			$returnString .= " as `$this->_newAalias`";
		}
		
		return $returnString;
	}
}
?>