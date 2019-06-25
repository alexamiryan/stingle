<?php
class LoaderMongo extends Loader{
	protected function includes(){
		stingleInclude ('Exceptions/MongoDBException.class.php');
		stingleInclude ('Managers/MongoDB.class.php');
	}
	
	protected function loadMongo(){
		$this->register(new MongoDB($this->config->AuxConfig));
	}
	
}
