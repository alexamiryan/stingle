<?php
/**
 * 
 *	Job queue object class 
 */
class JobQueueObj
{
	/**
	 * Job unique Id from DB
	 * @var Integer
	 */
	public $id;
	
	/**
	 * Job Name (Class name)
	 * @var String
	 */
	public $name;
	
	/**
	 * Array of properties for current job
	 * @var Array
	 */
	public $properties;
	
	/**
	 * Status of Job new, finished, error and in process
	 * 
	 * @var Integer 0|1|2|3
	 */
	public $status;
	
	/**
	 * Job Start date
	 * @var Date
	 */
	public $startDate;
	
	/**
	 * log message(optional)
	 * @var String
	 */
	public $logMessage;
}
