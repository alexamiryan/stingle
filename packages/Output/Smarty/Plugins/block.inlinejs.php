<?php

/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     block.inlinejs.php
 * Type:     block
 * Name:     inlinejs
 * Purpose:  Saves inline JS to display it later
 * -------------------------------------------------------------
 */

function smarty_block_inlinejs($params, $content, Smarty_Internal_Template $template, &$repeat) {
	// only output on the closing tag
	if (!$repeat) {
		if (!empty($content)) {
			$smarty = Reg::get(ConfigManager::getConfig("Output", "Smarty")->Objects->Smarty);
			$smarty->addInlineJs($content);
		}
	}
}
