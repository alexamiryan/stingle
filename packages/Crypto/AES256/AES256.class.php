<?
class AES256
{
	public static function encrypt($string, $key){
		$td = mcrypt_module_open('rijndael-256', '', MCRYPT_MODE_CBC, '');

		// Create the IV and determine the keysize length
		$iv = ConfigManager::getConfig('Crypto','AES256')->AuxConfig->iv;

		$ks = mcrypt_enc_get_key_size($td);
		
		// Create key
		$key = substr(md5($key), 0, $ks);

		// Intialize encryption
		mcrypt_generic_init($td, $key, $iv);
		
		// Encrypt data
		$encryptedString = base64_encode(mcrypt_generic($td, $string));
		
		// Terminate encryption handler
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);
		
		return $encryptedString;
	}
	
	public static function decrypt($string, $key){
		$td = mcrypt_module_open('rijndael-256', '', MCRYPT_MODE_CBC, '');
		
		// Create the IV and determine the keysize length
		$iv = ConfigManager::getConfig('Crypto','AES256')->AuxConfig->iv;

		$ks = mcrypt_enc_get_key_size($td);
		
		// Create key
		$key = substr(md5($key), 0, $ks);
		
		// Initialize encryption module for decryption
		mcrypt_generic_init($td, $key, $iv);
		
		// Decrypt encrypted string
		$decryptedString = trim(mdecrypt_generic($td, base64_decode($string)));
		
		// Terminate decryption handle and close module
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);
		
		// Show string
		return $decryptedString;
	} 
}
?>