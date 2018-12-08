<?php
/**
 * Payments filter class
 *
 */
class EmailLogFilter extends MergeableFilter{
	
	public function __construct(){
		parent::__construct(Tbl::get('TBL_EMAIL_LOG', 'EmailLogManager'), "el", "id");
		
		$this->qb->select(new Field("*", $this->primaryTableAlias))
			->from($this->primaryTable, $this->primaryTableAlias);
	}
	
	
	public function getStats(){
		$this->qb->select(
			array(
				new Func('DATE', new Field('date'), 'date'),
				new Func('COUNT', '*', 'count')
			)
		);
		
		$this->qb->groupBy(new Func('DATE', new Field('date')));
		//$this->qb->groupBy(new Func('DATE_FORMAT', array(new Field('date'), '%Y%m%d%H')));
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
	
	public function setUserId($userId){
		$this->qb->andWhere($this->qb->expr()->equal(new Field('user_id', $this->primaryTableAlias), $userId));
		return $this;
	}
	
	public function setUser(User $user){
		$this->qb->andWhere($this->qb->expr()->equal(new Field('user_id', $this->primaryTableAlias), $user->id));
		return $this;
	}
	
	public function setEmailId($emailId){
		$this->qb->andWhere($this->qb->expr()->equal(new Field('email_id', $this->primaryTableAlias), $emailId));
		return $this;
	}
	
	public function setType($type){
		if(empty($type)){
			throw new InvalidIntegerArgumentException("\$type have to be not empty string");
		}
		$this->qb->andWhere($this->qb->expr()->equal(new Field('type', $this->primaryTableAlias), $type));
		return $this;
	}
	
	public function setTypeIn($types){
		if(empty($types) or ! is_array($types)){
			throw new InvalidIntegerArgumentException("\$types have to be not empty array");
		}
		$this->qb->andWhere($this->qb->expr()->in(new Field('type', $this->primaryTableAlias), $types));
		return $this;
	}
	
	public function setBounceType($type){
		if(empty($type)){
			throw new InvalidIntegerArgumentException("\$type have to be not empty string");
		}
		$this->qb->andWhere($this->qb->expr()->equal(new Field('bounce_type', $this->primaryTableAlias), $type));
		return $this;
	}
	
	public function setBounceTypeNotEqual($type){
		if(empty($type)){
			throw new InvalidIntegerArgumentException("\$type have to be not empty string");
		}
		$this->qb->andWhere($this->qb->expr()->notEqual(new Field('bounce_type', $this->primaryTableAlias), $type));
		return $this;
	}
	
	public function setBounceCode($code){
		if(empty($code)){
			throw new InvalidIntegerArgumentException("\$code have to be not empty string");
		}
		$this->qb->andWhere($this->qb->expr()->equal(new Field('bounce_code', $this->primaryTableAlias), $code));
		return $this;
	}
	
	public function setEmail($email){
		$this->qb->andWhere($this->qb->expr()->equal(new Field('email', $this->primaryTableAlias), $email));
		return $this;
	}
	
	/**
	 * 
	 * @param  $date Date in default format (DEFAULT_DATE_FORMAT)
	 */
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
	
	public function setOnlyLast(){
		$this->setOrder(new Field('id', $this->primaryTableAlias), MySqlDatabase::ORDER_DESC);
		$this->setLimit(1);
		return $this;
	}
	
}
