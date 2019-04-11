<?php

/**
 * Db_MySqlDatabase
 *
 * @category   ToolsAndUtilities
 * @package    DatabaseDrivers
 * @subpackage MySQL
 */
define('DEFAULT_DATETIME_FORMAT', "Y-m-d H:i:s");
define('DEFAULT_DATE_FORMAT', "Y-m-d");

class MySqlDatabase extends Model {

	const JOIN_STANDART = "JOIN";
	const JOIN_LEFT = "LEFT JOIN";
	const JOIN_RIGHT = "RIGHT JOIN";
	const JOIN_INNER = "INNER JOIN";
	const JOIN_OUTER = "OUTER JOIN";
	const JOIN_STRAIGTH = "STRAIGTH_JOIN";
	const ORDER_ASC = "ASC";
	const ORDER_DESC = "DESC";

	/**
	 * Database connection link
	 * @access private
	 * @var mysqli
	 */
	protected $link;

	protected $isRO = false;

	/**
	 * Multiton pattern implementation makes "new" unavailable
	 *
	 * @return void
	 */

	/**
	 *
	 * @param $server
	 * @param $username
	 * @param $password
	 * @param $db_name
	 * @param $persistency
	 * @return unknown_type
	 */
	public function __construct($server, $username, $password, $db_name = null, $persistency = false) {
		if ($persistency) {
			$server = 'p:' . $server;
		}

		$this->link = new mysqli($server, $username, $password, $db_name);
		if ($this->link->connect_errno) {
			throw new MySqlException($this->link->connect_error, $this->link->connect_errno);
		}
	}

	/**
	 * Destructor
	 *
	 * @access public
	 * @return void
	 */
	public function __destruct() {
		if (is_resource($this->link)) {
			$this->link->close();
		}
	}

	public function setIsRO($isRO) {
		$this->isRO = $isRO;
	}

	public function isRO() {
		return $this->isRO;
	}

	protected function proceedIfRW() {
		if ($this->isRO) {
			throw new MySqlException("Failed to perform action because this DB connection is RO", 0);
		}
	}

	public function selectDatabase($dbName) {
		if (!$this->link->ping()) {
			throw new MySqlException("There is no connection to the server", 3);
		}
		return $this->link->select_db($dbName);
		;
	}

	/**
	 * Returns link variable for current database
	 *
	 * @access public
	 * @return resource
	 */
	public function getLink() {
		return $this->link;
	}

	public function setConnectionEncoding($encoding) {
		if (empty($encoding)) {
			throw new InvalidArgumentException("\$encoding have to be non empty string.");
		}
		return $this->link->query("SET NAMES $encoding");
	}

}
