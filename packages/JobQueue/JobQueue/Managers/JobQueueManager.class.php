<?php

/**
 * 
 * Manager vor Job queues
 * @author Stingle
 *
 */
class JobQueueManager extends DbAccessor{
	
	/**
	 * Active jobs table name
	 * @var String
	 */
	const TBL_JOB_QUEUE = "job_queue";
	
	/**
	 * Finished jobs archive table
	 * @var String
	 */
	const TBL_JOB_QUEUE_ARCHIVE = "job_queue_archive";
	
	/**
	 * jobs Classes array, key class name, value class file full path for include
	 * @var Associative array
	 */
	static $jobs = array();
	
	/**
	 * 
	 * Job statuses
	 */
	const JOB_STATUS_NEW = '0';
	const JOB_STATUS_IN_PROCESS = '1';
	const JOB_STATUS_FINISHED = '2';
	const JOB_STATUS_FAILD = '3';
	
	
	/**
	 * Construcor
	 * @param string $dbInstanceKey
	 */
	public  function __construct($dbInstanceKey = null){
		parent::__construct($dbInstanceKey);
		
	}
	
	/**
	 * Add new Job to queue
	 * @param String $jobName
	 * @param Array $jobProperties
	 * @throws JobQueueException
	 * @return last inserted job Id
	 */
	public function addJob($jobName, array $jobProperties = array()) {
		if(empty($jobName)){
			throw new JobQueueException("Job name have to been non empty string!");
		}
		$qb = new QueryBuilder();
		$qb->insert(Tbl::get('TBL_JOB_QUEUE'))
			->values( array(	"name" => $jobName, 
								"properties" => serialize($jobProperties),
								"status" => self::JOB_STATUS_NEW ));
			
		return $this->query->exec($qb->getSQL())->getLastInsertId();	
	}
	
	/**
	 * Update job status, and optional update log message
	 * @param JobQueueObj $job
	 * @throws JobQueueException
	 * @return true|false
	 */
	public function updateJobStatus(JobQueueObj $job) {
		if(empty($job)){
			throw new JobQueueException("job object is empty!");
		}
		if(empty($job->name)){
			throw new JobQueueException("Job name have to been non empty string!");
		}
		$qb = new QueryBuilder();
		$qb->update(TBL::get('TBL_JOB_QUEUE'))
			->set(new Field('status'), $job->status)
			->set(new Field('log_message'), $job->logMessage)
			->where($qb->expr()->equal(new Field('id'), $job->id));
			
		return $this->query->exec($qb->getSQL())->affected();
				
	}
	
	/**
	 * Check is job queue free.
	 * @return boolean
	 */
	public function isJobQueueFree(){
		$qb = new QueryBuilder();
		
		$qb->select($qb->expr()->count(new Field('*'), 'count'))
			->from(Tbl::get("TBL_JOB_QUEUE"))
			->where($qb->expr()->equal(new Field('status'), JobQueueManager::JOB_STATUS_IN_PROCESS));
		
		if( $this->query->exec($qb->getSQL())->fetchField('count') > 0 ) {
			return false;
		}
		return true;
		
	}
	
	/**
	 * Start job function check if job queu is free
	 * then get first new job from queue find and include class by name 
	 * and call current class run function
	 * @return boolean
	 */
	public function startJob() {
		if($this->isJobQueueFree()){
			$qb = new QueryBuilder();
			
			$qb->select("*")
				->from(Tbl::get("TBL_JOB_QUEUE"))
				->where($qb->expr()->equal(new Field('status'), JobQueueManager::JOB_STATUS_NEW))
				->limit(1);
			
			$this->query->exec($qb->getSQL());
			if($this->query->countRecords() > 0 ){
				$row = $this->query->fetchRecord();
				$jobQueueObj = $this->getJobQueueObjectFromData($row);
				$this->lockJob($jobQueueObj);
				if(!class_exists($jobQueueObj->name)){
					if(array_key_exists($jobQueueObj->name, self::$jobs) && file_exists(self::$jobs[$jobQueueObj->name])){
						include_once self::$jobs[$jobQueueObj->name];
					}
					else{
						$jobQueueObj->status = JobQueueManager::JOB_STATUS_FAILD;
						$jobQueueObj->logMessage = "There is no class with name '{$jobQueueObj->name}' or specified file with class name '{$jobQueueObj->name}' does not exist";
						
						$this->updateJobStatus($jobQueueObj);
						return false;
					}
				} 
				$job = new $jobQueueObj->name($jobQueueObj);
				$job->run();
				
				$jobQueueObj->status = JobQueueManager::JOB_STATUS_FINISHED;
				$this->moveJobToArchive($jobQueueObj);
				
				return true;
			}
		}
		return false;	
	}
	
	/**
	 * Move finished Job from active jobs table to archive table 
	 * Helper function
	 * @access private
	 * @param JobQueueObj $job
	 * 
	 */
	private function moveJobToArchive(JobQueueObj $job) {
		if(!is_numeric($job->id)){
			throw JobQueueException("Job id is not numeric!");
		}
		$qb = new QueryBuilder();
		$qb->insert(Tbl::get('TBL_JOB_QUEUE_ARCHIVE'))
			->values( array(	"name" => $job->name,
								"properties" => serialize($job->properties),
								"status" => $job->status,
								"start_date" => $job->startDate, 
								"log_message" => $job->logMessage ));
			
		$this->query->exec($qb->getSQL());
		
		$qb = new QueryBuilder();
		$qb->delete(Tbl::get('TBL_JOB_QUEUE'))
			->where($qb->expr()->equal(new Field('id'), $job->id));
		
		$this->query->exec($qb->getSQL());
	}
	
	
	/**
	 * Lock job queue for working on current job
	 * Helper funcion
	 * @access private
	 * @param JobQueueObj $job
	 * @return TRUE|FALSE
	 */
	private function lockJob(JobQueueObj $job) {
		if(!is_numeric($job->id)){
			throw JobQueueException("Job id is not numeric!");
		}
		$qb = new QueryBuilder();
		$qb->update(TBL::get('TBL_JOB_QUEUE'))
			->set(new Field('status'), self::JOB_STATUS_IN_PROCESS)
			->set(new Field('start_date'), new Func('NOW'))
			->where($qb->expr()->equal(new Field('id'), $job->id));
		
		return $this->query->exec($qb->getSQL())->affected();
		
	}
	
	/**
	 * Return Job queu object from db row
	 * Helper function
	 * @access private
	 * @param Array $data
	 * @return JobQueueObj
	 */
	private function getJobQueueObjectFromData($data) {
		$job = new JobQueueObj();
		$job->id = $data["id"];
		$job->name = $data["name"];
		$job->properties = unserialize($data["properties"]);
		$job->status = $data["status"];
		$job->startDate = $data["start_date"];
		$job->logMessage = $data["log_message"];
		
		return $job;
	}
}
