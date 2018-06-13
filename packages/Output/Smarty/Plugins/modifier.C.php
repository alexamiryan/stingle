<?php
/**
 * @param string $string
 * @return string
 */

function smarty_modifier_C($string){
	return Reg::get('lm')->getValueOf($string);
}
