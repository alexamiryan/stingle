<?
class ConversationComet extends CometChunk{
	
	protected $name = "conv";
	
	protected $messages = array();
	
	public function run(){
		if(isset($this->params['msgId']) and isset($this->params['uuid'])){
			$filter = new ConversationMessagesFilter();
			
			$filter->setId($this->params['msgId']);
			$filter->setUUID($this->params['uuid']);
			
			try{
				array_push($this->messages, Reg::get('convMgr')->getConversationMessage($filter));
				$this->setIsAnyData();
			}
			catch(ConversationNotUniqueException $e){ }
		}
	}
	
	public function getDataArray(){
		$responseArray = array();
		
		if(!empty($this->messages)){
			$responseArray['messages'] = $this->messages;
		}
		
		return $responseArray;
	}
}
?>