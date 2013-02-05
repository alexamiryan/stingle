<?php
class Field extends Base{
	
	protected $_preSeparator = '';
	protected $_postSeparator = '';
	
	protected $_field = '';
	protected $_tableAlias;
	protected $_fieldAalias;
	
	public function __construct($field, $tableAlias = null, $fieldAlias = null){
		$this->_field = $field;
		$this->_tableAlias = $tableAlias;
		$this->_fieldAalias = $fieldAlias;
	}
	
	public function __toString(){
		$field = $this->_field;
		if($this->_field != "*" and !is_a($this->_field, 'QBPart') and !is_a($this->_field, 'QueryBuilder')){
			$field = "`$this->_field`";
		}
		
		$returnString = $field;
		
		if($this->_tableAlias !== null){
			$returnString = "`$this->_tableAlias`.$field";
		}
		
		if($this->_fieldAalias !== null){
			$returnString .= " as `$this->_fieldAalias`";
		}
		
		return $returnString;
	}
}
