<?php
class LoaderGPG extends Loader{
	
	protected function includes(){
		// If you have PECL GnuPG package installed you don't need this include
		//stingleInclude ('Crypt/GPG.php');
		stingleInclude ('Managers/GPG.class.php');
	}
}
