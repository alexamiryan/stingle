<?
class FormKey {  
    private $issuedKeys = array();  
   
    function __construct(&$sessionVar){  
		$this->issuedKeys = &$sessionVar;
		if(!is_array($this->issuedKeys)){
			$this->issuedKeys = array();
		}
    }  

    private function genKey(){
        $ip = $_SERVER['REMOTE_ADDR'];
        $uniqueid = uniqid(mt_rand(), true);  

        return md5($ip . $uniqueid);
    }

    private function cleanupOldKeys(){
    	foreach($this->issuedKeys as $position => $keyArray){
    		if(time()-$keyArray[1] <= 600){
	        	array_splice($this->issuedKeys, $position, 1);
    		}
    	}
    }

    public function getKey(){
       	$newKey = $this->genKey();
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
    	
		$e = new SecurityException("Unauthorized page access.");
		$e->setUserMessage("You are performing an unauthorized access to this page.");
		throw $e;
    }
}
?>