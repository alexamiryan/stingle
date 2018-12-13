<?php
/**
 * Payments filter class
 *
 */
class LinkShortenerFilter extends MergeableFilter{
	
	public function __construct(){
		parent::__construct(Tbl::get('TBL_LINKS', 'LinkShortener'), "ls", "id");
		
		$this->qb->select(new Field("*", $this->primaryTableAlias))
			->from($this->primaryTable, $this->primaryTableAlias);
	}
	
	public function setOrderByIdAsc(){
		$this->setOrder(new Field('id', $this->primaryTableAlias), MySqlDatabase::ORDER_ASC);
	}
	
	public function setOrderByIdDesc(){
		$this->setOrder(new Field('id', $this->primaryTableAlias), MySqlDatabase::ORDER_DESC);
	}
	
	public function setOrderDateAsc(){
		$this->setOrder(new Field('date', $this->primaryTableAlias), MySqlDatabase::ORDER_ASC);
	}
	
	public function setOrderDateDesc(){
		$this->setOrder(new Field('date', $this->primaryTableAlias), MySqlDatabase::ORDER_DESC);
	}
	
	public function setId($id){
		if(empty($id) or !is_numeric($id)){
			throw new InvalidIntegerArgumentException("\$id have to be not empty integer");
		}
	
		$this->qb->andWhere($this->qb->expr()->equal(new Field('id', $this->primaryTableAlias), $id));
		return $this;
	}
	
	public function setIdNotEqual($id){
		if(empty($id) or !is_numeric($id)){
			throw new InvalidIntegerArgumentException("\$id have to be not empty integer");
		}
	
		$this->qb->andWhere($this->qb->expr()->notEqual(new Field('id', $this->primaryTableAlias), $id));
		return $this;
	}
	
	public function setIdIn(array $ids){
		if(empty($ids) or !is_array($ids)){
			throw new InvalidIntegerArgumentException("\$ids have to be not empty array");
		}
	
		$this->qb->andWhere($this->qb->expr()->in(new Field('id', $this->primaryTableAlias), $ids));
		return $this;
	}
	
	public function setIdNotIn(array $ids){
		if(empty($ids) or !is_array($ids)){
			throw new InvalidIntegerArgumentException("\$ids have to be not empty array");
		}
	
		$this->qb->andWhere($this->qb->expr()->notIn(new Field('id', $this->primaryTableAlias), $ids));
		return $this;
	}
	
	public function setLinkId($id){
		$this->qb->andWhere($this->qb->expr()->equal(new Field('link_id', $this->primaryTableAlias), $id));
		return $this;
	}
	
	public function setLinkIdIn($ids){
		$this->qb->andWhere($this->qb->expr()->in(new Field('link_id', $this->primaryTableAlias), $ids));
		return $this;
	}
	
	public function setIsClicked($isClicked){
		$this->qb->andWhere($this->qb->expr()->equal(new Field('is_clicked', $this->primaryTableAlias), $isClicked));
		return $this;
	}
	
	
	public function setDate($date){
		$nextDay = date(DEFAULT_DATE_FORMAT, strtotime($date .' +1 day'));
		$this->qb->andWhere($this->qb->expr()->between(new Field("date", $this->primaryTableAlias), $date, $nextDay));
		return $this;
	}
	
	public function setYearMonth($year, $month){
		$monthFirstDay = new DateTime("$year-$month-01");
		$nextMonthFirstDay = clone($monthFirstDay);
		$nextMonthFirstDay->add(new DateInterval('P1M'));
		
		return $this->setPaymentsDateRange($monthFirstDay->format('Y-m-d'), $nextMonthFirstDay->format('Y-m-d'));
	}
	
	public function setCurrentMonth(){
		$now = new DateTime();
		$monthFirstDay = new DateTime($now->format('Y-m-01'));
		$nextMonthFirstDay = clone($monthFirstDay);
		$nextMonthFirstDay->add(new DateInterval('P1M'));
		
		return $this->setDateRange($monthFirstDay->format('Y-m-d'), $nextMonthFirstDay->format('Y-m-d'));
	}
	
	public function setDateRange($dateFrom, $dateTo){
		$this->qb->andWhere($this->qb->expr()->between(new Field("date", $this->primaryTableAlias), $dateFrom, $dateTo));
		return $this;
	}
	
	public function setExpires($date){
		$nextDay = date(DEFAULT_DATE_FORMAT, strtotime($date .' +1 day'));
		$this->qb->andWhere($this->qb->expr()->between(new Field("expires", $this->primaryTableAlias), $date, $nextDay));
		return $this;
	}
	
	public function setIsExpired(){
		$this->qb->andWhere($this->qb->expr()->less(new Field("expires", $this->primaryTableAlias), new Func('NOW')));
		return $this;
	}
	
	public function setOnlyLast(){
		$this->setOrder(new Field('id', $this->primaryTableAlias), MySqlDatabase::ORDER_DESC);
		$this->setLimit(1);
		return $this;
	}
	
}
