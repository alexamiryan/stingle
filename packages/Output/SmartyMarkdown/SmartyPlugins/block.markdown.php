<?php

/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     block.markdown.php
 * Type:     block
 * Name:     markdown
 * Purpose:  Converts markdown to html
 * -------------------------------------------------------------
 */

function smarty_block_markdown($params, $content, Smarty_Internal_Template $template, &$repeat) {
	// only output on the closing tag
	if (!$repeat) {
		if (!empty($content)) {
            return (new Parsedown())->text($content);
		}
	}
}
