<?
abstract class Filter{
	protected $qb;
	
	public function __construct(){
		$this->qb = new QueryBuilder();
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
}
?>