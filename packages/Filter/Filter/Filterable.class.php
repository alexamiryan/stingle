<?

abstract class Filterable extends DbAccessor{
	abstract protected function getFilterableFieldAlias($field);
	
	protected function getWhereClause($field, $value, $match){
		switch($match){
			case Filter::MATCH_EQUAL :
				return " AND $field = '$value'";
			case Filter::MATCH_NOT_EQUAL :
				return " AND $field <> '$value'";
			case Filter::MATCH_LESS :
				return " AND $field < '$value'";
			case Filter::MATCH_LESS_EQUAL :
				return " AND $field <= '$value'";
			case Filter::MATCH_GREATER :
				return " AND $field > '$value'";
			case Filter::MATCH_GREATER_EQUAL :
				return " AND $field >= '$value'";
			case Filter::MATCH_STARTS :
				return " AND $field LIKE '$value%'";
			case Filter::MATCH_ENDS :
				return " AND $field LIKE '%$value'";
			case Filter::MATCH_CONTAINS :
				return " AND $field LIKE '%$value%'";
			case Filter::MATCH_IN :
				return " AND $field IN ('".implode("', '", $value)."')";
			case Filter::MATCH_NOT_IN :
				return " AND $field NOT IN ('".implode("', '", $value)."')";
			case Filter::MATCH_NULL :
				return " AND $field IS NULL";
			case Filter::MATCH_NOT_NULL :
				return " AND $field IS NOT NULL";
			default :
				throw new RuntimeException("Given match:'$match' not found.");
		}
	}
	
	protected function getComparisonWhereClause($field1, $field2, $match){
		switch($match){
			case Filter::MATCH_EQUAL :
				return " AND $field1 = $field2";
			case Filter::MATCH_NOT_EQUAL :
				return " AND $field1 <> $field2";
			case Filter::MATCH_LESS :
				return " AND $field1 < $field2";
			case Filter::MATCH_LESS_EQUAL :
				return " AND $field1 <= $field2";
			case Filter::MATCH_GREATER :
				return " AND $field1 > $field2";
			case Filter::MATCH_GREATER_EQUAL :
				return " AND $field1 >= $field2";
			default :
				throw new RuntimeException("Given \$match not found.");
		}
	}
	
	protected function generateWhere(Filter $filter = null){
		if($filter){
			$whereClause = "";
			foreach($filter->getConditions() as $condition){
				$field = $condition['field'];
				$match = $condition['condition'];
				$value = $condition['params'];
				
				$field = "`{$this->getFilterableFieldAlias($field)}`.`$field`";
				$whereClause .= $this->getWhereClause($field, $value, $match);
			}
			
			foreach($filter->getComparisons() as $params){
				list($firstField, $secondField, $match) = $params;
				$firstField = $this->getFilterableFieldAlias($firstField) . ".`" . $firstField . "`";
				$secondField = $this->getFilterableFieldAlias($secondField) . ".`" . $secondField . "`";
				$whereClause .= $this->getComparisonWhereClause($firstField, $secondField, $match);
			}
			
			foreach($filter->getExtraWheres() as $field => $params){
				list($tableAlias, $fieldName, $value, $match) = $params;
				$field = "`$tableAlias`.`$fieldName`";
				$whereClause .= $this->getWhereClause($field, $value, $match);
			}
			
			foreach($filter->getCustomWheres() as $customWhere){
				$whereClause .= " AND " . $customWhere;
			}
			
			return $whereClause;
		}
		return false;
	}
	
	protected function generateJoins(Filter $filter = null){
		if($filter){
			$joins = "";
			foreach($filter->getExtraJoins() as $table => $extraJoins){
				foreach($extraJoins as $joinParams){
					list($alias, $field, $masterField, $joinType) = $joinParams;
					$masterField = $this->getFilterableFieldAlias($masterField) . ".`" . $masterField . "`";
					if(!isset($joinType)){
						$joinType = MySqlDatabase::JOIN_STANDART;
					}
					$joins .= " " . $joinType . " `" . $table . "` " . $alias . " ON (" . $alias . ".`" . $field . "` = " . $masterField . ")";
				}
			}
			
			return $joins;
		}
		return false;
	}
	
	protected function generateLimits(Filter $filter = null){
		if($filter){
			$limits = $filter->getLimits();
			if(count($limits)){
				list($offset, $length) = $limits;
				return " LIMIT $offset, $length";
			}
			return false;
		}
		return false;
	}
	
	protected function generateOrder(Filter $filter = null){
		if($filter){
			$order_statemnent = '';
			foreach($filter->getOrder() as $one_order){
				list($field, $order_name) = $one_order;
				$table_alias = $this->getFilterableFieldAlias($field);
				$order_statemnent .= " `$table_alias`.`$field` $order_name, ";
			}
			
			foreach($filter->getExtraOrder() as $one_order){
				list($table_alias, $field, $order_name) = $one_order;
				$order_statemnent .= " `$table_alias`.`$field` $order_name, ";
			}
			$order_statemnent = substr($order_statemnent, 0, -2);
			if(!empty($order_statemnent)){
				$order_statemnent = ' ORDER BY' . $order_statemnent;
			}
			return $order_statemnent;
		}
		return false;
	}
}

?>