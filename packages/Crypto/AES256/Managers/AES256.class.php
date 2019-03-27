<?php
class AES256
{
	public static function encrypt($string, $key = null, $salt = null, $iv = null){
		$config = ConfigManager::getConfig('Crypto','AES256')->AuxConfig;
		if($key === null){
			$key = $config->key;
		}
		if($salt === null){
			$salt = $config->salt;
		}
		if($iv === null){
			$iv = $config->iv;
		}
		
		$cacheKey = md5($key.$salt.$iv);
		$key = apcuGet($cacheKey);
		
		$td = mcrypt_module_open('rijndael-128', '', MCRYPT_MODE_CBC, '');

		$ks = mcrypt_enc_get_key_size($td);
		$bs = mcrypt_enc_get_block_size($td);
		
		$iv = substr(hash("sha256", $iv), 0, $bs);
		
		// Create key
		if(empty($key)){
			$key = Crypto::pbkdf2("sha512", $key, $salt, $config->pbkdfRounds, $ks);
			apcuStore($cacheKey, $key);
		}

		// Intialize encryption
		mcrypt_generic_init($td, $key, $iv);
		
		// Encrypt data
		$encryptedString = bin2hex(mcrypt_generic($td, $string));
		
		// Terminate encryption handler
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);
		
		return $encryptedString;
	}
	
	public static function decrypt($string, $key = null, $salt = null, $iv = null){
		$config = ConfigManager::getConfig('Crypto','AES256')->AuxConfig;
		if($key === null){
			$key = $config->key;
		}
		if($salt === null){
			$salt = $config->salt;
		}
		if($iv === null){
			$iv = $config->iv;
		}
		
		$cacheKey = md5($key.$salt.$iv);
		$key = apcuGet($cacheKey);
		
		$td = mcrypt_module_open('rijndael-128', '', MCRYPT_MODE_CBC, '');
		
		$ks = mcrypt_enc_get_key_size($td);
		$bs = mcrypt_enc_get_block_size($td);
		
		$iv = substr(hash("sha256", $iv), 0, $bs);
		
		// Create key
		if(empty($key)){
			$key = Crypto::pbkdf2("sha512", $key, $salt, $config->pbkdfRounds, $ks);
			apcuStore($cacheKey, $key);
		}
		
		// Initialize encryption module for decryption
		mcrypt_generic_init($td, $key, $iv);
		
		$decryptedString = "";
		// Decrypt encrypted string
		try{
			if(ctype_xdigit($string)){
				$decryptedString = trim(mdecrypt_generic($td, pack("H*", $string)));
			}
		}
		catch(ErrorException $e){ }
		
		// Terminate decryption handle and close module
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);
		
		// Show string
		return $decryptedString;
	} 
}
