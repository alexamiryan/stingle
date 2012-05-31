<?
class ConversationEventComet extends CometEventHandler{
	
	protected $name = "conv";
	
	public function getDataArray($params){
		$messages = array();
		if(count($params) and isset($this->params['uuid'])){
			$msgIds = array();
			foreach($params as $paramSet){
				if(isset($paramSet['msgId']) and is_numeric($paramSet['msgId'])){
					array_push($msgIds, $paramSet['msgId']);
				}
			}
			
			$msgsCount = count($msgIds);
			if($msgsCount > 0){
				$filter = new ConversationMessagesFilter();
				
				if($msgsCount == 1){
					$filter->setId($msgIds[0]);
				}
				else{
					$filter->setIdIn($msgIds);
				}
				
				$filter->setUUID($this->params['uuid']);
					
				try{
					array_push($messages, Reg::get('convMgr')->getConversationMessage($filter));
				}
				catch(ConversationNotUniqueException $e){ }
			}
		}
		
		$responseArray = array();
		
		if(!empty($messages)){
			$responseArray['messages'] = $messages;
		}
		
		return $responseArray;
	}
}
?>