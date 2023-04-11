<?php

class AES256
{
    public static function encrypt(string $string, string $key = null, string $difficulty = null) : string {
        $config = ConfigManager::getConfig('Crypto','AES256')->AuxConfig;
        if ($key === null) {
            if (!empty($config->key)) {
                $key = $config->key;
            } else {
                throw new Exception("Encryption key is missing");
            }
        }
        
        $salt = openssl_random_pseudo_bytes(16);
        
        if($difficulty === null){
            $difficulty = $config->argon2DefaultDifficulty;
        }
        
        $opsLimit = SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE;
        $memLimit = SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE;
        if($difficulty === 'moderate') {
            $opsLimit = SODIUM_CRYPTO_PWHASH_OPSLIMIT_MODERATE;
            $memLimit = SODIUM_CRYPTO_PWHASH_MEMLIMIT_MODERATE;
        }
        elseif ($difficulty === 'sensitive') {
            $opsLimit = SODIUM_CRYPTO_PWHASH_OPSLIMIT_SENSITIVE;
            $memLimit = SODIUM_CRYPTO_PWHASH_MEMLIMIT_SENSITIVE;
        }
        
        // Derive a key from the given key and salt using Argon2id
        $derived_key = sodium_crypto_pwhash(32, $key, $salt, $opsLimit, $memLimit, SODIUM_CRYPTO_PWHASH_ALG_ARGON2ID13);
        
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        
        // Encrypt the string using the derived key and IV
        $encrypted_string = openssl_encrypt($string, 'aes-256-cbc', $derived_key, OPENSSL_RAW_DATA, $iv);
        
        // Combine the salt, IV, and encrypted string
        $result = $salt . $iv . $encrypted_string;
        
        // Return the base64-encoded result
        return base64_url_encode($result);
    }
    
    public static function decrypt(string $encryptedString, string $key = null, string $difficulty = null) : string {
        $config = ConfigManager::getConfig('Crypto','AES256')->AuxConfig;
        if ($key === null) {
            if (!empty($config->key)) {
                $key = $config->key;
            } else {
                throw new Exception("Decryption key is missing");
            }
        }
        
        // Base64-decode the encrypted string
        $data = base64_url_decode($encryptedString);
        
        // Extract the salt, IV, and encrypted string
        $salt = substr($data, 0, 16);
        $iv = substr($data, 16, openssl_cipher_iv_length('aes-256-cbc'));
        $encryptedString = substr($data, 16 + openssl_cipher_iv_length('aes-256-cbc'));
    
        if($difficulty === null){
            $difficulty = $config->argon2DefaultDifficulty;
        }
    
        $opsLimit = SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE;
        $memLimit = SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE;
        if($difficulty === 'moderate') {
            $opsLimit = SODIUM_CRYPTO_PWHASH_OPSLIMIT_MODERATE;
            $memLimit = SODIUM_CRYPTO_PWHASH_MEMLIMIT_MODERATE;
        }
        elseif ($difficulty === 'sensitive') {
            $opsLimit = SODIUM_CRYPTO_PWHASH_OPSLIMIT_SENSITIVE;
            $memLimit = SODIUM_CRYPTO_PWHASH_MEMLIMIT_SENSITIVE;
        }
        
        // Derive a key from the given key and salt using Argon2id
        $derived_key = sodium_crypto_pwhash(32, $key, $salt, $opsLimit, $memLimit, SODIUM_CRYPTO_PWHASH_ALG_ARGON2ID13);
        
        // Decrypt the string using the derived key and IV
        return openssl_decrypt($encryptedString, 'aes-256-cbc', $derived_key, OPENSSL_RAW_DATA, $iv);
    }
}
