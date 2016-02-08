<?php
class LoaderYubikey extends Loader{
	protected function includes(){
		stingleInclude ('Managers/GoogleAuthenticator.class.php');
		stingleInclude ('Managers/GoogleAuthManager.class.php');
		stingleInclude ('Managers/GoogleAuthUserAuthorization.class.php');
		stingleInclude ('Exceptions/GoogleAuthException.class.php');
		stingleInclude ('Exceptions/GoogleAuthRequiredException.class.php');
		stingleInclude ('Exceptions/InvalidGoogleAuthException.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('YubikeyUserAuthorization');
	}
	
	public function hookYubicoAuth($params){
		$yubikeyUserAuthorization = new YubikeyUserAuthorization($this->config->AuxConfig);
		
		if(isset($params['user'])and is_a($params['user'], "User")){
			$yubikeyOTP = "";
			if(isset($params['additionalCredentials']) and isset($params['additionalCredentials']['yubikeyOTP'])){
				$yubikeyOTP = $params['additionalCredentials']['yubikeyOTP'];
			}
			
			$yubikeyUserAuthorization->auth($params['user'], $yubikeyOTP);
		}
		else{
			throw new RuntimeException("No user provided for Yubikey Authorization");
		}
	}
}
