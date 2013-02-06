<?php
/**
 * Get Country code from IP
 *
 * @param string $ip
 * @return string
 */
function smarty_modifier_ipToCountryCode($ip){
	return Reg::get('geoIp')->getCountryCode($ip, -1);
}
