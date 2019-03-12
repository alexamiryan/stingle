<?php

const RANDOM_STRING_LOWERCASE = 0;
const RANDOM_STRING_UPPERCASE = 1;
const RANDOM_STRING_DIGITS = 2;
const RANDOM_STRING_SYMBOLS = 3;
/**
 * Generate secure random string of given length
 * 
 * @param int $length
 * @return String
 */
function generateRandomString($tokenLength = 12, $alphabetMode = array(RANDOM_STRING_LOWERCASE, RANDOM_STRING_UPPERCASE, RANDOM_STRING_DIGITS)){
    $token = "";
	
    //Combination of character, number and special character...
    $combinationString = "";
	
	if(in_array(RANDOM_STRING_LOWERCASE, $alphabetMode)){
		$combinationString .= "abcdefghijklmnopqrstuvwxyz";
	}
	if(in_array(RANDOM_STRING_UPPERCASE, $alphabetMode)){
		$combinationString .= "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
	}
	if(in_array(RANDOM_STRING_DIGITS, $alphabetMode)){
		$combinationString .= "0123456789";
	}
	if(in_array(RANDOM_STRING_SYMBOLS, $alphabetMode)){
		$combinationString .= "!@#$%^&*()[]{}";
	}
	
    for($i=0;$i<$tokenLength;$i++){
        $token .= $combinationString[uniqueSecureHelper(0,strlen($combinationString))];
    }
    return $token;
}

/*
    This helper function will return unique and secure string...
*/
function uniqueSecureHelper($minVal, $maxVal) {
	$range = $maxVal - $minVal;
	if ($range < 0){
		return $minVal; // not so random...
	}

	$log = log($range, 2);
	$bytes = (int) ($log / 8) + 1; // length in bytes
	$bits = (int) $log + 1; // length in bits
	$filter = (int) (1 << $bits) - 1; // set all lower bits to 1

	$rnd = 0;
	
	do {
		$rndTmp = null;
		if (function_exists('random_bytes')) {
			$rndTmp = bin2hex(random_bytes($bytes));
		}
		elseif (function_exists('openssl_random_pseudo_bytes')) {
			$rndTmp = bin2hex(openssl_random_pseudo_bytes($bytes));
		}
		$rnd = hexdec($rndTmp)  & $filter;
	} while ($rnd >= $range);

	return $minVal + $rnd;
}

/**
 * Create random value on give criteria
 *
 * @param int $length
 * @param string $type (mixed, chars, digits)
 * @return string
 */
function generateRandomStringOld($length, $type = null){
	if(!Reg::get('packageMgr')->isPluginLoaded('Crypto', 'Crypto')){
		throw new RuntimeException("Crypto plugin is not loaded!");
	}

	if($length === null){
		$length = 12;
	}
	if($type === null){
		$type = 'mixed';
	}

	if(($type != 'mixed') && ($type != 'chars') && ($type != 'digits')) return false;

	$rand_value = '';
	while(strlen($rand_value) < $length){
		if($type == 'digits'){
			$char = Crypto::s_rand(0, 9);
		}
		else{
			$char = chr(Crypto::s_rand(0, 255));
		}
		if($type == 'mixed'){
			if(preg_match('/^[a-z0-9]$/i', $char)) $rand_value .= $char;
		}
		elseif($type == 'chars'){
			if(preg_match('/^[a-z]$/i', $char)) $rand_value .= $char;
		}
		elseif($type == 'digits'){
			if(preg_match('^[0-9]$', $char)) $rand_value .= $char;
		}
	}

	return $rand_value;
}
