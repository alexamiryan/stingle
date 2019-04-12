<?php

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

class WebPushNotificationsManager extends DbAccessor{
	
	const TBL_PUSH_NOTIFICATIONS = "push_notifications";
	protected $config = null;

	public function __construct($config, $instanceName = null) {
		parent::__construct($instanceName);
		$this->config = $config;
	}


	public function getUserSubscriptions($userId){
		if(empty($userId)){
			throw new InvalidArgumentException("Invalid userId");
		}
		
		$qb = new QueryBuilder();
		$qb->select(new Field('*'))
			->from(Tbl::get("TBL_PUSH_NOTIFICATIONS"))
			->where($qb->expr()->equal(new Field('user_id'), $userId));
		
		$this->query->exec($qb->getSQL());
		
		$subscr = array();
		if ($this->query->countRecords()) {
			foreach ($this->query->fetchRecords() as $row) {
				array_push($subscr, $this->getObjectFromArray($row));
			}
		}
		return $subscr;
	}
	
	public function sendNotification(PushNotificationSubscription $subscr, $title, $body, $url, $icon, $tag = ""){
		$a = $subscr->getAssocArray();
		$subscription = Subscription::create($subscr->getAssocArray());

		$auth = array(
			'VAPID' => array(
				'subject' => ConfigManager::getGlobalConfig()->site->siteName,
				'publicKey' => $this->config->publicKey,
				'privateKey' => $this->config->privateKey
			),
		);
		
		$webPush = new WebPush($auth);

		$data = array(
			'title' => $title,
			'body' => $body,
			'url' => $url,
			'icon' => $icon,
			'tag' => $tag
		);
		
		$res = $webPush->sendNotification($subscription, json_encode($data));

		$result = array(
			'success' => 0,
			'failed' => 0
		);
		
		if($res){
			// handle eventual errors here, and remove the subscription from your server if it is expired
			foreach ($webPush->flush() as $report) {
				//$endpoint = $report->getRequest()->getUri()->__toString();

				if ($report->isSuccess()) {
					$result['success']++;
				} else {
					try{
						$this->deleteSubscription($subscr->endpoint);
					}
					catch (Exception $e){}
					$result['failed']++;
				}
			}
		}
		return $result;
	}
	
	public function sendNotificationToUser($userId, $title, $body, $url, $icon, $tag = ""){
		$results = array();
		$subscrs = $this->getUserSubscriptions($userId);
		foreach($subscrs as $sub){
			$res = $this->sendNotification($sub, $title, $body, $url, $icon, $tag);
			array_push($results, $res);
		}
		return $results;
	}
	
	public function addOrUpdateSubscription($userId, $endpoint, $p256dh, $auth){
		if(empty($userId) or empty($endpoint) or empty($p256dh) or empty($auth)){
			throw new InvalidArgumentException("Invalid arguments");
		}
		
		$endpointHash = md5($endpoint);
		
		$qb = new QueryBuilder();
		
		$qb->insert(Tbl::get('TBL_PUSH_NOTIFICATIONS'))
			->values(array(
				'endpoint_hash' => $endpointHash,
				'user_id' => $userId,
				'endpoint' => $endpoint,
				'p256dh' => $p256dh,
				'auth' => $auth
		))
		->onDuplicateKeyUpdate()
		->set(new Field('user_id'), $userId)
		->set(new Field('p256dh'), $p256dh)
		->set(new Field('auth'), $auth);
			
		$this->query->exec($qb->getSQL());
	
		return $this->query->affected();
	}
	
	public function deleteSubscription($endpoint){
		if(empty($endpoint)){
			throw new InvalidArgumentException("Endpoint is empty");
		}
		
		$endpointHash = md5($endpoint);
		
		$qb = new QueryBuilder();
		$qb->delete(Tbl::get("TBL_PUSH_NOTIFICATIONS"))
			->where($qb->expr()->equal(new Field('endpoint_hash'), $endpointHash));
			
		return $this->query->exec($qb->getSQL())->affected();
	}
	
	protected function getObjectFromArray($row){
		$subscr = new PushNotificationSubscription();
		
		$subscr->userId = $row['user_id'];
		$subscr->endpoint = $row['endpoint'];
		$subscr->p256dh = $row['p256dh'];
		$subscr->auth = $row['auth'];
		
		return $subscr;
	}
}
