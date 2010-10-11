<?
class LoaderGeoIP extends Loader{
	
	protected function includes(){
		require_once ('GeoIP.class.php');
	}
	
	protected function loadGeoIP(){
		Reg::register($this->config->Objects->GeoIP, geoip_open(STINGLE_PATH . "packages/GeoIP/GeoIP/GeoIP.dat",GEOIP_STANDARD));
	}
}
?>