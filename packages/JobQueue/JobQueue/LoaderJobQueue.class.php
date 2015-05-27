<?php
class LoaderJobQueue extends Loader{
	
	protected function includes(){
		stingleInclude ('Exceptions/JobQueueException.class.php');
		stingleInclude ('Objects/JobQueueObj.class.php');
		stingleInclude ('Interfaces/JobQueue.interface.php');
		stingleInclude ('Managers/JobQueueManager.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('JobQueueManager');
	}
	
	protected function loadJobQueueManager(){
		$this->register(new JobQueueManager());
	}
	
	public function hookCollectJobQueuesDir(Array $params){
		extract($params);
	
		if(	is_dir(STINGLE_PATH . "packages/{$packageName}/") and is_dir(STINGLE_PATH . "packages/{$packageName}/{$pluginName}")){
			if(is_dir(STINGLE_PATH . "packages/{$packageName}/{$pluginName}/JobQueues")){
				$this->addClassesToJobsByDir(STINGLE_PATH . "packages/{$packageName}/{$pluginName}/JobQueues");
			}
		}
	
		if(is_dir(SITE_PACKAGES_PATH . "{$packageName}/") and is_dir(SITE_PACKAGES_PATH . "{$packageName}/{$pluginName}")){
			if(is_dir(SITE_PACKAGES_PATH . "{$packageName}/{$pluginName}/JobQueues")){
				$this->addClassesToJobsByDir(SITE_PACKAGES_PATH . "{$packageName}/{$pluginName}/JobQueues");
			}
		}
	}
	
	private function addClassesToJobsByDir($directory){
		$allFiles = scandir($directory);
		$files = array_diff($allFiles, array('.', '..'));
		if(!empty($files)){
			foreach($files as $file){
				if (strcmp(substr($file, -10), ".class.php") == 0){
					if(!array_key_exists($file, JobQueueManager::$jobs)) {
						JobQueueManager::$jobs[substr($file, 0, -10)] = $directory . "/" . $file;
					}
					else {
						throw new JobQueueException("Current JobQueue already exist - $file");
					}
				}
			}
			
		}
	}
}
