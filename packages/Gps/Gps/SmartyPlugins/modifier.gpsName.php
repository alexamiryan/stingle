<?php
/**
 * @param string $gpsId
 * @return string
 */

function smarty_modifier_gpsName($gpsId){
	if(empty($gpsId) or !is_numeric($gpsId)){
		return null;
	}
	
	
	
	return Reg::get('gps')->getNodeName($gpsId);
}
