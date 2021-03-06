<?php
class CometEvents extends DbAccessor{
	const TBL_COMET_EVENTS = "comet_events";
	
	public function getEvents(CometEventsFilter $filter, $reduced = false){
		$this->query->exec($filter->getSQL());
		
		$events = array(); 
		if($this->query->countRecords() > 0){
			while(($row = $this->query->fetchRecord()) != null){
				array_push($events, $this->getEventObjectFromData($row, $reduced));
			}
		}
		
		return $events;
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
	
	public function addEvent($name, $selfUserId = null, $userId = null, $data = array()){
		if(empty($name)){
			throw new InvalidArgumentException("\$name have to be non empty string");
		}
		if($selfUserId !== null and (empty($selfUserId) or !is_numeric($selfUserId))){
			throw new InvalidArgumentException("\$selfUserId have to be non zero integer");
		}
		if($userId !== null and (empty($userId) or !is_numeric($userId))){
			throw new InvalidArgumentException("\$userId have to be non zero integer");
		}
		if(!is_array($data)){
			throw new InvalidArgumentException("\$data have to be array");
		}
		
		$qb = new QueryBuilder();

		$values = array(
						'name' => $name,
						'data' => serialize($data)
						);
		if($selfUserId !== null){
			$values['self_user_id'] = $selfUserId;
		}
		if($userId !== null){
			$values['user_id'] = $userId;
		}
		$qb->insert(Tbl::get('TBL_COMET_EVENTS'))->values($values);
		
		return $this->query->exec($qb->getSQL())->affected();
	}
	
	public function cleanEvents($hoursToKeep){
		$qb = new QueryBuilder();

		$date = new Func('DATE_SUB', array(new Func('NOW'), new Literal("INTERVAL $hoursToKeep HOUR")));
		
		$qb->delete(Tbl::get("TBL_COMET_EVENTS"))
			->where($qb->expr()->less(new Field('date'), $date));

		$this->query->exec($qb->getSQL());
	}
	
	protected function getNewEventObject(){
		return new CometEvent();
	}
	
	protected function getEventObjectFromData($eventRow, $reduced = false){
		$event = $this->getNewEventObject();
		$event->id = $eventRow['id'];
		$event->date = $eventRow['date'];
		$event->selfUserId = $eventRow['self_user_id'];
		$event->userId = $eventRow['user_id'];
		if(!$reduced){
			$UserManager = Reg::get(ConfigManager::getConfig("Users","Users")->Objects->UserManager);
			if(!empty($eventRow['self_user_id'])){
				$event->selfUser = $UserManager->getUserById($eventRow['self_user_id']);
			}
			if(!empty($eventRow['user_id'])){
				$event->user = $UserManager->getUserById($eventRow['user_id']);
			}
		}
		$event->name = $eventRow['name'];
		$event->data = unserialize($eventRow['data']);
		
		return $event;
	}
}
