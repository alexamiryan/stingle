<?
/**
 * Get relative file path from site absolute link
 * For example output from img modifier 
 * 
 * @param string $filePath
 * @return string
 */

function smarty_modifier_rel($filePath){
	return preg_replace("/^\//", "", $filePath);
}
?>