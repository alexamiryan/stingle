<?php
class LoaderExternalAuth extends Loader{
	
	protected function includes(){
		stingleInclude ('Interfaces/ExternalAuth.interface.php');
		stingleInclude ('Objects/ExternalUser.class.php');
		stingleInclude ('Managers/ExternalUserManager.class.php');
	}
}
