<?php
class LoaderAES256 extends Loader{
	
	protected function includes(){
		require_once ('Managers/AES256.class.php');
	}
}
