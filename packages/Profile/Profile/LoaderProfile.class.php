<?php
class LoaderProfile extends Loader{
	protected function includes(){
		stingleInclude ('Managers/ProfileManager.class.php');
		stingleInclude ('Filters/ProfileKeyFilter.class.php');
		stingleInclude ('Filters/ProfileValueFilter.class.php');
		stingleInclude ('Filters/UserProfileFilter.class.php');
		stingleInclude ('Objects/ProfileKey.class.php');
		stingleInclude ('Objects/ProfileKeyValuePair.class.php');
		stingleInclude ('Objects/ProfileValue.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('ProfileManager');
	}
}
