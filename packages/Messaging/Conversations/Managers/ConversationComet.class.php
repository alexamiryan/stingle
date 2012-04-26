<?
class ConversationComet extends CometChunk{
	
	private $lastId;
	private $uuid;
	
	private $newMessages;
	
	public function __construct($params){
		$this->setName('conv');
		
		$this->lastId = $params['lastId'];
		$this->uuid = $params['uuid'];
	}
	
	
	public function run(){
		$filter = new ConversationMessagesFilter();
		
		$filter->setUUID($this->uuid);
		$filter->setIdGreater($this->lastId);
		
		$messages = Reg::get('convMgr')->getConversationMessages($filter);
		
		if(count($messages) > 0){
			$this->newMessages = $messages;
			$this->setIsAnyData();
		}
	}
	
	public function getDataArray(){
		
		$responseArray = null;
		
		$responseArray['lastId'] = Reg::get('convMgr')->getMessagesLastId();
		
		if(is_array($this->newMessages) and count($this->newMessages)>0){
			$responseArray['messages'] = $this->newMessages;
		}
		
		return $responseArray;
	}
}
?>