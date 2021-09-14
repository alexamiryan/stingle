<?php
class LoaderAES256 extends Loader{
	
	protected function includes(){
		stingleInclude ('Managers/AES256.class.php');
		stingleInclude ('Managers/AES256File.class.php');
	}
}
