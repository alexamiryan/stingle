<?php

class ApiOutput extends Model {

	const STATUS_NOK = 'nok';
	const STATUS_OK = 'ok';

	private $error = null;
	private $info = null;
	private $parts = array();
	private $status = self::STATUS_OK;

	public function __construct() {
		$this->error = Reg::get(ConfigManager::getConfig('Info', 'Info')->Objects->Error);
		$this->info = Reg::get(ConfigManager::getConfig('Info', 'Info')->Objects->Info);
	}

	public function setStatusOk() {
		$this->setStatus(self::STATUS_OK);
	}

	public function setStatusNotOk() {
		$this->setStatus(self::STATUS_NOK);
	}

	public function setStatus($status) {
		if (!in_array($status, self::getConstsArray("STATUS"))) {
			throw new InvalidArgumentException("Invalid \$status given");
		}
		$this->status = $status;
	}

	public function getStatus() {
		return $this->status;
	}
	
	public function isStatusOk() {
		return ($this->status === self::STATUS_OK ? true : false);
	}

	public function set($partName, $partValue) {
		if (empty($partName)) {
			throw new InvalidArgumentException("\$partName is empty");
		}
		$this->parts[$partName] = $partValue;
	}

	public function output() {
		$output = array();
		$output['parts'] = array();

		$output['status'] = $this->status;
		$output['infos'] = $this->info->getAll();
		$output['errors'] = $this->error->getAll();

		foreach ($this->parts as $partName => $partValue) {
			$output['parts'][$partName] = $partValue;
		}

		header('Content-Type: application/json');
		JSON::jsonOutput($output);
	}

}
