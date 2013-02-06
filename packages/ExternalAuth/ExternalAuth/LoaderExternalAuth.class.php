<?php
class LoaderExternalAuth extends Loader{
	
	protected function includes(){
		require_once ('Interfaces/ExternalAuth.interface.php');
		require_once ('Objects/ExternalUser.class.php');
		require_once ('Managers/ExternalUserManager.class.php');
	}
}
