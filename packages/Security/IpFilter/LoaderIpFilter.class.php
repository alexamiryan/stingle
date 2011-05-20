<?
class LoaderIpFilter extends Loader{
	protected function includes(){
		require_once ('IPBlockedException.class.php');
		require_once ('IpFilter.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('IpFilter');
	}
	
	public function hookCheckForBlockedHost(){
		if(!Cgi::getMode()){
			$ipFilter = new IpFilter();
			if($ipFilter->isBlocked()){
				throw new IPBlockedException("This host is blocked!");
			}
		}
	}
}
?>