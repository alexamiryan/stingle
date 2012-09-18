<?
/**
 * Create random value on give criteria
 *
 * @param int $length
 * @param string $type (mixed, chars, digits)
 * @return string
 */
function generateRandomString($length, $type = null){
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
?>