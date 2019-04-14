<?php
class LoaderJobQueue extends Loader{
	
	protected function includes(){
		stingleInclude ('Exceptions/JobQueueException.class.php');
		stingleInclude ('Objects/JobQueueChunk.class.php');
		stingleInclude ('Managers/JobQueueManager.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('JobQueueManager');
	}
	
	protected function loadJobQueueManager(){
		$this->register(new JobQueueManager($this->config->AuxConfig));
	}
	
	
}
