<?
/**
 * Make link string from given formatted string.
 * If OUTPUT_LINK_STYLE is
 *
 * @param string $string
 * @param appand $_GET parametrs to the end
 * @param exclude param from $_GET. (should be coma separated)
 * @return string
 */
function smarty_modifier_glink($link, $with_gets = false, $exclude = ''){
	$exclude = explode(",", $exclude);
	
	if($with_gets){
		$link = RewriteURL::ensureSourceLastDelimiter($link)  . get_all_get_params($exclude);
	}
	
	$link = Reg::get('rewriteURL')->glink($link);

	return $link;
}
?>