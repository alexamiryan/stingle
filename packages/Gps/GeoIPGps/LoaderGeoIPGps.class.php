<?php
class LoaderGeoIPGps extends Loader{
	
	protected function includes(){
		require_once ('GeoIPGps.class.php');
	}
	
	protected function loadGeoIPGps(){
		$geoIPConfig = ConfigManager::getConfig("GeoIP", "GeoIP");
		$gpsConfig = ConfigManager::getConfig("Gps", "Gps");
		
		$geoIpGps = new GeoIPGps(Reg::get($geoIPConfig->Objects->GeoIP), Reg::get($gpsConfig->Objects->Gps));
		$this->register($geoIpGps);
	}
}
