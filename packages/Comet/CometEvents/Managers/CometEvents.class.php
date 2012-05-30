<?
class CometEvents extends DbAccessor{
	const TBL_COMET_EVENTS = "comet_events";
	
	public function getNewEvents($lastId, $userId, $reduced = false){
		if(!is_numeric($lastId)){
			throw new InvalidArgumentException("\$lastId have to be integer");
		}
		
		$qb = new QueryBuilder();
		
		$qb->select("*")
			->from(Tbl::get("TBL_COMET_EVENTS"))
			->where($qb->expr()->greater(new Field('id'), $lastId))
			->andWhere($qb->expr()->equal(new Field('user_id'), $userId));
		
		$this->query->exec($qb->getSQL());
		
		$newEvents = array(); 
		if($this->query->countRecords() > 0){
			while(($row = $this->query->fetchRecord()) != null){
				array_push($newEvents, $this->getEventObjectFromData($row, $reduced));
			}
		}
		
		return $newEvents;
	}
	
	public function getEventsLastId(){
		$qb = new QueryBuilder();
	
		$qb->select($qb->expr()->max(new Field('id'), 'maxId'))
			->from(Tbl::get('TBL_COMET_EVENTS'));
	
		$maxId = $this->query->exec($qb->getSQL())->fetchField('maxId');
		
		if(empty($maxId)){
			$maxId = 0;
		}
		
		return $maxId;
	}
	
	public function addEvent($name, $userId, $data = array()){
		if(empty($name)){
			throw new InvalidArgumentException("\$name have to be non empty string");
		}
		if(empty($userId) or !is_numeric($userId)){
			throw new InvalidArgumentException("\$userId have to be non zero integer");
		}
		if(!is_array($data)){
			throw new InvalidArgumentException("\$data have to be array");
		}
		
		$qb = new QueryBuilder();
		
		$qb->insert(Tbl::get('TBL_COMET_EVENTS'))
			->values(
					array(
						'name' => $name,
						'user_id' => $userId,
						'data' => serialize($data)
						)
					);
		
		return $this->query->exec($qb->getSQL())->affected();
	}
	
	protected function getEventObjectFromData($eventRow, $reduced = false){
		$event = new CometEvent();
		$event->id = $eventRow['id'];
		$event->date = $eventRow['date'];
		$event->userId = $eventRow['user_id'];
		if(!$reduced){
			$userManagement = Reg::get(ConfigManager::getConfig("Users","Users")->Objects->UserManagement);
			$event->user = $userManagement->getObjectById($eventRow['user_id']);
		}
		$event->name = $eventRow['name'];
		$event->data = unserialize($eventRow['data']);
		
		return $event;
	}
}
?>