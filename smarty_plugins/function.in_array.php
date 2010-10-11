<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */

/**
 * Smarty {html_image} function plugin
 *
 * Type:     function<br>
 * Name:     in_array<br>
 * Date:     Aug 06, 2007<br>
 * Purpose:  Check in array<br>
 * Input:<br>
 *         - needle = file (and path) of image (required)
 *         - haystack = image height (optional, default actual height)
 *
 * @author   Alex dzya
 * @version  1.0
 * @param array
 * @param Smarty
 * @return bool
 */
function smarty_function_in_array($params, &$smarty)
{
	return in_array($params['needle'], $params['haystack']);
}

/* vim: set expandtab: */

?>
