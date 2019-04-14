<?php

class MailJobQueueChunk extends JobQueueChunk{
	
	public static $name = 'mail';
	
	public function run($params) {
		Reg::get('mail')->send($params['mail'], $params['transportConfigName']);
	}

}