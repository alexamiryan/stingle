<?php
class FilterMerger{
	
	private $mainFilter;
	
	public function __construct(MergeableFilter $mainFilter){
		$this->mainFilter = $mainFilter;
	}
	
	public function mergeLeft(MergeableFilter $filter, Field $leftField = null, Field $rightField = null, $comparisionType = Comparison::EQ){
		$this->merge($filter, $leftField, $rightField, Join::LEFT_JOIN, $comparisionType);
	}
	
	public function mergeRight(MergeableFilter $filter, Field $leftField = null, Field $rightField = null, $comparisionType = Comparison::EQ){
		$this->merge($filter, $leftField, $rightField, Join::RIGHT_JOIN, $comparisionType);
	}
	
	public function merge(MergeableFilter $filter, Field $leftField = null, Field $rightField = null, $joinType = Join::LEFT_JOIN, $comparisionType = Comparison::EQ){
		if($leftField === null){
			$leftField = $this->mainFilter->getPrimaryJoinfField();
		}
		if($rightField === null){
			$rightField = $filter->getPrimaryJoinfField();
		}
		
		$this->mainFilter->getQb()->join($filter->getPrimaryTable(), $filter->getPrimaryTableAlias(), $joinType, new Comparison($leftField, $comparisionType, $rightField));
		
		$parts = $filter->getQb()->getSQLParts();
		
		foreach($parts['join'] as $join){
			$this->mainFilter->getQb()->add("join", $join, true);
		}
		
		$this->mainFilter->getQb()->andWhere($parts['where']);
	}
}
