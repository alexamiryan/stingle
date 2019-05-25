<?php
/**
 * Payments filter class
 *
 */
class EmailStatsFilter extends MergeableFilter{
	
	public function __construct(){
		parent::__construct(Tbl::get('TBL_EMAIL_STATS', 'EmailStatsManager'), "es", "id");
		
		$this->qb->select(new Field("*", $this->primaryTableAlias))
			->from($this->primaryTable, $this->primaryTableAlias);
	}
	
	public function getStats($item = 'day', $byWhichDate = 'sent'){
		
		
		$groupField = null;
		if($byWhichDate == 'sent'){
			$groupField = 'date';
		}
		elseif($byWhichDate == 'read'){
			$groupField = 'date_read';
		}
		elseif($byWhichDate == 'click'){
			$groupField = 'date_clicked';
		}
		elseif($byWhichDate == 'activate'){
			$groupField = 'date_activated';
		}
		elseif($byWhichDate == 'unsubscribe'){
			$groupField = 'date_unsubscribed';
		}
		elseif($byWhichDate == 'bounce'){
			$groupField = 'date_bounced';
		}
		else{
			throw new InvalidArgumentException('$byWhichDate should be either sent,read,click,activate,unsubscribe,bounce');
		}
		
		if($item == 'day'){
			$this->qb->groupBy(new Func('DATE', new Field($groupField)));
		}
		elseif($item == 'hour'){
			$this->qb->groupBy(new Func('DATE_FORMAT', array(new Field($groupField), '%Y%m%d%H')));
		}
		else{
			throw new InvalidArgumentException('$item should be either day or hour');
		}
		
		$this->qb->select(
			array(
				($item == 'day' ? new Func('DATE', new Field($groupField), 'date') : new Field($groupField, null, 'date')),
				new Func('COUNT', '*', 'count'),
				new Func('SUM', new Field('is_read'), 'read_count'),
				new Func('SUM', new Field('is_clicked'), 'clicked_count'),
				new Func('SUM', new Field('is_activated'), 'activated_count'),
				new Func('SUM', new Field('is_unsubscribed'), 'unsubscribed_count'),
				new Func('SUM', new Field('is_bounced_soft'), 'bounced_soft_count'),
				new Func('SUM', new Field('is_bounced_hard'), 'bounced_hard_count'),
				new Func('SUM', new Field('is_bounced_block'), 'bounced_block_count'),
			)
		);
		
		
	}
	
	public function getAvailableFroms(){
		$this->qb->select(new Field('from'));
		$this->qb->groupBy(new Field('from'));
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
	
	public function setEmailId($id){
		$this->qb->andWhere($this->qb->expr()->equal(new Field('email_id', $this->primaryTableAlias), $id));
		return $this;
	}
	
	public function setEmailIdIn($ids){
		$this->qb->andWhere($this->qb->expr()->in(new Field('user_id', $this->primaryTableAlias), $ids));
		return $this;
	}
	
	public function setEmail($email){
		$this->qb->andWhere($this->qb->expr()->equal(new Field('email', $this->primaryTableAlias), $email));
		return $this;
	}
	
	public function setEmailIn($emails){
		$this->qb->andWhere($this->qb->expr()->in(new Field('email', $this->primaryTableAlias), $emails));
		return $this;
	}
	
	public function setDomain($domain){
		$this->qb->andWhere($this->qb->expr()->equal(new Field('domain', $this->primaryTableAlias), $domain));
		return $this;
	}
	
	public function setFrom($from){
		$this->qb->andWhere($this->qb->expr()->equal(new Field('from', $this->primaryTableAlias), $from));
		return $this;
	}
	
	public function setFromIn($froms){
		$this->qb->andWhere($this->qb->expr()->in(new Field('from', $this->primaryTableAlias), $froms));
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
	
	public function setIsRead(){
		$this->qb->andWhere($this->qb->expr()->equal(new Field('is_read', $this->primaryTableAlias), 1));
		return $this;
	}
	
	public function setIsClicked(){
		$this->qb->andWhere($this->qb->expr()->equal(new Field('is_clicked', $this->primaryTableAlias), 1));
		return $this;
	}
	
	public function setIsActivated(){
		$this->qb->andWhere($this->qb->expr()->equal(new Field('is_activated', $this->primaryTableAlias), 1));
		return $this;
	}
	
	public function setIsUnsubscribed(){
		$this->qb->andWhere($this->qb->expr()->equal(new Field('is_unsubscribed', $this->primaryTableAlias), 1));
		return $this;
	}
	
	public function setIsBouncedSoft(){
		$this->qb->andWhere($this->qb->expr()->equal(new Field('is_bounced_soft', $this->primaryTableAlias), 1));
		return $this;
	}
	
	public function setIsBouncedHard(){
		$this->qb->andWhere($this->qb->expr()->equal(new Field('is_bounced_hard', $this->primaryTableAlias), 1));
		return $this;
	}
	
	public function setIsBouncedBlocked(){
		$this->qb->andWhere($this->qb->expr()->equal(new Field('is_bounced_block', $this->primaryTableAlias), 1));
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
	
	/**
	 * 
	 * @param  $date Date in default format (DEFAULT_DATE_FORMAT)
	 */
	public function setDate($date){
		$nextDay = date(DEFAULT_DATE_FORMAT, strtotime($date .' +1 day'));
		$this->qb->andWhere($this->qb->expr()->between(new Field("date", $this->primaryTableAlias), $date, $nextDay));
		return $this;
	}
	
	public function setLastXDays($days){
		// BETWEEN NOW() - INTERVAL 30 DAY AND NOW()
		$this->qb->andWhere($this->qb->expr()->between(new Field("date_bounced", $this->primaryTableAlias), $this->qb->expr()->diff(new Func('NOW'), new Literal('INTERVAL '.$days.' DAY')), new Func('NOW')));
		return $this;
	}
	
	public function setYearMonth($year, $month){
		$monthFirstDay = new DateTime("$year-$month-01");
		$nextMonthFirstDay = clone($monthFirstDay);
		$nextMonthFirstDay->add(new DateInterval('P1M'));
		
		return $this->setDateRange($monthFirstDay->format('Y-m-d'), $nextMonthFirstDay->format('Y-m-d'));
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
