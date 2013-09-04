<?php
class LoaderComet extends Loader{
	protected function includes(){
		stingleInclude ('Managers/Comet.class.php');
		stingleInclude ('Objects/CometChunk.class.php');
	}
}
