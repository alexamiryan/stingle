<?
/**
 * @param string $string
 * @return string
 */

function smarty_modifier_C($constantName){
	return constant($constantName);
}
?>