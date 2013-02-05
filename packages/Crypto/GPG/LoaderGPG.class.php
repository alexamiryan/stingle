<?php
class LoaderGPG extends Loader{
	
	protected function includes(){
		require_once ('Crypt/GPG.php');
		require_once ('GPG.class.php');
	}
}
