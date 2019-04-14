<?php
class LoaderWebPushNotifications extends Loader{
	protected function includes(){
		stingleInclude ('Objects/PushNotificationSubscription.class.php');
		stingleInclude ('Objects/WebPushJobQueueChunk.class.php');
		stingleInclude ('Managers/WebPushNotificationsManager.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('WebPushNotificationsManager');
	}
	
	protected function loadWebPushNotificationsManager(){
		$this->register(new WebPushNotificationsManager($this->config->AuxConfig));
	}
	
}
