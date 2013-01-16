<?
class FormKey {  
    private $issuedKeys = array(); 
    private $keyTimeout = 1800; 
   
    function __construct(Config $auxConfig){  
		$this->issuedKeys = &$_SESSION[$auxConfig->sessionVarName];
		
		if(!empty($auxConfig->keyTimeout)){
			$this->keyTimeout = $auxConfig->keyTimeout;
		}
		if(!is_array($this->issuedKeys)){
			$this->issuedKeys = array();
		}
    }  

    private function cleanupOldKeys(){
    	foreach($this->issuedKeys as $position => $keyArray){
    		if(time()-$keyArray[1] > $this->keyTimeout){
	        	array_splice($this->issuedKeys, $position, 1);
    		}
    	}
    }

    public function getKey(){
       	$newKey = Crypto::secureRandom(256);
       	array_push($this->issuedKeys, array($newKey, time()));  
        
        return $newKey;  
    }

    public function validate($key){
		$this->cleanupOldKeys();
    	foreach($this->issuedKeys as $position => $keyArray){
    		if($keyArray[0] == $key){
	        	array_splice($this->issuedKeys, $position, 1);
            	return true;
    		}
    	}
    	
		throw new FormKeySecurityException("Unauthorized page access.");
    }
}
?>