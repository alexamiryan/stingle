<?php

class Chat extends DbAccessor{

	const LIVE_MESSAGES = 1;
	const ARCHIVE_MESSAGES = 2;
	const ALL_MESSAGES = 4;

	const TBL_MAIN = "wcs_messages";
	const TBL_ARCHIVE = "wcs_archive";
	const TBL_ONLINE = "chat_tran";
	
	private $cache_count;
	private $messages_count;

	/**
	 * Class constructor
	 *
	 * @return Chat instance
	 * @version 1.0
	 */
	public function __construct(){
		parent::__construct();
	}

	public static function setProperties(Config $config){
		$this->cache_count = $config->cache_count;
		$this->messages_count = $config->messages_count;
	}

	private function archiveCache(){
		$this->query->exec("LOCK TABLES `".static::TBL_MAIN."` WRITE,
										`".static::TBL_ARCHIVE."` WRITE");
		$this->query->exec("SELECT COUNT(`id`) AS msg_count FROM `" . static::TBL_MAIN . "`");
		$msg_count = $this->query->fetchField("msg_count");
		if($msg_count >= $this->cache_count + $this->messages_count){
			$this->query->exec("SELECT UNIX_TIMESTAMP(`insert_date`) AS `udate` FROM `" . $static::TBL_MAIN. "` ORDER BY `insert_date` ASC LIMIT " . $this->cache_count . ", 1");
			if(($date = $this->query->fetchField("udate"))){
				if($this->query->exec("INSERT INTO `" . static::TBL_ARCHIVE . "` SELECT `transaction_id`,`sender`,`message`,`insert_date` FROM `" . static::TBL_MAIN . "` WHERE UNIX_TIMESTAMP(`insert_date`)< '$date'")){
					$this->query->exec("DELETE from `" . static::TBL_MAIN . "` WHERE UNIX_TIMESTAMP(`insert_date`) < '$date'");
					$this->query->exec("UNLOCK TABLES");
					return true;
				}
				else{
					$this->query->exec("UNLOCK TABLES");
					return false;
				}
			}
		}
		$this->query->exec("UNLOCK TABLES");
	}

	private function getTableQuery($value){
		switch($value){
			case static::ARCHIVE_MESSAGES :
				return "SELECT * FROM `" . static::TBL_ARCHIVE . "`";
			case static::ALL_MESSAGES :
				return "SELECT `transaction_id`,`sender`,`message`,`insert_date` FROM `" . static::TBL_MAIN . "` UNION SELECT `transaction_id`,`sender`,`message`,`insert_date` FROM `" . static::TBL_ARCHIVE . "`";
			case static::LIVE_MESSAGES :
			default :
				return "SELECT * FROM `" . static::TBL_MAIN . "`";
		}
	}

	/**
	 * Enter description here...
	 *
	 * @param array
	 * @return array
	 */

	private function getWhereClause($parameters){
		$ret_array = array();
		$param_count = count($parameters);
		switch($param_count){
			case 1 :
				if(is_int($parameters[0])){
					$ret_array["offset"] = $parameters[0];
				}
				else{
					$date_from = strtotime($parameters[0]);
					$ret_array["sql_query"] = "UNIX_TIMESTAMP(`insert_date`)>=" . $date_from;
				}
				break;
			case 2 :
				if(is_int($parameters[0])){
					$ret_array["offset"] = $parameters[0];
					$ret_array["length"] = $parameters[1];
				}
				else{
					$date_from = $parameters[0];
					if(is_int($parameters[1])){
						$ret_array["offset"] = $parameters[1];
						$ret_array["sql_query"] = "UNIX_TIMESTAMP(`insert_date`)>=" . $date_from;
					}
					else{
						$date_to = $parameters[1];
						$ret_array["sql_query"] = "UNIX_TIMESTAMP(`insert_date`)>=" . $date_from . " and  UNIX_TIMESTAMP(`insert_date`)<" . $date_to;
					}
				}
				break;
			case 3 :
				if(is_int($parameters[1])){
					$ret_array["offset"] = $parameters[1];
					$ret_array["length"] = $parameters[2];
				}
				else{
					$date_from = $parameters[0];
					$date_to = $parameters[1];
					$ret_array["offset"] = $parameters[2];
					$ret_array["sql_query"] = "UNIX_TIMESTAMP(`insert_date`)>=" . $date_from . " and  UNIX_TIMESTAMP(`insert_date`)<" . $date_to;
				}
				break;
			case 4 :
				$date_from = $parameters[0];
				$date_to = $parameters[1];
				$ret_array["offset"] = $parameters[2];
				$ret_array["length"] = $parameters[3];
				$ret_array["sql_query"] = "UNIX_TIMESTAMP(`insert_date`)>=" . $date_from . " and  UNIX_TIMESTAMP(`insert_date`)<" . $date_to;
			default :
				break;
		}

		return $ret_array;
	}

	public function send($transaction_id, $sender, $message){
		$this->archiveCache();
		$this->query->exec("INSERT INTO `" . static::TBL_MAIN . "` (`transaction_id`, `sender`, `message`, `insert_date`) VALUES ('$transaction_id', '$sender', '$message', now())");
		if($this->query->affected() == 1){
			return $this->query->getLastInsertId();
		}
		return false;
	}

	/**
	 * Enter description here...
	 *
	 * @param messages, transaction_id, username
	 * @param messages, transaction_id, username, offset
	 * @param messages, transaction_id, username, offset, length
	 * @param messages, transaction_id, username, from_date
	 * @param messages, transaction_id, username, from_date, to_date
	 * @param messages, transaction_id, username, from_date, offset
	 * @param messages, transaction_id, username, from_date, offset, length
	 * @param messages, transaction_id, username, from_date, to_date, offset
	 * @param messages, transaction_id, username, from_date, to_date, offset, length
	 * @var messages = { Chat::LIVE_MESSAGES | Chat::ARCHIVED_MESSAGES | Chat::ALL_MESSAGES }
	 * @return array
	 */

	public function getMessages(){
		$arguments = func_get_args();
		$args_count = func_num_args();

		if(($sql_query = $this->getTableQuery(array_shift($arguments)))){
			if($args_count == 3){
				$sql_query = $sql_query . " WHERE `transaction_id`='" . array_shift($arguments) . "' AND sender<>'" . array_shift($arguments) . "' AND `read`=0 ORDER BY `id` ASC";
				$this->query->exec($sql_query);
				if($this->query->countRecords()){
					return $this->query->fetchRecords();
				}
			}
			elseif($args_count > 3){
				$sql_query = $sql_query . " WHERE `transaction_id`='" . array_shift($arguments) . "' AND sender<>'" . array_shift($arguments) . "' ORDER BY `id` ASC";

				$where_array = $this->getWhereClause($arguments);
				if(isset($where_array["sql_query"])){
					$sql_query .= " AND " . $where_array["sql_query"];
				}
				$this->query->exec($sql_query);
				if($this->query->countRecords()){
					if(isset($where_array["offset"]) and isset($where_array["length"])){
						return $this->query->fetchRecords($where_array["offset"], $where_array["length"]);
					}
					elseif(isset($where_array["offset"])){
						return $this->query->fetchRecords($where_array["offset"]);
					}
					else{
						return $this->query->fetchRecords();
					}
				}
			}
		}
		return array();
	}

	public function makeRead($id_array){
		if(count($id_array)){
			foreach($id_array as $id){
				$this->query->exec("UPDATE `".static::TBL_MAIN."` SET `read`=1 WHERE `id` = '$id'");
			}
		}
	}

	/**
	 * Enter description here...
	 *
	 * @param integer $transaction_id
	 * @param string $field
	 * @return unknown
	 */
	public function updateOnlineList($transaction_id, $field){
		$now = time();
		$this->query->exec("UPDATE `" . static::TBL_ONLINE . "` SET `$field`='$now' WHERE `id`='$transaction_id'");
		return $now;
	}

	function getTransaction($transaction_id){
		$this->query->exec("SELECT * FROM `" . static::TBL_ONLINE . "` WHERE `id`='$transaction_id'");
		if(($tran = $this->query->fetchRecord())){
			return $tran;
		}
		else{
			return false;
		}
	}

	public function getLastPing($transaction_id, $field){
		$this->query->exec("SELECT `$field` FROM `" . static::TBL_ONLINE . "` WHERE `id`='$transaction_id'");
		if($this->query->countRecords() != 0){
			return $this->query->fetchField($field);
		}
		else{
			return false;
		}
	}

	public function deleteFromOnlineList($transaction_id){
		if($this->query->exec("DELETE FROM `" . static::TBL_ONLINE . "` WHERE `id`='$transaction_id'")){
			return true;
		}
		else{
			return false;
		}
	}

	public function deleteArchive(){
		$arguments = func_get_args();

		$sql_query = "DELETE FROM `" . static::TBL_ARCHIVE . "`";
		$where_array = $this->getWhereClause($arguments);
		if(isset($where_array["sql_query"])){
			$sql_query .= " WHERE " . $where_array["sql_query"];
		}

		$this->query->exec($sql_query);
		return $this->query->affected();
	}
}

?>