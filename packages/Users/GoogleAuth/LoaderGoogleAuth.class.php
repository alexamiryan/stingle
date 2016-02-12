<?php
class LoaderGoogleAuth extends Loader{
	protected function includes(){
		stingleInclude ('Managers/GoogleAuthenticator.class.php');
		stingleInclude ('Managers/GoogleAuthManager.class.php');
		stingleInclude ('Managers/GoogleAuthUserAuthorization.class.php');
		stingleInclude ('Exceptions/GoogleAuthException.class.php');
		stingleInclude ('Exceptions/GoogleAuthRequiredException.class.php');
		stingleInclude ('Exceptions/InvalidGoogleAuthException.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('GoogleAuthUserAuthorization');
	}
	
	public function hookGoogleAuth($params){
		$googleAuth = new GoogleAuthUserAuthorization($this->config->AuxConfig);
		
		if(isset($params['user'])and is_a($params['user'], "User")){
			$gauthOTP = "";
			if(isset($params['additionalCredentials']) and isset($params['additionalCredentials']['gauthOTP'])){
				$gauthOTP = $params['additionalCredentials']['gauthOTP'];
			}
			
			$googleAuth->auth($params['user'], $gauthOTP);
		}
		else{
			throw new RuntimeException("No user provided for GoogleAuth");
		}
	}
}
