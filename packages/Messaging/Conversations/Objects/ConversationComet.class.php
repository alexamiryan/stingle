<?php
class ConversationComet extends CometChunk{
	
	protected $name = "conv";
	
	protected $newLastId;
	protected $newMessages;
	
	public function run(){
		if(isset($this->params['uuid']) and isset($this->params['lastId'])){
			$filter = new ConversationMessagesFilter();
			
			$filter->setUUID($this->params['uuid']);
			$filter->setIdGreater($this->params['lastId']);
			
			$messages = Reg::get('convMgr')->getConversationMessages($filter);
			
			if(count($messages) > 0){
				$this->newMessages = $messages;
				$this->newLastId = $messages[count($messages)-1]->id;
				$this->setIsAnyData();
			}
		}
	}
	
	public function getDataArray(){
		$responseArray = array();
		
		if(!empty($this->newLastId)){
			$responseArray['lastId'] = $this->newLastId;
		}
		else{
			$responseArray['lastId'] = Reg::get('convMgr')->getMessagesLastId();
		}
		
		if(is_array($this->newMessages) and count($this->newMessages)>0){
			$responseArray['messages'] = $this->newMessages;
		}
		
		return $responseArray;
	}
}
