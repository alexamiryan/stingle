<?php
function format_exception(Exception $e, $insert_pre = false){
	$message = get_class($e) . "\n" .
	"Message:\n" . $e->getMessage() . "\n\n" .
	"File: " . $e->getFile() . " on line " . $e->getLine() . "\n\n" .
	"Trace:\n" . $e->getTraceAsString() . "\n\n" .
	"Get:\n". print_r($_GET,true) . "\n\n" .
	"Post:\n". print_r($_POST,true) . "\n\n" .
	"Cookie:\n". print_r($_COOKIE,true) . "\n\n" .
	"Session:\n". print_r($_SESSION,true) . "\n\n" .
	"Server:\n". print_r($_SERVER,true) . "\n\n" .
	"Code: " . $e->getCode();
	if($insert_pre){
		$message = "<pre>$message</pre>";
	}

	return $message;
}

function ensurePathLastSlash(&$path){
	if(substr($path, strlen($path)-1) != '/'){
		$path .= '/';
	}
}

/**
 * Function return size with it's units.<br>
 * e.g. 2 M, 35 Kb, 346 b
 *
 * @param double $size
 * @return string
 */
function determine_size_units($size = 0){
	if($size < 1024){
		$size .= ' b';
	}
	if($size >= 1024 and $size < 1048576){
		$size = round($size /= 1024);
		$size .= ' Kb';
	}
	if($size >= 1048576){
		$size = round($size /= 1048576);
		$size .= ' M';
	}
	return $size;
}

function get_age ($birthday){
	$explodedArray = explode("-",$birthday);
	if(count($explodedArray) != 3){
		return false;
	}
	
	list($Year, $Month, $Day) = $explodedArray;

	$YearDiff = date("Y") - $Year;
	if(date("m") < $Month || (date("m") == $Month && date("d") < $Day)){
		$YearDiff--;
	}
	return $YearDiff;
}

/**
 * Analog of empty() but taking function return value
 *
 * @param mixed $var
 * @return boolean
 */
function fempty($var){
	if($var === '' || $var === 0 || $var == null || $var === false || $var === "0" || $var === array()){
		return true;
	}
	return false;
}

/**
 * Redirect to other page
 *
 * @param string $url
 */
function redirect($url){
	header('Location: ' . $url);
	exit();
}

/**
 * Checks validity of given email addess
 * Also checks domain name for validity via DNS
 *
 * @param string $address
 * @return boolean
 */
function valid_email($email){
	$isValid = false;
	if(filter_var($email, FILTER_VALIDATE_EMAIL)){
		$host = substr($email, strpos($email, '@') + 1);

		if(function_exists('getmxrr') and getmxrr($host, $mxhosts) != false){
			$isValid = true;
		}
		elseif(gethostbyname($host) != $host){
			$isValid = true;
		}
	}
	return $isValid;
}

/**
 * Adds leading zero to number
 *
 * @param double $digit
 * @return string
 */

function add_leading_zero($digit){
	if(abs($digit) < 10){
		if($digit < 0){
			return '-0' . abs($digit);
		}
		else{
			return '0' . $digit;
		}
	}
	else{
		return $digit;
	}
}

/**
 * Cuts given string with given number of characters preserving words
 *
 * @param string $string
 * @param int $char_limit
 * @param string $trailing_chars
 * @return string
 */
function smart_cut($string, $char_limit, $trailing_chars = '...'){
	if(empty($string) or $char_limit < 1){
		return '';
	}
	$arr = explode(' ', $string);
	$ret_str = '';
	$lend = 0;
	foreach($arr as $word){
		if(mb_strlen($ret_str, "UTF-8") + mb_strlen($word, "UTF-8") <= $char_limit){
			$ret_str .= $word . ' ';
		}
		else{
			$ret_str = mb_substr($ret_str, 0, mb_strlen($ret_str) - 1, "UTF-8") . $trailing_chars;
			$lend = 1;
			break;
		}
	}
	if($lend == 0){
		$ret_str = mb_substr($ret_str, 0, mb_strlen($ret_str) - 1, "UTF-8");
	}
	return $ret_str;
}

/**
 * Returns number of days for given month and year
 *
 * @param int $month
 * @param int $year
 * @return int Number of days
 */
function getMonthDays($month, $year){
	//If claendar extantion is installed.
	if(is_callable("cal_days_in_month")){
		return cal_days_in_month(CAL_GREGORIAN, $month, $year);
	}
	else{ //Get it directly
		return date("d", mktime(0, 0, 0, $month + 1, 0, $year));
	}
}

/**
 * Convert seconds to readable time format
 * @param unknown_type $seconds
 * @param unknown_type $delimiter
 */
function secondsToTime($seconds, $delimiter = ":"){
	$seconds = abs($seconds);
	$hours = floor($seconds/3600);
	$minutes = floor(($seconds - ($hours*3600))/60);
	$sec = $seconds - $hours * 3600 - $minutes * 60;
	if($hours < 10) $hours = "0".$hours;
	if($minutes < 10) $minutes = "0".$minutes;
	if($sec < 10) $sec = "0".$sec;
	return $hours. $delimiter .$minutes. $delimiter .$sec;
}

function urlFriendlyText($string){
	return preg_replace('/\s+/i', "-", $string);
}

function removeAccents($msg){
	$a = array(
	'/[ÂÀÁÄÃ]/u'=>'A',
	'/[âãàáä]/u'=>'a',
	'/[ÊÈÉË]/u'=>'E',
	'/[êèéë]/u'=>'e',
	'/[ÎÍÌÏ]/u'=>'I',
	'/[îíìï]/u'=>'i',
	'/[ÔÕÒÓÖ]/u'=>'O',
	'/[ôõòóö]/u'=>'o',
	'/[ÛÙÚÜ]/u'=>'U',
	'/[ûúùü]/u'=>'u',
	'/ç/u'=>'c',
	'/Ç/u'=> 'C');
	return preg_replace(array_keys($a), array_values($a), $msg);
}

/**
 * Get value of array by key
 *
 * @param array $array
 * @param string $key
 * @return string|array
 */
function getValue($array, $key){
	if(isset($array[$key])){
    	return $array[$key];
	}
	return false;
}

/**
 * Is site now in production mode
 * 
 * @return boolean
 */
function isInProductionMode(){
	if (SiteMode::get() == SiteMode::MODE_PRODUCTION){
		return true;
	}
	return false;
}

/**
 * Is site now in development mode
 * 
 * @return boolean
 */
function isInDevelopmentMode(){
	if (SiteMode::get() == SiteMode::MODE_DEVELOPMENT){
		return true;
	}
	return false;
}

/**
 * Function does parsing constants and replaces all regex with given values
 * 
 * @param string $text
 * @param array $params
 * @return string|bollean|false
 */
function parse($text, $params = null){
	if(!isset($text)){
		return false;
	}
	else {
		if(empty($params)){
			return $text;
		}
		else {
			$patterns = array();
			$replacements = array();
			foreach($params as $pattern=>$replacement){
				$patterns[] = '/\['.$pattern.'\]/';
				$replacements[] = $replacement;
			}
			return preg_replace($patterns, $replacements, $text);
		}
	}
	return false;
}
/**
 * 
 * @param Array $array
 */
function addSlashesToArray(&$array){
	foreach($array as $key => $val){
		if(is_array($val)){
			addslashesToArray($val);
		}
		else{
			$array[$key] = addslashes($val);
		}
	}
}
/**
 * 
 * @param Array $array
 */
function stripSlashesFromArray(&$array){
	if(!is_array($array)){
		stripslashes($array);
	}
	else{
		foreach($array as $key => $val){
			if(is_array($val)){
				stripSlashesFromArray($val);
			}
			else{
				$array[$key] = stripslashes($val);
			}
		}
	}
}

/**
 * Checks is array assouciative
 * @param array $arr
 */
function isAssoc(array $array){
	return array_keys($array) !== range(0, count($array) - 1);
}

/**
 * Base64 encode data that can be used in GET parameter
 * 
 * @param mixed $input
 * @return string
 */
function base64_url_encode($input) {
	return strtr(base64_encode($input), '+/=', '-_,');
}

/**
 * Base64 decode data that can be used in GET parameter
 * 
 * @param string $input
 * @return mixed
 */
function base64_url_decode($input) {
	return base64_decode(strtr($input, '-_,', '+/='));
}

function stingleInclude($file, $precompileCode = null, $postcompileCode = null, $isPathAbsolute = false){
	$bt = debug_backtrace();
	$old = getcwd();
	chdir(dirname($bt[0]['file']));
	require_once ($file);
	if(ConfigManager::getGlobalConfig()->Stingle->BootCompiler === true){
		if($isPathAbsolute){
			array_push($GLOBALS['includedClasses'], array('file' => $file, 'precompileCode'=>$precompileCode, 'postcompileCode' => $postcompileCode));
		}
		else{
			array_push($GLOBALS['includedClasses'], array('file' => dirname($bt[0]['file']) . '/' . $file, 'precompileCode'=>$precompileCode, 'postcompileCode' => $postcompileCode));
		}
	}
	chdir($old);
}

