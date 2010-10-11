<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */

function smarty_modifier_htmlspecialchars($string){
	return htmlspecialchars($string);
}

?>
