<?php

class JobQueueManager extends DbAccessor {

	const TBL_JOB_QUEUE = "job_queue";

	protected $config = null;
	protected $chunks = [];
	
	public function __construct(Config $config, $instanceName = null) {
		parent::__construct($instanceName);
		$this->config = $config;
	}


	public function addJob($jobName, array $params = []) {
		if (empty($jobName)) {
			throw new JobQueueException("Job name have to been non empty string!");
		}
		$qb = new QueryBuilder();
		$qb->insert(Tbl::get('TBL_JOB_QUEUE'))
			->values([
				"name" => $jobName,
				"params" => serialize($params)
			]);

		$this->query->exec($qb->getSQL());
	}
	
	public function addChunk(JobQueueChunk $chunk){
		$this->chunks[$chunk->getName()] = $chunk;
	}
	
	public function runQueue(){
		$queueStartTime = time();
		
		while($queueStartTime + $this->config->maximumExecutionTime > time()){
			echo "Run..\n";
			$result = $this->runSingleJob();
			if(!$result){
				sleep($this->config->intervalBetweenRuns);
			}
		}
	}
	
	protected function runSingleJob() {
		
		$this->query->lockEndpoint();
		$this->query->lockTables(Tbl::get("TBL_JOB_QUEUE"), 'w');
		
		$row = $this->getRowFromQueue();
		
		if ($row and !empty($row['id'])) {
			$this->deleteRowFromQueue($row['id']);
			$this->query->unlockTables();
			
			echo "Found job... - {$row['name']}\n";
			$this->runQueueItem($row);
		}
		else{
			$this->query->unlockTables();
			return false;
		}
		return true;
	}
	
	protected function runQueueItem(array $jobDbRow){
		if($this->isChunkRegistered($jobDbRow['name'])){
            try {
                $this->chunks[$jobDbRow['name']]->run(unserialize($jobDbRow['params']));
                return true;
            }
            catch (Exception $e){
                HookManager::callHookSimple("DBLog", ['JobQueue', "Error while running job {$jobDbRow['name']}\n\n" . format_exception($e)]);
            }
		}
		return false;
	}
	
	protected function getRowFromQueue(){
		$qb = new QueryBuilder();

		$qb->select("*")
			->from(Tbl::get("TBL_JOB_QUEUE"))
			->orderBy(new Field('id'), MySqlDatabase::ORDER_ASC)
			->limit(1);

		$this->query->exec($qb->getSQL());
		if ($this->query->countRecords() > 0) {
			return $this->query->fetchRecord();
		}
		return false;
	}
	
	protected function deleteRowFromQueue($id){
		$qb = new QueryBuilder();
		$qb->delete(Tbl::get('TBL_JOB_QUEUE'))
			->where($qb->expr()->equal(new Field('id'), $id));
		
		return $this->query->exec($qb->getSQL())->affected();
	}

	protected function isChunkRegistered($name){
		if(in_array($name, array_keys($this->chunks))){
			return true;
		}
		return false;
	}
}
