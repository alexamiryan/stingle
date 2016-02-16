<?php
class LoaderYubikey extends Loader{
	protected function includes(){
		stingleInclude ('Managers/Yubikey.class.php');
		stingleInclude ('Managers/YubikeyObject.class.php');
		stingleInclude ('Managers/YubikeyUserAuthorization.class.php');
		stingleInclude ('Managers/YubikeyManager.class.php');
		stingleInclude ('Exceptions/YubikeyException.class.php');
		stingleInclude ('Exceptions/YubikeyRequiredException.class.php');
		stingleInclude ('Exceptions/InvalidYubikeyException.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('YubikeyUserAuthorization');
	}
	
	public function hookYubicoAuth(&$params){
		$yubikeyUserAuthorization = new YubikeyUserAuthorization($this->config->AuxConfig);
		
		if(isset($params['user'])and is_a($params['user'], "User")){
			$yubikeyOTP = "";
			if(isset($params['additionalCredentials']) and isset($params['additionalCredentials']['yubikeyOTP'])){
				$yubikeyOTP = $params['additionalCredentials']['yubikeyOTP'];
			}
			
			$result = $yubikeyUserAuthorization->auth($params['user'], $yubikeyOTP);
			$params['wasActive'] = $result;
		}
		else{
			throw new RuntimeException("No user provided for Yubikey Authorization");
		}
	}
}
