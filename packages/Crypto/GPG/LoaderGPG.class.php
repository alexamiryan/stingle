<?php
class LoaderGPG extends Loader{
	
	protected function includes(){
		// If you have PECL GnuPG package installed you don't need this include
		require_once ('Crypt/GPG.php');
		require_once ('Managers/GPG.class.php');
	}
}
