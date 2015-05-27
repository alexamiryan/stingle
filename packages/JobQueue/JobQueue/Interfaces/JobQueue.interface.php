<?php

/**
 * 
 * Job Queue Interface for implement run function
 */
interface JobQueue
{
	/**
	 * Function for running individual job for current child class.
	 */
	public function run();
}
