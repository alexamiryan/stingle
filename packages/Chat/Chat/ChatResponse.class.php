<?
class ChatResponse extends JSON{
	
	private $chatMessages  = array();
	private $lastId = null;
	private $responseArray = array('messages'=>array(),'lastId'=>array());
	
	public function response(){
		if(!empty($this->chatMessages)){
			$responseArray['messages'] = $this->chatMessages;
		}
		else {
			unset($responseArray['messages']);
		}
		if($this->lastId !==null){
			$responseArray['lastId'] = $this->lastId;
		}
		else{
			unset($responseArray['lastId']);
		}
		return parent::jsonOutput($responseArray);
	}
	
	public function setMessages($array){
		$this->chatMessages = $array;
	}
	
	public function setLastId($id){
		$this->lastId = $id;
	}
	
}
?>