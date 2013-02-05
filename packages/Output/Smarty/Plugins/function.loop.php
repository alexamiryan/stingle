<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */

/**
 * Smarty {loop} function plugin
 *
 * Type:     function<br>
 * Name:     sprintf<br>
 * Purpose:  same as in php
 * @author   Alex Amiryan
 * Input:
 *         - value = value to return
 *         - count = number of steps to return
 *         - name = name of the loop
 *         - dofirst = return or not on first step
 *         - do_x_times = return only x times, not more
 * @return string
 */

function smarty_function_loop($params, &$smarty){
	static $loop_vars;
	
	$count = (isset($params['count'])) ? intval($params['count']) : 2;
	$name = (empty($params['name'])) ? 'default' : $params['name'];
	$dofirst = (isset($params['dofirst'])) ? (bool)$params['dofirst'] : false;
	$do_x_times = (isset($params['do_x_times'])) ? (bool)$params['do_x_times'] : false;
	
	if(empty($params['value'])){
		$smarty->trigger_error("loop: missing 'value' parameter");
	}
	
	if(!isset($loop_vars[$name]) || $loop_vars[$name]['value'] != $params['value']){
		$loop_vars[$name] = array();
		$loop_vars[$name]['index'] = 0;
		$loop_vars[$name]['value'] = $params['value'];
		$loop_vars[$name]['count'] = $count;
		$loop_vars[$name]['dofirst'] = $dofirst;
		$loop_vars[$name]['do_x_times'] = $do_x_times;
	}
	$loop_vars[$name]['index']++;
	if($loop_vars[$name]['index'] == 1 && $loop_vars[$name]['dofirst']){
		$loop_vars[$name]['index']--;
		$loop_vars[$name]['dofirst'] = false;
		return $loop_vars[$name]['value'];
	}
	if($loop_vars[$name]['index'] != 1 && $loop_vars[$name]['index'] % $loop_vars[$name]['count'] == 0){
		if($loop_vars[$name]['do_x_times']){
			if($loop_vars[$name]['index'] / $loop_vars[$name]['count'] < $loop_vars[$name]['do_x_times']+1){
				return $loop_vars[$name]['value'];
			}
		}
		else{
			return $loop_vars[$name]['value'];
		}
	}
	return false;
}
