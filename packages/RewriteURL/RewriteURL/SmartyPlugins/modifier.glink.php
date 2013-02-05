<?php
/**
 * Make link string from given formatted string.
 * If OUTPUT_LINK_STYLE is
 *
 * @param string $string
 * @param boolean $with_gets $_GET parametrs to the end
 * @param string $exclude param from $_GET. (should be coma separated)
 * @return string
 */
function smarty_modifier_glink($link, $with_gets = false, $exclude = ''){
	$exclude = explode(",", $exclude);
	
	if($with_gets){
		RewriteURL::ensureLastSlash($link);
		$link .= getAllGetParams($exclude);
	}
	
	$link = Reg::get('rewriteURL')->glink($link);

	return $link;
}
