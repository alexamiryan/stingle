<?php
class LoaderSES extends Loader{
	
	protected function includes(){
		stingleInclude ('Managers/SESTransport.class.php');
		stingleInclude ('Managers/SESBounceHandler.class.php');
	}
	
}
