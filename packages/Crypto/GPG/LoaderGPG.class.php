<?php
class LoaderGPG extends Loader{
	
	protected function includes(){
		require_once ('Crypt/GPG.php');
		require_once ('Managers/GPG.class.php');
	}
}
