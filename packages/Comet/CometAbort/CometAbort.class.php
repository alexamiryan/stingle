<?
class CometAbort extends CometChunk{
	
	const TBL_COMET_ABORT = 'comet_abort';
	const GC_TIMEOUT = 120; // In seconds
	
	private $id;
	private $sql;
	
	public function __construct($id){
		$this->setName('abort');
		
		$this->id = $id;
		$this->sql = MySqlDbManager::getQueryObject();
	}
	
	public function run(){
		
		$qb = new QueryBuilder();
		
		$qb->select($qb->expr()->count("id", 'cnt'))
			->from(Tbl::get("TBL_COMET_ABORT"))
			->where($qb->expr()->equal(new Field('id'), $this->id));
		
		$count = $this->sql->exec($qb->getSQL())->fetchField('cnt');
		
		if($count > 0){
			$this->setIsAnyData();
			
			$qb = new QueryBuilder();
			
			$qb->delete(Tbl::get("TBL_COMET_ABORT"))
				->where($qb->expr()->equal(new Field('id'), $this->id));
			
			$this->sql->exec($qb->getSQL());
		}
	}
	
	public function getDataArray(){
		return array();
	}
	
	
	// Static part
	
	public static function causeOutput($id){
		$sql = MySqlDbManager::getQueryObject();
		$qb = new QueryBuilder();
	
		$qb->insertIgnore(Tbl::get("TBL_COMET_ABORT"))->values(array("id" => $id));
	
		$sql->exec($qb->getSQL());
	}
	
	public static function clearGarbage(){
		$sql = MySqlDbManager::getQueryObject();
		$qb = new QueryBuilder();
			
		$qb->delete(Tbl::get("TBL_COMET_ABORT"))
			->where($qb->expr()->greater($qb->expr()->diff(new Func("NOW"), new Field('date')), self::GC_TIMEOUT));
		
		return $sql->exec($qb->getSQL())->affected();
	}
}
?>