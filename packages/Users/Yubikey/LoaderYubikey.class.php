<?php
class LoaderYubikey extends Loader{
	protected function includes(){
		require_once ('Managers/Yubikey.class.php');
		require_once ('Managers/YubikeyUserAuthorization.class.php');
		require_once ('Exceptions/YubikeyException.class.php');
		require_once ('Exceptions/YubikeyRequiredException.class.php');
		require_once ('Exceptions/InvalidYubikeyException.class.php');
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
