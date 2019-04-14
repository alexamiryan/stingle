<?php

class WebPushJobQueueChunk extends JobQueueChunk{
	
	public static $name = 'webPush';
	
	public function run($params) {
		Reg::get('pushNotif')->sendNotificationToUser($params['userId'], $params['title'], $params['body'], $params['url'], $params['icon'], $params['tag']);
	}

}