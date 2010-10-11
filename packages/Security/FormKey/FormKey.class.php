<?php
class FormKey {  
	
    private $formKey;  
    private $oldFormKey;  
   
    function __construct() {  
        if( isset($_SESSION['genformkey']) ) {  
            $this->oldFormKey = $_SESSION['genformkey'];  
        }  
    }  

    private function genKey() {  
        $ip = $_SERVER['REMOTE_ADDR'];  
        $uniqueid = uniqid(mt_rand(), true);  

        return md5($ip . $uniqueid);  
    }  

    public function getKey() {  
    	
        $this->formKey = $this->genKey();  
        $_SESSION['genformkey'] = $this->formKey;
        
        return "<input type='hidden' name='genformkey' id='genformkey' value='".$this->formKey."' />";  
    }  

    public function validate($formKeyPost) {
    	
        if( $formKeyPost == $this->oldFormKey ) {  
            return true;  
        }  
        else {  
        	$e = new SecurityException("Unauthorized page access.");
        	$e->setUserMessage("You are performing an unauthorized access to this page.");
			throw $e;
	    }  
    }  
}
?>