<?
class CometEventsChunk extends CometChunk{
	
	protected $newLastId;
	
	protected $params;
	protected $chunks = array();
	protected $cometEvents;
	
	public function __construct($params){
		$this->setName('events');
		
		$this->params = $params;
		$this->cometEvents = Reg::get(ConfigManager::getConfig("Comet", "CometEvents")->Objects->CometEvents);
	}
	
	public function addChunk(CometChunk $chunk){
		$this->chunks[$chunk->getName()] = $chunk;
	}
	
	public function isAnyChunk(){
		if(count($this->chunks) > 0){
			return true;
		}
		return false;
	}
	
	public function isChunkRegistered($chunkName){
		if(in_array($chunkName, array_keys($this->chunks))){
			return true;
		}
		return false;
	}
	
	public function getChunks(){
		return $this->chunks;
	}
	
	public function getChunk($chunkName){
		if($this->isChunkRegistered($chunkName)){
			return $this->chunks[$chunkName];
		}
		return false;
	}
	
	public function run(){
		if(isset($this->params['lastId']) and isset($this->params['userId'])){
			$newEvents = $this->cometEvents->getNewEvents($this->params['lastId'], $this->params['userId']);
			
			if(count($newEvents) > 0){
				foreach($newEvents as $event){
					if($this->isChunkRegistered($event->name)){
						$chunk = $this->getChunk($event->name);
						if($chunk){
							$chunk->mergeParams($event->data);
							$chunk->run();
							$this->setIsAnyData();
						}
					}
				}
			}
		}
	}
	
	public function getDataArray(){
		$responseArray = array();
		
		$responseArray['lastId'] = $this->cometEvents->getEventsLastId();
		
		if($this->isAnyChunk()){
			$responseArray['chunks'] = array();
			foreach($this->getChunks() as $innerChunkName =>$innerChunk){
				$data = $innerChunk->getDataArray();
				if(!empty($data)){
					$responseArray['chunks'][$innerChunkName] = $data;
				}
			}
		}
		
		return $responseArray;
	}
}
?>