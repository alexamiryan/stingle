<?php
/**
 * @param string $string
 * @return string
 */

function smarty_modifier_C($constantName){
	$value = "";
//	try{
		$value = Reg::get('lm')->getValueOf($constantName);
	/*}
	catch(Exception $e){
		$value = constant($constantName);
	}*/
	return $value;
}
