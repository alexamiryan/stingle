<?php
class CometEventsChunk extends CometChunk{
	
	protected $name = "events";
	
	protected $eventHandlers = array();
	protected $cometEvents;
	protected $newEvents = array();
	
	public function __construct($params){
		parent::__construct($params);
		
		$this->cometEvents = Reg::get(ConfigManager::getConfig("Comet", "CometEvents")->Objects->CometEvents);
	}
	
	public function addHandler(CometEventHandler $handler){
		$this->eventHandlers[$handler->getName()] = $handler;
	}
	
	public function isAnyHandler(){
		if(count($this->eventHandlers) > 0){
			return true;
		}
		return false;
	}
	
	public function isEventHandlerRegistered($handlerName){
		if(in_array($handlerName, array_keys($this->eventHandlers))){
			return true;
		}
		return false;
	}
	
	public function getEventHandlers(){
		return $this->eventHandlers;
	}
	
	public function getEventHandler($handlerName){
		if($this->isEventHandlerRegistered($handlerName)){
			return $this->eventHandlers[$handlerName];
		}
		return false;
	}
	
	public function run(){
		if(isset($this->params['lastId'])){
			$userIdsToMonitor = array();
			$eventTypesToMonitor = array();
			foreach($this->getEventHandlers() as $handlerName => $handler){
				if(is_a($handler, "CometBroadcastEventHandler") and $handler->isBroadcast == true){
					$userIdsToMonitor = array_merge($userIdsToMonitor, $handler->getUsersListToListenTo());
					$eventTypesToMonitor = array_merge($eventTypesToMonitor, $handler->getEventTypesToListenTo());
				}
			}
			
			// Events for myself
			if(isset($this->params['userId']) and !empty($this->params['userId'])){
				$filter = new CometEventsFilter();
				$filter->setUserId($this->params['userId']);
				$filter->setIdGreater($this->params['lastId']);
				$this->newEvents = $this->cometEvents->getEvents($filter);
			}
			
			// Broadcast events from selected users
			if(count($userIdsToMonitor)){
				$filter = new CometEventsFilter();
				$filter->setSelfUserIdIn($userIdsToMonitor);
				$filter->setUserIdNull();
				$filter->setIdGreater($this->params['lastId']);
				$this->newEvents = array_merge($this->newEvents, $this->cometEvents->getEvents($filter));
			}
			
			// Broadcast events by event types
			if(count($eventTypesToMonitor)){
				$filter = new CometEventsFilter();
				$filter->setNameIn($eventTypesToMonitor);
				$filter->setUserIdNull();
				$filter->setIdGreater($this->params['lastId']);
				$this->newEvents = array_merge($this->newEvents, $this->cometEvents->getEvents($filter));
			}
			
			foreach($this->newEvents as $event){
				if(isset($this->eventHandlers[$event->name]) and is_a($this->eventHandlers[$event->name], "CometEventHandler")){
					if($this->eventHandlers[$event->name]->isAnyData($event->data)){
						$this->setIsAnyData();
						break;
					}
				}
			}
		}
	}
	
	public function getDataArray(){
		$responseArray = array();
		
		$responseArray['lastId'] = $this->cometEvents->getEventsLastId();
		
		if(count($this->newEvents) and $this->isAnyHandler()){
			$handlersData = array();
			
			// Collect and group data for each handler
			foreach($this->newEvents as $event){
				if(!isset($handlersData[$event->name]) or !is_array($handlersData[$event->name])){
					$handlersData[$event->name] = array();
				}
				array_push($handlersData[$event->name], $event->data);
			}
			
			$responseArray['chunks'] = array();
			
			// Pass collected data to handlers and get their responce
			foreach($this->getEventHandlers() as $handlerName => $handler){
				if(!isset($handlersData[$handlerName])){
					$handlersData[$handlerName] = array();
				}
				$data = $handler->getDataArray($handlersData[$handlerName]);
				if(!empty($data)){
					$responseArray['chunks'][$handlerName] = $data;
				}
			}
		}
		
		return $responseArray;
	}
}
