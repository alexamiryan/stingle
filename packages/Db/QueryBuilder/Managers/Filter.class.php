<?php
abstract class Filter{
	protected $qb;
	
	public function __construct(){
		$this->qb = new QueryBuilder();
	}
	
	/**
	 * Clone also inner objects
	 */
	public function __clone(){
        $this->qb = clone $this->qb;
    }
	
	public function setOrder(Field $field, $order = null){
		$this->qb->addOrderBy($field, $order);
	}
	
	public function setRandOrder(){
		$this->qb->orderBy("RAND()");
	}
	
	public function setLimit($offset, $length = null){
		$this->qb->limit($offset, $length);
	}
	
	public function getSQL(){
		return $this->qb->getSQL();
	}
	
	public function setSelectCount(){
		$this->qb->select($this->qb->expr()->count(new Field('*'), 'cnt'));
	}
	
	public function setSelectSum($field){
		$this->qb->select(new Func('SUM', new Field($field), 'sum'));
	}
	
	/**
	 * @return QueryBuilder
	 */
	public function getQb(){
		return $this->qb;
	}
	
	public function setQb($qb){
		$this->qb = $qb;
	}
}
