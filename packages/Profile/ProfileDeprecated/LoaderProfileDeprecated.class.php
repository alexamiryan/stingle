<?php
class LoaderProfileDeprecated extends Loader{
	protected function includes(){
		stingleInclude ('Managers/ProfileDeprecated.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('ProfileDeprecated');
	}
}
