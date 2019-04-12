<?php
class FormKey {  
    private $issuedKeys = array(); 
    private $config = null;
   
    function __construct(Config $config){  
		$this->config = $config;
		$this->issuedKeys = &$_SESSION[$this->config->sessionVarName];
		
		if(!is_array($this->issuedKeys)){
			$this->issuedKeys = array();
		}
		$this->cleanupOldKeys();
    }  

    private function cleanupOldKeys(){
		if(!$this->config->enabled){
			return;
		}
    	foreach($this->issuedKeys as $position => $keyArray){
    		if(time()-$keyArray[1] > $this->config->keyTimeout){
	        	array_splice($this->issuedKeys, $position, 1);
    		}
    	}
    }

    public function getKey(){
		if(!$this->config->enabled){
			return '';
		}
       	$newKey = Crypto::secureRandom(256);
       	array_push($this->issuedKeys, array($newKey, time()));  
        
        return $newKey;  
    }

    public function validate($key){
		if(!$this->config->enabled){
			return true;
		}
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
