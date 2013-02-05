<?php
class LoaderExternalAuth extends Loader{
	
	protected function includes(){
		require_once ('ExternalAuth.interface.php');
		require_once ('ExternalUser.class.php');
		require_once ('ExternalUserManager.class.php');
	}
}
