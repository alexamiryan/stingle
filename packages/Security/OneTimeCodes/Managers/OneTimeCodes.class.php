<?php
class OneTimeCodes extends DbAccessor {
	
	const TBL_ONE_TIME_CODES	= "security_one_time_codes";
	
	const CODE_MAX_LENGTH = 1024;
	private $config;
	
	public function __construct (Config $config, $instanceName = null) {
		parent::__construct($instanceName); 
		$this->config = $config;
	}
	
	/**
	 * 
	 * Generate code using given parameters
	 * @param array $paramsArray
	 */
	public function generate(OTCConfig $config = null){
		if($config === null){
			$config = new OTCConfig();
		}
		
		$paramsArray = $config->paramsArray;
		
		if(isset($paramsArray['r'])){
			throw new RuntimeException("Key 'r' is not allowed to be present in \$paramsArray. Please remove or rename it.");
		}
		
		$paramsArray['r'] = generateRandomString(12);
		$keyValArray = array();
		$keysUniqueCheckArray = array();
		foreach ($paramsArray as $key => $value){
			if(preg_match("/[:;]/", $key) or preg_match("/[:;]/", $value)){
				throw new RuntimeException("Invalid characters in \$paramsArray. No ; or : characters are allowed!");
			}
			if(in_array($key, $keysUniqueCheckArray)){
				throw new RuntimeException("Duplicate key '$key' in \$paramsArray. It's not allowed!");
			}
			array_push($keysUniqueCheckArray, $key);
			array_push($keyValArray, "$key:$value");
		}
		$stringToEncrypt = implode(";", $keyValArray);
		$encryptedString = AES256::encrypt($stringToEncrypt);
		
		if(strlen($encryptedString) > static::CODE_MAX_LENGTH){
			throw new RuntimeException("Resulting code is longer than allowed " . static::CODE_MAX_LENGTH . " characters!");
		}
		$qb = new QueryBuilder();
		$qb->insert(Tbl::get('TBL_ONE_TIME_CODES'))
			->values(array(
							"code" => $encryptedString, 
							"multi" => ($config->multiUse ? 1 : 0), 
							"usage_limit" => ($config->usageLimit ? $config->usageLimit : new Literal("NULL") ), 
							"not_cleanable" => ($config->notCleanable ? 1 : 0), 
							"valid_until" => ($config->validityTime ? 
															new Func('FROM_UNIXTIME', 
															$qb->expr()->sum(
																new Func(
																	'UNIX_TIMESTAMP', 
																	new Func('NOW')
																), $config->validityTime)
															) : new Literal("NULL"))
						)
					);
						
		$this->query->exec($qb->getSQL());
		
		return $encryptedString;
	}
	
	
	/**
	 * Validate given code using paramsArray
	 * @param string $code
	 * @param array $paramsArray
	 * @return boolean
	 */
	public function validate($code, $paramsArray = array()){
		if(empty($code)){
			throw new InvalidArgumentException("Empty \$code supplied for validation!");
		}
		$qb = new QueryBuilder();
		$orX = new Orx();
		$orX->add($qb->expr()->isNull(new Field('valid_until')));
		$orX->add($qb->expr()->greaterEqual(new Field('valid_until'), new Func('NOW')));
		
		$qb->select(new Field('*'))
			->from(Tbl::get('TBL_ONE_TIME_CODES'))
			->where($qb->expr()->equal(new Field('code'), $code))
			->andWhere($orX);	
		$this->query->exec($qb->getSQL());
		
		if($this->query->countRecords() == 0){
			return false;
		}
		
		$dbRow = $this->query->fetchRecord();
		
		$paramsArrayFromCode = $this->getArrayFromCode($dbRow['code']);
		
		if($paramsArrayFromCode === false){
			return false;
		}
		
		$resultingArray = array_diff_assoc($paramsArray, $paramsArrayFromCode);
		
		if(count($resultingArray) != 0){
			return false;
		}
		
		if($dbRow['multi'] == '1'){
			if($dbRow['usage_limit'] > 0){
				$qb = new QueryBuilder();
				if($dbRow['usage_count'] < $dbRow['usage_limit']){
					$qb->update(Tbl::get('TBL_ONE_TIME_CODES'))
							->set(new Field('usage_count'), $qb->expr()->sum(new Field('usage_count'), 1))
							->where($qb->expr()->equal(new Field('id'), $dbRow['id']));	
					$this->query->exec($qb->getSQL());
				}
				else{
					$qb->delete(Tbl::get('TBL_ONE_TIME_CODES'))
						->where($qb->expr()->equal(new Field("id"), $dbRow['id']));	
					$this->query->exec($qb->getSQL());
					return false;
				}
			}
		}
		else{
			$qb = new QueryBuilder();
			$qb->delete(Tbl::get('TBL_ONE_TIME_CODES'))
				->where($qb->expr()->equal(new Field("id"), $dbRow['id']));	
			$this->query->exec($qb->getSQL());
		}
		
		return true;
	}
	
	public function revokeCode($code){
		if(empty($code)){
			throw new InvalidArgumentException("Empty \$code supplied for revokation!");
		}
		$qb = new QueryBuilder();
			$qb->delete(Tbl::get('TBL_ONE_TIME_CODES'))
				->where($qb->expr()->equal(new Field("code"), $code));	
		$this->query->exec($qb->getSQL());
		
		return ($this->query->affected() > 0 ? true : false);
	}
	
	public function getArrayFromCode($code){
		$decryptedString = AES256::decrypt($code);
		$keyValuePairs = explode(";", $decryptedString);
		
		$resultingArray = array();
		foreach($keyValuePairs as $keyVal){
			$keyValArr = explode(":", $keyVal);
			if(count($keyValArr) == 2){
				$resultingArray[$keyValArr[0]] = $keyValArr[1];
			}
		}
		
		if(count($resultingArray) ==0 or !isset($resultingArray['r'])){
			return false;
		}
		
		unset($resultingArray['r']);
		
		return $resultingArray; 
	}
	
	/**
	 * CleanUp codes from DB that are too old
	 */
	public function cleanUp(){
		$time = 60*60*24*$this->config->cleanUpTimeOut;
		$qb = new QueryBuilder();
		$orX = new Orx();
		$andX1 = new Andx();
		$andX2 = new Andx();
		$andX1->add($qb->expr()->less(new Func(
												'UNIX_TIMESTAMP', 
												new Field('issue_date')
										), 
										$qb->expr()->diff( 
													new Func(
														'UNIX_TIMESTAMP', 
														new Func('NOW')
													),
										$time)
									 )
					);
		$andX1->add($qb->expr()->equal(new Field('not_cleanable'), 0));
		$andX1->add($qb->expr()->isNull(new Field('valid_until')));
		
		$andX2->add($qb->expr()->isNotNull(new Field('valid_until')));
		$andX2->add($qb->expr()->less(new Field('valid_until'), new Func('NOW')));
		
		$orX->add($andX1);
		$orX->add($andX2);
		
		$qb->delete(Tbl::get('TBL_ONE_TIME_CODES'))->where($orX);
			
		$this->query->exec($qb->getSQL());
		
		return $this->query->affected();
	}
}
