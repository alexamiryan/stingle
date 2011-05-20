<?
class MessageManagement extends Filterable
{
	
	const TBL_MESSAGES = "wmsg_messages";
	const TBL_EXTRA = "wmsg_extra";
	
	
	/**
	 * Filterable fields
	 */
	const FILTER_ID_FIELD = "id";
	const FILTER_DATE_FIELD = "date";
	const FILTER_SENDER_FIELD = "sender";
	const FILTER_RECEIVER_FIELD = "receiver";
	const FILTER_READ_FIELD = "read";
	const FILTER_DELETED_FIELD = "deleted";
	const FILTER_TRASHED_FIELD = "trashed";

	/**
	 * Boxes
	 */
	const BOX_INBOX = "inbox";
	const BOX_SENT = "sent";
	const BOX_TRASH = "trash";
	const BOX_DELETED = "deleted";

	/**
	 * Statuses
	 */
	const STATUS_READ_READ = 1;
	const STATUS_READ_UNREAD = 0;
	const STATUS_TRASHED_TRASHED = 1;
	const STATUS_TRASHED_UNTRASHED = 0;
	const STATUS_DELETED_DELETED = 1;
	const STATUS_DELETED_UNDELETED = 0;

	/**
	 * Class constructor
	 *
	 * @return Message Object
	 * @package PHP Web Messaging System Managment Class
	 * @version 1.2
	 */
	public function __construct($dbInstanceKey = null) {
		parent::__construct($dbInstanceKey);
	}

	protected function getFilterableFieldAlias($field){
		switch($field){
			case static::FILTER_ID_FIELD :
			case static::FILTER_DATE_FIELD :
				return "main";
			case static::FILTER_SENDER_FIELD :
			case static::FILTER_RECEIVER_FIELD :
			case static::FILTER_READ_FIELD :
			case static::FILTER_DELETED_FIELD :
			case static::FILTER_TRASHED_FIELD :
				return "extra";
		}

		throw new Exception("Specified field does not exist or not filterable");
	}

	/**
	 * Creates a message in main_table
	 *
	 * @param String $subject
	 * @param String $message
	 * @return Integer $message_id or Boolean FALSE, if something is wrong
	 */
	private function createMessage($subject, $message){
		if($this->query->exec("insert into `" . Tbl::get('TBL_MESSAGES') . "` (`subject`, `message`, `date`) values ('$subject', '$message', '".time()."')")){
			if(($id = $this->query->getLastInsertId()) != false){
				return $id;
			}
			else{
				return false;
			}
		}
		else{
			return false;
		}
	}

	/**
	 * Deletes whole message
	 *
	 * @param Message Object $msg or Integer $message_id
	 * @return Boolean
	 */
	public function deleteMessage($param){
		if(is_object($param)){
			$message_id = $param->getId();
		}
		else{
			$message_id = intval($param);
		}
		if($this->query->exec("delete from `" . Tbl::get('TBL_MESSAGES') . "` where `id`=" . $message_id)){
			return true;
		}
		else{
			return false;
		}
	}

	/**
	 * Creates message duplicates
	 *
	 * @param Integer $message_id
	 * @param Integer $sender
	 * @param Array Integer $receivers or Integer $receivers
	 * @return Boolean
	 */
	private function createDuplicates($message_id, $sender, $receivers, $replied = 0){
		if(!is_array($receivers)){
			if(is_numeric($receivers)){
				$receivers = array($receivers);
			}
			else{
				return false;
			}
		}

		array_unshift($receivers, $sender);
		if(!empty($receivers)){
			$sql_query = "insert into `" . Tbl::get('TBL_EXTRA') . "` (`message_id`, `receiver`, `sender`";
			if($replied != 0){
				$sql_query .= ", `replied`";
			}
			$sql_query .= ") values ";

			foreach ($receivers as $receiver) {
				if(intval($message_id)==0 or intval($receiver)==0 or intval($sender)==0){
					return false;
				}
				$sql_query .= "('" . intval($message_id) . "', '" . intval($receiver) . "', '" . intval($sender) . "'";
				if($replied != 0){
					$sql_query .= ", '" . intval($replied) . "'";
				}
				$sql_query .= "), ";
			}
			if($this->query->exec(substr($sql_query,0,-2))){
				return true;
			}
			else{
				return false;
			}
		}
		else{
			return false;
		}
	}

	/**
	 * Deletes current receiver duplicate
	 *
	 * @param Message Object $param or Integer $message_id
	 * @param Integer $receiver
	 * @return Boolean
	 */
	public function deleteDuplicate($param, $receiver){
		if(is_object($param)){
			$message_id = $param->getId();
		}
		else{
			$message_id = intval($param);
		}
		if($this->query->exec("UPDATE `" . Tbl::get('TBL_EXTRA') . "` SET `deleted`=1 WHERE `message_id`=$message_id AND `receiver`=" . intval($receiver))){
			$this->query->exec("SELECT COUNT(`message_id`) as `count` FROM `".Tbl::get('TBL_EXTRA')."` WHERE `message_id`=$message_id and `deleted`=0");
			if($this->query->fetchField("count") == 0){
				$this->deleteMessage($message_id);
			}
			return true;
		}
		else{
			return false;
		}
	}

	/**
	 * Sends duplicate to trash
	 *
	 * @param Message Object or Integer $param
	 * @param Integer $receiver
	 * @return Boolean
	 */
	public function sendToTrash($param, $receiver){
		if(is_object($param)){
			$message_id = $param->getId();
		}
		else{
			$message_id = intval($param);
		}
		if($this->query->exec("update `" . Tbl::get('TBL_EXTRA') . "` set `trashed`=1 where `message_id`=" . $message_id . " and `receiver`=" . intval($receiver))){
			return true;
		}
		else{
			return false;
		}
	}

	/**
	 * Restores duplicate from trash
	 *
	 * @param Message Object or Integer $param
	 * @param Integer $receiver
	 * @return Boolean
	 */
	public function restore($param, $receiver){
		if(is_object($param)){
			$message_id = $param->getId();
		}
		else{
			$message_id = intval($param);
		}
		if($this->query->exec("update `" . Tbl::get('TBL_EXTRA') . "` set `trashed`=0 where `message_id`=" . $message_id . " and `receiver`=" . intval($receiver))){
			return true;
		}
		else{
			return false;
		}
	}

	/**
	 * Sends a message to receivers
	 *
	 * @param Integer $sender
	 * @param Array Integer $receivers
	 * @param String $subject
	 * @param String $message
	 * @param Array $_FILES <=> $attachs
	 * @return Boolean
	 */
	public function send($sender, $receivers, $subject, $message){
		if(($msg_id = $this->createMessage($subject, $message)) != false){
			if($this->createDuplicates($msg_id, $sender, $receivers)){
				return $msg_id;
			}
			else{
				$this->deleteMessage($msg_id);
				return false;
			}
		}
		else{
			return false;
		}
	}

	/**
	 * Reply a message to one receiver the
	 * parent message author
	 *
	 * @param Message Object or messeage ID Integer $parent
	 * @param Integer $replyer
	 * @param String $subject
	 * @param String $message
	 * @param Array $_FILES <=> $attachs
	 * @return Boolean
	 */
	public function reply($parent, $replyer, $subject, $message){
		if(is_object($parent)){
			$parent_id = $parent->getId();
		}
		else{
			$parent_id = intval($parent);
			$parent = $this->getMessage($parent_id);
		}

		if(($msg_id = $this->createMessage($subject, $message)) != false){
			if($this->createDuplicates($msg_id, $replyer, $parent->sender, $parent_id)){
				return $msg_id;
			}
			else{
				$this->deleteMessage($msg_id);
				return false;
			}
		}
		else{
			return false;
		}
	}


	/**
	 * Sets duplicates read status
	 *
	 * @param Integer $receiver
	 * @param Message Object Reference $msg
	 * @param Boolean $status
	 * @return Message Object
	 */
	public function setReadStatus($receiver, &$msg, $status = true){
		if($receiver == $msg->sender){
			return false;
		}

		if($status){
			$status = 1;
		}
		else{
			$status = 0;
		}

		if($this->query->exec("update `" . Tbl::get('TBL_EXTRA') . "` set `read`=" . $status . " where `message_id`=" . $msg->getId() . " and `receiver`=" . intval($receiver))){
			$msg->read = $status;
			return $msg;
		}
		return false;
	}

	/**
	 * Returns duplicate read status
	 *
	 * @param unknown_type $receiver
	 * @param Message Object or Integer $param
	 * @return Boolean
	 */
	public function getReadStatus($receiver, $param, $cacheMinutes = 0){
		if(is_object($param)){
			$message_id = $param->getId();
		}
		else{
			$message_id = intval($param);
		}

		$this->query->exec("select `read` from `" . Tbl::get('TBL_EXTRA') . "` where `receiver`=" . intval($receiver) . " and `message_id`=" . $message_id, $cacheMinutes);
		if($this->query->fetchField("read")){
			return true;
		}
		else{
			return false;
		}
	}

	/**
	 * Tells is the duplicate in trash
	 *
	 * @param Integer $receiver
	 * @param Message Object or Integer $param
	 * @return Boolean
	 */
	public function getTrashedStatus($receiver, $param, $cacheMinutes = 0){
		if(is_object($param)){
			$message_id = $param->getId();
		}
		else{
			$message_id = intval($param);
		}

		$this->query->exec("select `trashed` from `" . Tbl::get('TBL_EXTRA') . "` where `receiver`=" . intval($receiver) . " and `message_id`=" . $message_id, $cacheMinutes);
		if($this->query->fetchField("trashed")){
			return true;
		}
		else{
			return false;
		}
	}

	public function getMessage($id, $cacheMinutes = 0){
		$sql_query = "SELECT main.*, extra.`sender`, extra.`replied`
						FROM
							`" . Tbl::get('TBL_MESSAGES') . "` main
						LEFT JOIN
							`" . Tbl::get('TBL_EXTRA') . "` extra
						ON (main.`id` = extra.`message_id`)
						WHERE main.`id` = '" . intval($id) . "'
						AND extra.`sender` = extra.`receiver`";

		if(!$this->query->exec($sql_query, $cacheMinutes)){
			return false;
		}

		if(($message = $this->query->fetchRecord()) != false){
			$msg = new Message();
			$msg->setId($message["id"]);
			$msg->sender = $message["sender"];
			$msg->subject = $message["subject"];
			$msg->message = $message["message"];
			$msg->date = date(DEFAULT_DATETIME_FORMAT,$message["date"]);
			$msg->parent = $message["replied"];
			$receivers = $this->getReceivers($msg, true, $cacheMinutes);
			if(is_array($receivers)){
				$msg->receivers = array_values(array_diff($receivers, array($message["sender"])));
			}
			else{
				$msg->receivers = array();
			}
			$msg->read = array();
			$msg->trashed = array();
			for($i=0; $i<count($msg->receivers); $i++){
				$msg->read[$msg->receivers[$i]] = $this->getReadStatus($msg->receivers[$i], $msg, $cacheMinutes);
			}
			for($i=0; $i<count($receivers); $i++){
				$msg->trashed[$receivers[$i]] = $this->getTrashedStatus($receivers[$i], $msg, $cacheMinutes);
			}
			return $msg;
		}
		else {
			return false;
		}
	}


	/**
	 * Returns replied messages count
	 *
	 * @param Message Object or Integer $param
	 * @return Integer
	 */
	public function getRepliesCount($param, $cacheMinutes = 0){
		if(is_object($param)){
			$message_id = $param->getId();
		}
		else{
			$message_id = intval($param);
		}

		$this->query->exec("SELECT COUNT(*) as cnt
							FROM `" . Tbl::get('TBL_EXTRA') . "`
							WHERE `receiver`<>`sender`
							AND `replied`='{$message_id}'", $cacheMinutes);

		return $this->query->fetchField("cnt");
	}

	/**
	 * Returns current message receivers
	 *
	 * @param Message Object or Integer $param
	 * @param Boolean $with_sender
	 * @return Array Integer
	 */
	public function getReceivers($param, $with_sender = false, $cacheMinutes = null){
		if(is_object($param)){
			$message_id = $param->getId();
		}
		else{
			$message_id = intval($param);
		}

		if($with_sender){
			$with_sender = "";
		}
		else{
			$with_sender = " and `receiver`<>`sender`";
		}

		$this->query->exec("select `receiver` from `" . Tbl::get('TBL_EXTRA') . "` where `message_id`=" . $message_id . $with_sender, $cacheMinutes);
		while(($receiver = $this->query->fetchField("receiver")) != false){
			$receivers[] = $receiver;
		}

		if(count($receivers)){
			return $receivers;
		}
		else{
			return false;
		}
	}

	public function messageExists($message_id, $cacheMinutes = 0){
		$this->query->exec("SELECT COUNT(`id`) AS cnt FROM `" . Tbl::get('TBL_MESSAGES') . "` WHERE `id`={$message_id}", $cacheMinutes);
		if($this->query->fetchField("cnt") == 0){
			return false;
		}
		else{
			return true;
		}
	}

	public function getApproximateMessagesCount($cacheMinutes = 0){
		$this->query->exec("SHOW TABLE STATUS LIKE '".Tbl::get('TBL_MESSAGES')."'", $cacheMinutes);
		return $this->query->fetchField("Rows");
	}

/**
	 * Get count of messages
	 *
	 * @param MessageFilter $filter
	 * @return array
	 */
	public function getMessagesCount(MessageFilter $filter = null, $cacheMinutes = 0){
		$sqlQuery = "SELECT count(*) as `cnt`
					FROM `".Tbl::get('TBL_MESSAGES')."` main
					LEFT JOIN `".Tbl::get('TBL_EXTRA')."` extra
					ON (main.`id` = extra.`message_id`)
					{$this->generateJoins($filter)}
					WHERE 1 {$this->generateWhere($filter)}";

		$this->query->exec($sqlQuery, $cacheMinutes);

		return $this->query->fetchField("cnt");
	}

	/**
	 * Get array of Message objects
	 *
	 * @param MessageFilter $filter
	 * @param MysqlPager $pager
	 * @param bool $headersOnly
	 * @return array
	 */
	public function getMessages(MessageFilter $filter = null, MysqlPager $pager = null, $headersOnly = true, $cacheMinutes = 0){
		if($headersOnly){
			$selectFields = "main.`id`, main.`subject`, main.`date`, extra.`sender`, extra.`read`, extra.`trashed`, extra.`deleted`";
		}
		else{
			$selectFields = "*";
		}

		$sqlQuery = "SELECT $selectFields
					FROM `".Tbl::get('TBL_MESSAGES')."` main
					LEFT JOIN `".Tbl::get('TBL_EXTRA')."` extra
					ON (main.`id` = extra.`message_id`)
					{$this->generateJoins($filter)}
					WHERE 1
					{$this->generateWhere($filter)}
					{$this->generateOrder($filter)}";

		if($pager !== null){
			$this->query = $pager->executePagedSQL($sqlQuery, $cacheMinutes);
		}
		else{
			$this->query->exec($sqlQuery, $cacheMinutes);
		}
		$messages = array();
		if($this->query->countRecords()){
			foreach($this->query->fetchRecords() as $message){
				$messages[] = new Message();
				$i = count($messages)-1;
				$messages[$i]->setId($message["id"]);
				$messages[$i]->read = $message["read"];
				$messages[$i]->sender = $message["sender"];
				$messages[$i]->subject = $message["subject"];
				$messages[$i]->date = date(DEFAULT_DATETIME_FORMAT,$message["date"]);
				$messages[$i]->deleted = $message["deleted"];;
				$messages[$i]->trashed = $message["trashed"];;
				$messages[$i]->receivers = $this->getReceivers($messages[$i], $cacheMinutes);
				if(!$headersOnly){
					$messages[$i]->message = $message["message"];
				}
			}
		}
		return $messages;
	}
	
	public function isMessageBelongsToUser($messageId, $userId){
		$this->query->exec("SELECT COUNT(*) as `count` 
								FROM `".Tbl::get('TBL_EXTRA')."`
								WHERE `message_id` = '$messageId' and (`sender` = '$userId' or `receiver` = '$userId')");
		if($this->query->fetchField("count")>0){
			return true;
		}
		return false;
	}
}

?>