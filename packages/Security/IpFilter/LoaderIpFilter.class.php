<?php
class LoaderIpFilter extends Loader{
	protected function includes(){
		stingleInclude ('Exceptions/IPBlockedException.class.php');
		stingleInclude ('Managers/IpFilter.class.php');
		stingleInclude ('Managers/IpFilterManager.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('IpFilter');
	}
	
	protected function loadIpFilterManager(){
		$this->register(new IpFilterManager());
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
