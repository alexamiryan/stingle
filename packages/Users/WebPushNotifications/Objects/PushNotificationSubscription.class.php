<?php

class PushNotificationSubscription{
	public $userId;
	public $endpoint;
	public $p256dh;
	public $auth;
	
	public function getAssocArray(){
		$arr = array();
		$arr['keys'] = array();
		
		$arr['endpoint'] = $this->endpoint;
		$arr['keys']['p256dh']  = $this->p256dh;
		$arr['keys']['auth']  = $this->auth;
		
		return $arr;
	}
}