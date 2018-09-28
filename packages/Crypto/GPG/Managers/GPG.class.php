<?php
/**
 * Wrapper for GPG functionality
 *
 */
class GPG
{
	
	/**
	 * Encrypt given data to one or more recipients
	 * 
	 * @param string $string
	 * @param string|array $encryptKeyID
	 * @param bollean $armour
	 * @return string
	 */
	public static function encrypt($string, $encryptKeyID = null, $armour = true){
		$config = ConfigManager::getConfig("Crypto","GPG")->AuxConfig;

		$gpg = new Crypt_GPG(array('homedir' => $config->gpgHomeDir));
		
		if($encryptKeyID === null){
			$encryptKeyID = $config->defaultPublicKey;
		}
		
		if(is_array($encryptKeyID)){
			foreach($encryptKeyID as $keyId){
				$gpg->addEncryptKey($keyId);
			}
		}
		else{
			$gpg->addEncryptKey($encryptKeyID);
		}

		return $gpg->encrypt($string, $armour);
	}
	
	/**
	 * Decrypt given data
	 *  
	 * @param string $string
	 * @param string $keyPassword
	 * @param string $keyID
	 * @return string
	 */
	public static function decrypt($string, $keyPassword = null, $keyID = null){
		$config = ConfigManager::getConfig("Crypto","GPG")->AuxConfig;

		$gpg = new Crypt_GPG(array('homedir' => $config->gpgHomeDir));
		
		if($keyID === null){
			$keyID = $config->defaultKey;
		}
		
		if($keyPassword === null){
			$keyPassword = $config->defaultKeyPasswd;
		}
	
		$gpg->addDecryptKey($keyID, $keyPassword);
		
		return $gpg->decrypt($string);
	}
	
	/**
	 * Sign given string
	 * 
	 * @param string $string
	 * @param string $keyPassword
	 * @param string $keyID
	 * @param boolean $mode
	 * @param boolean $armor
	 * @return string
	 */
	public static function sign($string, $keyPassword = null, $keyID = null, $mode = null, $armor = true){
		$config = ConfigManager::getConfig("Crypto","GPG")->AuxConfig;

		$gpg = new Crypt_GPG(array('homedir' => $config->gpgHomeDir));

		if($mode === null){
			$mode = Crypt_GPG::SIGN_MODE_CLEAR;
		}
		
		if($keyID === null){
			$keyID = $config->defaultKey;
		}
		
		if($keyPassword === null){
			$keyPassword = $config->defaultKeyPasswd;
		}
		
		$gpg->addSignKey($keyID, $keyPassword);
		
		return $gpg->sign($string, $mode);
	}
	
	/**
	 * Verify signature of given message
	 * 
	 * @param string $string
	 * @return boolean
	 */
	public static function verify($string){
		$homeDir = ConfigManager::getConfig("Crypto","GPG")->AuxConfig->gpgHomeDir;
		$gpg = new Crypt_GPG(array('homedir'=>$homeDir));
		$signatures = $gpg->verify($string);
		
		if ($signatures[0]->isValid()) {
			return true;
		} 
		else{
			return false;
		}
	}
	
	/**
	 * Encrypt and sign given string to one or more recipients
	 * 
	 * @param string $string
	 * @param string|array $encryptKeyID
	 * @param string $signkeyPassword
	 * @param string $signkeyID
	 * @param boolean $mode
	 * @param boolean $armor
	 * @return string
	 */
	public static function encryptAndSign($string, $encryptKeyID, $signkeyPassword = null, $signkeyID = null, $mode = null, $armor = true){
		$config = ConfigManager::getConfig("Crypto","GPG")->AuxConfig;

		$gpg = new Crypt_GPG(array('homedir' => $config->gpgHomeDir));

		if($mode === null){
			$mode = Crypt_GPG::SIGN_MODE_CLEAR;
		}
		
		if($signkeyID === null){
			$signkeyID = $config->defaultKey;
		}
		
		if($signkeyPassword === null){
			$signkeyPassword = $config->defaultKeyPasswd;
		}
		
		$gpg->addSignKey($signkeyID, $signkeyPassword);
		if(is_array($encryptKeyID)){
			foreach($encryptKeyID as $keyId){
				$gpg->addEncryptKey($keyId);
			}
		}
		else{
			$gpg->addEncryptKey($encryptKeyID);
		}
		
		return $gpg->encryptAndSign($string, $armor);
	}
	
	/**
	 * Decrypt and verify given string
	 * 
	 * @param string $string
	 * @param string $keyPassword
	 * @param string $keyID
	 * @return array|false
	 */
	public static function decryptAndVerify($string, $keyPassword = null, $keyID = null){
		$config = ConfigManager::getConfig("Crypto","GPG")->AuxConfig;
		
		$gpg = new Crypt_GPG(array('homedir' => $config->gpgHomeDir));
		
		if($keyID === null){
			$keyID = $config->defaultKey;
		}
		
		if($keyPassword === null){
			$keyPassword = $config->defaultKeyPasswd;
		}
	
		$gpg->addDecryptKey($keyID, $keyPassword);
		
		$result = $gpg->decryptAndVerify($string);
		
		if(empty($result['data']) and empty($result['signatures'])){
			return false;
		}
		
		if(isset($result['signatures'][0])){
			$result['signature'] = $result['signatures'][0]->isValid();
			unset($result['signatures']);
		}
		
		return $result;
	}
}
