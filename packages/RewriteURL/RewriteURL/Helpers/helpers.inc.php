<?
function getCurrentUrl($exclude = array()){
	$levels = ConfigManager::getConfig("RewriteURL", "RewriteURL")->AuxConfig->levels->toArray();

	$levelUrlParts = array();
	foreach($levels as $level){
		if(isset($_GET[$level]) and !empty($_GET[$level])){
			array_push($levelUrlParts, $_GET[$level]);
		}
		else{
			break;
		}
	}

	$currentUrl = implode("/", $levelUrlParts);

	$currentUrl = Reg::get('rewriteURL')->glink($currentUrl);

	$currentUrl .= getAllGetParams($exclude);

	return $currentUrl;
}

/**
 * Build the string of GET parameters
 *
 * @param array $exclude_array
 * @return string
 */
function getAllGetParams(array $excludeArray = array()){
	$levels = ConfigManager::getConfig("RewriteURL", "RewriteURL")->AuxConfig->levels->toArray();
	$returnString = '';

	foreach($excludeArray as &$exclude){
		$exclude = trim($exclude);
	}

	if(is_array($_GET) && (sizeof($_GET) > 0)){
		reset($_GET);
		while((list($key, $value) = each($_GET)) != false){
			if(in_array($key, $levels)){
				continue;
			}
			if(!in_array(trim($key), $excludeArray)){
				$returnString .= $key . ':' . rawurlencode($value) . '/';
			}
		}
	}

	return $returnString;
}
?>